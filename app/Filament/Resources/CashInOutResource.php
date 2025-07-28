<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashInOutResource\Pages;
use App\Filament\Resources\CashInOutResource\RelationManagers;
use App\Models\mCashInOut;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Get;
use Filament\Forms\Set;
class CashInOutResource extends Resource
{
    protected static ?string $model = mCashInOut::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Cash in out';
    protected static ?string $modelLabel = 'Cash in out';
    protected static ?string $pluralModelLabel = 'Cash in out';
    public static function canAccess(): bool
    {
        $user = auth()->user();

        // Cek role owner dan manager (akses default)
        if ($user->hasRole(['owner','manager','admin'])) {
            return true;
        }

        // Cek role operator dengan konfigurasi operator_produk
        if ($user->hasRole('operator')) {
            $tenantId = $user->tenant_id;

            $operatorProdukConfig = \App\Models\ConfigTenants::where('tenant_id', $tenantId)
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

        // Jika user adalah admin atau superadmin, tampilkan pilihan tenant
        // Jika bukan, gunakan tenant_id dari user yang login
        $tenantField = null;

        if ($user->isAdministrator()) {
            $tenantField = Select::make('tenant_id')
                ->label('Tenant')
                ->relationship('tenant', 'name')
                ->required()
                ->searchable()
                ->preload();
        } else {
            $tenantField = TextInput::make('tenant_id')
                ->label('Tenant')
                ->default($user->tenant_id)
                ->disabled()
                ->dehydrated(true)
                ->formatStateUsing(function ($state) use ($user) {
                    return $user->tenant ? $user->tenant->name : 'Tidak ada tenant';
                });
        }

        return $form
            ->schema([
                Section::make('Informasi Umum')
                    ->schema([
                        $tenantField,
                    ]),
                Repeater::make('cash_in_out_entries')
                    ->label('Entri Cash In Out')
                    ->schema([
                        TextInput::make('nama_barang')
                            ->label('Nama Barang')
                            ->nullable()
                            ->columnSpanFull(),

                        Select::make('type_id')
                            ->label('Tipe')
                            ->relationship('type', 'name', function ($query) use ($user) {
                                // Filter tipe berdasarkan tenant jika user bukan admin
                                if (!$user->isAdministrator()) {
                                    $query->where('tenant_id', $user->tenant_id);
                                }
                            })
                            ->required()
                            ->live(),
                        TextInput::make('nilai')
                            ->label('Nilai')
                            ->numeric()
                            ->required()
                            ->prefix('Rp')
                            ->minValue(0),
                        DateTimePicker::make('waktu')
                            ->closeOnDateSelection()
                            ->native(false)
                            ->label('Waktu')
                            ->required(),
                        Textarea::make('deksripsi')
                            ->label('Deskripsi')
                            ->nullable()
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->addActionLabel('Tambah Entri')
                    ->maxItems(10) // Optional: batasi jumlah entri
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();

        // Filter data berdasarkan tenant user jika bukan admin
        $query = function (Builder $query) use ($user) {
            if (!$user->isAdministrator()) {
                $query->where('tenant_id', $user->tenant_id);
            }
        };

        $columns = [
            TextColumn::make('nama_barang')
                ->label('Nama Barang')
                ->searchable(),
            TextColumn::make('type.name')
                ->label('Tipe')
                ->searchable(),
            TextColumn::make('type.is_income')
                ->label('Status')
                ->formatStateUsing(fn ($state): string => $state ? 'Pemasukan' : 'Pengeluaran')
                ->badge()
                ->color(fn ($state): string => $state ? 'success' : 'danger')
                ->icon(fn ($state): string => $state ? 'heroicon-o-arrow-down' : 'heroicon-o-arrow-up'),
            TextColumn::make('keterangan')
                ->label('Keterangan')
                ->html()
                ->formatStateUsing(fn ($state) => nl2br(e($state))),
            TextColumn::make('nilai')
                ->label('Nilai')
                ->formatStateUsing(function ($state) {
                    return 'Rp ' . number_format($state, 0, ',', '.');
                })
                ->sortable(),
            TextColumn::make('deksripsi')
                ->label('Deskripsi')
                ->limit(30)
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('waktu')
                ->label('Waktu Transaksi')
                ->dateTime('d M Y H:i')
                ->sortable(),
        ];

        // Jika user adalah admin, tambahkan kolom tenant
        if ($user->isAdministrator()) {
            array_splice($columns, 1, 0, [
                TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->sortable()
                    ->searchable()
            ]);
        }

        return $table
            ->modifyQueryUsing($query)
            ->columns($columns)
            ->filters([
                \Filament\Tables\Filters\Filter::make('waktu')
                    ->form([
                        DateTimePicker::make('dari')
                            ->label('Dari Tanggal')
                            ->native(false),
                        DateTimePicker::make('sampai')
                            ->label('Sampai Tanggal')
                            ->native(false),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['dari'],
                                fn($query) => $query->where('waktu', '>=', $data['dari'])
                            )
                            ->when(
                                $data['sampai'],
                                fn($query) => $query->where('waktu', '<=', $data['sampai'])
                            );
                    }),
                // Filter hari ini
                \Filament\Tables\Filters\Filter::make('hari_ini')
                    ->label('Hari Ini')
                    ->query(fn($query) => $query->whereDate('waktu', today())),

                // Filter minggu ini
                \Filament\Tables\Filters\Filter::make('minggu_ini')
                    ->label('Minggu Ini')
                    ->query(fn($query) => $query->whereBetween('waktu', [now()->startOfWeek(), now()->endOfWeek()])),

                // Filter bulan ini
                \Filament\Tables\Filters\Filter::make('bulan_ini')
                    ->label('Bulan Ini')
                    ->query(fn($query) => $query->whereBetween('waktu', [now()->startOfMonth(), now()->endOfMonth()])),

                // Filter tenant (hanya untuk administrator)
                \Filament\Tables\Filters\SelectFilter::make('tenant_id')
                    ->label('Tenant')
                    ->relationship('tenant', 'name')
                    ->visible(fn () => $user->isAdministrator()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListCashInOuts::route('/'),
            'create' => Pages\CreateCashInOut::route('/create'),
            'edit' => Pages\EditCashInOut::route('/{record}/edit'),
        ];
    }
}
