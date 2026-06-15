<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ImportTemplateSheet implements FromArray, WithTitle, ShouldAutoSize, WithStyles
{
    private $title;
    private $rows;
    private $highlightExampleRow;

    public function __construct(string $title, array $rows, bool $highlightExampleRow = true)
    {
        $this->title = $title;
        $this->rows = $rows;
        $this->highlightExampleRow = $highlightExampleRow;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function styles(Worksheet $sheet)
    {
        if ($this->title === 'INSTRUCCIONES') {
            return [
                1 => [
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1F4E78'],
                    ],
                ],
            ];
        }

        $styles = [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2F5597'],
                ],
            ],
        ];

        if ($this->highlightExampleRow) {
            $styles[2] = [
                'font' => ['italic' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFF2CC'],
                ],
            ];
        }

        return $styles;
    }
}
