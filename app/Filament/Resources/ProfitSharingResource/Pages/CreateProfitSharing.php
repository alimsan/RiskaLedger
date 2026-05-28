<?php

namespace App\Filament\Resources\ProfitSharingResource\Pages;

use App\Filament\Resources\ProfitSharingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProfitSharing extends CreateRecord
{
    protected static string $resource = ProfitSharingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Jika bukan superadmin atau admin, set tenant_id ke tenant pengguna saat ini
        if (!auth()->user()->hasRole(['superadmin', 'admin'])) {
            $data['tenant_id'] = auth()->user()->tenant_id;
        }

        return $data;
    }
}
