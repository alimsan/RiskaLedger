<?php

namespace App\Filament\Resources\ItemResource\Pages;

use App\Filament\Resources\ItemResource;
use App\Models\Item;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Filament\Support\RawJs;

class CreateItem extends CreateRecord
{
    protected static string $resource = ItemResource::class;

    protected static ?string $title = 'Tambah Produk';

    public function form(Form $form): Form
    {
        $user = auth()->user();
        $schema = [];

        if ($user->isAdministrator()) {
            $schema[] = Forms\Components\Section::make('Pilih Tenant')
                ->schema([
                    Forms\Components\Select::make('tenant_id')
                        ->label('Tenant')
                        ->relationship('tenant', 'name')
                        ->required()
                        ->preload()
                        ->searchable(),
                ]);
        }

        $schema[] = Forms\Components\Section::make('Daftar Produk Baru')
            ->description('Gunakan tombol "+ Tambah Baris Produk" untuk menginput banyak produk sekaligus. Tombol scanner pada kolom barcode terisolasi khusus untuk baris tersebut.')
            ->schema([
                Forms\Components\Repeater::make('items')
                    ->label('')
                    ->schema([
                        Forms\Components\Grid::make(12)
                            ->schema([
                                ItemResource::getBarcodeField(true)
                                    ->columnSpan(['default' => 12, 'md' => 3]),

                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Produk')
                                    ->placeholder('Nama produk...')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(['default' => 12, 'md' => 4]),

                                Forms\Components\TextInput::make('category')
                                    ->label('Kategori')
                                    ->placeholder('Kategori...')
                                    ->maxLength(255)
                                    ->datalist(function () {
                                        $tenantId = auth()->user()->tenant_id;
                                        return Item::where('tenant_id', $tenantId)
                                            ->distinct()
                                            ->pluck('category')
                                            ->filter()
                                            ->toArray();
                                    })
                                    ->columnSpan(['default' => 12, 'md' => 2]),

                                Forms\Components\TextInput::make('price')
                                    ->label('Harga')
                                    ->prefix('Rp')
                                    ->required()
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                                    ->stripCharacters('.')
                                    ->numeric()
                                    ->formatStateUsing(fn ($state) => filled($state) ? number_format((float) $state, 0, '', '.') : '')
                                    ->dehydrateStateUsing(fn ($state) => filled($state) ? (float) str_replace('.', '', (string) $state) : 0)
                                    ->columnSpan(['default' => 12, 'md' => 3]),

                                Forms\Components\TextInput::make('stock')
                                    ->label('Stok')
                                    ->numeric()
                                    ->default(0)
                                    ->columnSpan(['default' => 12, 'md' => 2]),

                                Forms\Components\TextInput::make('sku')
                                    ->label('SKU')
                                    ->placeholder('SKU (opsional)...')
                                    ->maxLength(255)
                                    ->columnSpan(['default' => 12, 'md' => 3]),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Aktif')
                                    ->default(true)
                                    ->inline(false)
                                    ->columnSpan(['default' => 12, 'md' => 1]),

                                Forms\Components\TextInput::make('description')
                                    ->label('Deskripsi')
                                    ->placeholder('Deskripsi singkat (opsional)...')
                                    ->maxLength(255)
                                    ->columnSpan(['default' => 12, 'md' => 6]),

                                Forms\Components\FileUpload::make('image')
                                    ->label('Gambar Produk')
                                    ->image()
                                    ->directory('items')
                                    ->disk('public')
                                    ->visibility('public')
                                    ->maxSize(5120)
                                    ->columnSpan(['default' => 12, 'md' => 12]),
                            ]),
                    ])
                    ->defaultItems(1)
                    ->addActionLabel('+ Tambah Baris Produk')
                    ->reorderable(false)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => 
                        !empty($state['name']) 
                            ? $state['name'] . (!empty($state['barcode']) ? ' [' . $state['barcode'] . ']' : '') 
                            : 'Baris Produk Baru'
                    ),
            ]);

        return $form->schema($schema);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $tenantId = auth()->user()->isAdministrator()
            ? ($data['tenant_id'] ?? auth()->user()->tenant_id)
            : auth()->user()->tenant_id;

        $items = $data['items'] ?? [];
        $createdRecords = [];

        DB::transaction(function () use ($items, $tenantId, &$createdRecords) {
            foreach ($items as $itemData) {
                if (empty($itemData['name'])) {
                    continue;
                }
                $itemData['tenant_id'] = $tenantId;
                if (isset($itemData['price'])) {
                    $itemData['price'] = (float) str_replace('.', '', (string) $itemData['price']);
                }
                $createdRecords[] = Item::create($itemData);
            }
        });

        if (count($createdRecords) > 1) {
            Notification::make()
                ->title('Berhasil!')
                ->body('Sebanyak ' . count($createdRecords) . ' produk berhasil ditambahkan.')
                ->success()
                ->send();
        }

        return !empty($createdRecords) ? $createdRecords[0] : new Item();
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Produk berhasil ditambahkan!';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
