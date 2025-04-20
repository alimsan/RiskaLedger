<?php

namespace App\Filament\Resources\ItemResource\Pages;

use App\Filament\Resources\ItemResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateItem extends CreateRecord
{
    protected static string $resource = ItemResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Jika bukan admin, set tenant_id ke tenant pengguna saat ini
        if (!auth()->user()->isAdministrator()) {
            $data['tenant_id'] = auth()->user()->tenant_id;
        }

        return $data;
    }
}
