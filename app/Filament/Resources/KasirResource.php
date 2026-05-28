<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KasirResource\Pages;
use App\Models\Item;
use App\Models\CashInOutType;
use App\Models\mCashInOut;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Pages\Page;

class KasirResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Kasir';
    protected static ?string $modelLabel = 'Kasir';
    protected static ?string $pluralModelLabel = 'Kasir';
    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return 'Kasir';
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole(['manager','owner', 'operator']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Tidak ada form untuk resource ini
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Tidak ada tabel untuk resource ini
            ])
            ->filters([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
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
            'index' => Pages\KasirPage::route('/'),
        ];
    }
}
