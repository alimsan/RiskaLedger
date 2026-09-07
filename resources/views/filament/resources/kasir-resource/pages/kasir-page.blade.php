<x-filament::page>
<script>
    window.checkoutFunctions = function() {
        return {
            // State untuk mesin scanner barcode
            isScannerActive: false,
            scannerStatus: 'Mesin Scanner Terhubung & Siap Scan',
            audioCtx: null,
            lastScannedCode: '',
            lastScanTime: 0,
            scanCooldownMs: 800,

            // State untuk potongan harga (diskon)
            currentSubtotal: {{ (float) $this->cartSubtotal }},
            currentDiscount: {{ (float) $this->discount }},

            handleModalDiscountChange(e) {
                const raw = (e.target.value || '').replace(/[^0-9]/g, '');
                let val = parseInt(raw, 10) || 0;
                if (val > this.currentSubtotal) {
                    val = this.currentSubtotal;
                }
                this.currentDiscount = val;
                e.target.value = val > 0 ? val.toLocaleString('id-ID') : '';
                this.updateModalDisplay();

                const caller = (this.$wire || @this);
                if (caller && typeof caller.setDiscountAmount === 'function') {
                    caller.setDiscountAmount(val);
                }
            },

            updateModalDisplay() {
                const total = Math.max(0, this.currentSubtotal - this.currentDiscount);
                const totalEl = document.getElementById('modal_total_display');
                if (totalEl) {
                    totalEl.innerText = 'Rp ' + total.toLocaleString('id-ID');
                }
                const discDisplay = document.getElementById('modal_discount_display');
                if (discDisplay) {
                    discDisplay.innerText = '- Rp ' + this.currentDiscount.toLocaleString('id-ID');
                }
            },

            init() {
                // Global hotkey F2 untuk toggle mode scanner barcode
                window.addEventListener('keydown', (e) => {
                    if (e.key === 'F2') {
                        e.preventDefault();
                        this.toggleScannerMode();
                    }
                });

                // Tangani event scanner-result dari Livewire
                window.addEventListener('scanner-result', (event) => {
                    const data = Array.isArray(event.detail) ? event.detail[0] : event.detail;
                    if (!data) return;
                    if (data.success) {
                        this.playBeep(true);
                    } else {
                        this.playBeep(false);
                    }
                });

                // Deteksi global input cepat dari hardware scanner barcode
                let keyBuffer = '';
                let lastKeyTime = Date.now();

                window.addEventListener('keydown', (e) => {
                    if (!this.isScannerActive) return;

                    const activeEl = document.activeElement;
                    const searchInput = document.getElementById('search-product-input');

                    // Jika sedang fokus pada input lain selain search, jangan intersep
                    if (activeEl && activeEl !== searchInput && (activeEl.tagName === 'INPUT' || activeEl.tagName === 'TEXTAREA')) {
                        return;
                    }

                    const now = Date.now();
                    const timeDiff = now - lastKeyTime;
                    lastKeyTime = now;

                    if (e.key === 'Enter') {
                        if (keyBuffer.length >= 2 && timeDiff < 90) {
                            e.preventDefault();
                            e.stopImmediatePropagation();
                            const codeToScan = keyBuffer;
                            keyBuffer = '';
                            if (searchInput) {
                                searchInput.value = codeToScan;
                                searchInput.dispatchEvent(new Event('input', { bubbles: true }));
                            }
                            this.handleBarcodeScan(codeToScan);
                        } else {
                            keyBuffer = '';
                        }
                    } else if (e.key.length === 1) {
                        if (timeDiff < 90 || keyBuffer.length === 0) {
                            keyBuffer += e.key;
                        } else {
                            keyBuffer = e.key;
                        }
                    }
                });
            },

            checkScannerDevice() {
                if (navigator.hid) {
                    navigator.hid.getDevices().then(devices => {
                        if (devices && devices.length > 0) {
                            this.scannerStatus = 'Mesin Scanner Terhubung (' + (devices[0].productName || 'USB Barcode Scanner') + ')';
                        } else {
                            this.scannerStatus = 'Mesin Scanner Terhubung (Mode HID Keyboard)';
                        }
                    }).catch(() => {
                        this.scannerStatus = 'Mesin Scanner Terhubung (Mode HID Keyboard)';
                    });
                } else {
                    this.scannerStatus = 'Mesin Scanner Terhubung (Mode HID Keyboard)';
                }
            },

            toggleScannerMode(forceState = null) {
                this.isScannerActive = forceState !== null ? forceState : !this.isScannerActive;
                if (this.isScannerActive) {
                    this.checkScannerDevice();
                    this.playBeep(true);
                    this.$nextTick(() => {
                        const searchInput = document.getElementById('search-product-input');
                        if (searchInput) {
                            searchInput.focus();
                            searchInput.select();
                        }
                    });
                }
            },

            handleSearchKeydown(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    e.stopPropagation();
                    const val = (e.target.value || '').trim();
                    if (val) {
                        if (this.isScannerActive) {
                            this.handleBarcodeScan(val);
                        } else {
                            const caller = (this.$wire || @this);
                            if (caller) {
                                caller.set('searchQuery', val);
                            }
                        }
                    }
                }
            },

            handleBarcodeScan(code) {
                const cleanCode = (code || '').trim();
                if (!cleanCode) return;

                const now = Date.now();
                // Cegah double scan / duplikasi jika barcode sama discan dalam waktu kurang dari 800ms
                if (cleanCode === this.lastScannedCode && (now - this.lastScanTime) < this.scanCooldownMs) {
                    return;
                }
                this.lastScannedCode = cleanCode;
                this.lastScanTime = now;

                // Pastikan nilai input Cari Produk terisi dengan barcode yang di-scan
                const searchInput = document.getElementById('search-product-input');
                if (searchInput && searchInput.value !== cleanCode) {
                    searchInput.value = cleanCode;
                    searchInput.dispatchEvent(new Event('input', { bubbles: true }));
                }

                // Panggil method Livewire scanBarcode
                const caller = (this.$wire || @this);
                if (caller) {
                    if (typeof caller.scanBarcode === 'function') {
                        caller.scanBarcode(cleanCode);
                    } else if (typeof caller.call === 'function') {
                        caller.call('scanBarcode', cleanCode);
                    }
                }

                // Tetap fokuskan kursor ke input Cari Produk untuk scan barcode selanjutnya
                this.$nextTick(() => {
                    if (searchInput && this.isScannerActive) {
                        searchInput.focus();
                        searchInput.select();
                    }
                });
            },

            playBeep(isSuccess = true) {
                try {
                    const AudioContext = window.AudioContext || window.webkitAudioContext;
                    if (!AudioContext) return;

                    if (!this.audioCtx) {
                        this.audioCtx = new AudioContext();
                    }

                    if (this.audioCtx.state === 'suspended') {
                        this.audioCtx.resume();
                    }

                    const osc = this.audioCtx.createOscillator();
                    const gainNode = this.audioCtx.createGain();
                    osc.connect(gainNode);
                    gainNode.connect(this.audioCtx.destination);

                    if (isSuccess) {
                        // Beep sukses standar mesin kasir (nada tinggi 1200Hz durasi 90ms)
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(1200, this.audioCtx.currentTime);
                        gainNode.gain.setValueAtTime(0.2, this.audioCtx.currentTime);
                        gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioCtx.currentTime + 0.09);
                        osc.start();
                        osc.stop(this.audioCtx.currentTime + 0.09);
                    } else {
                        // Buzz gagal/warning (nada rendah 320Hz durasi 200ms)
                        osc.type = 'sawtooth';
                        osc.frequency.setValueAtTime(320, this.audioCtx.currentTime);
                        gainNode.gain.setValueAtTime(0.25, this.audioCtx.currentTime);
                        gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioCtx.currentTime + 0.2);
                        osc.start();
                        osc.stop(this.audioCtx.currentTime + 0.2);
                    }
                } catch (err) {
                    console.warn('Audio feedback failed:', err);
                }
            },

            initCheckoutForm() {
                // Reset checkbox values to false
                document.getElementById('is_piutang').checked = false;
                document.getElementById('download_receipt').checked = false;
                document.getElementById('add_buyer_info').checked = false;
                document.getElementById('custom_time').checked = false;

                // Sync data diskon di modal
                this.currentSubtotal = {{ (float) $this->cartSubtotal }};
                this.currentDiscount = {{ (float) $this->discount }};
                const modalDiscInput = document.getElementById('modal_discount_input');
                if (modalDiscInput) {
                    modalDiscInput.value = this.currentDiscount > 0 ? this.currentDiscount.toLocaleString('id-ID') : '';
                }
                this.updateModalDisplay();

                // Setup thermal print button
                this.setupThermalPrintButton();

                // Set nilai default datetime-local ke waktu sekarang
                const now = new Date();
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

                    dropdown.classList.remove('hidden');
                    activeIndex = -1;

                    vendorOptions.forEach(option => {
                        const text = option.innerText.toLowerCase();
                        if (text.includes(searchValue)) {
                            option.classList.remove('hidden');
                            hasVisibleOptions = true;
                        } else {
                            option.classList.add('hidden');
                        }
                    });

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
                // Keep consistency
            },

            setupThermalPrintButton() {
                const printBtn = document.getElementById('print_thermal_btn');
                if (!printBtn) return;

                printBtn.addEventListener('click', (e) => {
                    e.preventDefault();

                    const typeId = document.getElementById('payment_method').value;
                    const isPiutang = document.getElementById('is_piutang').checked;
                    const vendorId = isPiutang ? document.getElementById('selected_vendor_id').value : null;
                    const notes = document.getElementById('notes').value;

                    const addBuyerInfo = document.getElementById('add_buyer_info').checked;
                    const buyerName = addBuyerInfo ? document.getElementById('buyer_name').value : null;
                    const cashierName = addBuyerInfo ? document.getElementById('cashier_name').value : null;

                    const customTime = document.getElementById('custom_time').checked;
                    const transactionTime = customTime ? document.getElementById('transaction_time').value : null;

                    if (isPiutang && !vendorId) {
                        alert('Mohon pilih vendor terlebih dahulu untuk transaksi piutang');
                        return;
                    }

                    if (!@json(count($this->cartItems))) {
                        alert('Keranjang belanja kosong. Silakan tambahkan produk terlebih dahulu.');
                        return;
                    }

                    const caller = (this.$wire || @this);
                    const discountVal = this.currentDiscount || 0;
                    caller.saveCartForPrinting(typeId, isPiutang, vendorId, notes, buyerName, cashierName, transactionTime, discountVal).then(response => {
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
                        if (discountVal > 0) params.append('discount', discountVal);

                        const newTab = window.open(`{{ route('thermal-print') }}?${params.toString()}`, '_blank');
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

                if (isPiutang && !vendorId) {
                    alert('Silakan pilih vendor terlebih dahulu');
                    return;
                }

                const caller = (this.$wire || @this);
                const discountVal = this.currentDiscount || 0;
                caller.checkout({
                    type_id: typeId,
                    notes: notes,
                    is_receivable: isPiutang,
                    vendor_id: vendorId,
                    download_receipt: downloadReceipt,
                    buyer_name: buyerName,
                    cashier_name: cashierName,
                    transaction_time: transactionTime,
                    discount: discountVal
                });
            }
        };
    };

    if (window.Alpine) {
        window.Alpine.data('checkoutFunctions', window.checkoutFunctions);
    } else {
        document.addEventListener('alpine:init', () => {
            window.Alpine.data('checkoutFunctions', window.checkoutFunctions);
        });
    }

    // Menangani event 'close-checkout-modal'
    window.addEventListener('close-checkout-modal', function() {
        Livewire.dispatch('close-modal', { id: 'checkout-modal' });
    });

    // Menangani event 'download-receipt-pdf' untuk download otomatis nota PDF
    window.addEventListener('download-receipt-pdf', function(event) {
        const data = event.detail;
        const url = typeof data === 'string' ? data : (data?.url || (Array.isArray(data) ? data[0]?.url : null));
        if (url) {
            const link = document.createElement('a');
            link.href = url;
            link.download = '';
            link.target = '_blank';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    });

    // Fungsi untuk refresh halaman baru kemudian buka modal checkout
    function refreshBeforeCheckout() {
        if (!sessionStorage.getItem('freshCheckout')) {
            console.log('Refreshing for checkout...');
            sessionStorage.setItem('freshCheckout', 'true');
            window.location.reload();
            return;
        }

        console.log('Opening checkout modal after fresh page load');
        sessionStorage.removeItem('freshCheckout');
        Livewire.dispatch('open-modal', { id: 'checkout-modal' });
    }

    if (sessionStorage.getItem('freshCheckout') === 'true') {
        setTimeout(function() {
            refreshBeforeCheckout();
        }, 500);
    }
</script>

<div x-data="checkoutFunctions()">
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

        /* ============================================================
           KASIR DISCOUNT & SUMMARY COMPONENT (LIGHT & DARK ADAPTIVE)
           ============================================================ */
        .kasir-discount-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 0.75rem;
            transition: all 0.2s ease;
        }
        .dark .kasir-discount-box {
            background-color: #1e293b;
            border-color: #334155;
        }

        .kasir-discount-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .kasir-discount-title {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: #b45309;
        }
        .dark .kasir-discount-title {
            color: #fbbf24;
        }

        .kasir-reset-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.125rem 0.5rem;
            font-size: 0.6875rem;
            font-weight: 600;
            color: #ef4444;
            background-color: #fee2e2;
            border-radius: 9999px;
            border: none;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .kasir-reset-btn:hover {
            background-color: #fecaca;
            color: #b91c1c;
        }
        .dark .kasir-reset-btn {
            background-color: #450a0a;
            color: #fca5a5;
        }
        .dark .kasir-reset-btn:hover {
            background-color: #7f1d1d;
            color: #fecaca;
        }

        .kasir-input-group {
            display: flex;
            align-items: stretch;
            border-radius: 0.5rem;
            overflow: hidden;
            border: 1px solid #cbd5e1;
            background-color: #ffffff;
            transition: all 0.15s ease;
        }
        .dark .kasir-input-group {
            border-color: #475569;
            background-color: #0f172a;
        }
        .kasir-input-group:focus-within {
            border-color: #f59e0b;
            box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.25);
        }

        .kasir-input-prefix {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 0.625rem;
            font-size: 0.75rem;
            font-weight: 700;
            background-color: #f1f5f9;
            color: #64748b;
            border-right: 1px solid #cbd5e1;
            user-select: none;
        }
        .dark .kasir-input-prefix {
            background-color: #1e293b;
            color: #94a3b8;
            border-color: #475569;
        }

        .kasir-input-field {
            flex: 1;
            min-width: 0;
            border: none !important;
            background: transparent !important;
            padding: 0.4rem 0.625rem !important;
            font-size: 0.8125rem !important;
            font-weight: 700 !important;
            color: #0f172a !important;
            outline: none !important;
            box-shadow: none !important;
        }
        .dark .kasir-input-field {
            color: #f8fafc !important;
        }
        .kasir-input-field::placeholder {
            color: #94a3b8;
            font-weight: 500;
        }

        .kasir-chip-list {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.375rem;
            margin-top: 0.5rem;
        }

        .kasir-chip-btn {
            padding: 0.2rem 0.5rem;
            font-size: 0.6875rem;
            font-weight: 600;
            border-radius: 0.375rem;
            border: 1px solid #e2e8f0;
            background-color: #ffffff;
            color: #334155;
            cursor: pointer;
            transition: all 0.15s ease;
            user-select: none;
        }
        .kasir-chip-btn:hover {
            background-color: #fef3c7;
            border-color: #f59e0b;
            color: #92400e;
        }
        .dark .kasir-chip-btn {
            border-color: #475569;
            background-color: #0f172a;
            color: #cbd5e1;
        }
        .dark .kasir-chip-btn:hover {
            background-color: #78350f;
            border-color: #f59e0b;
            color: #fef3c7;
        }

        /* Modal Checkout Theme Adaptive */
        .checkout-summary-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 0.875rem;
        }
        .dark .checkout-summary-box {
            background-color: #18181b;
            border-color: #27272a;
        }

        .checkout-summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8125rem;
            color: #475569;
        }
        .dark .checkout-summary-row {
            color: #94a3b8;
        }

        .checkout-summary-val {
            font-weight: 700;
            color: #0f172a;
        }
        .dark .checkout-summary-val {
            color: #f8fafc;
        }

        .checkout-summary-total {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding-top: 0.625rem;
            margin-top: 0.5rem;
            border-top: 1px dashed #cbd5e1;
        }
        .dark .checkout-summary-total {
            border-top-color: #3f3f46;
        }

        .checkout-total-label {
            font-size: 0.9375rem;
            font-weight: 700;
            color: #0f172a;
        }
        .dark .checkout-total-label {
            color: #f8fafc;
        }

        .checkout-total-amount {
            font-size: 1.25rem;
            font-weight: 800;
            color: #d97706;
        }
        .dark .checkout-total-amount {
            color: #fbbf24;
        }
    </style>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Panel Produk -->
        <div class="md:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-300 dark:border-gray-700 p-4">
            <!-- Search dan Filter -->
            <div class="mb-2 flex flex-col sm:flex-row gap-3">
                <div class="flex-1 flex gap-2">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            type="text"
                            id="search-product-input"
                            x-ref="searchInput"
                            wire:model.live.debounce.300ms="searchQuery"
                            @keydown="handleSearchKeydown($event)"
                            placeholder="Cari produk (nama atau barcode)..."
                            :class="isScannerActive ? 'ring-2 ring-emerald-500 border-emerald-500 dark:border-emerald-500 bg-emerald-50/20 dark:bg-emerald-950/20' : 'border-gray-300 dark:border-gray-700'"
                            class="w-full pl-9 rounded-lg dark:bg-gray-800 dark:text-white shadow-sm text-sm transition-all"
                            autocomplete="off"
                        >
                    </div>

                    <!-- Tombol Mesin Scanner Barcode -->
                    <button
                        type="button"
                        x-on:click="toggleScannerMode()"
                        :class="isScannerActive ? 'bg-emerald-600 hover:bg-emerald-700 ring-2 ring-emerald-400 text-white' : 'bg-primary-600 hover:bg-primary-700 text-white'"
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg font-medium text-sm shadow-sm transition-all duration-150 whitespace-nowrap cursor-pointer"
                        title="Klik untuk cek status mesin dan aktifkan scan barcode (Shortcut: F2)"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                        </svg>
                        <span x-text="isScannerActive ? 'Scanner Aktif' : 'Scan Barcode'"></span>
                        <span x-show="isScannerActive" class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-200 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                        </span>
                    </button>
                </div>

                <div>
                    <select
                        wire:model.live="selectedCategory"
                        class="w-full sm:w-auto rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm text-sm"
                    >
                        <option value="">Semua Kategori</option>
                        @foreach($this->categories as $category)
                            <option value="{{ $category }}">{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Status Koneksi Mesin Scanner -->
            <div
                x-show="isScannerActive"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-1"
                class="mb-3 px-3 py-2 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-400/60 dark:border-emerald-600/60 rounded-lg flex items-center justify-between gap-2 text-xs"
            >
                <div class="flex items-center gap-2">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <span class="font-bold text-emerald-800 dark:text-emerald-200" x-text="scannerStatus"></span>
                    <span class="text-emerald-700 dark:text-emerald-300">| Tembak barcode, hasil scan akan langsung muncul di kolom Cari Produk.</span>
                </div>
                <button
                    type="button"
                    @click="toggleScannerMode(false)"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                    title="Tutup"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
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

                    <!-- Rincian Harga & Potongan Diskon -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-3 space-y-2.5">
                        <!-- Subtotal -->
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span>Subtotal</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ 'Rp ' . number_format($this->cartSubtotal, 0, ',', '.') }}</span>
                        </div>

                        <!-- Card Potongan Harga / Diskon Manual -->
                        <div class="kasir-discount-box">
                            <div class="kasir-discount-header">
                                <span class="kasir-discount-title">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                    Potongan Harga
                                </span>
                                @if($discount > 0)
                                    <button
                                        type="button"
                                        wire:click="resetDiscount"
                                        class="kasir-reset-btn"
                                        title="Hapus potongan harga"
                                    >
                                        <span>✕</span>
                                        <span>Hapus</span>
                                    </button>
                                @endif
                            </div>

                            <!-- Input Potongan Harga -->
                            <div class="kasir-input-group">
                                <span class="kasir-input-prefix">Rp</span>
                                <input
                                    type="text"
                                    id="cart_discount_input"
                                    wire:model.live.debounce.400ms="manualDiscountInput"
                                    placeholder="0"
                                    class="kasir-input-field"
                                    autocomplete="off"
                                >
                            </div>

                            <!-- Quick Preset Buttons -->
                            <div class="kasir-chip-list">
                                <button
                                    type="button"
                                    wire:click="setDiscountPercentage(5)"
                                    class="kasir-chip-btn"
                                >
                                    5%
                                </button>
                                <button
                                    type="button"
                                    wire:click="setDiscountPercentage(10)"
                                    class="kasir-chip-btn"
                                >
                                    10%
                                </button>
                                <button
                                    type="button"
                                    wire:click="setDiscountAmount(5000)"
                                    class="kasir-chip-btn"
                                >
                                    5.000
                                </button>
                                <button
                                    type="button"
                                    wire:click="setDiscountAmount(10000)"
                                    class="kasir-chip-btn"
                                >
                                    10.000
                                </button>
                                <button
                                    type="button"
                                    wire:click="setDiscountAmount(20000)"
                                    class="kasir-chip-btn"
                                >
                                    20.000
                                </button>
                            </div>
                        </div>

                        <!-- Baris Potongan jika aktif -->
                        @if($discount > 0)
                            <div class="flex justify-between text-xs text-amber-600 dark:text-amber-400 font-semibold">
                                <span>Potongan Diskon</span>
                                <span>- {{ 'Rp ' . number_format($this->discount, 0, ',', '.') }}</span>
                            </div>
                        @endif

                        <!-- Total Pembayaran -->
                        <div class="flex justify-between items-baseline pt-2 border-t border-gray-200 dark:border-gray-700">
                            <span class="text-base font-bold text-gray-900 dark:text-white">Total Bayar</span>
                            <span class="text-xl font-extrabold text-amber-500 dark:text-amber-400">
                                {{ 'Rp ' . number_format($this->cartTotal, 0, ',', '.') }}
                            </span>
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

            <!-- Rincian Pembayaran & Diskon di Modal -->
            <div class="checkout-summary-box space-y-2.5">
                <div class="checkout-summary-row">
                    <span>Subtotal Belanja</span>
                    <span class="checkout-summary-val" id="modal_subtotal_display">
                        {{ 'Rp ' . number_format($this->cartSubtotal, 0, ',', '.') }}
                    </span>
                </div>

                <div class="checkout-summary-row">
                    <label for="modal_discount_input" class="font-medium text-amber-600 dark:text-amber-400 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        Potongan Diskon
                    </label>
                    <div class="kasir-input-group" style="max-width: 160px;">
                        <span class="kasir-input-prefix">Rp</span>
                        <input
                            type="text"
                            id="modal_discount_input"
                            x-on:input="handleModalDiscountChange($event)"
                            value="{{ $this->discount > 0 ? number_format($this->discount, 0, ',', '.') : '' }}"
                            placeholder="0"
                            class="kasir-input-field text-right"
                        >
                    </div>
                </div>

                <div class="checkout-summary-row text-amber-600 dark:text-amber-400 font-semibold {{ $this->discount > 0 ? '' : 'hidden' }}" id="modal_discount_row">
                    <span>Potongan Terpasang</span>
                    <span id="modal_discount_display">- {{ 'Rp ' . number_format($this->discount, 0, ',', '.') }}</span>
                </div>

                <div class="checkout-summary-total">
                    <span class="checkout-total-label">Total Pembayaran</span>
                    <span class="checkout-total-amount" id="modal_total_display">
                        {{ 'Rp ' . number_format($this->cartTotal, 0, ',', '.') }}
                    </span>
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
</div>
</x-filament::page>
