<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CollectibleController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'collectible');

        $query = Ticket::query()
            ->whereIn('status', ['resolved', 'closed'])
            // Only include tickets that have actual financial items
            // (skip zero-balance tickets like third-party / self-repair)
            ->where(function ($q) {
                $q->where('service_charge', '>', 0)
                    ->orWhere('down_payment', '>', 0)
                    ->orWhereHas('tractorParts', fn ($q) => $q->where('ticket_tractor_part.amount', '>', 0));
            })
            ->with([
                'submitter',
                'tractor',
                'assignee',
                'assignees',
                'tractorParts',
                'payments' => fn ($q) => $q->with('collector'),
            ]);

        // Filter by tab
        switch ($tab) {
            case 'to_approve':
                // Tickets marked as "to_approve" or recently resolved without review
                $query->where(function ($q) {
                    $q->where('collectible_status', 'to_approve')
                        ->orWhere(function ($q2) {
                            $q2->whereNull('collectible_status')
                                ->where('status', 'resolved');
                        });
                });
                break;
            case 'paid':
                $query->where('collectible_status', 'paid');
                break;
            case 'collectible':
            default:
                // Only show tickets from July 10, 2026 onwards
                $query->where('resolved_at', '>=', '2026-07-10')
                    ->where(function ($q) {
                        $q->where('collectible_status', 'collectible')
                            ->orWhereNull('collectible_status');
                    });
                break;
        }

        $tickets = $query->orderByDesc('resolved_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Ticket $ticket) => $this->formatTicket($ticket));

        return Inertia::render('Collectible/Index', [
            'tickets' => $tickets,
            'filters' => $request->only(['tab', 'search']),
            'tabCounts' => $this->getTabCounts(),
        ]);
    }

    public function show(Ticket $ticket)
    {
        $ticket->load([
            'submitter',
            'tractor:id,no_plate,brand,model',
            'tractorParts' => fn ($q) => $q->withPivot('amount', 'quantity'),
            'payments' => fn ($q) => $q->with('collector')->latest('paid_at'),
            'resolver',
        ]);

        $totalParts = $ticket->tractorParts->sum(fn ($p) => ($p->pivot->amount * $p->pivot->quantity));
        $serviceCharge = (float) ($ticket->service_charge ?? 0);
        $totalAmount = $totalParts + $serviceCharge;
        $downPayment = (float) ($ticket->down_payment ?? 0);
        $totalPaid = (float) $ticket->payments->sum('amount') + $downPayment;
        $remainingBalance = $totalAmount - $totalPaid;

        $installments = (int) ($ticket->installments ?? 0);
        $scheduleData = $this->getInstallmentSchedule($ticket, $totalAmount, $totalPaid);

        return response()->json([
            'ticket' => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'fca_name' => $ticket->fca_name,
                'submitter' => $ticket->submitter ? [
                    'id' => $ticket->submitter->id,
                    'name' => $ticket->submitter->name,
                    'phone' => $ticket->submitter->phone,
                    'email' => $ticket->submitter->email,
                ] : null,
                'tractor' => $ticket->tractor,
                'service_charge' => (float) ($ticket->service_charge ?? 0),
                'down_payment' => (float) ($ticket->down_payment ?? 0),
                'installments' => $installments,
                'installment_base' => max(0, $totalAmount - $downPayment),
                'monthly_amount' => $scheduleData['monthly_amount'],
                'current_month' => $scheduleData['current_month'],
                'installment_schedule' => $scheduleData['schedule'],
                'next_due_date' => $scheduleData['next_due_date']?->toIso8601String(),
                'is_overdue' => $scheduleData['is_overdue'],
                'tractor_parts' => $ticket->tractorParts->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'amount' => (float) $p->pivot->amount,
                    'quantity' => (int) $p->pivot->quantity,
                    'subtotal' => (float) ($p->pivot->amount * $p->pivot->quantity),
                ]),
                'total_parts' => $totalParts,
                'total_amount' => $totalAmount,
                'total_paid' => $totalPaid,
                'remaining_balance' => max(0, $remainingBalance),
                'payments' => $ticket->payments->map(fn ($pm) => [
                    'id' => $pm->id,
                    'amount' => (float) $pm->amount,
                    'notes' => $pm->notes,
                    'collected_by' => $pm->collector?->name ?? '—',
                    'paid_at' => $pm->paid_at?->toIso8601String(),
                ]),
            ],
        ]);
    }

    public function addPayment(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500',
            'paid_at' => 'nullable|date',
        ]);

        $payment = DB::transaction(function () use ($ticket, $validated, $request) {
            $payment = TicketPayment::create([
                'ticket_id' => $ticket->id,
                'collected_by' => $request->user()->id,
                'amount' => $validated['amount'],
                'notes' => $validated['notes'] ?? null,
                'paid_at' => $validated['paid_at'] ?? now(),
            ]);

            // Check if fully paid
            $totalParts = $ticket->tractorParts->sum(fn ($p) => ($p->pivot->amount * $p->pivot->quantity));
            $serviceCharge = (float) ($ticket->service_charge ?? 0);
            $totalAmount = $totalParts + $serviceCharge;
            $downPayment = (float) ($ticket->down_payment ?? 0);
            $totalPaid = (float) $ticket->payments()->sum('amount') + $downPayment;

            if ($totalPaid >= $totalAmount) {
                $ticket->update(['collectible_status' => 'paid']);
            } else {
                $ticket->update(['collectible_status' => 'collectible']);
            }

            return $payment;
        });

        return redirect()->back()->with('success', 'Payment recorded successfully.');
    }

    public function approve(Ticket $ticket)
    {
        // Check if already fully paid (e.g., down payment covers everything)
        $totalParts = $ticket->tractorParts->sum(fn ($p) => ($p->pivot->amount * $p->pivot->quantity));
        $serviceCharge = (float) ($ticket->service_charge ?? 0);
        $totalAmount = $totalParts + $serviceCharge;
        $downPayment = (float) ($ticket->down_payment ?? 0);
        $paymentsTotal = (float) $ticket->payments()->sum('amount');
        $totalPaid = $paymentsTotal + $downPayment;

        $status = $totalPaid >= $totalAmount ? 'paid' : 'collectible';

        $ticket->update(['collectible_status' => $status]);

        return redirect()->back()->with('success', 'Ticket approved for collection.');
    }

    private function formatTicket(Ticket $ticket): array
    {
        $totalParts = $ticket->tractorParts->sum(fn ($p) => ($p->pivot->amount * $p->pivot->quantity));
        $serviceCharge = (float) ($ticket->service_charge ?? 0);
        $totalAmount = $totalParts + $serviceCharge;
        $downPayment = (float) ($ticket->down_payment ?? 0);
        $totalPaid = (float) $ticket->payments->sum('amount') + $downPayment;
        $remainingBalance = max(0, $totalAmount - $totalPaid);
        $lastPayment = $ticket->payments->first();

        $installments = (int) ($ticket->installments ?? 0);
        $scheduleData = $this->getInstallmentSchedule($ticket, $totalAmount, $totalPaid);

        return [
            'id' => $ticket->id,
            'fca_name' => $ticket->fca_name,
            'organization_name' => $ticket->submitter?->organization_name ?: $ticket->fca_name,
            'tractor' => $ticket->tractor ? [
                'id' => $ticket->tractor->id,
                'name' => $ticket->tractor->name,
                'no_plate' => $ticket->tractor->no_plate,
                'brand' => $ticket->tractor->brand,
                'model' => $ticket->tractor->model,
            ] : null,
            'assignee' => $ticket->assignee ? [
                'id' => $ticket->assignee->id,
                'name' => $ticket->assignee->name,
            ] : null,
            'assignees' => $ticket->assignees?->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
            ]) ?? [],
            'submitter' => $ticket->submitter ? [
                'id' => $ticket->submitter->id,
                'name' => $ticket->submitter->name,
                'phone' => $ticket->submitter->phone,
                'email' => $ticket->submitter->email,
            ] : null,
            'subject' => $ticket->subject,
            'service_charge' => (float) ($ticket->service_charge ?? 0),
            'down_payment' => (float) ($ticket->down_payment ?? 0),
            'total_parts' => $totalParts,
            'total_amount' => $totalAmount,
            'total_paid' => $totalPaid,
            'remaining_balance' => max(0, $remainingBalance),
            'last_payment' => $lastPayment ? [
                'amount' => (float) $lastPayment->amount,
                'paid_at' => $lastPayment->paid_at?->toIso8601String(),
            ] : null,
            'collectible_status' => $ticket->collectible_status ?? 'collectible',
            'resolved_at' => $ticket->resolved_at?->toIso8601String(),
            'created_at' => $ticket->created_at?->toIso8601String(),
            'tractor_parts_count' => $ticket->tractorParts->count(),
            'payments_count' => $ticket->payments->count(),
            'installments' => $installments,
            'installment_base' => max(0, $totalAmount - $downPayment),
            'monthly_amount' => $scheduleData['monthly_amount'],
            'current_month' => $scheduleData['current_month'],
            'next_due_date' => $scheduleData['next_due_date']?->toIso8601String(),
            'is_overdue' => $scheduleData['is_overdue'],
        ];
    }

    /**
     * Build monthly installment schedule.
     * Down payment is separate — only the remaining balance after down payment
     * is divided into monthly installments.
     * Returns: monthly_amount, current_month, next_due_date, is_overdue, schedule[]
     */
    private function getInstallmentSchedule(Ticket $ticket, float $totalAmount, float $totalPaid): array
    {
        $installments = (int) ($ticket->installments ?? 0);
        $downPayment = (float) ($ticket->down_payment ?? 0);

        if ($installments <= 0 || ! $ticket->resolved_at) {
            return [
                'monthly_amount' => 0,
                'current_month' => 0,
                'next_due_date' => null,
                'is_overdue' => false,
                'schedule' => [],
            ];
        }

        // Amount to be paid via monthly installments (total minus down payment)
        $installmentBase = max(0, $totalAmount - $downPayment);
        $monthlyAmount = round($installmentBase / $installments, 2);

        // Guard: if monthly amount is zero (e.g. down payment covers the full amount),
        // there's no installment schedule to compute.
        if ($monthlyAmount <= 0) {
            return [
                'monthly_amount' => 0,
                'current_month' => 0,
                'next_due_date' => null,
                'is_overdue' => false,
                'schedule' => [],
            ];
        }

        // Only payments AFTER down payment count toward covering months
        $installmentPayments = max(0, $totalPaid - $downPayment);
        $coveredMonths = (int) floor($installmentPayments / $monthlyAmount);
        $currentMonth = min($coveredMonths + 1, $installments);
        $now = now();

        $schedule = [];
        $nextDueDate = null;
        $isOverdue = false;

        for ($i = 1; $i <= $installments; $i++) {
            $dueDate = $ticket->resolved_at->copy()->addMonths($i);
            $isPaid = $i <= $coveredMonths;
            $isDueNow = $i === $currentMonth;
            $isPast = $dueDate->isPast();

            $status = 'upcoming';
            if ($isPaid) {
                $status = 'paid';
            } elseif ($isPast) {
                $status = 'overdue';
                if ($isDueNow) {
                    $isOverdue = true;
                }
            } elseif ($isDueNow) {
                $status = 'pending';
            }

            if (! $isPaid && $nextDueDate === null) {
                $nextDueDate = $dueDate;
            }

            $schedule[] = [
                'month' => $i,
                'due_date' => $dueDate->toIso8601String(),
                'amount' => $monthlyAmount,
                'status' => $status,
            ];
        }

        return [
            'monthly_amount' => $monthlyAmount,
            'current_month' => $currentMonth,
            'next_due_date' => $nextDueDate,
            'is_overdue' => $isOverdue,
            'schedule' => $schedule,
        ];
    }

    /**
     * Calculate total payment excluding down payment (pure installment payments).
     */
    private function totalInstallmentPayments(Ticket $ticket, float $totalPaid): float
    {
        $downPayment = (float) ($ticket->down_payment ?? 0);

        return max(0, $totalPaid - $downPayment);
    }

    /**
     * Check if the ticket is fully paid (totalPaid >= totalAmount).
     */
    private function isFullyPaid(Ticket $ticket, float $totalPaid, float $totalAmount): bool
    {
        return $totalPaid >= $totalAmount;
    }

    private function getTabCounts(): array
    {
        $hasBalance = fn ($q) => $q->where('service_charge', '>', 0)
            ->orWhere('down_payment', '>', 0)
            ->orWhereHas('tractorParts', fn ($pq) => $pq->where('ticket_tractor_part.amount', '>', 0));

        $baseQuery = Ticket::whereIn('status', ['resolved', 'closed'])
            ->where($hasBalance);

        return [
            'collectible' => (clone $baseQuery)
                ->where('resolved_at', '>=', '2026-07-10')
                ->where(function ($q) {
                    $q->where('collectible_status', 'collectible')
                        ->orWhereNull('collectible_status');
                })
                ->count(),
            'to_approve' => (clone $baseQuery)
                ->where(function ($q) {
                    $q->where('collectible_status', 'to_approve')
                        ->orWhere(function ($q2) {
                            $q2->whereNull('collectible_status')
                                ->where('status', 'resolved');
                        });
                })
                ->count(),
            'paid' => (clone $baseQuery)
                ->where('collectible_status', 'paid')
                ->count(),
        ];
    }
}
