<?php

namespace App\Filament\Resources\CustomCashInOutTableResource\Pages;

use App\Filament\Resources\CustomCashInOutTableResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomCashInOutTables extends ListRecords
{
    protected static string $resource = CustomCashInOutTableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
