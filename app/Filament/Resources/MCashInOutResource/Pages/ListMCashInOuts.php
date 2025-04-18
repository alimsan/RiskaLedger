<?php

namespace App\Filament\Resources\MCashInOutResource\Pages;

use App\Filament\Resources\MCashInOutResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMCashInOuts extends ListRecords
{
    protected static string $resource = MCashInOutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
