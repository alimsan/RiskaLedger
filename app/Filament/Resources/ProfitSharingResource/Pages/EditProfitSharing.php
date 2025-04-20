<?php

namespace App\Filament\Resources\ProfitSharingResource\Pages;

use App\Filament\Resources\ProfitSharingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProfitSharing extends EditRecord
{
    protected static string $resource = ProfitSharingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
