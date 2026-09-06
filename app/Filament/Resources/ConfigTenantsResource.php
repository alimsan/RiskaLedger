<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConfigTenantsResource\Pages;
use App\Filament\Resources\ConfigTenantsResource\RelationManagers;
use App\Models\ConfigTenants;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ConfigTenantsResource extends Resource
{
    protected static ?string $model = ConfigTenants::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static ?string $navigationGroup = 'Pengaturan';
    public static function canAccess(): bool
    {
        return auth()->user()->hasRole(['superadmin','admin']);
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('tenant_id')
                    ->label('Tenant')
                    ->relationship('tenant', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('name')
                    ->label('Nama Konfigurasi')
                    ->placeholder('Contoh: stock_use, operator_produk, strict_operator')
                    ->datalist([
                        'stock_use',
                        'operator_produk',
                        'strict_operator',
                    ])
                    ->required()
                    ->maxLength(255)
                    ->unique(
                        table: 'config_tenants',
                        column: 'name',
                        ignorable: fn ($record) => $record,
                        modifyRuleUsing: function ($rule, callable $get) {
                            return $rule->where('tenant_id', $get('tenant_id'));
                        }
                    ),
                Forms\Components\Toggle::make('status')
                    ->label('Status Aktif')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Konfigurasi')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('status')
                    ->label('Status'),
            ])
            ->filters([
                //
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
            'index' => Pages\ListConfigTenants::route('/'),
            'create' => Pages\CreateConfigTenants::route('/create'),
            'edit' => Pages\EditConfigTenants::route('/{record}/edit'),
        ];
    }
}
