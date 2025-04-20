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

    protected function getHeaderWidgets(): array
    {
        return [
            // Tambahkan widget jika diperlukan
        ];
    }

    public function getTitle(): string
    {
        $user = auth()->user();

        if ($user->isAdministrator()) {
            return 'Cash In Out';
        }

        // Jika bukan admin, tambahkan nama tenant
        return 'Cash In Out - ' . ($user->tenant ? $user->tenant->name : '');
    }
}
