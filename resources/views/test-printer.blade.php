<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Printer Thermal Bluetooth</title>
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
        <h1 class="text-3xl font-bold text-center mb-8">Test Printer Thermal Bluetooth</h1>

        <!-- Card untuk setting printer -->
        <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden mb-8">
            <div class="p-6">
                <h2 class="font-bold text-lg mb-4">Pengaturan Printer</h2>

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span>Pilih Ukuran Kertas</span>
                        <select id="paper_size" class="border rounded py-1 px-2">
                            <option value="58mm">58mm</option>
                            <option value="80mm">80mm</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-between">
                        <span>Nama Toko</span>
                        <input type="text" id="store_name" value="TOKO CONTOH" class="border rounded py-1 px-2">
                    </div>

                    <div class="flex items-center justify-between">
                        <span>Alamat</span>
                        <input type="text" id="store_address" value="Jl. Contoh No. 123, Jakarta" class="border rounded py-1 px-2">
                    </div>

                    <div class="flex items-center justify-between">
                        <span>No Telepon</span>
                        <input type="text" id="store_phone" value="081234567890" class="border rounded py-1 px-2">
                    </div>

                    <div class="flex items-center justify-between">
                        <span>Nama Kasir</span>
                        <input type="text" id="cashier_name" value="{{ auth()->user()->name ?? 'Admin' }}" class="border rounded py-1 px-2">
                    </div>
                </div>
            </div>
        </div>

        <!-- Konten Struk -->
        <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden mb-8">
            <div class="p-6">
                <h2 class="font-bold text-lg mb-4">Informasi Transaksi</h2>

                <div class="mb-4">
                    <p><strong>No Transaksi:</strong> {{ $transaction_id }}</p>
                    <p><strong>Tanggal:</strong> {{ $transaction_date }}</p>
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
                Print ke Thermal Bluetooth
            </button>
        </div>

        <!-- Console Log untuk debugging -->
        <div class="max-w-md mx-auto mt-4">
            <div class="console-log" id="console-log">
                <p class="info">Console log akan muncul di sini...</p>
            </div>
        </div>
    </div>

    <!-- Notification -->
    <div id="notification" class="notification">
        <span id="notification-message"></span>
    </div>

    <script>
        // Implementasi sederhana dari PrintPlugin
        class ThermalPrinter {
            constructor(paperSize) {
                this.paperSize = paperSize || "58mm";
                this.device = null;
                this.server = null;
                this.service = null;
                this.characteristic = null;
                this.encoder = new TextEncoder();
            }

            log(message, type = 'info') {
                const consoleLog = document.getElementById('console-log');
                const p = document.createElement('p');
                p.classList.add(type);
                p.textContent = message;
                consoleLog.appendChild(p);
                consoleLog.scrollTop = consoleLog.scrollHeight;
                console.log(`[${type}] ${message}`);
            }

            async connect() {
                try {
                    this.log('Meminta izin Bluetooth...', 'info');

                    // Request Bluetooth device
                    this.device = await navigator.bluetooth.requestDevice({
                        filters: [{
                            // Filter untuk printer thermal Bluetooth
                            services: ['000018f0-0000-1000-8000-00805f9b34fb'] // Ganti dengan UUID printer Anda jika diketahui
                        }],
                        optionalServices: ['000018f0-0000-1000-8000-00805f9b34fb']
                    });

                    this.log(`Perangkat terpilih: ${this.device.name}`, 'success');

                    // Connect to GATT Server
                    this.log('Menghubungkan ke GATT server...', 'info');
                    this.server = await this.device.gatt.connect();

                    // Get Printer Service
                    this.log('Mendapatkan layanan printer...', 'info');
                    this.service = await this.server.getPrimaryService('000018f0-0000-1000-8000-00805f9b34fb');

                    // Get Write Characteristic
                    this.log('Mendapatkan karakteristik printer...', 'info');
                    this.characteristic = await this.service.getCharacteristic('00002af1-0000-1000-8000-00805f9b34fb');

                    this.log('Berhasil terhubung ke printer!', 'success');
                    return true;
                } catch (error) {
                    this.log(`Error koneksi: ${error.message}`, 'error');
                    throw error;
                }
            }

            async print(text) {
                try {
                    if (!this.characteristic) {
                        throw new Error('Printer belum terhubung');
                    }

                    const data = this.encoder.encode(text);
                    await this.characteristic.writeValue(data);
                    this.log(`Berhasil mencetak: ${text.substring(0, 20)}...`, 'success');
                    return true;
                } catch (error) {
                    this.log(`Error mencetak: ${error.message}`, 'error');
                    throw error;
                }
            }

            async printReceipt(data) {
                try {
                    await this.connect();

                    // Header
                    await this.print(`\n\n${data.storeName}\n`);
                    await this.print(`${data.storeAddress}\n`);
                    await this.print(`${data.storePhone}\n\n`);
                    await this.print(`No.Transaksi: ${data.transactionId}\n`);
                    await this.print(`Kasir: ${data.cashierName}\n`);
                    await this.print(`${data.transactionDate}\n\n`);

                    // Garis pembatas
                    await this.print(`--------------------------------\n`);

                    // Items
                    for (const item of data.items) {
                        await this.print(`${item.name}\n`);
                        await this.print(`${item.quantity} x ${item.price}    ${item.total}\n`);
                    }

                    // Garis pembatas
                    await this.print(`--------------------------------\n`);

                    // Total
                    await this.print(`Total: ${data.total}\n\n`);
                    await this.print(`Metode: ${data.paymentMethod}\n\n`);

                    // Footer
                    await this.print(`Terima kasih atas kunjungan Anda\n\n\n\n`);

                    this.log('Semua data berhasil dicetak!', 'success');
                    return true;
                } catch (error) {
                    this.log(`Error cetak struk: ${error.message}`, 'error');
                    throw error;
                }
            }
        }

        document.getElementById('print-btn').addEventListener('click', function() {
            startPrinting();
        });

        function showNotification(message, type) {
            const notification = document.getElementById('notification');
            const notificationMessage = document.getElementById('notification-message');

            notification.className = 'notification ' + type;
            notificationMessage.textContent = message;

            // Show notification
            setTimeout(() => {
                notification.classList.add('show');
            }, 100);

            // Hide notification after 5 seconds
            setTimeout(() => {
                notification.classList.remove('show');
            }, 5000);
        }

        async function startPrinting() {
            const paperSize = document.getElementById('paper_size').value;
            const storeName = document.getElementById('store_name').value;
            const storeAddress = document.getElementById('store_address').value;
            const storePhone = document.getElementById('store_phone').value;
            const cashierName = document.getElementById('cashier_name').value;

            // Tampilkan notifikasi connecting
            showNotification("Menghubungkan ke printer Bluetooth...", "success");

            try {
                const printer = new ThermalPrinter(paperSize);

                // Siapkan data untuk dicetak
                const printData = {
                    storeName: storeName,
                    storeAddress: storeAddress,
                    storePhone: storePhone,
                    cashierName: cashierName,
                    transactionId: "{{ $transaction_id }}",
                    transactionDate: "{{ $transaction_date }}",
                    paymentMethod: "Tunai",
                    items: [
                        @foreach($items as $item)
                        {
                            name: "{{ $item['name'] }}",
                            quantity: "{{ $item['quantity'] }}",
                            price: "{{ number_format($item['price'], 0, ',', '.') }}",
                            total: "{{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}"
                        },
                        @endforeach
                    ],
                    total: "{{ number_format($total, 0, ',', '.') }}"
                };

                // Cetak struk
                await printer.printReceipt(printData);
                showNotification("Nota berhasil dicetak ke printer thermal!", "success");
            } catch (error) {
                console.error("Error printing:", error);
                showNotification("Gagal mencetak: " + error.message, "error");
            }
        }

        // Tambahkan custom log untuk console
        const originalConsoleLog = console.log;
        const originalConsoleError = console.error;

        console.log = function() {
            const consoleLog = document.getElementById('console-log');
            const p = document.createElement('p');
            p.classList.add('info');
            p.textContent = Array.from(arguments).join(' ');
            consoleLog.appendChild(p);
            consoleLog.scrollTop = consoleLog.scrollHeight;
            originalConsoleLog.apply(console, arguments);
        };

        console.error = function() {
            const consoleLog = document.getElementById('console-log');
            const p = document.createElement('p');
            p.classList.add('error');
            p.textContent = Array.from(arguments).join(' ');
            consoleLog.appendChild(p);
            consoleLog.scrollTop = consoleLog.scrollHeight;
            originalConsoleError.apply(console, arguments);
        };
    </script>
</body>
</html>
