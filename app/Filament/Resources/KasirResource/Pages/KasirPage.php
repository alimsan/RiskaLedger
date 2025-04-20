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

    public function processCheckout($data)
    {
        // Proses transaksi
        $tenantId = auth()->user()->tenant_id;
        $userId = auth()->id();
        $typeId = $data['type_id'];
        $notes = $data['notes'] ?? '';
        $total = $this->cartTotal();

        try {
            // Buat daftar item yang dibeli
            $itemsDetails = [];
            foreach ($this->cartItems as $item) {
                $itemsDetails[] = $item['name'] . ' (x' . $item['quantity'] . ')';
            }

            // Simpan transaksi ke database
            $transaction = new mCashInOut();
            $transaction->tenant_id = $tenantId;
            $transaction->type_id = $typeId;
            $transaction->nama_barang = 'Penjualan Kasir';
            $transaction->deksripsi = implode(', ', $itemsDetails);
            $transaction->keterangan = $notes;
            $transaction->nilai = $total;
            $transaction->waktu = Carbon::now();
            $transaction->save();

            // Bersihkan keranjang
            $this->clearCart();

            // Beri notifikasi sukses
            Notification::make()
                ->title('Transaksi berhasil dicatat')
                ->success()
                ->send();

            return true;
        } catch (\Exception $e) {
            Notification::make()
                ->title('Terjadi kesalahan')
                ->body('Transaksi gagal: ' . $e->getMessage())
                ->danger()
                ->send();

            return false;
        }
    }

    protected function getHeaderActions(): array
    {
        return [];
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

        // Proses checkout
        $success = $this->processCheckout($data);

        if ($success) {
            // Notifikasi sudah ditampilkan oleh processCheckout
            $this->dispatch('close-checkout-modal');
        }
    }
}
