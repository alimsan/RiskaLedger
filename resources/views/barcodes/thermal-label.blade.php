<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pencetak Label Thermal - NIIMBOT B1</title>
    <!-- Tailwind CSS CDN untuk styling yang rapi dan konsisten -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- NiimBlueLib UMD Bundle lokal -->
    <script src="{{ asset('js/niimbluelib.min.js') }}"></script>
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            #printable-area, #printable-area * {
                visibility: visible;
            }
            #printable-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .print-page-break {
                page-break-after: always;
                break-after: page;
            }
        }
        .canvas-container {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid #e5e7eb;
            background: #ffffff;
            border-radius: 6px;
            display: inline-block;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 antialiased min-h-screen flex flex-col">

    <!-- Header Navigasi -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-30 shadow-sm no-print">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center space-x-3">
                <a href="{{ route('filament.admin.resources.items.index') }}" 
                   class="inline-flex items-center text-gray-500 hover:text-gray-800 p-2 rounded-lg hover:bg-gray-100 transition"
                   title="Kembali ke Katalog Produk">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span>Pencetak Label Thermal</span>
                        <span class="text-xs bg-amber-100 text-amber-800 font-semibold px-2 py-0.5 rounded-full border border-amber-300">
                            NIIMBOT B1 / BLE
                        </span>
                    </h1>
                    <p class="text-xs text-gray-500">{{ $storeName }} • Total {{ count($itemsData) }} Produk Dipilih</p>
                </div>
            </div>

            <!-- Status Bluetooth & Tombol Aksi Utama -->
            <div class="flex items-center flex-wrap gap-2.5">
                <!-- Status Badge -->
                <div id="connection-status-badge" class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                    <span id="status-indicator-dot" class="w-2.5 h-2.5 rounded-full bg-gray-400 mr-2"></span>
                    <span id="status-text">Belum Terhubung</span>
                    <span id="battery-indicator" class="ml-2 hidden text-emerald-700 font-bold">🔋 --%</span>
                </div>

                <!-- Tombol Connect Bluetooth & USB -->
                <div id="connect-buttons-group" class="flex items-center gap-2">
                    <button onclick="connectPrinter('bluetooth')" class="inline-flex items-center px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white text-xs font-semibold rounded-lg shadow-sm transition" title="Hubungkan secara nirkabel via Bluetooth">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                        </svg>
                        Hubungkan Bluetooth
                    </button>
                    <button onclick="connectPrinter('serial')" class="inline-flex items-center px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold rounded-lg shadow-sm transition" title="Hubungkan via kabel USB Type-C (Paling stabil untuk Windows Ghost Spectre)">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                        </svg>
                        Hubungkan USB (Kabel)
                    </button>
                </div>

                <button id="btn-disconnect" onclick="disconnectPrinter()" class="hidden inline-flex items-center px-3 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-semibold rounded-lg transition">
                    Putuskan
                </button>

                <!-- Tombol Cetak Semua -->
                <button id="btn-print-all" onclick="printAllLabels()" class="inline-flex items-center px-5 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-bold rounded-lg shadow-sm transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                    Cetak Semua Label (<span id="total-copies-count">0</span> Lembar)
                </button>

                <!-- Tombol Fallback Cetak Browser -->
                <button onclick="window.print()" class="inline-flex items-center px-3 py-2 bg-white hover:bg-gray-100 text-gray-700 border border-gray-300 text-xs font-semibold rounded-lg transition" title="Cetak via driver printer standar Windows/OS">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                    Dialog Browser (Ctrl+P)
                </button>
            </div>
        </div>
    </header>

    <!-- Banner Info Windows 11 Ghost Spectre & Bluetooth Guide -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 no-print space-y-2">
        <div class="p-3.5 bg-blue-50 border border-blue-200 rounded-xl text-blue-900 text-xs flex items-start gap-2.5">
            <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <strong class="font-semibold text-blue-950">💡 Panduan Windows 11 (Ghost Spectre / Status "Driver is unavailable"):</strong>
                Status <em>"Driver is unavailable"</em> pada menu Settings Windows adalah <strong>hal normal</strong> karena Windows mencari driver printer kertas biasa. Printer <strong>NIIMBOT B1</strong> adalah printer BLE pintar yang <strong>TIDAK membutuhkan driver Windows</strong>! Anda cukup:
                <span class="font-medium">Klik tombol <strong>"Hubungkan Bluetooth"</strong> di atas lalu pilih <strong>B1-H819123546</strong> pada popup browser Chrome/Edge.</span>
                Atau jika Bluetooth Windows bermasalah, colokkan kabel USB Type-C lalu klik <strong>"Hubungkan USB (Kabel)"</strong>.
            </div>
        </div>

        <div id="bluetooth-warning" class="hidden p-3 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 text-xs flex items-start gap-2.5">
            <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
            <div>
                Gunakan Google Chrome atau Microsoft Edge di PC/Laptop untuk fitur Web Bluetooth / Web Serial.
            </div>
        </div>
    </div>

    <!-- Modal Progress Cetak -->
    <div id="print-progress-modal" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm hidden flex items-center justify-center p-4 no-print">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-100 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center animate-pulse">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
            </div>
            <h3 class="text-base font-bold text-gray-900 mb-1" id="progress-title">Sedang Mencetak Label...</h3>
            <p class="text-xs text-gray-500 mb-4" id="progress-subtitle">Harap jangan menutup halaman atau memutuskan Bluetooth</p>
            
            <!-- Bar -->
            <div class="w-full bg-gray-200 rounded-full h-3 mb-2 overflow-hidden">
                <div id="progress-bar-fill" class="bg-emerald-600 h-3 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
            <div class="flex justify-between text-xs text-gray-500 font-semibold mb-5">
                <span id="progress-count-text">0 / 0 Label</span>
                <span id="progress-percentage-text">0%</span>
            </div>

            <button onclick="abortPrinting()" class="text-xs text-red-600 hover:text-red-700 font-medium py-1 px-3 border border-red-200 rounded-lg hover:bg-red-50 transition">
                Hentikan Antrean
            </button>
        </div>
    </div>

    <!-- Konten Utama -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex-1 w-full">

        <!-- Bar Pengaturan Cepat -->
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 mb-6 flex flex-wrap items-center justify-between gap-4 no-print">
            <div class="flex flex-wrap items-center gap-4 text-xs">
                <!-- Ukuran Label -->
                <div class="flex items-center space-x-2">
                    <label for="setting-label-size" class="font-semibold text-gray-700">Ukuran Label:</label>
                    <select id="setting-label-size" onchange="applyGlobalSettings()" class="bg-gray-50 border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs text-gray-800 font-medium focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="50x30" {{ $labelSize === '50x30' ? 'selected' : '' }}>50 x 30 mm (Standar B1)</option>
                        <option value="40x30" {{ $labelSize === '40x30' ? 'selected' : '' }}>40 x 30 mm</option>
                        <option value="30x20" {{ $labelSize === '30x20' ? 'selected' : '' }}>30 x 20 mm</option>
                    </select>
                </div>

                <!-- Kepekatan Cetak (Density) -->
                <div class="flex items-center space-x-2">
                    <label for="setting-density" class="font-semibold text-gray-700">Kepekatan (Density):</label>
                    <select id="setting-density" class="bg-gray-50 border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs text-gray-800 font-medium">
                        <option value="1" {{ $density == 1 ? 'selected' : '' }}>1 - Tipis</option>
                        <option value="2" {{ $density == 2 ? 'selected' : '' }}>2 - Sedang</option>
                        <option value="3" {{ $density == 3 || $density == 2 ? 'selected' : '' }}>3 - Normal (Standar B1)</option>
                        <option value="4" {{ $density == 4 ? 'selected' : '' }}>4 - Pekat</option>
                        <option value="5" {{ $density == 5 ? 'selected' : '' }}>5 - Sangat Pekat</option>
                    </select>
                </div>

                <!-- Cantumkan Harga -->
                <label class="inline-flex items-center cursor-pointer select-none">
                    <input type="checkbox" id="setting-include-price" onchange="applyGlobalSettings()" class="sr-only peer" {{ $includePrice ? 'checked' : '' }}>
                    <div class="w-8 h-4 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3.5 after:transition-all peer-checked:bg-indigo-600 relative"></div>
                    <span class="ml-2 font-semibold text-gray-700">Cantumkan Harga</span>
                </label>
            </div>

            <!-- Total Ringkasan -->
            <div class="text-xs text-gray-500 font-medium">
                Pilih / sesuaikan jumlah label pada tiap kartu sebelum mencetak.
            </div>
        </div>

        <!-- Grid Kartu Label Produk -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5" id="label-cards-container">
            <!-- Diisi secara dinamis oleh JavaScript -->
        </div>

        <!-- Hidden container untuk cetak browser (Ctrl+P) -->
        <div id="printable-area" class="hidden"></div>
    </main>

    <!-- Footer Status Bar -->
    <footer class="bg-white border-t border-gray-200 py-3 no-print mt-auto">
        <div class="max-w-7xl mx-auto px-4 text-xs text-gray-500 flex flex-wrap justify-between items-center gap-2">
            <div>
                Sistem Cetak Label Thermal Barcode • Protokol B1 BLE (203 DPI)
            </div>
            <div id="printer-log-summary" class="text-gray-400 italic">
                Siap mencetak
            </div>
        </div>
    </footer>

    <!-- Payload Data dari Backend Laravel -->
    <script>
        const rawProductsData = @json($itemsData);
        let currentLabelSize = "{{ $labelSize }}";
        let currentIncludePrice = {{ $includePrice ? 'true' : 'false' }};
        
        // Objek ukuran label (lebar x tinggi dots pada 203 DPI)
        // Catatan: NIIMBOT B1 memiliki printhead 48mm (384 dots). Kelipatan 8 dots per baris.
        const labelDimensions = {
            '50x30': { width: 384, height: 240, mmW: 50, mmH: 30 },
            '40x30': { width: 320, height: 240, mmW: 40, mmH: 30 },
            '30x20': { width: 240, height: 160, mmW: 30, mmH: 20 }
        };

        let niimbotClient = null;
        let isConnected = false;
        let isPrinting = false;
        let shouldAbort = false;

        // Inisialisasi Halaman
        document.addEventListener('DOMContentLoaded', () => {
            // Cek dukungan Web Bluetooth
            if (!navigator.bluetooth) {
                document.getElementById('bluetooth-warning').classList.remove('hidden');
            }
            renderAllLabelCards();
            updateTotalCopiesCounter();
        });

        // Render seluruh kartu produk dan canvas
        function renderAllLabelCards() {
            const container = document.getElementById('label-cards-container');
            container.innerHTML = '';

            rawProductsData.forEach((item, index) => {
                const card = document.createElement('div');
                card.className = 'bg-white rounded-xl p-4 shadow-sm border border-gray-200 flex flex-col justify-between hover:shadow-md transition duration-150';
                card.id = `card-item-${item.id}`;

                card.innerHTML = `
                    <div>
                        <!-- Header Kartu -->
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-bold text-gray-900 truncate" title="${item.name}">${item.name}</h4>
                                <p class="text-xs text-gray-500">${item.category} • Stok: <span class="font-semibold text-gray-700">${item.stock}</span></p>
                            </div>
                            <input type="checkbox" id="check-item-${item.id}" checked onchange="updateTotalCopiesCounter()" 
                                   class="w-4 h-4 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500 cursor-pointer mt-1" 
                                   title="Sertakan dalam cetak semua">
                        </div>

                        <!-- Canvas Preview Label -->
                        <div class="flex justify-center items-center py-2 bg-gray-50 rounded-lg mb-3 border border-dashed border-gray-200 overflow-hidden">
                            <div class="canvas-container">
                                <canvas id="canvas-${item.id}" class="max-w-full h-auto block"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Kartu: Pengaturan Copies & Cetak Individual -->
                    <div class="pt-2 border-t border-gray-100 flex items-center justify-between gap-2">
                        <div class="flex items-center space-x-1.5">
                            <label class="text-xs font-medium text-gray-600">Jumlah:</label>
                            <input type="number" id="copies-input-${item.id}" value="${item.copies}" min="1" max="100" 
                                   onchange="updateItemCopies(${item.id}, this.value)" 
                                   class="w-16 px-2 py-1 text-xs font-semibold text-center border border-gray-300 rounded-lg focus:ring-1 focus:ring-indigo-500">
                            <span class="text-xs text-gray-400">lbr</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span id="badge-status-${item.id}" class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">
                                Siap
                            </span>
                            <button onclick="printSingleLabel(${item.id})" class="px-2.5 py-1 text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition" title="Cetak label produk ini saja">
                                Cetak 1 Ini
                            </button>
                        </div>
                    </div>
                `;

                container.appendChild(card);

                // Render visual label di Canvas
                renderLabelToCanvas(item);
            });

            updateTotalCopiesCounter();
        }

        // Gambar isi stiker ke HTML5 Canvas pixel-perfect
        function renderLabelToCanvas(item) {
            const canvas = document.getElementById(`canvas-${item.id}`);
            if (!canvas) return;

            const dims = labelDimensions[currentLabelSize] || labelDimensions['50x30'];
            canvas.width = dims.width;
            canvas.height = dims.height;

            const ctx = canvas.getContext('2d');
            ctx.imageSmoothingEnabled = false;

            // 1. Background putih murni
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            // 2. Teks Nama Produk (Baris atas)
            ctx.fillStyle = '#000000';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'top';

            let productName = item.name.trim();
            // Ukuran font adaptif berdasarkan panjang nama produk
            let nameFontSize = dims.width <= 240 ? 18 : (productName.length > 20 ? 20 : 24);
            ctx.font = `bold ${nameFontSize}px sans-serif`;

            // Truncate jika nama terlalu panjang
            let maxTextWidth = canvas.width - 24;
            if (ctx.measureText(productName).width > maxTextWidth) {
                while (ctx.measureText(productName + '...').width > maxTextWidth && productName.length > 0) {
                    productName = productName.substring(0, productName.length - 1);
                }
                productName += '...';
            }
            ctx.fillText(productName, canvas.width / 2, 10);

            // 3. Teks Harga Produk (Baris kedua, opsional)
            let currentY = 10 + nameFontSize + 4;
            if (currentIncludePrice) {
                let priceFontSize = dims.width <= 240 ? 18 : 22;
                ctx.font = `bold ${priceFontSize}px sans-serif`;
                ctx.fillText(item.formatted_price, canvas.width / 2, currentY);
                currentY += priceFontSize + 6;
            } else {
                currentY += 4;
            }

            // 4. Barcode Code 128 Image
            const img = new Image();
            img.onload = () => {
                // Hitung area tersisa untuk gambar barcode dan nomor barcode
                const remainingHeight = canvas.height - currentY - 26;
                const barcodeHeight = Math.max(36, remainingHeight);
                const barcodeWidth = Math.min(canvas.width - 32, 320);
                const barcodeX = (canvas.width - barcodeWidth) / 2;

                ctx.drawImage(img, barcodeX, currentY, barcodeWidth, barcodeHeight);

                // 5. Teks Angka Barcode di Bawah Garis Barcode
                const textY = currentY + barcodeHeight + 4;
                const codeFontSize = dims.width <= 240 ? 14 : 16;
                ctx.font = `bold ${codeFontSize}px ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace`;
                ctx.letterSpacing = '1.5px';
                ctx.fillText(item.barcode, canvas.width / 2, textY);
            };
            img.src = item.barcode_base64;
        }

        // Terapkan pengaturan global (ukuran label & opsi harga)
        function applyGlobalSettings() {
            currentLabelSize = document.getElementById('setting-label-size').value;
            currentIncludePrice = document.getElementById('setting-include-price').checked;

            rawProductsData.forEach(item => {
                renderLabelToCanvas(item);
            });
        }

        // Update jumlah copies per item
        function updateItemCopies(itemId, val) {
            val = parseInt(val) || 1;
            val = Math.max(1, Math.min(100, val));
            const item = rawProductsData.find(p => p.id === itemId);
            if (item) {
                item.copies = val;
            }
            updateTotalCopiesCounter();
        }

        // Hitung total copies aktif yang akan dicetak
        function updateTotalCopiesCounter() {
            let total = 0;
            rawProductsData.forEach(item => {
                const chk = document.getElementById(`check-item-${item.id}`);
                if (chk && chk.checked) {
                    total += (parseInt(item.copies) || 1);
                }
            });
            document.getElementById('total-copies-count').textContent = total;
        }

        // Log status ke footer
        function setLog(text) {
            const el = document.getElementById('printer-log-summary');
            if (el) el.textContent = text;
        }

        // -------------------------------------------------------------
        // MANAJEMEN KONEKSI PRINTER NIIMBOT B1 (BLUETOOTH & USB SERIAL)
        // -------------------------------------------------------------
        let currentConnectMode = 'bluetooth';

        async function connectPrinter(mode = 'bluetooth') {
            if (typeof niimbluelib === 'undefined') {
                alert('Pustaka NiimBlueLib gagal dimuat. Pastikan file public/js/niimbluelib.min.js tersedia.');
                return;
            }

            currentConnectMode = mode;

            if (mode === 'serial') {
                if (!navigator.serial) {
                    alert('Browser Anda tidak mendukung Web Serial API. Harap gunakan Google Chrome atau Microsoft Edge terbaru.');
                    return;
                }
            } else {
                if (!navigator.bluetooth) {
                    alert('Browser Anda tidak mendukung Web Bluetooth API. Harap gunakan Google Chrome atau Microsoft Edge terbaru.');
                    return;
                }
            }

            try {
                if (mode === 'serial') {
                    setLog('Menghubungkan via kabel USB Type-C...');
                    document.getElementById('status-text').textContent = 'Memilih Port USB...';
                    document.getElementById('status-indicator-dot').className = 'w-2.5 h-2.5 rounded-full bg-blue-400 animate-ping mr-2';

                    niimbotClient = new niimbluelib.NiimbotSerialClient();
                } else {
                    setLog('Mencari printer Bluetooth (B1)...');
                    document.getElementById('status-text').textContent = 'Mencari Bluetooth...';
                    document.getElementById('status-indicator-dot').className = 'w-2.5 h-2.5 rounded-full bg-amber-400 animate-ping mr-2';

                    niimbotClient = new niimbluelib.NiimbotBluetoothClient();
                    // Pastikan service UUID diset
                    niimbotClient.setServiceUuidFilter(['e7810a71-73ae-499d-8c15-faa9aef0c3f2']);
                }

                await niimbotClient.connect();

                isConnected = true;
                const connectionLabel = mode === 'serial' ? 'USB Kabel' : 'Bluetooth';
                setLog(`Terhubung ke NIIMBOT B1 (${connectionLabel}). Mengambil info...`);

                // Ambil info baterai jika didukung
                try {
                    const abstraction = new niimbluelib.Abstraction(niimbotClient);
                    const batteryLevel = await abstraction.getBatteryChargeLevel();
                    if (batteryLevel !== undefined && batteryLevel !== null) {
                        const batteryEl = document.getElementById('battery-indicator');
                        batteryEl.textContent = `🔋 ${batteryLevel}%`;
                        batteryEl.classList.remove('hidden');
                    }
                } catch (e) {
                    console.warn('Info baterai tidak terbaca:', e);
                }

                // Update UI Status Terhubung
                document.getElementById('status-text').textContent = `NIIMBOT B1 (${connectionLabel})`;
                document.getElementById('status-indicator-dot').className = 'w-2.5 h-2.5 rounded-full bg-emerald-500 mr-2';
                document.getElementById('connection-status-badge').className = 'inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-300';
                
                document.getElementById('connect-buttons-group').classList.add('hidden');
                document.getElementById('btn-disconnect').classList.remove('hidden');

                setLog(`Printer NIIMBOT B1 siap mencetak via ${connectionLabel}.`);
            } catch (err) {
                console.error('Koneksi printer gagal:', err);
                isConnected = false;
                document.getElementById('status-text').textContent = 'Koneksi Dibatalkan / Gagal';
                document.getElementById('status-indicator-dot').className = 'w-2.5 h-2.5 rounded-full bg-red-500 mr-2';
                document.getElementById('connection-status-badge').className = 'inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200';
                setLog(err.message ? `Error: ${err.message}` : 'Koneksi dibatalkan.');
            }
        }

        async function disconnectPrinter() {
            if (niimbotClient) {
                try {
                    await niimbotClient.disconnect();
                } catch (e) {
                    console.warn(e);
                }
            }
            isConnected = false;
            document.getElementById('status-text').textContent = 'Belum Terhubung';
            document.getElementById('status-indicator-dot').className = 'w-2.5 h-2.5 rounded-full bg-gray-400 mr-2';
            document.getElementById('connection-status-badge').className = 'inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200';
            document.getElementById('battery-indicator').classList.add('hidden');
            document.getElementById('connect-buttons-group').classList.remove('hidden');
            document.getElementById('btn-disconnect').classList.add('hidden');
            setLog('Koneksi printer diputuskan.');
        }

        // -------------------------------------------------------------
        // EKSEKUSI CETAK LABEL (BATCH & SINGLE)
        // -------------------------------------------------------------
        async function printAllLabels() {
            if (!isConnected || !niimbotClient) {
                alert('Printer NIIMBOT B1 belum terhubung!\n\nSilakan klik tombol "Hubungkan Bluetooth" atau "Hubungkan USB (Kabel)" di bagian atas terlebih dahulu.');
                return;
            }

            // Kumpulkan produk yang dicentang
            const queue = [];
            rawProductsData.forEach(item => {
                const chk = document.getElementById(`check-item-${item.id}`);
                if (chk && chk.checked) {
                    const copies = parseInt(item.copies) || 1;
                    queue.push({
                        item: item,
                        copies: copies,
                        canvas: document.getElementById(`canvas-${item.id}`)
                    });
                }
            });

            if (queue.length === 0) {
                alert('Pilih minimal satu produk untuk dicetak.');
                return;
            }

            let totalLabels = queue.reduce((sum, q) => sum + q.copies, 0);

            // Buka Modal Progress
            const modal = document.getElementById('print-progress-modal');
            const barFill = document.getElementById('progress-bar-fill');
            const countText = document.getElementById('progress-count-text');
            const percentText = document.getElementById('progress-percentage-text');
            modal.classList.remove('hidden');

            isPrinting = true;
            shouldAbort = false;
            let printedCount = 0;

            try {
                const abstraction = new niimbluelib.Abstraction(niimbotClient);
                const printTaskType = niimbotClient.getPrintTaskType() || 'B1';
                const densityVal = parseInt(document.getElementById('setting-density').value) || 2;

                setLog(`Menginisialisasi pencetakan (${totalLabels} label)...`);

                // Inisialisasi Print Task NIIMBOT B1
                const task = abstraction.newPrintTask(printTaskType, {
                    totalPages: totalLabels,
                    density: densityVal,
                    labelType: 1 // 1 = die-cut with gap
                });

                await task.printInit();

                for (let i = 0; i < queue.length; i++) {
                    if (shouldAbort) {
                        setLog('Pencetakan dihentikan oleh pengguna.');
                        break;
                    }

                    const q = queue[i];
                    const badge = document.getElementById(`badge-status-${q.item.id}`);
                    if (badge) {
                        badge.className = 'text-[10px] font-semibold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 animate-pulse';
                        badge.textContent = 'Mencetak...';
                    }

                    // Dapatkan printDirection dari metadata model (B1 menggunakan 'top')
                    const meta = (typeof niimbotClient.getModelMetadata === 'function') ? niimbotClient.getModelMetadata() : null;
                    const printDir = meta?.printDirection || 'top';

                    // Encode Canvas 203 DPI dengan arah 'top' agar tercetak HORIZONTAL sesuai kertas roll B1
                    const encoded = niimbluelib.ImageEncoder.encodeCanvas(q.canvas, printDir);

                    // Cetak sebanyak jumlah copies yang diminta untuk item ini
                    await task.printPage(encoded, q.copies);

                    printedCount += q.copies;
                    const percent = Math.round((printedCount / totalLabels) * 100);
                    barFill.style.width = `${percent}%`;
                    countText.textContent = `${printedCount} / ${totalLabels} Label`;
                    percentText.textContent = `${percent}%`;

                    if (badge) {
                        badge.className = 'text-[10px] font-semibold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800';
                        badge.textContent = `Selesai (${q.copies} lbr)`;
                    }

                    // Jeda sejenak untuk stabilitas buffer printer
                    await new Promise(r => setTimeout(r, 120));
                }

                await task.waitForFinished();
                setLog(`Pencetakan selesai! Berhasil mencetak ${printedCount} label.`);
                setTimeout(() => {
                    modal.classList.add('hidden');
                    alert(`✅ Pencetakan selesai!\nTotal ${printedCount} label berhasil dikirim ke printer NIIMBOT B1.`);
                }, 400);

            } catch (err) {
                console.error('Error saat proses cetak:', err);
                alert(`Gagal mencetak: ${err.message || 'Terjadi gangguan koneksi Bluetooth'}`);
                setLog('Error pencetakan.');
                modal.classList.add('hidden');
            } finally {
                isPrinting = false;
            }
        }

        // Cetak 1 produk tunggal
        async function printSingleLabel(itemId) {
            if (!isConnected || !niimbotClient) {
                alert('Printer NIIMBOT B1 belum terhubung!\n\nSilakan klik tombol "Hubungkan Bluetooth" atau "Hubungkan USB (Kabel)" di bagian atas terlebih dahulu.');
                return;
            }

            const item = rawProductsData.find(p => p.id === itemId);
            const canvas = document.getElementById(`canvas-${itemId}`);
            const copies = parseInt(item.copies) || 1;
            const badge = document.getElementById(`badge-status-${itemId}`);

            if (!item || !canvas) return;

            try {
                if (badge) {
                    badge.className = 'text-[10px] font-semibold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 animate-pulse';
                    badge.textContent = 'Mencetak...';
                }

                const abstraction = new niimbluelib.Abstraction(niimbotClient);
                const printTaskType = niimbotClient.getPrintTaskType() || 'B1';
                const densityVal = parseInt(document.getElementById('setting-density').value) || 2;

                setLog(`Mencetak ${copies} label untuk ${item.name}...`);

                const task = abstraction.newPrintTask(printTaskType, {
                    totalPages: copies,
                    density: densityVal,
                    labelType: 1
                });

                await task.printInit();

                const meta = (typeof niimbotClient.getModelMetadata === 'function') ? niimbotClient.getModelMetadata() : null;
                const printDir = meta?.printDirection || 'top';

                const encoded = niimbluelib.ImageEncoder.encodeCanvas(canvas, printDir);
                await task.printPage(encoded, copies);
                await task.waitForFinished();

                if (badge) {
                    badge.className = 'text-[10px] font-semibold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800';
                    badge.textContent = `Selesai (${copies} lbr)`;
                }

                setLog(`Selesai mencetak label ${item.name}.`);
            } catch (err) {
                console.error(err);
                alert(`Gagal mencetak: ${err.message}`);
                if (badge) {
                    badge.className = 'text-[10px] font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-800';
                    badge.textContent = 'Gagal';
                }
            }
        }

        function abortPrinting() {
            shouldAbort = true;
            document.getElementById('print-progress-modal').classList.add('hidden');
        }

        // Siapkan printable area untuk dialog cetak browser (Ctrl+P)
        window.onbeforeprint = () => {
            const printArea = document.getElementById('printable-area');
            printArea.innerHTML = '';

            rawProductsData.forEach(item => {
                const chk = document.getElementById(`check-item-${item.id}`);
                if (chk && chk.checked) {
                    const canvas = document.getElementById(`canvas-${item.id}`);
                    const copies = parseInt(item.copies) || 1;
                    const dataUrl = canvas.toDataURL('image/png');

                    for (let c = 0; c < copies; c++) {
                        const page = document.createElement('div');
                        page.className = 'print-page-break flex items-center justify-center p-2';
                        page.innerHTML = `<img src="${dataUrl}" style="max-width: 100%; height: auto;" />`;
                        printArea.appendChild(page);
                    }
                }
            });
            printArea.classList.remove('hidden');
        };

        window.onafterprint = () => {
            document.getElementById('printable-area').classList.add('hidden');
        };
    </script>
</body>
</html>
