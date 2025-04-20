<x-filament::page>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Panel Produk -->
        <div class="md:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-300 dark:border-gray-700 p-4">
            <!-- Search dan Filter -->
            <div class="mb-4 flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="searchQuery"
                        placeholder="Cari produk..."
                        class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm"
                    >
                </div>
                <div>
                    <select
                        wire:model.live="selectedCategory"
                        class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm"
                    >
                        <option value="">Semua Kategori</option>
                        @foreach($this->categories as $category)
                            <option value="{{ $category }}">{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Produk Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @forelse($this->items as $item)
                    <div class="bg-white dark:bg-gray-700 rounded-lg shadow-sm overflow-hidden border border-gray-200 dark:border-gray-600 transition hover:shadow-md">
                        <div class="relative pb-[100%] bg-gray-100 dark:bg-gray-800">
                            @if($item->image)
                                <img
                                    src="{{ $item->image_url }}"
                                    alt="{{ $item->name }}"
                                    class="absolute inset-0 w-full h-full object-cover"
                                    onerror="this.onerror=null; this.src='{{ asset('images/placeholder.jpg') }}'; console.log('Failed to load image: {{ $item->image_url }}');"
                                >
                                <!-- Debug info -->
                                @if(config('app.debug'))
                                <div class="absolute bottom-0 right-0 bg-black bg-opacity-50 text-white text-xs p-1">
                                    {{ substr($item->image, 0, 15) }}...
                                </div>
                                @endif
                            @else
                                {{-- <div class="absolute inset-0 flex items-center justify-center text-gray-400 dark:text-gray-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div> --}}
                            @endif
                        </div>
                        <div class="p-3">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $item->name }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 truncate">
                                @if($item->category)
                                    <span class="text-xs bg-gray-100 dark:bg-gray-600 rounded px-1.5 py-0.5">{{ $item->category }}</span>
                                @endif
                            </p>
                            <div class="mt-2 flex flex-col gap-2">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $item->formatted_price }}</span>
                                <button
                                    wire:click="addToCart({{ $item->id }})"
                                    class="w-full text-white bg-primary-600 hover:bg-primary-700 focus:ring-2 focus:ring-primary-500 font-medium rounded-lg text-xs py-1.5 px-2 inline-flex items-center justify-center"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    Tambah
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-8 text-gray-500 dark:text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 12H4M12 4v16" />
                        </svg>
                        <p class="mt-2">Tidak ada produk ditemukan</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Panel Keranjang -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-300 dark:border-gray-700 p-4">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Keranjang Belanja</h2>

            @if(count($cartItems) > 0)
                <div class="space-y-4">
                    <div class="max-h-80 overflow-y-auto pr-2">
                        @foreach($cartItems as $itemId => $item)
                            <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700 last:border-b-0">
                                <div class="flex-1">
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ $item['name'] }}</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ 'Rp ' . number_format($item['price'], 0, ',', '.') }}</p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button
                                        wire:click="updateCartQuantity({{ $itemId }}, {{ $item['quantity'] - 1 }})"
                                        class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                        </svg>
                                    </button>
                                    <span class="text-gray-700 dark:text-gray-300 w-8 text-center">{{ $item['quantity'] }}</span>
                                    <button
                                        wire:click="updateCartQuantity({{ $itemId }}, {{ $item['quantity'] + 1 }})"
                                        class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                    </button>
                                    <button
                                        wire:click="removeFromCart({{ $itemId }})"
                                        class="text-red-500 hover:text-red-700 ml-2"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <div class="flex justify-between text-base font-medium text-gray-900 dark:text-white">
                            <p>Total</p>
                            <p>{{ 'Rp ' . number_format($this->cartTotal, 0, ',', '.') }}</p>
                        </div>
                    </div>

                    <!-- Tombol Tindakan -->
                    <div class="grid grid-cols-2 gap-3">
                        <button
                            wire:click="clearCart"
                            class="inline-flex justify-center items-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Bersihkan
                        </button>

                        <button
                            onclick="openCheckoutModal()"
                            class="inline-flex justify-center items-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            Checkout
                        </button>
                    </div>
                </div>
            @else
                <div class="text-center py-12">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Keranjang Kosong</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Mulai belanja dengan menambahkan item ke keranjang.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Checkout -->
    <div
        id="checkoutModal"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden"
    >
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg max-w-md w-full p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Pembayaran</h3>
                <button
                    onclick="closeCheckoutModal()"
                    class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                Pilih metode pembayaran dan selesaikan transaksi
            </p>

            <form id="checkoutForm">
                <div class="space-y-4">
                    <div>
                        <label for="payment_method" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Metode Pembayaran
                        </label>
                        <select
                            id="payment_method"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm"
                        >
                            @foreach($this->incomeTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Total Pembayaran
                        </label>
                        <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                            {{ 'Rp ' . number_format($this->cartTotal, 0, ',', '.') }}
                        </div>
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Catatan
                        </label>
                        <textarea
                            id="notes"
                            placeholder="Tambahkan catatan untuk transaksi ini (opsional)"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm"
                        ></textarea>
                    </div>
                </div>

                <div class="mt-6">
                    <button
                        type="button"
                        onclick="submitCheckout()"
                        class="w-full flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                    >
                        Proses Transaksi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCheckoutModal() {
            document.getElementById('checkoutModal').classList.remove('hidden');
        }

        function closeCheckoutModal() {
            document.getElementById('checkoutModal').classList.add('hidden');
        }

        function submitCheckout() {
            const typeId = document.getElementById('payment_method').value;
            const notes = document.getElementById('notes').value;

            // Panggil method Livewire untuk proses checkout
            @this.processCheckout({
                type_id: typeId,
                notes: notes
            }).then((result) => {
                if (result) {
                    closeCheckoutModal();
                }
            });
        }

        // Menangani event 'close-checkout-modal'
        document.addEventListener('DOMContentLoaded', function() {
            window.addEventListener('close-checkout-modal', function() {
                closeCheckoutModal();
            });
        });
    </script>
</x-filament::page>
