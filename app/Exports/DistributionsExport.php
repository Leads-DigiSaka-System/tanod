<?php

namespace App\Exports;

use App\Models\TractorDistribution;
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

class DistributionsExport implements FromArray, ShouldAutoSize, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    public function __construct(
        private array $ids,
    ) {}

    public function title(): string
    {
        return 'Distributions';
    }

    public function headings(): array
    {
        return [
            'Organization Name (FCA)',
            'Area',
            'IMEI Number',
            'Sim Card Number',
            'Status of GPS',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 24,
            'C' => 22,
            'D' => 22,
            'E' => 18,
        ];
    }

    public function array(): array
    {
        $distributions = TractorDistribution::with([
            'tractor.device.latestLocation',
            'distributedToUser',
        ])->whereIn('id', $this->ids)->get();

        $rows = [];

        foreach ($distributions as $dist) {
            $device = $dist->tractor->device ?? null;
            $loc = $device->latestLocation ?? null;

            // GPS status: online if heartbeat within 10 minutes
            $gpsStatus = 'Offline';
            if ($loc && $loc->heartbeat_at) {
                $heartbeat = \Carbon\Carbon::parse($loc->heartbeat_at);
                $gpsStatus = $heartbeat->diffInMinutes(now()) < 10 ? 'Online' : 'Offline';
            }

            $rows[] = [
                $dist->distributedToUser->organization_name ?? $dist->distributedToUser->name ?? '—',
                $dist->area ?? '—',
                $device->imei ?? '—',
                $device->sim ?? '—',
                $gpsStatus,
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // Header style
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '166534']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $lastRow = $sheet->getHighestRow();
        if ($lastRow < 2) {
            return [];
        }

        // Data style
        $sheet->getStyle("A2:E{$lastRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Zebra striping
        for ($r = 2; $r <= $lastRow; $r++) {
            if (($r - 2) % 2 === 1) {
                $sheet->getStyle("A{$r}:E{$r}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->setStartColor(new Color('F9FAFB'));
            }
        }

        // Center GPS status column
        $sheet->getStyle("E2:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [];
    }
}
