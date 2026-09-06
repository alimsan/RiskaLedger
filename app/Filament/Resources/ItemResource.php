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
                ->numeric()
                ->prefix('Rp'),
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
                    ->label('Cetak Barcode')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->visible(fn (Item $record): bool => !empty($record->barcode))
                    ->form([
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
                    ])
                    ->action(function (Item $record, array $data) {
                        return \App\Services\BarcodeService::downloadPdf(
                            collect([$record]),
                            (int) ($data['copies'] ?? 1),
                            (bool) ($data['include_price'] ?? true)
                        );
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('printSelectedBarcodes')
                        ->label('Cetak Barcode Terpilih (PDF)')
                        ->icon('heroicon-o-printer')
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
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);

        // Filter tenant jika bukan admin
        if (!$user->isAdministrator()) {
            $table->modifyQueryUsing(fn (Builder $query) => $query->where('tenant_id', $user->tenant_id));
        }

        return $table;
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
