<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Navigation\NavigationGroup;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set aplikasi ke Bahasa Indonesia
        app()->setLocale('id');

        //S\Blade::componentNamespace('Filament\\Tables\\Components', 'tables');
        try {
            \Filament\Facades\Filament::serving(function () {
                // Logging atau debugging
                \Log::info('Filament Login Page: ' . config('filament.pages.login'));
                \Log::info('Filament auth', [
                    'auth' => auth()->check(),
                    'middleware' => request()->route()?->gatherMiddleware() ?? []
                ]);
                // Mengatur urutan navigation group
                \Filament\Facades\Filament::registerNavigationGroups([
                    NavigationGroup::make()
                        ->label('Kasir'),
                    NavigationGroup::make()
                        ->label('Piutang'),
                    NavigationGroup::make()
                        ->label('Pengaturan'),
                ]);
            });
        } catch (\Exception $e) {
            \Log::error('Filament Login Page Error: ' . $e->getMessage());
        }
    }
}
