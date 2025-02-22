<?php

namespace App\Filament\Resources\CashInOutResource\Pages;

use App\Filament\Resources\CashInOutResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
class EditCashInOut extends EditRecord
{
    protected static string $resource = CashInOutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Tambahkan data dari record yang sedang diedit ke dalam repeater
        $record = $this->getRecord();

        // Siapkan data untuk repeater
        $data['cash_in_out_entries'] = [
            [
                'nama_barang' => $record->nama_barang,
                'type' => $record->type,
                'deksripsi' => $record->deksripsi,
                'tipe_cio' => $record->tipe_cio,
                'waktu' => $record->waktu,
                'nilai' => $record->nilai,
            ]
        ];

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Ambil data dari repeater
        $entries = $data['cash_in_out_entries'][0] ?? [];

        // Update record dengan data dari entri pertama
        $record->update([
            'nama_barang' => $entries['nama_barang'] ?? $record->nama_barang,
            'type' => $entries['type'] ?? $record->type,
            'deksripsi' => $entries['deksripsi'] ?? $record->deksripsi,
            'tipe_cio' => $entries['tipe_cio'] ?? $record->tipe_cio,
            'waktu' => $entries['waktu'] ?? $record->waktu,
            'nilai' => $entries['nilai'] ?? $record->nilai,
        ]);

        return $record;
    }
}
