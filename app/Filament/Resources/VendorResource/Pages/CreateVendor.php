<?php

namespace App\Filament\Resources\VendorResource\Pages;

use App\Filament\Resources\VendorResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateVendor extends CreateRecord
{
    protected static string $resource = VendorResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Jika bukan superadmin atau admin, set tenant_id ke tenant pengguna saat ini
        if (!auth()->user()->hasRole(['superadmin', 'admin'])) {
            $data['tenant_id'] = auth()->user()->getCurrentTenantId();
        }

        return $data;
    }
}
