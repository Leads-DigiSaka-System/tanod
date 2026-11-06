<?php

namespace App\Exports;

use App\Models\Ticket;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TicketsExport implements FromArray, ShouldAutoSize, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    public function __construct(
        private array $ids,
    ) {}

    public function title(): string
    {
        return 'Tickets';
    }

    public function headings(): array
    {
        return [
            'Type',
            'Organization Name',
            'Subject',
            'Action Taken',
            'Service Charge',
            'Status',
            'Assigned TPS',
            'Reported',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14,
            'B' => 30,
            'C' => 36,
            'D' => 36,
            'E' => 18,
            'F' => 14,
            'G' => 22,
            'H' => 18,
        ];
    }

    public function array(): array
    {
        $tickets = Ticket::with(['submitter', 'tractor', 'assignees'])
            ->whereIn('id', $this->ids)
            ->get();

        $rows = [];
        $statusMap = [
            'open' => 'Open',
            'in_progress' => 'In Progress',
            'resolved' => 'Completed',
            'closed' => 'Closed',
        ];

        foreach ($tickets as $ticket) {
            $tpsNames = $ticket->assignees->pluck('name')->filter()->join(', ') ?: '—';

            $rows[] = [
                $ticket->category ?? 'repair',
                $ticket->organization_name ?? $ticket->fca_name ?? '—',
                $ticket->subject ?? '—',
                $ticket->description ?? '—',
                $ticket->service_charge ? '₱'.number_format((float) $ticket->service_charge, 2) : '—',
                $statusMap[$ticket->status] ?? $ticket->status ?? '—',
                $tpsNames,
                $ticket->reported_date ?? $ticket->created_at?->format('Y-m-d') ?? '—',
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $lastRow = $sheet->getHighestRow();
        if ($lastRow < 2) {
            return [];
        }

        $sheet->getStyle("A2:H{$lastRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        for ($r = 2; $r <= $lastRow; $r++) {
            if (($r - 2) % 2 === 1) {
                $sheet->getStyle("A{$r}:H{$r}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->setStartColor(new Color('F9FAFB'));
            }
        }

        $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("F2:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [];
    }
}
