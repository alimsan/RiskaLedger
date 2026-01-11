<?php

namespace App\Filament\Resources\PiutangResource\Pages;

use App\Filament\Resources\PiutangResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePiutang extends CreateRecord
{
    protected static string $resource = PiutangResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Jika bukan superadmin atau admin, set tenant_id ke tenant pengguna saat ini
        if (!auth()->user()->hasRole(['superadmin', 'admin'])) {
            $data['tenant_id'] = auth()->user()->getCurrentTenantId();
        }

        return $data;
    }
}
