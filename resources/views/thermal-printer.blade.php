<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Printer Thermal Bluetooth - {{ $tenant_name ?? 'TOKO CONTOH' }}</title>
    <!-- Ganti dengan versi lokal dari library -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
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

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            z-index: 100;
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 0.3s, transform 0.3s;
        }

        .notification.success {
            background-color: #d1fae5;
            border-left: 4px solid #10b981;
            color: #065f46;
        }

        .notification.error {
            background-color: #fee2e2;
            border-left: 4px solid #ef4444;
            color: #b91c1c;
        }

        .notification.show {
            opacity: 1;
            transform: translateY(0);
        }

        /* Gaya untuk log console */
        .console-log {
            background-color: #1e293b;
            color: #e2e8f0;
            font-family: monospace;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-top: 1rem;
            height: 150px;
            overflow-y: auto;
        }

        .console-log p {
            margin: 0;
            padding: 0;
            line-height: 1.5;
        }

        .console-log .success {
            color: #4ade80;
        }

        .console-log .error {
            color: #f87171;
        }

        .console-log .info {
            color: #60a5fa;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto py-12 px-4">
        <h1 class="text-3xl font-bold text-center mb-8">Print Struk Thermal Bluetooth</h1>

        <!-- Card untuk setting printer -->
        <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden mb-8">
            <div class="p-6">
                <h2 class="font-bold text-lg mb-4">Pengaturan Printer</h2>

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span>Pilih Ukuran Kertas</span>
                        <select id="paper_size" class="border rounded py-1 px-2">
                            <option value="58mm" selected>58mm</option>
                            <option value="80mm">80mm</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-between">
                        <span>Nama Toko</span>
                        <input type="text" id="store_name" value="{{ $tenant_name ?? 'TOKO CONTOH' }}" class="border rounded py-1 px-2"readonly>
                    </div>

                    <div class="flex items-center justify-between">
                        <span>Alamat</span>
                        <input type="text" id="store_address" value="{{ $tenant_address ?? 'Jl. Contoh No. 123, Jakarta' }}" class="border rounded py-1 px-2"readonly>
                    </div>

                    <div class="flex items-center justify-between">
                        <span>No Telepon</span>
                        <input type="text" id="store_phone" value="{{ $tenant_phone ?? '081234567890' }}" class="border rounded py-1 px-2"readonly>
                    </div>

                    <div class="flex items-center justify-between">
                        <span>Nama Kasir</span>
                        <input type="text" id="cashier_name" value="{{ $cashier_name ?? auth()->user()->name ?? 'Admin' }}" class="border rounded py-1 px-2" readonly>
                    </div>
                </div>
            </div>
        </div>

        <!-- Konten Struk -->
        <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden mb-8">
            <div class="p-6">
                <h2 class="font-bold text-lg mb-4">Informasi Transaksi</h2>

                <div class="mb-4">
                    <p><strong>Tanggal:</strong> {{ $transaction_date }}</p>
                    <p><strong>Metode Pembayaran:</strong> {{ $payment_method ?? 'Tunai' }}</p>
                    @if(isset($buyer_name) && !empty($buyer_name))
                    <p><strong>Pembeli:</strong> {{ $buyer_name }}</p>
                    @endif
                    @if(session('print_is_receivable'))
                    <p><strong>Jenis Transaksi:</strong> Piutang</p>
                    @if(session('print_vendor_name'))
                    <p><strong>Vendor:</strong> {{ session('print_vendor_name') }}</p>
                    @endif
                    @endif
                    @if(session('print_notes'))
                    <p><strong>Catatan:</strong> {{ session('print_notes') }}</p>
                    @endif
                </div>

                <table class="w-full mb-4">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="text-left p-2">Item</th>
                            <th class="text-right p-2">Qty</th>
                            <th class="text-right p-2">Harga</th>
                            <th class="text-right p-2">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                        <tr class="border-t">
                            <td class="p-2">{{ $item['name'] }}</td>
                            <td class="text-right p-2">{{ $item['quantity'] }}</td>
                            <td class="text-right p-2">{{ number_format($item['price'], 0, ',', '.') }}</td>
                            <td class="text-right p-2">{{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t">
                            <td colspan="3" class="p-2 text-right font-bold">Total</td>
                            <td class="p-2 text-right font-bold">{{ number_format($total, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Tombol Print -->
        <div class="max-w-md mx-auto">
            <button id="print-btn" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-3 px-4 rounded-lg transition-colors flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Print via Bluetooth
            </button>

            <div class="mt-4">
                <a href="{{ url()->previous() }}" class="inline-block w-full bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-4 rounded-lg transition-colors text-center">
                    Kembali ke Kasir
                </a>
            </div>
        </div>

        <!-- Console Log -->
        <div class="max-w-md mx-auto mt-6">
            <h3 class="font-bold text-md mb-2">Log Printer:</h3>
            <div id="console-log" class="console-log"></div>
        </div>
    </div>

    <div id="notification" class="notification">
        <span id="notification-message"></span>
    </div>

    <script>
        // Implementasi sederhana dari PrintPlugin
        class ThermalPrinter {
            constructor(paperSize) {
                this.paperSize = paperSize || "58mm";
                this.device = null;
                this.encoder = new TextEncoder();
                this.decoder = new TextDecoder('utf-8');
                this.connected = false;
                this.logElement = document.getElementById('console-log');
            }

            async connect() {
                try {
                    // Minta akses perangkat Bluetooth
                    this.device = await navigator.bluetooth.requestDevice({
                        filters: [
                            { services: ['000018f0-0000-1000-8000-00805f9b34fb'] },
                            { namePrefix: 'POS' },
                            { namePrefix: 'Printer' },
                            { namePrefix: 'BP' }
                        ],
                        optionalServices: ['000018f0-0000-1000-8000-00805f9b34fb']
                    });

                    this.log('info', 'Perangkat dipilih: ' + this.device.name);

                    // Hubungkan ke perangkat
                    const server = await this.device.gatt.connect();
                    this.log('info', 'Terhubung ke GATT server');

                    // Dapatkan service
                    const service = await server.getPrimaryService('000018f0-0000-1000-8000-00805f9b34fb');
                    this.log('info', 'Service ditemukan');

                    // Dapatkan karakteristik untuk menulis data
                    this.characteristic = await service.getCharacteristic('00002af1-0000-1000-8000-00805f9b34fb');
                    this.log('success', 'Siap untuk mencetak!');

                    this.connected = true;
                    return true;
                } catch (error) {
                    this.log('error', 'Gagal terhubung: ' + error.message);
                    this.connected = false;
                    return false;
                }
            }

            async print(data) {
                if (!this.connected) {
                    const connected = await this.connect();
                    if (!connected) return false;
                }

                try {
                    // Convert data to ArrayBuffer
                    const buffer = this.encoder.encode(data);

                    // Sending data in chunks
                    const CHUNK_SIZE = 20;
                    for (let i = 0; i < buffer.length; i += CHUNK_SIZE) {
                        const chunk = buffer.slice(i, i + CHUNK_SIZE);
                        await this.characteristic.writeValue(chunk);
                    }

                    this.log('success', 'Data berhasil dikirim ke printer!');
                    return true;
                } catch (error) {
                    this.log('error', 'Gagal mencetak: ' + error.message);
                    return false;
                }
            }

            log(type, message) {
                console.log(`[${type}] ${message}`);
                if (this.logElement) {
                    const p = document.createElement('p');
                    p.className = type;
                    p.textContent = message;
                    this.logElement.appendChild(p);
                    this.logElement.scrollTop = this.logElement.scrollHeight;
                }

                // Tampilkan notifikasi
                showNotification(message, type);
            }

            generateReceiptText() {
                const paperSize = document.getElementById('paper_size').value;
                const storeName = document.getElementById('store_name').value;
                const storeAddress = document.getElementById('store_address').value;
                const storePhone = document.getElementById('store_phone').value;
                const cashierName = document.getElementById('cashier_name').value;

                // Menentukan lebar struk berdasarkan ukuran kertas
                const width = paperSize === "58mm" ? 32 : 48;

                // Fungsi bantuan untuk mengatur rata tengah teks
                const center = (text) => {
                    const padding = Math.max(0, width - text.length) / 2;
                    return ' '.repeat(Math.floor(padding)) + text + ' '.repeat(Math.ceil(padding));
                };

                // Fungsi bantuan untuk rata kanan
                const right = (text, length) => {
                    const padding = Math.max(0, length - text.length);
                    return ' '.repeat(padding) + text;
                };

                // Fungsi bantuan untuk membuat baris dengan teks kiri dan kanan
                const leftRight = (left, right, length = width) => {
                    const padding = Math.max(0, length - left.length - right.length);
                    return left + ' '.repeat(padding) + right;
                };

                // Fungsi untuk membuat garis
                const line = () => '-'.repeat(width);

                // Mulai menyusun teks struk
                let receipt = '\n';

                // Header
                receipt += center(storeName) + '\n';
                receipt += center(storeAddress) + '\n';
                if (storePhone) receipt += center(storePhone) + '\n';
                receipt += line() + '\n';

                // Info transaksi
                receipt += leftRight('Tanggal:', '{{ $transaction_date }}') + '\n';
                receipt += leftRight('Kasir:', cashierName) + '\n';
                receipt += leftRight('Pembayaran:', '{{ $payment_method ?? "Tunai" }}') + '\n';
                @if(isset($buyer_name) && !empty($buyer_name))
                receipt += leftRight('Pembeli:', '{{ $buyer_name }}') + '\n';
                @endif
                receipt += line() + '\n';

                // Item-item
                @foreach($items as $item)
                receipt += '{{ $item["name"] }}' + '\n';
                const subtotal{{ $loop->index }} = {{ $item["price"] * $item["quantity"] }};
                const qtyPrice{{ $loop->index }} = '{{ $item["quantity"] }} x ' + formatMoney({{ $item["price"] }});
                receipt += leftRight(qtyPrice{{ $loop->index }}, formatMoney(subtotal{{ $loop->index }})) + '\n';
                @endforeach

                receipt += line() + '\n';
                receipt += leftRight('TOTAL:', formatMoney({{ $total }})) + '\n';
                receipt += line() + '\n';

                // Footer
                receipt += center('Terima Kasih') + '\n';
                receipt += center('Atas Kunjungan Anda') + '\n\n\n';

                return receipt;
            }
        }

        function formatMoney(amount) {
            return 'Rp ' + amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        function showNotification(message, type) {
            const notification = document.getElementById('notification');
            const notificationMessage = document.getElementById('notification-message');

            notification.className = 'notification ' + type;
            notificationMessage.textContent = message;
            notification.classList.add('show');

            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const printer = new ThermalPrinter(document.getElementById('paper_size').value);
            const printBtn = document.getElementById('print-btn');
            const paperSizeSelect = document.getElementById('paper_size');

            // Event listener untuk tombol print
            printBtn.addEventListener('click', async function() {
                try {
                    const receiptText = printer.generateReceiptText();
                    const connected = await printer.connect();

                    if (connected) {
                        await printer.print(receiptText);
                    }
                } catch (error) {
                    printer.log('error', 'Error: ' + error.message);
                }
            });

            // Update paperSize saat pilihan berubah
            paperSizeSelect.addEventListener('change', function() {
                printer.paperSize = this.value;
            });
        });
    </script>
</body>
</html>
