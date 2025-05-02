<?php

namespace App\Filament\Resources\KasirResource\Pages;

use App\Filament\Resources\KasirResource;
use App\Models\Item;
use App\Models\CashInOutType;
use App\Models\mCashInOut;
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

    public function addToCart($itemId)
    {
        $item = Item::find($itemId);

        if (!$item) {
            return;
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
            $this->cartItems[$itemId]['quantity'] = $quantity;
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
                $piutang->waktu = now();
                $piutang->lunas = false;
                $piutang->save();

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

                // Simpan transaksi ke database
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
                $transaction->waktu = Carbon::now();
                $transaction->save();

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
                    'transaction_date' => Carbon::now()->format('d-m-Y H:i:s'),
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
}
