<?php

namespace App\Filament\Resources\AnomaliTransaksiResource\Pages;

use App\Filament\Resources\AnomaliTransaksiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAnomaliTransaksis extends ListRecords
{
    protected static string $resource = AnomaliTransaksiResource::class;

    public function getSubheading(): ?string
    {
        return 'Anomali ini terjadi ketika data memiliki riwayat transaksi namun tidak tercatat pada pengeluaran / piutangnya.';
    }

    protected function getHeaderActions(): array
    {
        return [
            // Kosong, karena kita tidak ingin tombol 'Create' muncul di halaman anomali
        ];
    }
}
