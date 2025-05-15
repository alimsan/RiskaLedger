<?php

namespace App\Filament\Resources\ActivityLogsResource\Pages;

use App\Filament\Resources\ActivityLogsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogsResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
