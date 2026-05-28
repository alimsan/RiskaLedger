<?php

namespace App\Filament\Resources\CashInOutTypeResource\Pages;

use App\Filament\Resources\CashInOutTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCashInOutType extends EditRecord
{
    protected static string $resource = CashInOutTypeResource::class;

    /**
     * Membatasi akses hanya pada data yang dimiliki tenant sendiri untuk non-admin
     */
    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        $user = auth()->user();

        // Jika bukan admin, pastikan hanya bisa mengedit tipe transaksi milik tenant sendiri
        if (!$user->isAdministrator() && $this->record->tenant_id !== $user->tenant_id) {
            abort(403);
        }
    }

    /**
     * Mengisi data default sebelum record diupdate
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();

        // Jika bukan admin, pastikan tenant_id tidak berubah
        if (!$user->isAdministrator()) {
            $data['tenant_id'] = $user->tenant_id;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
