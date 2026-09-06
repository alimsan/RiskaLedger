<?php

namespace App\Filament\Resources\ItemResource\Pages;

use App\Filament\Resources\ItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListItems extends ListRecords
{
    protected static string $resource = ItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('exportBarcodes')
                ->label('Export Barcode (PDF)')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\Select::make('filter')
                        ->label('Produk yang Dicetak')
                        ->options([
                            'all' => 'Semua Produk Aktif',
                            'with_stock' => 'Hanya Produk yang Memiliki Stok (> 0)',
                        ])
                        ->default('all'),
                    \Filament\Forms\Components\Select::make('copies_type')
                        ->label('Penentuan Jumlah Label')
                        ->options([
                            'fixed' => 'Jumlah Tetap (Tentukan per produk)',
                            'by_stock' => 'Sesuai Jumlah Stok Produk Saat Ini',
                        ])
                        ->default('fixed')
                        ->live(),
                    \Filament\Forms\Components\TextInput::make('copies')
                        ->label('Jumlah Label per Produk')
                        ->numeric()
                        ->default(1)
                        ->minValue(1)
                        ->maxValue(50)
                        ->visible(fn (\Filament\Forms\Get $get) => $get('copies_type') === 'fixed')
                        ->required(),
                    \Filament\Forms\Components\Toggle::make('include_price')
                        ->label('Cantumkan Harga Produk')
                        ->default(true),
                ])
                ->action(function (array $data) {
                    $user = auth()->user();
                    $query = \App\Models\Item::query()->whereNotNull('barcode')->where('barcode', '!=', '');
                    if (!$user->isAdministrator()) {
                        $query->where('tenant_id', $user->tenant_id);
                    }
                    if (($data['filter'] ?? 'all') === 'with_stock') {
                        $query->where('stock', '>', 0);
                    }
                    $query->where('is_active', true);
                    $items = $query->orderBy('name', 'asc')->get();

                    if ($items->isEmpty()) {
                        \Filament\Notifications\Notification::make()
                            ->title('Gagal Mencetak')
                            ->body('Tidak ditemukan produk yang memiliki barcode untuk dicetak.')
                            ->warning()
                            ->send();
                        return null;
                    }

                    $copies = ($data['copies_type'] ?? 'fixed') === 'by_stock' ? 'by_stock' : (int) ($data['copies'] ?? 1);
                    $includePrice = (bool) ($data['include_price'] ?? true);

                    return \App\Services\BarcodeService::downloadPdf($items, $copies, $includePrice);
                }),
            Actions\CreateAction::make(),
        ];
    }
}
