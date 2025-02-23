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
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Repeater::make('cash_in_out_entries')
                    ->label('Entri Cash In Out')
                    ->schema([
                        TextInput::make('nama_barang')
                            ->label('Nama Barang')
                            ->nullable()
                            ->columnSpanFull(),

                        Select::make('type')
                            ->label('Tipe')
                            ->options([
                                'B_BAKU' => 'Bahan Baku',
                                'PERALATAN' => 'Peralatan',
                                'BAND' => 'Band',
                                'LISTRIK' => 'Listrik',
                                'GAS' => 'Gas',
                                'REFUND' => 'Refund',
                                'KASBON' => 'Kasbon',
                                'OWNER' => 'Owner',
                                'COMPLIMENT' => 'Compliment',
                                'BPJS' => 'BPJS',
                                'QRIS' => 'QRIS',
                                'TUNAI' => 'Tunai',
                                'PAJAK' => 'Pajak',
                                'TAX' => 'TAX 5%',
                                'GAJI' => 'Gaji',
                                'GAJI_C_PIRING' => 'Gaji Cuci Piring',
                                'BB_MAKASSAR'=> 'Makassar Bahan Baku',
                            ])
                            ->default('QRIS')
                            ->required()
                            ->live() // Tambahkan live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                $peemasukanTypes = ['QRIS', 'TUNAI'];
                                $set('tipe_cio', in_array($state, $peemasukanTypes) ? 1 : 2);
                            }),
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
                        Select::make('tipe_cio')
                            ->label('Tipe Transaksi')
                            ->options([
                                1 => 'Pemasukan',
                                2 => 'Pengeluaran'
                            ])
                            ->default(function (Get $get) {
                                $peemasukanTypes = ['QRIS', 'TUNAI'];
                                return in_array($get('type'), $peemasukanTypes) ? 1 : 2;
                            })
                            ->live()
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
        return $table
            ->columns([
                TextColumn::make('nama_barang')
                    ->label('Nama Barang'),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->searchable(),
                BadgeColumn::make('tipe_cio')
                    ->label('Tipe Transaksi')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        '1' => 'Pemasukan',
                        '2' => 'Pengeluaran',
                        default => 'Tidak Diketahui'
                    })
                    ->colors([
                        'success' => 1,
                        'danger' => 2,
                    ])
                    ->icon(fn(string $state): string => match ($state) {
                        '1' => 'heroicon-o-arrow-down-tray',
                        '2' => 'heroicon-o-arrow-up-tray',
                        default => 'heroicon-o-question-mark-circle'
                    }),
                TextColumn::make('nilai')
                    ->label('Nilai')
                    ->formatStateUsing(function ($state) {
                        return 'Rp ' . number_format($state, 0, ',', '.');
                    }),
                TextColumn::make('waktu')
                    ->label('Waktu Transaksi')
                    ->dateTime('d M Y'),
            ])->defaultSort('waktu', 'desc')
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
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
