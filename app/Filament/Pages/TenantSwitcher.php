<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class TenantSwitcher extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static string $view = 'filament.pages.tenant-switcher';

    protected static ?string $navigationLabel = 'Ganti Tenant';

    protected static ?string $title = 'Ganti Tenant';

    protected static ?int $navigationSort = 99;

    public ?array $data = [];

    public function mount(): void
    {
        $currentTenantId = Auth::user()->getCurrentTenantId();
        $this->form->fill([
            'tenant_id' => $currentTenantId,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('tenant_id')
                    ->label('Pilih Tenant')
                    ->options(function () {
                        $user = Auth::user();
                        
                        if ($user->isAdministrator()) {
                            return \App\Models\Tenant::pluck('name', 'id');
                        }
                        
                        return $user->tenants()
                            ->select('tenants.id', 'tenants.name')
                            ->pluck('tenants.name', 'tenants.id');
                    })
                    ->required()
                    ->searchable()
                    ->native(false),
            ])
            ->statePath('data');
    }

    public function switchAction(): Action
    {
        return Action::make('switch')
            ->label('Ganti Tenant')
            ->color('primary')
            ->action(function () {
                $user = Auth::user();
                $data = $this->form->getState();
                $tenantId = $data['tenant_id'];

                if ($user->isAdministrator()) {
                    $user->update(['current_tenant_id' => $tenantId]);
                    
                    Notification::make()
                        ->title('Tenant berhasil diganti')
                        ->success()
                        ->send();
                        
                    return redirect()->route('filament.admin.pages.dashboard');
                } else {
                    if ($user->switchTenant($tenantId)) {
                        Notification::make()
                            ->title('Tenant berhasil diganti')
                            ->success()
                            ->send();
                            
                        return redirect()->route('filament.admin.pages.dashboard');
                    } else {
                        Notification::make()
                            ->title('Anda tidak memiliki akses ke tenant ini')
                            ->danger()
                            ->send();
                    }
                }
            });
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }

        // Allow if user is admin or has multiple tenants
        return $user->isAdministrator() || $user->tenants()->count() > 1;
    }
}
