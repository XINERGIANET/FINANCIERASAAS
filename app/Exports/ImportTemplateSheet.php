<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ImportTemplateSheet implements FromArray, WithTitle, ShouldAutoSize, WithStyles
{
    private $title;
    private $rows;

    public function __construct(string $title, array $rows)
    {
        $this->title = $title;
        $this->rows = $rows;
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
                1 => ['font' => ['bold' => true, 'size' => 12]],
            ];
        }

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
