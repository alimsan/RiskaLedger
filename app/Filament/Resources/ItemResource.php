<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ItemResource\Pages;
use App\Filament\Resources\ItemResource\RelationManagers;
use App\Models\Item;
use App\Models\ConfigTenants;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Filament\Support\RawJs;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'Produk';
    protected static ?string $modelLabel = 'Produk';
    protected static ?string $pluralModelLabel = 'Daftar Produk';
    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return 'Kasir';
    }
    public static function canAccess(): bool
    {
        $user = auth()->user();
        
        // Cek role owner dan manager (akses default)
        if ($user->hasRole(['owner','manager'])) {
            return true;
        }
        
        // Cek role operator dengan konfigurasi operator_produk
        if ($user->hasRole('operator')) {
            $tenantId = $user->tenant_id;
            
            $operatorProdukConfig = ConfigTenants::where('tenant_id', $tenantId)
                ->where('name', 'operator_produk')
                ->where('status', true)
                ->first();
                
            return $operatorProdukConfig ? true : false;
        }
        
        return false;
    }
    public static function form(Form $form): Form
    {
        $user = auth()->user();

        $schema = [
            Forms\Components\TextInput::make('name')
                ->label('Nama Produk')
                ->required()
                ->maxLength(255),
            Forms\Components\Textarea::make('description')
                ->label('Deskripsi')
                ->maxLength(65535)
                ->columnSpanFull(),
            Forms\Components\TextInput::make('price')
                ->label('Harga')
                ->required()
                ->prefix('Rp')
                ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                ->stripCharacters('.')
                ->numeric()
                ->formatStateUsing(fn ($state) => filled($state) ? number_format((float) $state, 0, '', '.') : '')
                ->dehydrateStateUsing(fn ($state) => filled($state) ? (float) str_replace('.', '', (string) $state) : 0),
            Forms\Components\TextInput::make('stock')
                ->label('Stok')
                ->numeric()
                ->default(0),
            Forms\Components\FileUpload::make('image')
                ->label('Gambar Produk')
                ->image()
                ->directory('items')
                ->disk('public')
                ->visibility('public')
                ->maxSize(5120), // 5MB max
            Forms\Components\TextInput::make('sku')
                ->label('SKU')
                ->maxLength(255),
            static::getBarcodeField(),
            Forms\Components\TextInput::make('category')
                ->label('Kategori')
                ->maxLength(255),
            Forms\Components\Toggle::make('is_active')
                ->label('Aktif')
                ->default(true),
        ];

        if ($user->isAdministrator()) {
            array_unshift($schema,
                Forms\Components\Select::make('tenant_id')
                    ->label('Tenant')
                    ->relationship('tenant', 'name')
                    ->required()
                    ->preload()
                    ->searchable()
            );
        }

        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Produk')
                    ->schema($schema)
                    ->columns(2),
            ]);
    }

    /**
     * Komponen input barcode dengan tombol generate barcode, tombol scanner, live preview, dan enter protection
     */
    public static function getBarcodeField(bool $inRepeater = false): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('barcode')
            ->label('Barcode')
            ->placeholder('Scan / ketik barcode...')
            ->maxLength(255)
            ->suffixActions([
                Forms\Components\Actions\Action::make('generateBarcode')
                    ->icon('heroicon-m-sparkles')
                    ->tooltip('Generate Barcode Otomatis')
                    ->color('warning')
                    ->extraAttributes([
                        'type' => 'button',
                        'title' => 'Klik untuk generate barcode acak unik',
                    ])
                    ->alpineClickHandler('
                        const wrapper = $el.closest(".fi-fo-field-wrp") || $el.closest("[wire\\\\:key]");
                        const input = wrapper ? wrapper.querySelector("input") : null;
                        if (input) {
                            const randCode = "899" + Math.floor(100000000 + Math.random() * 900000000);
                            input.value = randCode;
                            input.dispatchEvent(new Event("input", { bubbles: true }));
                            input.dispatchEvent(new Event("change", { bubbles: true }));
                            try {
                                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                                const osc = ctx.createOscillator();
                                const gain = ctx.createGain();
                                osc.connect(gain);
                                gain.connect(ctx.destination);
                                osc.frequency.value = 880;
                                gain.gain.value = 0.12;
                                osc.start();
                                osc.stop(ctx.currentTime + 0.1);
                            } catch(e){}
                        }
                    '),
                Forms\Components\Actions\Action::make('scanBarcode')
                    ->icon('heroicon-m-qr-code')
                    ->tooltip('Klik untuk scan barcode dengan mesin')
                    ->color('success')
                    ->extraAttributes([
                        'type' => 'button',
                        'title' => 'Klik untuk fokus dan scan barcode',
                    ])
                    ->alpineClickHandler('
                        const wrapper = $el.closest(".fi-fo-field-wrp") || $el.closest("[wire\\\\:key]");
                        const input = wrapper ? wrapper.querySelector("input") : null;
                        if (input) {
                            input.focus();
                            input.select();
                            try {
                                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                                const osc = ctx.createOscillator();
                                const gain = ctx.createGain();
                                osc.connect(gain);
                                gain.connect(ctx.destination);
                                osc.frequency.value = 1000;
                                gain.gain.value = 0.1;
                                osc.start();
                                osc.stop(ctx.currentTime + 0.08);
                            } catch(e){}
                        }
                    '),
            ])
            ->helperText(view('filament.forms.components.barcode-input-preview'))
            ->extraInputAttributes([
                '@keydown.enter.prevent' => '
                    try {
                        const ctx = new (window.AudioContext || window.webkitAudioContext)();
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.frequency.value = 1200;
                        gain.gain.value = 0.15;
                        osc.start();
                        osc.stop(ctx.currentTime + 0.1);
                    } catch(e){}
                    const row = $el.closest(".fi-fo-repeater-item") || $el.closest("form");
                    if (row) {
                        const nameInput = row.querySelector("input[name*=\'name\']");
                        if (nameInput) {
                            nameInput.focus();
                        }
                    }
                ',
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();

        $columns = [
            Tables\Columns\ImageColumn::make('image')
                ->label('Gambar')
                ->circular(),
            Tables\Columns\TextColumn::make('name')
                ->label('Nama Produk')
                ->searchable(),
            Tables\Columns\TextColumn::make('barcode')
                ->label('Barcode')
                ->searchable()
                ->html()
                ->formatStateUsing(function (?string $state) {
                    if (empty($state)) {
                        return '<span class="text-xs text-gray-400 italic">Belum ada</span>';
                    }
                    return \App\Services\BarcodeService::renderHtml($state, 1, 30);
                }),
            Tables\Columns\TextColumn::make('category')
                ->label('Kategori')
                ->searchable(),
            Tables\Columns\TextColumn::make('price')
                ->label('Harga')
                ->money('IDR')
                ->sortable(),
            Tables\Columns\TextColumn::make('stock')
                ->label('Stok')
                ->numeric()
                ->sortable(),
            Tables\Columns\IconColumn::make('is_active')
                ->label('Aktif')
                ->boolean(),
            Tables\Columns\TextColumn::make('created_at')
                ->label('Dibuat')
                ->dateTime('d M Y H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];

        if ($user->isAdministrator()) {
            array_unshift($columns,
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->sortable()
                    ->searchable()
            );
        }

        $table = $table
            ->columns($columns)
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Tidak Aktif',
                    ]),
                Tables\Filters\Filter::make('price')
                    ->form([
                        Forms\Components\TextInput::make('price_from')
                            ->label('Harga Dari')
                            ->numeric()
                            ->placeholder('Rp 0')
                            ->prefix('Rp'),
                        Forms\Components\TextInput::make('price_until')
                            ->label('Harga Sampai')
                            ->numeric()
                            ->placeholder('Rp 1.000.000')
                            ->prefix('Rp'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['price_from'],
                                fn (Builder $query, $price): Builder => $query->where('price', '>=', $price),
                            )
                            ->when(
                                $data['price_until'],
                                fn (Builder $query, $price): Builder => $query->where('price', '<=', $price),
                            );
                    }),
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategori')
                    ->options(function () {
                        $tenantId = auth()->user()->tenant_id;
                        $query = Item::select('category')->distinct();

                        if (!auth()->user()->isAdministrator()) {
                            $query->where('tenant_id', $tenantId);
                        }

                        return $query->whereNotNull('category')
                            ->pluck('category', 'category')
                            ->toArray();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('printBarcode')
                    ->label('Cetak Label / Barcode')
                    ->icon('heroicon-o-printer')
                    ->color('primary')
                    ->visible(fn (Item $record): bool => !empty($record->barcode))
                    ->form([
                        Forms\Components\Radio::make('print_method')
                            ->label('Metode Pencetakan')
                            ->options(function () {
                                if (\App\Models\ConfigTenants::isNiimbotB1Active()) {
                                    return [
                                        'thermal' => 'Printer Label Thermal (NIIMBOT B1 Bluetooth)',
                                        'pdf' => 'Export PDF Lembaran A4 (Siap Potong)',
                                    ];
                                }
                                return [
                                    'pdf' => 'Export PDF Lembaran A4 (Siap Potong)',
                                ];
                            })
                            ->default(fn () => \App\Models\ConfigTenants::isNiimbotB1Active() ? 'thermal' : 'pdf')
                            ->visible(fn () => \App\Models\ConfigTenants::isNiimbotB1Active())
                            ->live()
                            ->required(),
                        Forms\Components\TextInput::make('copies')
                            ->label('Jumlah Label yang Dicetak')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(100)
                            ->required(),
                        Forms\Components\Toggle::make('include_price')
                            ->label('Cantumkan Harga Produk')
                            ->default(true),
                        Forms\Components\Select::make('label_size')
                            ->label('Ukuran Kertas Label')
                            ->options([
                                '50x30' => '50 x 30 mm (Standar NIIMBOT B1)',
                                '40x30' => '40 x 30 mm',
                                '30x20' => '30 x 20 mm',
                            ])
                            ->default('50x30')
                            ->visible(fn (Forms\Get $get) => \App\Models\ConfigTenants::isNiimbotB1Active() && $get('print_method') === 'thermal'),
                        Forms\Components\Select::make('density')
                            ->label('Kepekatan Cetak (Density)')
                            ->options([
                                1 => '1 - Tipis',
                                2 => '2 - Sedang',
                                3 => '3 - Normal (Standar B1)',
                                4 => '4 - Pekat',
                                5 => '5 - Sangat Pekat',
                            ])
                            ->default(3)
                            ->visible(fn (Forms\Get $get) => \App\Models\ConfigTenants::isNiimbotB1Active() && $get('print_method') === 'thermal'),
                    ])
                    ->action(function (Item $record, array $data, \Livewire\Component $livewire) {
                        $method = $data['print_method'] ?? (\App\Models\ConfigTenants::isNiimbotB1Active() ? 'thermal' : 'pdf');
                        $copies = (int) ($data['copies'] ?? 1);
                        $includePrice = (bool) ($data['include_price'] ?? true);

                        if ($method === 'pdf' || !\App\Models\ConfigTenants::isNiimbotB1Active()) {
                            return \App\Services\BarcodeService::downloadPdf(
                                collect([$record]),
                                $copies,
                                $includePrice
                            );
                        }

                        // Mode Thermal NIIMBOT B1
                        session(['thermal_label_payload' => [
                            'ids' => [$record->id],
                            'copies' => $copies,
                            'include_price' => $includePrice,
                            'label_size' => $data['label_size'] ?? '50x30',
                            'density' => (int) ($data['density'] ?? 3),
                        ]]);

                        $url = route('admin.barcode.thermal-label', ['session' => 1]);
                        $livewire->js("window.open('{$url}', '_blank');");
                    }),
                Tables\Actions\EditAction::make()
                    ->hidden(fn () => \App\Models\ConfigTenants::isStrictOperator()),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn () => \App\Models\ConfigTenants::isStrictOperator()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('printSelectedThermal')
                        ->label('Print Label/Barkode (Thermal)')
                        ->icon('heroicon-o-printer')
                        ->color('warning')
                        ->visible(fn (): bool => \App\Models\ConfigTenants::isNiimbotB1Active())
                        ->form([
                            Forms\Components\Select::make('copies_type')
                                ->label('Penentuan Jumlah Label')
                                ->options([
                                    'fixed' => 'Jumlah Tetap (Tiap Produk)',
                                    'by_stock' => 'Sesuai Jumlah Stok Produk Saat Ini',
                                ])
                                ->default('fixed')
                                ->live(),
                            Forms\Components\TextInput::make('copies')
                                ->label('Jumlah Label Tiap Produk')
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->maxValue(100)
                                ->visible(fn (Forms\Get $get) => $get('copies_type') === 'fixed')
                                ->required(),
                            Forms\Components\Toggle::make('include_price')
                                ->label('Cantumkan Harga Produk')
                                ->default(true),
                            Forms\Components\Select::make('label_size')
                                ->label('Ukuran Kertas Label')
                                ->options([
                                    '50x30' => '50 x 30 mm (Standar NIIMBOT B1)',
                                    '40x30' => '40 x 30 mm',
                                    '30x20' => '30 x 20 mm',
                                ])
                                ->default('50x30'),
                            Forms\Components\Select::make('density')
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
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data, \Livewire\Component $livewire) {
                            $validRecords = $records->filter(fn (Item $r) => !empty($r->barcode));

                            if ($validRecords->isEmpty()) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Gagal Memproses')
                                    ->body('Produk yang dicentang tidak memiliki barcode untuk dicetak.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            $copies = ($data['copies_type'] ?? 'fixed') === 'by_stock' ? 'by_stock' : (int) ($data['copies'] ?? 1);

                            session(['thermal_label_payload' => [
                                'ids' => $validRecords->pluck('id')->toArray(),
                                'copies' => $copies,
                                'include_price' => (bool) ($data['include_price'] ?? true),
                                'label_size' => $data['label_size'] ?? '50x30',
                                'density' => (int) ($data['density'] ?? 2),
                            ]]);

                            $url = route('admin.barcode.thermal-label', ['session' => 1]);
                            $livewire->js("window.open('{$url}', '_blank');");
                        }),
                    Tables\Actions\BulkAction::make('printSelectedBarcodes')
                        ->label('Cetak Barcode Terpilih (PDF)')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->form([
                            Forms\Components\TextInput::make('copies')
                                ->label('Jumlah Label Tiap Produk')
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->maxValue(50)
                                ->required(),
                            Forms\Components\Toggle::make('include_price')
                                ->label('Cantumkan Harga Produk')
                                ->default(true),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            return \App\Services\BarcodeService::downloadPdf(
                                $records,
                                (int) ($data['copies'] ?? 1),
                                (bool) ($data['include_price'] ?? true)
                            );
                        }),
                    Tables\Actions\DeleteBulkAction::make()
                        ->hidden(fn () => \App\Models\ConfigTenants::isStrictOperator()),
                ]),
            ]);

        // Filter tenant jika bukan admin
        if (!$user->isAdministrator()) {
            $table->modifyQueryUsing(fn (Builder $query) => $query->where('tenant_id', $user->tenant_id));
        }

        return $table;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        if (\App\Models\ConfigTenants::isStrictOperator()) {
            return false;
        }

        return parent::canEdit($record);
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        if (\App\Models\ConfigTenants::isStrictOperator()) {
            return false;
        }

        return parent::canDelete($record);
    }

    public static function canDeleteAny(): bool
    {
        if (\App\Models\ConfigTenants::isStrictOperator()) {
            return false;
        }

        return parent::canDeleteAny();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListItems::route('/'),
            'create' => Pages\CreateItem::route('/create'),
            'edit' => Pages\EditItem::route('/{record}/edit'),
        ];
    }
}
