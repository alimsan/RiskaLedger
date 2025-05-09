<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashInOutTypeResource\Pages;
use App\Filament\Resources\CashInOutTypeResource\RelationManagers;
use App\Models\CashInOutType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;

class CashInOutTypeResource extends Resource
{
    protected static ?string $model = CashInOutType::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationLabel = 'Jenis Transaksi';
    protected static ?string $modelLabel = 'Jenis Transaksi';
    protected static ?string $pluralModelLabel = 'Jenis Transaksi';
    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return 'Pengaturan';
    }

    public static function form(Form $form): Form
    {
        $user = auth()->user();
        $record = $form->getRecord();
        $recordId = $record ? $record->id : null;

        $formSchema = [
            Forms\Components\TextInput::make('code')
                ->label('Kode')
                ->required()
                ->maxLength(255)
                ->helperText('Kode transaksi hanya perlu unik untuk tenant yang sama. Tenant yang berbeda bisa menggunakan kode yang sama.')
                ->unique(
                    table: 'cash_in_out_types',
                    column: 'code',
                    modifyRuleUsing: fn (\Illuminate\Validation\Rules\Unique $rule) =>
                        $rule->where('tenant_id', auth()->user()->tenant_id),
                    ignoreRecord: true
                )->validationMessages([
                    'unique' => 'Kode :attribute sudah digunakan.',
                ]),
            Forms\Components\TextInput::make('name')
                ->label('Nama')
                ->required()
                ->maxLength(255),
            Forms\Components\Textarea::make('description')
                ->label('Deskripsi')
                ->maxLength(65535)
                ->columnSpanFull(),
            Forms\Components\Toggle::make('is_income')
                ->label('Pemasukan')
                ->required()
                ->default(false)
                ->helperText('Jika diaktifkan, tipe ini akan dihitung sebagai pemasukan.'),
            Forms\Components\Toggle::make('is_active')
                ->label('Aktif')
                ->required()
                ->default(true),
            Forms\Components\Toggle::make('show_akhir')
                ->label('Tampilkan di Hasil Akhir')
                ->required()
                ->default(false)
                ->helperText('Jika diaktifkan, tipe ini akan ditampilkan di halaman Hasil Akhir.'),
            Forms\Components\TextInput::make('sort_order')
                ->label('Urutan')
                ->required()
                ->numeric()
                ->default(0),
        ];

        // Jika user adalah admin, tambahkan select tenant
        if ($user->isAdministrator()) {
            array_unshift($formSchema,
                Forms\Components\Select::make('tenant_id')
                    ->label('Tenant')
                    ->relationship('tenant', 'name')
                    ->preload()
                    ->searchable()
            );
        }

        return $form->schema([
            Forms\Components\Card::make()->schema($formSchema)->columns(2)
        ]);
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();

        // Filter data berdasarkan tenant user jika bukan admin
        $query = function (Builder $query) use ($user) {
            if (!$user->isAdministrator()) {
                $query->where('tenant_id', $user->tenant_id);
            }
        };

        $columns = [
            Tables\Columns\TextColumn::make('code')
                ->label('Kode')
                ->searchable(),
            Tables\Columns\TextColumn::make('name')
                ->label('Nama')
                ->searchable(),
            Tables\Columns\IconColumn::make('is_income')
                ->label('Pemasukan')
                ->boolean(),
            Tables\Columns\IconColumn::make('is_active')
                ->label('Aktif')
                ->boolean(),
            Tables\Columns\IconColumn::make('show_akhir')
                ->label('Tampil di Hasil Akhir')
                ->boolean(),
            Tables\Columns\TextColumn::make('sort_order')
                ->label('Urutan')
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

        // Jika user adalah admin, tambahkan kolom tenant
        if ($user->isAdministrator()) {
            array_unshift($columns,
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->sortable()
                    ->searchable()
            );
        }

        return $table
            ->modifyQueryUsing($query)
            ->columns($columns)
            ->filters([
                Tables\Filters\SelectFilter::make('is_income')
                    ->label('Tipe')
                    ->options([
                        '1' => 'Pemasukan',
                        '0' => 'Pengeluaran',
                    ]),
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Tidak Aktif',
                    ]),
                Tables\Filters\SelectFilter::make('show_akhir')
                    ->label('Tampil di Hasil Akhir')
                    ->options([
                        '1' => 'Ya',
                        '0' => 'Tidak',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Tables\Actions\DeleteAction $action, CashInOutType $record) {
                        if ($record->transactions()->exists()) {
                            Notification::make()
                                ->title('Tidak dapat menghapus')
                                ->body('Jenis transaksi ini masih digunakan dalam transaksi kas.')
                                ->danger()
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Tables\Actions\DeleteBulkAction $action, \Illuminate\Database\Eloquent\Collection $records) {
                            foreach ($records as $record) {
                                if ($record->transactions()->exists()) {
                                    Notification::make()
                                        ->title('Tidak dapat menghapus')
                                        ->body('Beberapa jenis transaksi masih digunakan dalam transaksi kas.')
                                        ->danger()
                                        ->send();

                                    $action->cancel();
                                    break;
                                }
                            }
                        }),
                ]),
            ])
            ->defaultSort('sort_order');
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
            'index' => Pages\ListCashInOutTypes::route('/'),
            'create' => Pages\CreateCashInOutType::route('/create'),
            'edit' => Pages\EditCashInOutType::route('/{record}/edit'),
        ];
    }
}
