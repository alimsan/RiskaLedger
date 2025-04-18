<?php

namespace App\Filament\Resources\CashInOutTypeResource\Pages;

use App\Filament\Resources\CashInOutTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCashInOutType extends EditRecord
{
    protected static string $resource = CashInOutTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
