<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VendorResource\Pages;
use App\Filament\Resources\VendorResource\RelationManagers;
use App\Models\Vendor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VendorResource extends Resource
{
    protected static ?string $model = Vendor::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationLabel = 'Vendor';
    protected static ?string $modelLabel = 'Vendor';
    protected static ?string $pluralModelLabel = 'Vendor';
    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'Piutang';
    }

    public static function form(Form $form): Form
    {
        $user = auth()->user();
        $formSchema = [
            Forms\Components\TextInput::make('nama_vendor')
                ->label('Nama Vendor')
                ->required()
                ->maxLength(255),
        ];

        // Tambahkan pilihan tenant hanya untuk superadmin dan admin
        if ($user->hasRole(['superadmin', 'admin'])) {
            array_unshift($formSchema,
                Forms\Components\Select::make('tenant_id')
                    ->label('Tenant')
                    ->relationship('tenant', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
            );
        }

        return $form
            ->schema([
                Forms\Components\Section::make('Data Vendor')
                    ->schema($formSchema)
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();
        $columns = [
            Tables\Columns\TextColumn::make('nama_vendor')
                ->label('Nama Vendor')
                ->searchable(),
            Tables\Columns\TextColumn::make('piutangs_count')
                ->label('Jumlah Piutang')
                ->counts('piutangs'),
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
            array_unshift($columns,
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->sortable()
                    ->searchable()
            );
        }

        return $table
            ->columns($columns)
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
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
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PiutangsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendors::route('/'),
            'create' => Pages\CreateVendor::route('/create'),
            'edit' => Pages\EditVendor::route('/{record}/edit'),
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
            // Jika user adalah owner atau operator, hanya tampilkan vendor untuk tenant mereka
            $query->where('tenant_id', auth()->user()->tenant_id);
        }

        return $query;
    }
}
