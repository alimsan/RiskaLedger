<?php

namespace App\Filament\Resources\ConfigTenantsResource\Pages;

use App\Filament\Resources\ConfigTenantsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConfigTenants extends EditRecord
{
    protected static string $resource = ConfigTenantsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
