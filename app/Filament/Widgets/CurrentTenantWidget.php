<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class CurrentTenantWidget extends Widget
{
    protected static string $view = 'filament.widgets.current-tenant-widget';

    protected static ?int $sort = -1;

    protected int | string | array $columnSpan = 'full';

    public function getCurrentTenant(): ?string
    {
        $user = Auth::user();
        
        if (!$user) {
            return null;
        }

        $currentTenant = $user->currentTenant;
        
        if (!$currentTenant && $user->tenant_id) {
            $currentTenant = $user->tenant;
        }

        return $currentTenant ? $currentTenant->name : 'Tidak ada tenant aktif';
    }

    public function getAvailableTenantsCount(): int
    {
        $user = Auth::user();
        
        if (!$user) {
            return 0;
        }

        if ($user->isAdministrator()) {
            return \App\Models\Tenant::count();
        }

        return $user->tenants()->count();
    }
}
