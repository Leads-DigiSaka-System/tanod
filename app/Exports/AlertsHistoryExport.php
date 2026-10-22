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

class AlertsHistoryExport implements FromArray, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    public function __construct(
        protected array $alerts,
        protected array $summary,
        protected array $filters = [],
    ) {}

    public function title(): string
    {
        return 'Alerts History';
    }

    public function headings(): array
    {
        return ['#', 'Title', 'Type', 'Tractor', 'Device', 'Status', 'Date'];
    }

    public function columnWidths(): array
    {
        return ['A' => 6, 'B' => 32, 'C' => 20, 'D' => 20, 'E' => 22, 'F' => 14, 'G' => 18];
    }

    public function array(): array
    {
        return collect($this->alerts)->map(fn ($a, $i) => [
            $i + 1,
            $a['title'] ?? '',
            ucwords(str_replace('_', ' ', $a['type'] ?? '')),
            $a['tractor']['no_plate'] ?? '—',
            $a['device']['device_name'] ?? $a['device']['imei'] ?? '—',
            ($a['is_acknowledged'] ?? false) ? 'Acknowledged' : 'Pending',
            $a['created_at'] ?? '',
        ])->toArray();
    }

    public function styles(Worksheet $sheet): array
    {
        $rowCount = count($this->alerts) + 6;
        $lastDataRow = count($this->alerts) + 4;

        // ── Title banner (Row 1-2) ──
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'ALERTS HISTORY');
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(44);

        // Subtitle
        $sheet->mergeCells('A2:G2');
        $filtersText = $this->filterLabel();
        $sheet->setCellValue('A2', "Total: {$this->summary['total']} · Unacknowledged: {$this->summary['unacknowledged']} · {$filtersText}");
        $sheet->getStyle('A2:G2')->applyFromArray([
            'font' => ['size' => 10, 'color' => ['rgb' => '6B7280']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(28);

        // ── Header row (Row 4) ──
        $headerStyle = [
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6366F1']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'C7D2FE']]],
        ];
        $sheet->getStyle('A4:G4')->applyFromArray($headerStyle);
        $sheet->getRowDimension(4)->setRowHeight(32);

        // ── Data rows ──
        $dataStyle = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];
        $sheet->getStyle("A5:G{$lastDataRow}")->applyFromArray($dataStyle);
        $sheet->getStyle("A5:A{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("F5:G{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Alternating row colors
        for ($r = 5; $r <= $lastDataRow; $r++) {
            if (($r - 5) % 2 === 1) {
                $sheet->getStyle("A{$r}:G{$r}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->setStartColor(new Color('F9FAFB'));
            }
        }

        // ── Footer ──
        $footerRow = $lastDataRow + 2;
        $sheet->mergeCells("A{$footerRow}:G{$footerRow}");
        $sheet->setCellValue("A{$footerRow}", 'Generated on '.now()->format('F j, Y \a\t g:i A').' · Tanod Fleet Management');
        $sheet->getStyle("A{$footerRow}:G{$footerRow}")->applyFromArray([
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
        if (! empty($this->filters['type'])) {
            $parts[] = 'Type: '.ucwords(str_replace('_', ' ', $this->filters['type']));
        }
        if (isset($this->filters['acknowledged']) && $this->filters['acknowledged'] !== '') {
            $parts[] = $this->filters['acknowledged'] === '1' ? 'Acknowledged' : 'Unacknowledged';
        }

        return $parts ? 'Filters: '.implode(' · ', $parts) : 'All alerts';
    }
}
