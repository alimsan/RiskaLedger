<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MCashInOutResource\Pages;
use App\Filament\Resources\MCashInOutResource\RelationManagers;
use App\Models\mCashInOut;
use App\Models\CashInOutType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MCashInOutResource extends Resource
{
    protected static ?string $model = mCashInOut::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Transaksi';
    protected static ?string $modelLabel = 'Transaksi';
    protected static ?string $pluralModelLabel = 'Transaksi';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Select::make('type_id')
                            ->label('Tipe Transaksi')
                            ->relationship('type', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('code')
                                    ->label('Kode')
                                    ->required()
                                    ->maxLength(50)
                                    ->unique(),
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('description')
                                    ->label('Deskripsi'),
                                Forms\Components\Toggle::make('is_income')
                                    ->label('Pemasukan')
                                    ->default(false),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Aktif')
                                    ->default(true),
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('nama_barang')
                            ->label('Nama Barang/Jasa')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('deksripsi')
                            ->label('Deskripsi')
                            ->columnSpanFull(),
                        Forms\Components\Select::make('tipe_cio')
                            ->label('Tipe Cash In/Out')
                            ->options([
                                1 => 'Pemasukan',
                                2 => 'Pengeluaran'
                            ])
                            ->default(2)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                // Ketika user memilih tipe transaksi
                                $typeId = $get('type_id');
                                if (!$typeId) return;

                                // Ambil data tipe untuk memeriksa is_income
                                $type = CashInOutType::find($typeId);
                                if (!$type) return;

                                // Jika tipe adalah pemasukan tapi user memilih pengeluaran atau sebaliknya
                                if (($type->is_income && $state == 2) || (!$type->is_income && $state == 1)) {
                                    // Beri peringatan
                                    \Filament\Notifications\Notification::make()
                                        ->title('Peringatan')
                                        ->body('Tipe transaksi dan jenis aliran dana tidak sesuai!')
                                        ->warning()
                                        ->send();
                                }
                            })
                            ->required(),
                        Forms\Components\TextInput::make('nilai')
                            ->label('Nilai')
                            ->required()
                            ->numeric(),
                        Forms\Components\DateTimePicker::make('waktu')
                            ->label('Waktu')
                            ->seconds(false)
                            ->required()
                            ->default(now()),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type.name')
                    ->label('Tipe Transaksi')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('nama_barang')
                    ->label('Nama Barang/Jasa')
                    ->searchable(),
                Tables\Columns\IconColumn::make('tipe_cio')
                    ->label('Jenis')
                    ->options([
                        'heroicon-o-arrow-down' => 1,
                        'heroicon-o-arrow-up' => 2,
                    ])
                    ->colors([
                        'success' => 1,
                        'danger' => 2,
                    ]),
                Tables\Columns\TextColumn::make('nilai')
                    ->label('Nilai')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('waktu')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type_id')
                    ->label('Tipe Transaksi')
                    ->relationship('type', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('tipe_cio')
                    ->label('Jenis')
                    ->options([
                        1 => 'Pemasukan',
                        2 => 'Pengeluaran'
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('waktu', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('waktu', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('waktu', 'desc');
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
            'index' => Pages\ListMCashInOuts::route('/'),
            'create' => Pages\CreateMCashInOut::route('/create'),
            'edit' => Pages\EditMCashInOut::route('/{record}/edit'),
        ];
    }
}
