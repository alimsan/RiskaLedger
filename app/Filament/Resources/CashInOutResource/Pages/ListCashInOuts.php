<?php

namespace App\Filament\Resources\CashInOutResource\Pages;

use App\Filament\Resources\CashInOutResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCashInOuts extends ListRecords
{
    protected static string $resource = CashInOutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
