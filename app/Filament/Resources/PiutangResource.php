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
        $formSchema = [
            Forms\Components\DateTimePicker::make('waktu')
                ->label('Waktu')
                ->default(now())
                ->required(),
            Forms\Components\Select::make('vendor_id')
                ->label('Vendor')
                ->options(function () use ($user) {
                    $query = Vendor::query();
                    if (!$user->hasRole(['superadmin', 'admin'])) {
                        $query->where('tenant_id', $user->tenant_id);
                    }
                    return $query->pluck('nama_vendor', 'id');
                })
                ->required()
                ->searchable()
                ->preload(),
            Forms\Components\TextInput::make('qty')
                ->label('Jumlah')
                ->numeric()
                ->required(),
            Forms\Components\TextInput::make('total_utang')
                ->label('Total Hutang')
                ->required()
                ->numeric()
                ->prefix('Rp'),
            Forms\Components\FileUpload::make('bukti_resi')
                ->label('Bukti Resi')
                ->directory('piutang-resi')
                ->image()
                ->maxSize(5120), // 5MB max
        ];

        // Tambahkan pilihan tenant hanya untuk superadmin dan admin
        if ($user->hasRole(['superadmin', 'admin'])) {
            array_splice($formSchema, 2, 0, [
                Forms\Components\Select::make('tenant_id')
                    ->label('Tenant')
                    ->relationship('tenant', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('vendor_id', null)),
            ]);
        }

        return $form
            ->schema([
                Forms\Components\Section::make('Data Piutang')
                    ->schema($formSchema)
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
            Tables\Columns\TextColumn::make('qty')
                ->label('Jumlah')
                ->numeric()
                ->sortable(),
            Tables\Columns\TextColumn::make('total_utang')
                ->label('Total Hutang')
                ->money('IDR')
                ->sortable(),
            Tables\Columns\ImageColumn::make('bukti_resi')
                ->label('Bukti Resi')
                ->disk('public')
                ->circular(),
            Tables\Columns\TextColumn::make('waktu')
                ->label('Waktu')
                ->dateTime('d M Y H:i')
                ->sortable(),
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
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
