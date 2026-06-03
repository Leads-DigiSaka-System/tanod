<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DeviceStatusExport implements FromArray, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    public function __construct(
        protected array $devices,
        protected array $summary,
    ) {}

    public function title(): string
    {
        return 'Device Status';
    }

    public function headings(): array
    {
        return ['#', 'Device Name', 'IMEI', 'Tractor', 'Status', 'Last Heartbeat', 'SIM', 'Expiration'];
    }

    public function columnWidths(): array
    {
        return ['A' => 6, 'B' => 24, 'C' => 20, 'D' => 24, 'E' => 14, 'F' => 20, 'G' => 18, 'H' => 16];
    }

    public function array(): array
    {
        return collect($this->devices)->map(fn ($d, $i) => [
            $i + 1,
            $d['device_name'] ?? $d['imei'] ?? '',
            $d['imei'] ?? '',
            $d['tractor']
                ? (($d['tractor']['brand'] ?? '').' '.($d['tractor']['model'] ?? '').' — '.($d['tractor']['no_plate'] ?? ''))
                : '—',
            ($d['is_online'] ?? false) ? 'Online' : 'Offline',
            $d['latest_location']['heartbeat_at'] ?? '—',
            $d['sim'] ?? '—',
            $d['expiration_date'] ?? '—',
        ])->toArray();
    }

    public function styles(Worksheet $sheet): array
    {
        $lastDataRow = count($this->devices) + 4;

        // ── Title banner ──
        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', 'DEVICE STATUS REPORT');
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(44);

        // Subtitle
        $sheet->mergeCells('A2:H2');
        $sheet->setCellValue('A2', "Total Devices: {$this->summary['total']} · Online: {$this->summary['online']} · Offline: {$this->summary['offline']} · Active: {$this->summary['active']}");
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
        $sheet->getStyle("E5:H{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Color online/offline status cells
        for ($r = 5; $r <= $lastDataRow; $r++) {
            $statusCell = $sheet->getCell("E{$r}")->getValue();
            if ($statusCell === 'Online') {
                $sheet->getStyle("E{$r}")->getFont()->getColor()->setRGB('16A34A');
            } elseif ($statusCell === 'Offline') {
                $sheet->getStyle("E{$r}")->getFont()->getColor()->setRGB('DC2626');
            }

            if (($r - 5) % 2 === 1) {
                $sheet->getStyle("A{$r}:H{$r}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->setStartColor(['rgb' => 'F9FAFB']);
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
}
