<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        //S\Blade::componentNamespace('Filament\\Tables\\Components', 'tables');
          try {
            \Filament\Facades\Filament::serving(function () {
                // Logging atau debugging
                \Log::info('Filament Login Page: ' . config('filament.pages.login'));
            });
        } catch (\Exception $e) {
            \Log::error('Filament Login Page Error: ' . $e->getMessage());
        }
    }
}
