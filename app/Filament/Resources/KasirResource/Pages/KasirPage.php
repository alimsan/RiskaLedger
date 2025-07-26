<?php

namespace App\Filament\Resources\KasirResource\Pages;

use App\Filament\Resources\KasirResource;
use App\Models\Item;
use App\Models\CashInOutType;
use App\Models\mCashInOut;
use App\Models\ConfigTenants;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use App\Models\Vendor;
use App\Models\Piutang;
use App\Models\TransactionItems;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class KasirPage extends Page
{
    protected static string $resource = KasirResource::class;

    protected static string $view = 'filament.resources.kasir-resource.pages.kasir-page';

    public $cartItems = [];
    public $searchQuery = '';
    public $selectedCategory = '';

    public function mount()
    {
        // Inisialisasi keranjang dari session jika ada
        $this->cartItems = session('cart_items', []);
    }

    #[Computed]
    public function categories()
    {
        $tenantId = auth()->user()->tenant_id;
        return Item::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->distinct()
            ->pluck('category')
            ->filter()
            ->toArray();
    }

    #[Computed]
    public function items()
    {
        $tenantId = auth()->user()->tenant_id;
        $query = Item::where('tenant_id', $tenantId)
            ->where('is_active', true);

        if ($this->searchQuery) {
            $query->where('name', 'like', '%' . $this->searchQuery . '%');
        }

        if ($this->selectedCategory) {
            $query->where('category', $this->selectedCategory);
        }

        return $query->get();
    }

    /**
     * Mengecek apakah penggunaan stock diaktifkan untuk tenant
     */
    public function isStockUseEnabled()
    {
        $tenantId = auth()->user()->tenant_id;
        
        $stockConfig = ConfigTenants::where('tenant_id', $tenantId)
            ->where('name', 'stock_use')
            ->where('status', true)
            ->first();
            
        return $stockConfig ? true : false;
    }

    /**
     * Mengecek apakah item dapat ditambahkan ke keranjang
     */
    public function canAddToCart($item)
    {
        // Jika stock_use tidak diaktifkan, selalu bisa tambah
        if (!$this->isStockUseEnabled()) {
            return true;
        }
        
        // Jika stock_use diaktifkan, cek stock tersedia
        $currentQuantityInCart = isset($this->cartItems[$item->id]) ? $this->cartItems[$item->id]['quantity'] : 0;
        
        // Bisa tambah jika masih ada stock tersisa setelah dikurangi yang sudah di keranjang
        return ($item->stock - $currentQuantityInCart) > 0;
    }

    public function addToCart($itemId)
    {
        $item = Item::find($itemId);

        if (!$item) {
            return;
        }

        // Jika stock_use diaktifkan, cek stock availability
        if ($this->isStockUseEnabled()) {
            $currentQuantityInCart = isset($this->cartItems[$itemId]) ? $this->cartItems[$itemId]['quantity'] : 0;
            
            // Cek apakah masih ada stock tersedia
            if ($currentQuantityInCart >= $item->stock) {
                Notification::make()
                    ->title('Stock tidak mencukupi!')
                    ->body('Item ' . $item->name . ' hanya tersedia ' . $item->stock . ' unit.')
                    ->warning()
                    ->send();
                return;
            }
        }

        // Cek apakah item sudah ada di keranjang
        if (isset($this->cartItems[$itemId])) {
            $this->cartItems[$itemId]['quantity']++;
        } else {
            $this->cartItems[$itemId] = [
                'id' => $item->id,
                'name' => $item->name,
                'price' => $item->price,
                'quantity' => 1,
            ];
        }

        // Simpan keranjang ke session
        session(['cart_items' => $this->cartItems]);

        // Beri notifikasi
        Notification::make()
            ->title($item->name . ' ditambahkan ke keranjang')
            ->success()
            ->send();
    }

    public function updateCartQuantity($itemId, $quantity)
    {
        if ($quantity <= 0) {
            unset($this->cartItems[$itemId]);
        } else {
            // Jika stock_use diaktifkan, cek stock availability
            if ($this->isStockUseEnabled()) {
                $item = Item::find($itemId);
                if ($item && $quantity > $item->stock) {
                    Notification::make()
                        ->title('Stock tidak mencukupi!')
                        ->body('Item ' . $item->name . ' hanya tersedia ' . $item->stock . ' unit.')
                        ->warning()
                        ->send();
                    
                    // Set quantity ke maksimal stock yang tersedia
                    $this->cartItems[$itemId]['quantity'] = $item->stock;
                } else {
                    $this->cartItems[$itemId]['quantity'] = $quantity;
                }
            } else {
                $this->cartItems[$itemId]['quantity'] = $quantity;
            }
        }

        // Simpan keranjang ke session
        session(['cart_items' => $this->cartItems]);
    }

    public function removeFromCart($itemId)
    {
        unset($this->cartItems[$itemId]);

        // Simpan keranjang ke session
        session(['cart_items' => $this->cartItems]);

        // Beri notifikasi
        Notification::make()
            ->title('Item dihapus dari keranjang')
            ->success()
            ->send();
    }

    public function clearCart()
    {
        $this->cartItems = [];

        // Hapus keranjang dari session
        session()->forget('cart_items');

        // Beri notifikasi
        Notification::make()
            ->title('Keranjang dibersihkan')
            ->success()
            ->send();
    }

    #[Computed]
    public function cartTotal()
    {
        $total = 0;

        foreach ($this->cartItems as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        return $total;
    }

    #[Computed]
    public function incomeTypes()
    {
        return CashInOutType::where('is_income', true)
            ->where('is_active', true)
            ->where(function($query) {
                $query->where('tenant_id', auth()->user()->tenant_id)
                    ->orWhereNull('tenant_id');
            })
            ->get();
    }

    #[Computed]
    public function vendors()
    {
        $tenantId = auth()->user()->tenant_id;
        return Vendor::where('tenant_id', $tenantId)->get();
    }

    #[On('checkout')]
    public function checkout($data)
    {
        // Validasi data
        if (empty($data['type_id'])) {
            Notification::make()
                ->title('Metode pembayaran belum dipilih')
                ->warning()
                ->send();
            return;
        }

        if (empty($this->cartItems)) {
            Notification::make()
                ->title('Keranjang kosong')
                ->warning()
                ->send();
            return;
        }

        // Validasi jika ini adalah piutang
        if (isset($data['is_receivable']) && $data['is_receivable']) {
            if (empty($data['vendor_id'])) {
                Notification::make()
                    ->title('Vendor belum dipilih')
                    ->warning()
                    ->send();
                return;
            }
        }

        // Proses checkout
        DB::beginTransaction();

        try {
            $user = auth()->user();
            $tenantId = $user->tenant_id;
            $total = $this->cartTotal();

            // Set waktu transaksi (gunakan custom time jika ada, atau waktu sekarang jika tidak)
            $transactionTime = !empty($data['transaction_time'])
                ? Carbon::parse($data['transaction_time'])
                : now();

            // Jika ini adalah piutang
            if (isset($data['is_receivable']) && $data['is_receivable']) {
                // Hitung total item di keranjang
                $totalItems = 0;
                foreach ($this->cartItems as $item) {
                    $totalItems += $item['quantity'];
                }

                // Simpan ke piutang
                $piutang = new Piutang();
                $piutang->tenant_id = $tenantId;
                $piutang->vendor_id = $data['vendor_id'];
                $piutang->type_id = $data['type_id'];
                $piutang->qty = $totalItems;
                $piutang->total_utang = $total;
                $piutang->waktu = $transactionTime;
                $piutang->lunas = false;
                $piutang->save();

                // Simpan ke tabel transaction_items
                foreach ($this->cartItems as $item) {
                    $transactionItem = new TransactionItems();
                    $transactionItem->transaction_id = $piutang->id;
                    $transactionItem->item_id = $item['id'];
                    $transactionItem->cash_in_out_type_id = $data['type_id'];
                    $transactionItem->transaction_type = 'piutang';
                    $transactionItem->quantity = $item['quantity'];
                    $transactionItem->price = $item['price'];
                    $transactionItem->subtotal = $item['price'] * $item['quantity'];
                    $transactionItem->waktu = $transactionTime;
                    $transactionItem->save();
                }

                // Beri notifikasi sukses
                Notification::make()
                    ->title('Piutang berhasil dicatat')
                    ->success()
                    ->send();
            } else {
                // Buat daftar item yang dibeli
                $itemsDetails = [];
                foreach ($this->cartItems as $item) {
                    $itemsDetails[] = $item['name'] . ' (x' . $item['quantity'] . ')';
                }

                // Simpan transaksi ke database cash_in_out
                $transaction = new mCashInOut();
                $transaction->tenant_id = $tenantId;
                $transaction->type_id = $data['type_id'];
                $transaction->nama_barang = 'Penjualan Kasir';
                $transaction->deksripsi = implode(', ', $itemsDetails);
                $transaction->keterangan = $data['notes'] ?? '';

                // Tambahkan informasi pembeli dan kasir jika disertakan
                $additionalInfo = [];

                if (isset($data['buyer_name']) && $data['buyer_name']) {
                    $additionalInfo[] = 'Pembeli: ' . $data['buyer_name'];
                }

                if (isset($data['cashier_name']) && $data['cashier_name']) {
                    $additionalInfo[] = 'Kasir: ' . $data['cashier_name'];
                }

                if (!empty($additionalInfo)) {
                    $infoString = implode("\n", $additionalInfo);
                    $transaction->keterangan = $transaction->keterangan
                        ? $transaction->keterangan . "\n" . $infoString
                        : $infoString;
                }

                $transaction->nilai = $total;
                $transaction->waktu = $transactionTime;
                $transaction->save();

                // Simpan ke tabel transaction_items
                foreach ($this->cartItems as $item) {
                    $transactionItem = new TransactionItems();
                    $transactionItem->transaction_id = $transaction->id;
                    $transactionItem->item_id = $item['id'];
                    $transactionItem->cash_in_out_type_id = $data['type_id'];
                    $transactionItem->transaction_type = 'penjualan';
                    $transactionItem->quantity = $item['quantity'];
                    $transactionItem->price = $item['price'];
                    $transactionItem->subtotal = $item['price'] * $item['quantity'];
                    $transactionItem->waktu = $transactionTime;
                    $transactionItem->save();
                }

                // Beri notifikasi sukses
                Notification::make()
                    ->title('Transaksi berhasil dicatat')
                    ->success()
                    ->send();
            }

            // Commit transaksi
            DB::commit();

            // Jika perlu mengunduh nota
            if (isset($data['download_receipt']) && $data['download_receipt']) {
                // Siapkan data untuk nota
                $receiptData = [
                    'items' => $this->cartItems,
                    'total' => $total,
                    'transaction_date' => $transactionTime->format('d-m-Y H:i:s'),
                    'transaction_id' => $transaction->id ?? ($piutang->id ?? 'N/A'),
                    'payment_method' => CashInOutType::find($data['type_id'])->name ?? 'N/A',
                    'is_receivable' => isset($data['is_receivable']) && $data['is_receivable'],
                    'vendor' => isset($data['vendor_id']) ? Vendor::find($data['vendor_id'])->nama_vendor : null,
                    'notes' => $data['notes'] ?? ''
                ];

                // Generate PDF dan Download
                return $this->generateReceipt($receiptData);
            }

            // Bersihkan keranjang
            $this->clearCart();

            // Tutup modal
            $this->dispatch('close-checkout-modal');
            $this->dispatch('close-modal', id: 'checkout-modal');

        } catch (\Exception $e) {
            // Rollback transaksi
            DB::rollBack();

            // Log error
            Log::error('Checkout error: ' . $e->getMessage());

            // Beri notifikasi error
            Notification::make()
                ->title('Terjadi kesalahan')
                ->body('Transaksi gagal: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Generate receipt PDF
     */
    protected function generateReceipt($data)
    {
        // Ambil data tenant
        $tenant = \App\Models\Tenant::find(auth()->user()->tenant_id);

        // Tambahkan informasi tenant ke data
        $data['tenant_name'] = $tenant->name ?? 'N/A';
        $data['tenant_phone'] = $tenant->phone ?? 'N/A';
        $data['tenant_address'] = $tenant->address ?? 'N/A';
        $data['nota_color'] = $tenant->nota_colour ?? '#4a8c36';

        // Generate PDF
        $pdf = PDF::loadView('receipts.kasir', $data);

        // Send for download
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'nota_' . now()->format('YmdHis') . '.pdf');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
    
    /**
     * Menyimpan data keranjang ke session untuk keperluan print thermal
     * 
     * @param string|null $typeId
     * @param bool|null $isPiutang
     * @param string|null $vendorId
     * @param string|null $notes
     * @param string|null $buyerName
     * @param string|null $cashierName
     * @param string|null $transactionTime
     * @return array
     */
    public function saveCartForPrinting($typeId = null, $isPiutang = false, $vendorId = null, $notes = null, $buyerName = null, $cashierName = null, $transactionTime = null)
    {
        // Format data items
        $items = [];
        $total = 0;
        
        foreach ($this->cartItems as $item) {
            $items[] = [
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'price' => $item['price']
            ];
            
            $total += $item['price'] * $item['quantity'];
        }
        
        // Ambil nama metode pembayaran jika $typeId disediakan
        $paymentMethod = 'Tunai';
        if ($typeId) {
            $paymentType = \App\Models\CashInOutType::find($typeId);
            if ($paymentType) {
                $paymentMethod = $paymentType->name;
            }
        }
        
        // Ambil nama vendor jika $vendorId disediakan dan transaksi adalah piutang
        $vendorName = null;
        if ($isPiutang && $vendorId) {
            $vendor = \App\Models\Vendor::find($vendorId);
            if ($vendor) {
                $vendorName = $vendor->name;
            }
        }
        
        // Format tanggal transaksi
        $formattedDate = now()->format('d/m/Y H:i');
        if ($transactionTime) {
            try {
                $dateTime = \Carbon\Carbon::parse($transactionTime);
                $formattedDate = $dateTime->format('d/m/Y H:i');
            } catch (\Exception $e) {
                // Gunakan format default jika parsing gagal
            }
        }
        
        // Simpan data ke session
        session([
            'print_items' => $items,
            'print_total' => $total,
            'print_transaction_id' => 'TRX' . now()->format('YmdHis'),
            'print_transaction_date' => $formattedDate,
            'print_payment_method' => $paymentMethod,
            'print_is_receivable' => $isPiutang,
            'print_vendor_id' => $vendorId,
            'print_vendor_name' => $vendorName,
            'print_notes' => $notes,
            'print_buyer_name' => $buyerName,
            'print_cashier_name' => $cashierName ?? auth()->user()->name ?? 'Admin',
            'print_custom_time' => !empty($transactionTime),
            'print_add_buyer_info' => !empty($buyerName) || !empty($cashierName),
        ]);
        
        return ['success' => true];
    }
}
