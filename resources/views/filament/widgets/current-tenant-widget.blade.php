<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold">Tenant Aktif</h3>
                <p class="text-2xl font-bold text-primary-600 dark:text-primary-400 mt-2">
                    {{ $this->getCurrentTenant() }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Total tenant tersedia: {{ $this->getAvailableTenantsCount() }}
                </p>
            </div>
            
            @if($this->getAvailableTenantsCount() > 1)
                <div>
                    <a href="{{ route('filament.admin.pages.tenant-switcher') }}" 
                       class="filament-button filament-button-size-md inline-flex items-center justify-center py-1 gap-1 font-medium rounded-lg border transition-colors focus:outline-none focus:ring-offset-2 focus:ring-2 focus:ring-inset min-h-[2.25rem] px-4 text-sm text-white shadow focus:ring-white border-transparent bg-primary-600 hover:bg-primary-500 focus:bg-primary-700 focus:ring-offset-primary-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                        Ganti Tenant
                    </a>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
