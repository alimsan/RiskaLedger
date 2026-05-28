<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnomaliTransaksiResource\Pages;
use App\Models\TransactionItems;
use App\Models\mCashInOut;
use App\Models\Piutang;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AnomaliTransaksiResource extends Resource
{
    protected static ?string $model = TransactionItems::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationGroup = 'Transaksi MOD';
    protected static ?string $navigationLabel = 'Anomali Transaksi';
    protected static ?string $modelLabel = 'Anomali Transaksi';
    protected static ?string $pluralModelLabel = 'Anomali Transaksi';
    protected static ?string $slug = 'anomali-transaksi';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return in_array($user->role, ['superadmin', 'admin', 'owner']) || $user->hasRole(['superadmin', 'admin', 'owner']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('ID Transaksi Utama')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('transaction_type')
                    ->label('Tipe Transaksi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'penjualan' => 'success',
                        'piutang' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('item.name')
                    ->label('Nama Barang')
                    ->searchable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty')
                    ->numeric(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Harga')
                    ->money('IDR'),
                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('IDR'),
                Tables\Columns\TextColumn::make('waktu')
                    ->label('Waktu Transaksi')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('transaction_type')
                    ->label('Tipe Transaksi')
                    ->options([
                        'penjualan' => 'Penjualan (cash_in_out)',
                        'piutang' => 'Piutang (piutangs)',
                    ]),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus Anomali'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Anomali Terpilih'),
                ]),
            ])
            ->emptyStateHeading('Tidak Ada Anomali Transaksi')
            ->emptyStateDescription('Semua data transaction_items saat ini memiliki parent ID yang valid di tabel utama.');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->where(function (Builder $query) {
                $query->where(function (Builder $q) {
                    $q->where('transaction_type', 'penjualan')
                      ->whereNotIn('transaction_id', mCashInOut::select('id'));
                })
                ->orWhere(function (Builder $q) {
                    $q->where('transaction_type', 'piutang')
                      ->whereNotIn('transaction_id', Piutang::select('id'));
                });
            });

        $user = auth()->user();
        $isSuperAdminOrAdmin = in_array($user->role, ['superadmin', 'admin']) || $user->hasRole(['superadmin', 'admin']);
        
        if (! $isSuperAdminOrAdmin) {
            $query->whereHas('item', function ($q) use ($user) {
                $q->where('tenant_id', $user->tenant_id);
            });
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnomaliTransaksis::route('/'),
        ];
    }
}
