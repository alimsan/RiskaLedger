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
            Actions\Action::make('printThermal')
                ->label('Cetak Label Thermal (Niimbot)')
                ->icon('heroicon-o-printer')
                ->color('warning')
                ->visible(fn (): bool => \App\Models\ConfigTenants::isNiimbotB1Active())
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
                        ->maxValue(100)
                        ->visible(fn (\Filament\Forms\Get $get) => $get('copies_type') === 'fixed')
                        ->required(),
                    \Filament\Forms\Components\Toggle::make('include_price')
                        ->label('Cantumkan Harga Produk')
                        ->default(true),
                    \Filament\Forms\Components\Select::make('label_size')
                        ->label('Ukuran Kertas Label')
                        ->options([
                            '50x30' => '50 x 30 mm (Standar NIIMBOT B1)',
                            '40x30' => '40 x 30 mm',
                            '30x20' => '30 x 20 mm',
                        ])
                        ->default('50x30'),
                    \Filament\Forms\Components\Select::make('density')
                        ->label('Kepekatan Cetak (Density)')
                        ->options([
                            1 => '1 - Tipis',
                            2 => '2 - Sedang',
                            3 => '3 - Normal (Standar B1)',
                            4 => '4 - Pekat',
                            5 => '5 - Sangat Pekat',
                        ])
                        ->default(3),
                ])
                ->action(function (array $data, \Livewire\Component $livewire) {
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
                            ->title('Gagal Memproses')
                            ->body('Tidak ditemukan produk yang memiliki barcode untuk dicetak.')
                            ->warning()
                            ->send();
                        return;
                    }

                    $copies = ($data['copies_type'] ?? 'fixed') === 'by_stock' ? 'by_stock' : (int) ($data['copies'] ?? 1);

                    session(['thermal_label_payload' => [
                        'ids' => $items->pluck('id')->toArray(),
                        'copies' => $copies,
                        'include_price' => (bool) ($data['include_price'] ?? true),
                        'label_size' => $data['label_size'] ?? '50x30',
                        'density' => (int) ($data['density'] ?? 2),
                    ]]);

                    $url = route('admin.barcode.thermal-label', ['session' => 1]);
                    $livewire->js("window.open('{$url}', '_blank');");
                }),
            Actions\Action::make('exportBarcodes')
                ->label('Export Barcode (PDF)')
                ->icon('heroicon-o-document-arrow-down')
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
