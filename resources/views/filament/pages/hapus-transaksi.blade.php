<x-filament-panels::page>
    <form wire:submit="hapusData">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit" color="danger" icon="heroicon-o-trash">
                Hapus Transaksi
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
