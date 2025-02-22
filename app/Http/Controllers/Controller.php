<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected static function getTotalNilai(array $types)
    {
        $query = \App\Models\mCashInOut::whereIn('type', $types);

        $selectedMonth = session('selected_month');
        if ($selectedMonth) {
            $date = \Carbon\Carbon::parse($selectedMonth);
            $startDate = $date->copy()->startOfMonth()->toDateString();
            $endDate = $date->copy()->endOfMonth()->toDateString();

            $query->whereBetween('tanggal', [$startDate, $endDate]);
        }

        return $query->sum('nilai');
    }
   /*  public function getData()
    {
        'penjualan' => static::getTotalByType(['QRIS', 'TUNAI'], $date),
    } */
}
