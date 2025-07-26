<x-filament::page x-data="checkoutFunctions">
    <style>
        /* Style sederhana untuk toggle switch */
        .simple-toggle {
            position: relative;
            display: inline-block;
            width: 46px;
            height: 24px;
        }

        .simple-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .simple-toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .simple-toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        .simple-toggle input:checked + .simple-toggle-slider {
            background-color: #f59e0b;
        }

        .simple-toggle input:checked + .simple-toggle-slider:before {
            transform: translateX(22px);
        }
    </style>

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
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 truncate">
                                @if($item->stock > 0)
                                    <span class="text-xs bg-gray-100 dark:bg-gray-600 rounded px-1.5 py-0.5">Stok {{ $item->stock }}</span>
                                @endif
                            </p>
                            <div class="mt-2 flex flex-col gap-2">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $item->formatted_price }}</span>
                                @php
                                    $canAdd = $this->canAddToCart($item);
                                @endphp
                                <button
                                    wire:click="addToCart({{ $item->id }})"
                                    @if(!$canAdd) disabled @endif
                                    class="w-full font-medium rounded-lg text-xs py-1.5 px-2 inline-flex items-center justify-center
                                        @if($canAdd)
                                            text-white bg-primary-600 hover:bg-primary-700 focus:ring-2 focus:ring-primary-500
                                        @else
                                            text-gray-400 bg-gray-300 dark:bg-gray-600 dark:text-gray-500 cursor-not-allowed
                                        @endif"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    @if($canAdd)
                                        Tambah
                                    @else
                                        Stok Habis
                                    @endif
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
                            onclick="refreshBeforeCheckout()"
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

    <!-- Modal Checkout (Menggunakan x-filament::modal) -->
    <x-filament::modal id="checkout-modal" width="md" x-on:open-modal.window="initCheckoutForm()">
        <x-slot name="header">
            <h2 class="font-bold text-lg">Pembayaran</h2>
        </x-slot>

        <div class="space-y-4 px-2">
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

            <div class="flex justify-between items-center py-2 border-t border-b border-gray-200 dark:border-gray-700">
                <span class="text-sm text-gray-600 dark:text-gray-400">Catat sebagai Piutang</span>
                <div class="flex items-center justify-center">
                    <label class="simple-toggle">
                        <input id="is_piutang" type="checkbox" x-on:change="toggleVendorSelect()">
                        <span class="simple-toggle-slider"></span>
                    </label>
                </div>
            </div>

            <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                <span class="text-sm text-gray-600 dark:text-gray-400">Unduh Nota</span>
                <div class="flex items-center justify-center">
                    <label class="simple-toggle">
                        <input id="download_receipt" type="checkbox">
                        <span class="simple-toggle-slider"></span>
                    </label>
                </div>
            </div>

            <div class="py-2 border-b border-gray-200 dark:border-gray-700">
                <button type="button" id="print_thermal_btn" class="w-full flex items-center justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Print struk
                </button>
            </div>

            <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                <span class="text-sm text-gray-600 dark:text-gray-400">Tambah Informasi Pembeli</span>
                <div class="flex items-center justify-center">
                    <label class="simple-toggle">
                        <input id="add_buyer_info" type="checkbox" x-on:change="toggleBuyerInfo()">
                        <span class="simple-toggle-slider"></span>
                    </label>
                </div>
            </div>

            <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                <span class="text-sm text-gray-600 dark:text-gray-400">Atur Waktu Transaksi</span>
                <div class="flex items-center justify-center">
                    <label class="simple-toggle">
                        <input id="custom_time" type="checkbox" x-on:change="toggleCustomTime()">
                        <span class="simple-toggle-slider"></span>
                    </label>
                </div>
            </div>

            <div id="custom_time_container" class="mt-3 hidden">
                <label for="transaction_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Waktu Transaksi
                </label>
                <input
                    type="datetime-local"
                    id="transaction_time"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm"
                >
            </div>

            <div id="buyer_info_container" class="mt-3 hidden">
                <div class="mb-3">
                    <label for="buyer_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Pembeli
                    </label>
                    <input
                        type="text"
                        id="buyer_name"
                        placeholder="Masukkan nama pembeli"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm"
                    >
                </div>

                <div>
                    <label for="cashier_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Kasir
                    </label>
                    <input
                        type="text"
                        id="cashier_name"
                        value="{{ auth()->user()->name ?? '' }}"
                        readonly
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm bg-gray-100 dark:bg-gray-700"
                    >
                </div>
            </div>

            <div id="vendor_select_container" class="mt-3 hidden">
                <label for="vendor_search" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Pilih Vendor
                </label>
                <div class="mt-1 relative">
                    <input
                        type="text"
                        id="vendor_search"
                        placeholder="Cari vendor..."
                        class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm"
                        autocomplete="off"
                        spellcheck="false"
                    >
                    <select
                        id="vendor_id"
                        class="hidden"
                    >
                        <option value="">-- Pilih Vendor --</option>
                        @foreach($this->vendors as $vendor)
                            <option value="{{ $vendor->id }}">{{ $vendor->nama_vendor }}</option>
                        @endforeach
                    </select>
                    <div id="vendor_dropdown" class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 shadow-lg max-h-56 rounded-md py-1 text-base overflow-auto focus:outline-none sm:text-sm hidden">
                        <div class="sticky top-0 cursor-default select-none relative py-2 px-4 text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                            -- Pilih Vendor --
                        </div>
                        @foreach($this->vendors as $vendor)
                            <div
                                data-value="{{ $vendor->id }}"
                                data-text="{{ $vendor->nama_vendor }}"
                                class="vendor-option cursor-pointer select-none relative py-2 px-4 text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                            >
                                {{ $vendor->nama_vendor }}
                            </div>
                        @endforeach
                    </div>
                </div>
                <input type="hidden" id="selected_vendor_id" name="selected_vendor_id" value="">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Total Pembayaran
                </label>
                <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                    {{ 'Rp ' . number_format($this->cartTotal, 0, ',', '.') }}
                </div>
            </div>

            <div class="hidden">
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

        <x-slot name="footer">
            <div class="flex justify-end gap-x-4">
                <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'checkout-modal' })">
                    Batal
                </x-filament::button>
                <x-filament::button color="primary" x-on:click="submitCheckout()">
                    Proses Transaksi
                </x-filament::button>
            </div>
        </x-slot>
    </x-filament::modal>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('checkoutFunctions', () => ({
                initCheckoutForm() {
                    // Reset checkbox values to false
                    document.getElementById('is_piutang').checked = false;
                    document.getElementById('download_receipt').checked = false;
                    document.getElementById('add_buyer_info').checked = false;
                    document.getElementById('custom_time').checked = false;

                    // Setup thermal print button
                    this.setupThermalPrintButton();

                    // Set nilai default datetime-local ke waktu sekarang
                    const now = new Date();
                    // Format tanggal untuk input datetime-local (YYYY-MM-DDThh:mm)
                    const year = now.getFullYear();
                    const month = String(now.getMonth() + 1).padStart(2, '0');
                    const day = String(now.getDate()).padStart(2, '0');
                    const hours = String(now.getHours()).padStart(2, '0');
                    const minutes = String(now.getMinutes()).padStart(2, '0');
                    const formattedDate = `${year}-${month}-${day}T${hours}:${minutes}`;

                    document.getElementById('transaction_time').value = formattedDate;

                    // Hide containers initially
                    document.getElementById('vendor_select_container').classList.add('hidden');
                    document.getElementById('buyer_info_container').classList.add('hidden');
                    document.getElementById('custom_time_container').classList.add('hidden');

                    // Clear vendor search field
                    if (document.getElementById('vendor_search')) {
                        document.getElementById('vendor_search').value = '';
                        document.getElementById('selected_vendor_id').value = '';
                    }

                    // Setup vendor search functionality
                    this.setupVendorSearch();
                },

                setupVendorSearch() {
                    const searchInput = document.getElementById('vendor_search');
                    const dropdown = document.getElementById('vendor_dropdown');
                    const vendorOptions = document.querySelectorAll('.vendor-option');
                    const hiddenInput = document.getElementById('selected_vendor_id');

                    if (!searchInput || !dropdown || !vendorOptions.length) return;

                    let activeIndex = -1;

                    // Function to highlight active option
                    const setActiveOption = (index) => {
                        vendorOptions.forEach(opt => opt.classList.remove('bg-gray-100', 'dark:bg-gray-700'));
                        if (index >= 0 && index < vendorOptions.length) {
                            const visibleOptions = Array.from(vendorOptions).filter(opt => !opt.classList.contains('hidden'));
                            if (visibleOptions[index]) {
                                visibleOptions[index].classList.add('bg-gray-100', 'dark:bg-gray-700');
                                visibleOptions[index].scrollIntoView({ block: 'nearest' });
                                activeIndex = index;
                            }
                        }
                    };

                    // Show dropdown when input is focused
                    searchInput.addEventListener('focus', () => {
                        dropdown.classList.remove('hidden');
                        // Show all options initially
                        vendorOptions.forEach(option => {
                            option.classList.remove('hidden');
                        });
                    });

                    // Keep dropdown open during typing
                    searchInput.addEventListener('click', (e) => {
                        e.stopPropagation();
                        dropdown.classList.remove('hidden');
                    });

                    // Hide dropdown when clicking outside
                    document.addEventListener('click', (e) => {
                        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
                            dropdown.classList.add('hidden');
                            activeIndex = -1;
                        }
                    });

                    // Keyboard navigation
                    searchInput.addEventListener('keydown', (e) => {
                        const visibleOptions = Array.from(vendorOptions).filter(opt => !opt.classList.contains('hidden'));

                        if (e.key === 'ArrowDown') {
                            e.preventDefault();
                            dropdown.classList.remove('hidden');
                            activeIndex = Math.min(activeIndex + 1, visibleOptions.length - 1);
                            setActiveOption(activeIndex);
                        } else if (e.key === 'ArrowUp') {
                            e.preventDefault();
                            dropdown.classList.remove('hidden');
                            activeIndex = Math.max(activeIndex - 1, 0);
                            setActiveOption(activeIndex);
                        } else if (e.key === 'Enter' && activeIndex >= 0) {
                            e.preventDefault();
                            if (visibleOptions[activeIndex]) {
                                const value = visibleOptions[activeIndex].dataset.value;
                                const text = visibleOptions[activeIndex].dataset.text;

                                searchInput.value = text;
                                hiddenInput.value = value;
                                dropdown.classList.add('hidden');
                                activeIndex = -1;
                            }
                        } else if (e.key === 'Escape') {
                            dropdown.classList.add('hidden');
                            activeIndex = -1;
                        }
                    });

                    // Live search functionality
                    searchInput.addEventListener('input', () => {
                        const searchValue = searchInput.value.toLowerCase().trim();
                        let hasVisibleOptions = false;

                        dropdown.classList.remove('hidden'); // Keep dropdown visible during search
                        activeIndex = -1; // Reset active index when searching

                        vendorOptions.forEach(option => {
                            const text = option.innerText.toLowerCase();
                            if (text.includes(searchValue)) {
                                option.classList.remove('hidden');
                                hasVisibleOptions = true;
                            } else {
                                option.classList.add('hidden');
                            }
                        });

                        // Show no results message if needed
                        let noResultsEl = dropdown.querySelector('.no-results');
                        if (!hasVisibleOptions) {
                            if (!noResultsEl) {
                                noResultsEl = document.createElement('div');
                                noResultsEl.className = 'no-results cursor-default select-none relative py-2 px-4 text-gray-500 dark:text-gray-400';
                                noResultsEl.textContent = 'Tidak ada hasil yang cocok';
                                dropdown.appendChild(noResultsEl);
                            }
                            noResultsEl.classList.remove('hidden');
                        } else if (noResultsEl) {
                            noResultsEl.classList.add('hidden');
                        }
                    });

                    // Select vendor when clicking on option
                    vendorOptions.forEach(option => {
                        option.addEventListener('click', () => {
                            const value = option.dataset.value;
                            const text = option.dataset.text;

                            searchInput.value = text;
                            hiddenInput.value = value;
                            dropdown.classList.add('hidden');
                            activeIndex = -1;
                        });
                    });
                },

                toggleVendorSelect() {
                    const isPiutang = document.getElementById('is_piutang').checked;
                    document.getElementById('vendor_select_container').classList.toggle('hidden', !isPiutang);
                },

                toggleBuyerInfo() {
                    const addBuyerInfo = document.getElementById('add_buyer_info').checked;
                    document.getElementById('buyer_info_container').classList.toggle('hidden', !addBuyerInfo);
                },

                toggleCustomTime() {
                    const customTime = document.getElementById('custom_time').checked;
                    document.getElementById('custom_time_container').classList.toggle('hidden', !customTime);
                },

                toggleDownloadReceipt() {
                    // Function ini hanya untuk menjaga konsistensi, tidak ada yang perlu dilakukan
                },

                setupThermalPrintButton() {
                    const printBtn = document.getElementById('print_thermal_btn');
                    if (!printBtn) return;

                    printBtn.addEventListener('click', (e) => {
                        // Mencegah form melakukan submit/refresh
                        e.preventDefault();

                        // Dapatkan data transaksi untuk dikirim ke halaman printer
                        const typeId = document.getElementById('payment_method').value;
                        const isPiutang = document.getElementById('is_piutang').checked;
                        const vendorId = isPiutang ? document.getElementById('selected_vendor_id').value : null;
                        const notes = document.getElementById('notes').value;

                        // Data pembeli jika ada
                        const addBuyerInfo = document.getElementById('add_buyer_info').checked;
                        const buyerName = addBuyerInfo ? document.getElementById('buyer_name').value : null;
                        const cashierName = addBuyerInfo ? document.getElementById('cashier_name').value : null;

                        // Waktu transaksi
                        const customTime = document.getElementById('custom_time').checked;
                        const transactionTime = customTime ? document.getElementById('transaction_time').value : null;

                        // Validasi jika pilihan vendor kosong untuk piutang
                        if (isPiutang && !vendorId) {
                            alert('Mohon pilih vendor terlebih dahulu untuk transaksi piutang');
                            return;
                        }

                        // Validasi jika keranjang kosong
                        if (!@json(count($this->cartItems))) {
                            alert('Keranjang belanja kosong. Silakan tambahkan produk terlebih dahulu.');
                            return;
                        }

                        // Kirim data cart melalui POST request ke server untuk disimpan di session
                        @this.call('saveCartForPrinting', typeId, isPiutang, vendorId, notes, buyerName, cashierName, transactionTime).then(response => {
                            // Setelah data disimpan di session, buka halaman thermal printer di tab baru
                            const params = new URLSearchParams();
                            params.append('session_data', 'true');
                            params.append('type_id', typeId);
                            params.append('is_piutang', isPiutang);
                            if (vendorId) params.append('vendor_id', vendorId);
                            if (notes) params.append('notes', notes);
                            if (buyerName) params.append('buyer_name', buyerName);
                            if (cashierName) params.append('cashier_name', cashierName);
                            if (transactionTime) params.append('transaction_time', transactionTime);
                            if (addBuyerInfo) params.append('add_buyer_info', addBuyerInfo);
                            if (customTime) params.append('custom_time', customTime);

                            // Buka halaman thermal printer di tab baru dan simpan referensi ke jendela baru
                            const newTab = window.open(`{{ route('thermal-print') }}?${params.toString()}`, '_blank');

                            // Jika newTab berhasil dibuka, fokus ke tab baru
                            if (newTab) {
                                newTab.focus();
                            }
                        });
                    });
                },

                submitCheckout() {
                    const typeId = document.getElementById('payment_method').value;
                    const notes = document.getElementById('notes').value;
                    const isPiutang = document.getElementById('is_piutang').checked;

                    // Mengambil nilai vendor_id dari input tersembunyi
                    let vendorId = null;
                    if (isPiutang) {
                        vendorId = document.getElementById('selected_vendor_id').value;
                    }

                    const downloadReceipt = document.getElementById('download_receipt').checked;
                    const addBuyerInfo = document.getElementById('add_buyer_info').checked;
                    const buyerName = addBuyerInfo ? document.getElementById('buyer_name').value : null;
                    const cashierName = addBuyerInfo ? document.getElementById('cashier_name').value : null;
                    const customTime = document.getElementById('custom_time').checked;
                    const transactionTime = customTime ? document.getElementById('transaction_time').value : null;

                    // Validasi jika pilihan vendor kosong
                    if (isPiutang && !vendorId) {
                        alert('Silakan pilih vendor terlebih dahulu');
                        return;
                    }

                    // Panggil method Livewire untuk proses checkout
                    @this.checkout({
                        type_id: typeId,
                        notes: notes,
                        is_receivable: isPiutang,
                        vendor_id: vendorId,
                        download_receipt: downloadReceipt,
                        buyer_name: buyerName,
                        cashier_name: cashierName,
                        transaction_time: transactionTime
                    });
                }
            }));
        });

        // Menangani event 'close-checkout-modal'
        document.addEventListener('DOMContentLoaded', function() {
            window.addEventListener('close-checkout-modal', function() {
                Livewire.dispatch('close-modal', { id: 'checkout-modal' });
            });
        });

        // Fungsi untuk refresh halaman baru kemudian buka modal checkout
        function refreshBeforeCheckout() {
            // Jika halaman belum di-refresh, refresh dulu lalu tandai untuk buka modal
            if (!sessionStorage.getItem('freshCheckout')) {
                console.log('Refreshing for checkout...');
                sessionStorage.setItem('freshCheckout', 'true');
                window.location.reload();
                return;
            }

            // Jika sudah di-refresh, buka modal dan hapus flag
            console.log('Opening checkout modal after fresh page load');
            sessionStorage.removeItem('freshCheckout');
            Livewire.dispatch('open-modal', { id: 'checkout-modal' });
        }

        // Cek apakah perlu buka modal saat halaman load
        document.addEventListener('DOMContentLoaded', function() {
            if (sessionStorage.getItem('freshCheckout') === 'true') {
                // Tunggu sebentar agar halaman selesai load dengan sempurna
                setTimeout(function() {
                    console.log('Auto-opening checkout modal');
                    refreshBeforeCheckout();
                }, 500);
            }
        });
    </script>
</x-filament::page>
