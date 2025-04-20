<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProfitSharingResource\Pages;
use App\Filament\Resources\ProfitSharingResource\RelationManagers;
use App\Models\ProfitSharing;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;

class ProfitSharingResource extends Resource
{
    protected static ?string $model = ProfitSharing::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationLabel = 'Pembagian Hasil';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?int $navigationSort = 101;
    public static function canCreate(): bool
    {
        return auth()->user()->hasRole(['admin', 'superadmin','manager']);
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->hasRole(['admin', 'superadmin','manager']);
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->hasRole(['admin', 'superadmin','manager']);
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()->hasRole(['admin', 'superadmin','manager']);
    }
    public static function form(Form $form): Form
    {
        $schema = [
            Forms\Components\Section::make('Data Pembagian Hasil')
                ->schema([
                    TextInput::make('name')
                        ->label('Nama')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('percentage')
                        ->label('Persentase (%)')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->step(0.01),
                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),
                    Toggle::make('is_default')
                        ->label('Default')
                        ->default(false)
                        ->helperText('Pembagian hasil ini akan digunakan sebagai default'),
                ])
                ->columns(2),
        ];

        // Tambahkan pilihan tenant hanya untuk superadmin dan admin
        if (auth()->user()->hasRole(['superadmin', 'admin'])) {
            array_unshift($schema[0]->getSchema(),
                Select::make('tenant_id')
                    ->label('Tenant')
                    ->relationship('tenant', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
            );
        } else {
            // Untuk user biasa, tenant_id akan diisi otomatis
            $form->mutateFormDataBeforeCreate(function (array $data) {
                $data['tenant_id'] = auth()->user()->tenant_id;
                return $data;
            });
        }

        return $form->schema($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('percentage')
                    ->label('Persentase (%)')
                    ->numeric(2)
                    ->sortable(),
                BooleanColumn::make('is_active')
                    ->label('Aktif'),
                BooleanColumn::make('is_default')
                    ->label('Default'),
                TextColumn::make('created_at')
                    ->label('Tgl Dibuat')
                    ->dateTime('d-M-Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
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
            'index' => Pages\ListProfitSharings::route('/'),
            'create' => Pages\CreateProfitSharing::route('/create'),
            'edit' => Pages\EditProfitSharing::route('/{record}/edit'),
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
            // Jika user adalah owner atau operator, hanya tampilkan profit sharing untuk tenant mereka
            $query->where('tenant_id', auth()->user()->tenant_id);
        }

        return $query;
    }
}
