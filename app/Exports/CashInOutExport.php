<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CashInOutExport implements FromArray, WithHeadings, WithMapping
{
    protected $data;
    protected $totals;

    public function __construct(array $data, array $totals)
    {
        $this->data = $data;
        $this->totals = $totals;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Penjualan',
            'Qris',
            'Tunai',
            'Bahan Baku',
            'Peralatan',
            'Band',
            'Listrik',
            'Gas',
            'Refund',
            'Kasbon',
            'Owner',
            'Compliment',
            'BPJS',
            'Jumlah'
        ];
    }

    public function map($row): array
    {
        return [
            $row->tanggal,
            $row->penjualan,
            $row->qris,
            $row->tunai,
            $row->b_baku,
            $row->peralatan,
            $row->band,
            $row->listrik,
            $row->gas,
            $row->refund,
            $row->kasbon,
            $row->owner,
            $row->compliment,
            $row->bpjs,
            $row->tb1_jumlah
        ];
    }
}
