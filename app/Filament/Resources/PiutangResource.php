<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PiutangResource\Pages;
use App\Filament\Resources\PiutangResource\RelationManagers;
use App\Models\Piutang;
use App\Models\Vendor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use App\Models\CashInOutType;
use App\Models\mCashInOut;

class PiutangResource extends Resource
{
    protected static ?string $model = Piutang::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Piutang';
    protected static ?string $modelLabel = 'Piutang';
    protected static ?string $pluralModelLabel = 'Piutang';
    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return 'Piutang';
    }

    public static function form(Form $form): Form
    {
        $user = auth()->user();

        return $form
            ->schema([
                Forms\Components\Section::make('Data Piutang')
                    ->schema([
                        Forms\Components\Select::make('vendor_id')
                            ->label('Vendor')
                            ->relationship('vendor', 'nama_vendor')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('nama_vendor')
                                    ->required()
                                    ->maxLength(255),
                            ]),

                        Forms\Components\Select::make('type_id')
                            ->label('Tipe Transaksi')
                            ->relationship('type', 'name', function ($query) {
                                return $query->where('is_income', false)
                                    ->where('is_active', true);
                            })
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('qty')
                            ->label('Jumlah Item')
                            ->required()
                            ->numeric()
                            ->default(1),

                        Forms\Components\TextInput::make('total_utang')
                            ->label('Total Hutang')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),

                        Forms\Components\DateTimePicker::make('waktu')
                            ->label('Waktu Transaksi')
                            ->required()
                            ->default(now()),

                        Forms\Components\Toggle::make('lunas')
                            ->label('Status Pelunasan')
                            ->onColor('success')
                            ->offColor('danger')
                            ->default(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Bukti Transaksi')
                    ->schema([
                        Forms\Components\FileUpload::make('bukti_resi')
                            ->label('Bukti Transaksi')
                            ->directory('bukti-resi')
                            ->image()
                            ->maxSize(5120),

                        Forms\Components\FileUpload::make('image_pelunasan')
                            ->label('Bukti Pelunasan')
                            ->directory('pelunasan-resi')
                            ->image()
                            ->maxSize(5120)
                            ->visible(function (?Piutang $record) {
                                if ($record) {
                                    return $record->lunas;
                                }
                                return false;
                            }),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();
        $columns = [
            Tables\Columns\TextColumn::make('vendor.nama_vendor')
                ->label('Vendor')
                ->searchable(),

            Tables\Columns\TextColumn::make('type.name')
                ->label('Tipe Transaksi')
                ->sortable(),

            Tables\Columns\TextColumn::make('qty')
                ->label('Jumlah Item')
                ->numeric()
                ->sortable(),

            Tables\Columns\TextColumn::make('total_utang')
                ->label('Total Hutang')
                ->money('IDR')
                ->sortable(),

            Tables\Columns\TextColumn::make('waktu')
                ->label('Waktu')
                ->dateTime('d M Y H:i')
                ->sortable(),

            Tables\Columns\IconColumn::make('lunas')
                ->label('Status')
                ->boolean()
                ->sortable(),

            Tables\Columns\ImageColumn::make('bukti_resi')
                ->label('Bukti Transaksi')
                ->disk('public')
                ->circular(),

            Tables\Columns\ImageColumn::make('image_pelunasan')
                ->label('Bukti Pelunasan')
                ->disk('public')
                ->circular()
                ->visible(function ($record) {
                    return $record instanceof Piutang && $record->lunas;
                }),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Dibuat Pada')
                ->dateTime('d M Y H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('updated_at')
                ->label('Diperbarui Pada')
                ->dateTime('d M Y H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('deleted_at')
                ->label('Dihapus Pada')
                ->dateTime('d M Y H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];

        // Jika user adalah admin atau superadmin, tambahkan kolom tenant
        if ($user->hasRole(['superadmin', 'admin'])) {
            array_splice($columns, 1, 0, [
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->sortable()
                    ->searchable(),
            ]);
        }

        $filters = [
            Tables\Filters\TrashedFilter::make(),
            Tables\Filters\SelectFilter::make('vendor_id')
                ->label('Vendor')
                ->options(function () use ($user) {
                    $query = Vendor::query();
                    if (!$user->hasRole(['superadmin', 'admin'])) {
                        $query->where('tenant_id', $user->tenant_id);
                    }
                    return $query->pluck('nama_vendor', 'id');
                }),
            Tables\Filters\Filter::make('waktu')
                ->form([
                    Forms\Components\DatePicker::make('waktu_dari')
                        ->label('Dari Tanggal'),
                    Forms\Components\DatePicker::make('waktu_sampai')
                        ->label('Sampai Tanggal'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['waktu_dari'],
                            fn (Builder $query, $date): Builder => $query->whereDate('waktu', '>=', $date),
                        )
                        ->when(
                            $data['waktu_sampai'],
                            fn (Builder $query, $date): Builder => $query->whereDate('waktu', '<=', $date),
                        );
                }),
        ];

        // Jika user adalah admin atau superadmin, tambahkan filter tenant
        if ($user->hasRole(['superadmin', 'admin'])) {
            $filters[] = Tables\Filters\SelectFilter::make('tenant_id')
                ->label('Tenant')
                ->relationship('tenant', 'name');
        }

        return $table
            ->columns($columns)
            ->filters($filters)
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('proses_pelunasan')
                        ->label('Proses Pelunasan')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->form([
                            Forms\Components\FileUpload::make('image_pelunasan')
                                ->label('Bukti Pelunasan')
                                ->directory('pelunasan-resi')
                                ->image()
                                ->maxSize(5120)
                                ->required(),
                        ])
                        ->action(function (Piutang $record, array $data) {
                            // Proses pelunasan piutang
                            DB::beginTransaction();
                            try {
                                // 1. Rekam transaksi pelunasan di cash_in_out
                                $mCashInOut = new mCashInOut();
                                $mCashInOut->tenant_id = $record->tenant_id;
                                $mCashInOut->type_id = $record->type_id; // Menggunakan type_id dari piutang
                                $mCashInOut->nama_barang = 'Pelunasan piutang ' . $record->vendor->nama_vendor;
                                $mCashInOut->deksripsi = 'Pelunasan piutang dengan jumlah ' . $record->qty . ' item';
                                $mCashInOut->nilai = $record->total_utang;
                                $mCashInOut->waktu = now();
                                $mCashInOut->save();

                                // 2. Update status piutang menjadi lunas
                                $record->lunas = true;
                                $record->image_pelunasan = $data['image_pelunasan'];
                                $record->save();

                                DB::commit();

                                Notification::make()
                                    ->title('Pelunasan berhasil')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                DB::rollBack();
                                Notification::make()
                                    ->title('Gagal memproses pelunasan')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(function ($record) {
                            return $record instanceof Piutang && !$record->lunas;
                        }),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_pelunasan')
                        ->label('Proses Pelunasan Massal')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->form([
                            Forms\Components\FileUpload::make('image_pelunasan')
                                ->label('Bukti Pelunasan')
                                ->directory('pelunasan-resi')
                                ->image()
                                ->maxSize(5120)
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            // Validasi hanya piutang yang belum lunas yang bisa diproses
                            $records = $records->filter(fn (Piutang $record) => !$record->lunas);

                            if ($records->isEmpty()) {
                                Notification::make()
                                    ->title('Tidak ada piutang yang dapat diproses')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            DB::beginTransaction();
                            try {
                                foreach ($records as $record) {
                                    // 1. Rekam transaksi pelunasan di cash_in_out
                                    $mCashInOut = new mCashInOut();
                                    $mCashInOut->tenant_id = $record->tenant_id;
                                    $mCashInOut->type_id = $record->type_id; // Menggunakan type_id dari piutang
                                    $mCashInOut->nama_barang = 'Pelunasan piutang ' . $record->vendor->nama_vendor;
                                    $mCashInOut->deksripsi = 'Pelunasan piutang dengan jumlah ' . $record->qty . ' item';
                                    $mCashInOut->nilai = $record->total_utang;
                                    $mCashInOut->waktu = now();
                                    $mCashInOut->save();

                                    // 2. Update status piutang menjadi lunas
                                    $record->lunas = true;
                                    $record->image_pelunasan = $data['image_pelunasan'];
                                    $record->save();
                                }

                                DB::commit();

                                Notification::make()
                                    ->title('Pelunasan massal berhasil')
                                    ->body('Berhasil memproses ' . $records->count() . ' piutang')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                DB::rollBack();
                                Notification::make()
                                    ->title('Gagal memproses pelunasan massal')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
            'index' => Pages\ListPiutangs::route('/'),
            'create' => Pages\CreatePiutang::route('/create'),
            'edit' => Pages\EditPiutang::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        // Jika pengguna bukan superadmin atau admin, batasi data yang ditampilkan
        if (!auth()->user()->hasRole(['superadmin', 'admin'])) {
            // Jika user adalah owner atau operator, hanya tampilkan piutang untuk tenant mereka
            $query->where('tenant_id', auth()->user()->tenant_id);
        }

        return $query;
    }
}
