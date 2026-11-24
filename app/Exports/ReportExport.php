<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Formats report data for export using the application reporting conventions.
 */
class ReportExport implements FromArray, WithHeadings
{
    /**
     * @param  array<int, string>  $headings
     * @param  array<int, array<int, string|int|float|null>>  $rows
     */
    public function __construct(private array $headings, private array $rows) {}

    /** @return array<int, array<int, string|int|float|null>> */
    public function array(): array
    {
        return $this->rows;
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return $this->headings;
    }
}
