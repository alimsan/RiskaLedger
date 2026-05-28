<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Pengguna';
    protected static ?string $modelLabel = 'Pengguna';
    protected static ?string $pluralModelLabel = 'Pengguna';
    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'Pengaturan';
    }

    public static function form(Form $form): Form
    {
        $user = auth()->user();

        $formSchema = [
            Forms\Components\TextInput::make('name')
                ->label('Nama')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('password')
                ->label('Password')
                ->password()
                ->dehydrateStateUsing(fn ($state) => bcrypt($state))
                ->dehydrated(fn ($state) => filled($state))
                ->required(fn (string $context): bool => $context === 'create'),
            Forms\Components\Select::make('role')
                ->label('Peran')
                ->options([
                    'superadmin' => 'Super Admin',
                    'admin' => 'Admin',
                    'owner' => 'Owner',
                    'operator' => 'Operator',
                ])
                ->default('operator')
                ->required()
                ->visible(fn () => auth()->user()->hasRole(['admin', 'superadmin']))
                ->reactive(),
            Forms\Components\Select::make('roles')
                ->label('Permissions')
                ->multiple()
                ->relationship('roles', 'name')
                ->preload()
                ->visible(fn () => auth()->user()->hasRole(['admin', 'superadmin'])),
        ];

        // Jika user adalah administrator, tambahkan select tenant
        // Field tenant hanya ditampilkan jika role adalah owner atau operator
        if ($user->isAdministrator()) {
            $formSchema[] = Forms\Components\Select::make('tenant_id')
                ->label('Tenant')
                ->relationship('tenant', 'name')
                ->preload()
                ->searchable()
                ->visible(fn (callable $get) => in_array($get('role'), ['owner', 'operator']))
                ->required(fn (callable $get) => in_array($get('role'), ['owner', 'operator']));
        }

        return $form->schema([
            Forms\Components\Card::make()->schema($formSchema)->columns(2),
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
            Tables\Columns\TextColumn::make('name')
                ->label('Nama')
                ->searchable(),
            Tables\Columns\TextColumn::make('email')
                ->label('Email')
                ->searchable(),
            Tables\Columns\TextColumn::make('role')
                ->label('Peran')
                ->badge()
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'superadmin' => 'Super Admin',
                    'admin' => 'Admin',
                    'owner' => 'Owner',
                    'operator' => 'Operator',
                    default => $state,
                })
                ->colors([
                    'danger' => 'superadmin',
                    'warning' => 'admin',
                    'success' => 'owner',
                    'primary' => 'operator',
                ]),
            Tables\Columns\TextColumn::make('roles.name')
                ->label('Permissions')
                ->badge(),
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
            array_splice($columns, 3, 0, [
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->sortable()
                    ->searchable()
            ]);
        }

        return $table
            ->modifyQueryUsing($query)
            ->columns($columns)
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Peran')
                    ->options([
                        'superadmin' => 'Super Admin',
                        'admin' => 'Admin',
                        'owner' => 'Owner',
                        'operator' => 'Operator',
                    ]),
                Tables\Filters\SelectFilter::make('tenant_id')
                    ->label('Tenant')
                    ->relationship('tenant', 'name')
                    ->visible(fn () => $user->isAdministrator()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
