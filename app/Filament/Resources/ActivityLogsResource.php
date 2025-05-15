<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogsResource\Pages;
use App\Models\ActivityLogs;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Colors\Color;

class ActivityLogsResource extends Resource
{
    protected static ?string $model = ActivityLogs::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Log Aktivitas';

    protected static ?string $modelLabel = 'Log Aktivitas';

    protected static ?string $pluralModelLabel = 'Log Aktivitas';

    protected static ?int $navigationSort = 100;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }
    public static function canAccess(): bool
    {
        return auth()->user()->hasRole(['superadmin','admin']);
    }
    public static function getNavigationGroup(): ?string
    {
        return 'Pengaturan';
    }
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Log')
                    ->schema([
                        Infolists\Components\TextEntry::make('log_name')
                            ->label('Nama Log')
                            ->badge()
                            ->color('primary'),
                        Infolists\Components\TextEntry::make('event')
                            ->label('Jenis Event')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'created' => 'success',
                                'updated' => 'warning',
                                'deleted' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('description')
                            ->label('Deskripsi')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Detail Objek')
                    ->schema([
                        Infolists\Components\TextEntry::make('subject_type')
                            ->label('Tipe Objek')
                            ->formatStateUsing(fn (string $state): string => class_basename($state)),
                        Infolists\Components\TextEntry::make('subject_id')
                            ->label('ID Objek'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Pengguna')
                    ->schema([
                        Infolists\Components\TextEntry::make('causer_id')
                            ->label('Nama Pengguna')
                            ->formatStateUsing(function ($state, $record) {
                                if (!$state) return 'Sistem';
                                $user = User::find($state);
                                return $user ? $user->name : 'Pengguna #' . $state;
                            }),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Waktu')
                            ->dateTime(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Data Properti')
                    ->schema([
                        Infolists\Components\TextEntry::make('properties')
                            ->formatStateUsing(fn ($state) => $state ? json_encode(json_decode($state), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : null)
                            ->markdown()
                            ->extraAttributes(['class' => 'font-mono text-xs'])
                            ->label('')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('log_name')
                    ->label('Nama Log')
                    ->searchable()
                    ->badge(),
                Tables\Columns\TextColumn::make('event')
                    ->label('Jenis Event')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->searchable()
                    ->limit(60),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Tipe Objek')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject_id')
                    ->label('ID Objek')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('causer_id')
                    ->label('Pengguna')
                    ->formatStateUsing(function ($state, $record) {
                        if (!$state) return 'Sistem';
                        $user = User::find($state);
                        return $user ? $user->name : 'Pengguna #' . $state;
                    })
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('log_name')
                    ->label('Nama Log')
                    ->options(fn (): array => ActivityLogs::distinct()->pluck('log_name', 'log_name')->toArray()),

                SelectFilter::make('event')
                    ->label('Jenis Event')
                    ->options([
                        'created' => 'Dibuat',
                        'updated' => 'Diperbarui',
                        'deleted' => 'Dihapus',
                    ]),

                SelectFilter::make('causer_id')
                    ->label('Pengguna')
                    ->relationship('user', 'name'),

                Filter::make('created_at')
                    ->label('Tanggal')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->poll('60s');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
            'view' => Pages\ViewActivityLog::route('/{record}'),
        ];
    }
}
