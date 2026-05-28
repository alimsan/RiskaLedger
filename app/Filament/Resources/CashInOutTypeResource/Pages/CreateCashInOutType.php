<?php

namespace App\Filament\Resources\CashInOutTypeResource\Pages;

use App\Filament\Resources\CashInOutTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCashInOutType extends CreateRecord
{
    protected static string $resource = CashInOutTypeResource::class;

    /**
     * Mengisi data default sebelum record disimpan
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        // Jika bukan admin, set tenant_id ke tenant user yang login
        if (!$user->isAdministrator()) {
            $data['tenant_id'] = $user->tenant_id;
        }

        return $data;
    }
}
