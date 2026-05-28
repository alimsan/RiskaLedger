<?php

namespace App\Filament\Resources\CashInOutTypeResource\Pages;

use App\Filament\Resources\CashInOutTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCashInOutTypes extends ListRecords
{
    protected static string $resource = CashInOutTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
