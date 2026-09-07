<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TicketSummaryExport implements FromArray, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    public function __construct(
        protected array $tickets,
        protected array $summary,
        protected array $filters = [],
    ) {}

    public function title(): string
    {
        return 'Ticket Summary';
    }

    public function headings(): array
    {
        return ['#', 'Subject', 'Tractor', 'Priority', 'Status', 'Submitted By', 'Date', 'Resolution (hrs)'];
    }

    public function columnWidths(): array
    {
        return ['A' => 6, 'B' => 36, 'C' => 20, 'D' => 12, 'E' => 14, 'F' => 22, 'G' => 18, 'H' => 16];
    }

    public function array(): array
    {
        return collect($this->tickets)->map(fn ($t, $i) => [
            $i + 1,
            $t['subject'] ?? '',
            $t['tractor']['no_plate'] ?? '—',
            ucfirst($t['priority'] ?? '—'),
            ucwords(str_replace('_', ' ', $t['status'] ?? '')),
            $t['submitter']['name'] ?? '—',
            $t['created_at'] ?? '',
            $t['resolution_hours'] ?? '—',
        ])->toArray();
    }

    public function styles(Worksheet $sheet): array
    {
        $lastDataRow = count($this->tickets) + 4;

        // ── Title banner ──
        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', 'TICKET SUMMARY');
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(44);

        // Subtitle
        $sheet->mergeCells('A2:H2');
        $sheet->setCellValue('A2', "Total: {$this->summary['total']} · Open: {$this->summary['open']} · In Progress: {$this->summary['in_progress']} · Resolved: {$this->summary['resolved']} · Avg Resolution: ".($this->summary['avg_resolution_hours'] ? $this->summary['avg_resolution_hours'].'h' : 'N/A').' · '.$this->filterLabel());
        $sheet->getStyle('A2:H2')->applyFromArray([
            'font' => ['size' => 10, 'color' => ['rgb' => '6B7280']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(28);

        // ── Header row ──
        $headerStyle = [
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6366F1']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'C7D2FE']]],
        ];
        $sheet->getStyle('A4:H4')->applyFromArray($headerStyle);
        $sheet->getRowDimension(4)->setRowHeight(32);

        // ── Data rows ──
        $dataStyle = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];
        $sheet->getStyle("A5:H{$lastDataRow}")->applyFromArray($dataStyle);
        $sheet->getStyle("A5:A{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D5:H{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        for ($r = 5; $r <= $lastDataRow; $r++) {
            if (($r - 5) % 2 === 1) {
                $sheet->getStyle("A{$r}:H{$r}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->setStartColor(new Color('F9FAFB'));
            }
        }

        // ── Footer ──
        $footerRow = $lastDataRow + 2;
        $sheet->mergeCells("A{$footerRow}:H{$footerRow}");
        $sheet->setCellValue("A{$footerRow}", 'Generated on '.now()->format('F j, Y \a\t g:i A').' · Tanod Fleet Management');
        $sheet->getStyle("A{$footerRow}:H{$footerRow}")->applyFromArray([
            'font' => ['size' => 9, 'italic' => true, 'color' => ['rgb' => '9CA3AF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->setSelectedCell('A1');

        return [];
    }

    private function filterLabel(): string
    {
        $parts = [];
        if (! empty($this->filters['from']) && ! empty($this->filters['to'])) {
            $parts[] = "{$this->filters['from']} – {$this->filters['to']}";
        }
        if (! empty($this->filters['status'])) {
            $parts[] = 'Status: '.ucwords(str_replace('_', ' ', $this->filters['status']));
        }
        if (! empty($this->filters['priority'])) {
            $parts[] = 'Priority: '.ucfirst($this->filters['priority']);
        }

        return $parts ? 'Filters: '.implode(' · ', $parts) : 'All tickets';
    }
}
