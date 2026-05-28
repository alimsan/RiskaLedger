<?php

namespace App\Filament\Resources\ActivityLogsResource\Pages;

use App\Filament\Resources\ActivityLogsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditActivityLogs extends EditRecord
{
    protected static string $resource = ActivityLogsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
