<x-filament-panels::page>
    <x-filament::card>
        {{ $this->form }}

        <div class="mt-6 flex justify-end">
            {{ $this->switchAction }}
        </div>
    </x-filament::card>

    <x-filament-actions::modals />
</x-filament-panels::page>
