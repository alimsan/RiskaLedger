<?php

namespace App\Filament\Resources\CashInOutResource\Pages;

use App\Filament\Resources\CashInOutResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model as EloquentModel;

class CreateCashInOut extends CreateRecord
{
    protected static string $resource = CashInOutResource::class;

    protected function getRedirectUrl(): string
    {
        // Redirect ke halaman list CashInOutResource
        return $this->getResource()::getUrl('index');
    }

    // Nonaktifkan redirect ke halaman edit setelah create
    protected function getCreatedNotificationAction(): ?\Filament\Notifications\Actions\Action
    {
        return null;
    }

    protected function handleRecordCreation(array $data): EloquentModel
    {
        $entries = $data['cash_in_out_entries'];

        // Opsi 1: Simpan satu per satu
        foreach ($entries as $entry) {
            $this->getModel()::create($entry);
        }

        // Opsi 2: Simpan sekaligus (gunakan jika model mendukung)
        // return $this->getModel()::create($entries);

        // Kembalikan model pertama atau model baru (sesuaikan kebutuhan)
        return $this->getModel()::first();
    }
}
