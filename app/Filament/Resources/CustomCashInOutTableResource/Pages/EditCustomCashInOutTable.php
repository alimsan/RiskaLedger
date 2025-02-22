<?php

namespace App\Filament\Resources\CustomCashInOutTableResource\Pages;

use App\Filament\Resources\CustomCashInOutTableResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomCashInOutTable extends EditRecord
{
    protected static string $resource = CustomCashInOutTableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
