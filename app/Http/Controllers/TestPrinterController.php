<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\mCashInOut;
use App\Models\Tenant;
use App\Models\TransactionItems;
use App\Models\CashInOutType;
use App\Models\Vendor;

class TestPrinterController extends Controller
{
    public function index()
    {
        // Data contoh untuk testing
        $items = [
            [
                'name' => 'Produk Contoh 1',
                'price' => 25000,
                'quantity' => 2
            ],
            [
                'name' => 'Produk Contoh 2',
                'price' => 15000,
                'quantity' => 1
            ],
            [
                'name' => 'Produk Contoh 3',
                'price' => 40000,
                'quantity' => 3
            ],
        ];

        // Hitung total
        $total = 0;
        foreach($items as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        return view('test-printer', [
            'items' => $items,
            'total' => $total,
            'transaction_id' => 'TRX' . date('YmdHis'),
            'transaction_date' => date('Y-m-d H:i:s')
        ]);
    }

    public function printThermal(Request $request, $id = null)
    {
        // Jika ada ID transaksi, ambil data dari database
        if ($id) {
            // Coba ambil dari transaksi penjualan (cash_in_out)
            $transaction = mCashInOut::find($id);
            $transactionType = 'penjualan';
            
            // Jika tidak ditemukan, mungkin dari tabel piutang
            if (!$transaction) {
                $transaction = \App\Models\Piutang::find($id);
                $transactionType = 'piutang';
            }
            
            if ($transaction) {
                // Ambil items dari transaksi
                $transactionItems = TransactionItems::where('transaction_id', $id)
                    ->where('transaction_type', $transactionType)
                    ->get();
                
                $items = [];
                $subtotal = 0;
                
                foreach ($transactionItems as $item) {
                    $product = \App\Models\Item::find($item->item_id);
                    $itemName = $product ? $product->name : 'Item #' . $item->item_id;
                    
                    $items[] = [
                        'name' => $itemName,
                        'price' => $item->price,
                        'quantity' => $item->quantity
                    ];
                    
                    $subtotal += $item->subtotal;
                }

                $finalTotal = $transactionType === 'piutang' ? ($transaction->total_utang ?? 0) : ($transaction->nilai ?? 0);
                $discount = max(0, $subtotal - $finalTotal);
                
                // Ambil data tenant
                $tenant = Tenant::find(auth()->user()->tenant_id);
                
                // Ambil jenis pembayaran
                $paymentMethod = CashInOutType::find($transaction->type_id)->name ?? 'Tunai';
                
                // Format tanggal
                $transactionDate = $transaction->waktu ? $transaction->waktu->format('d/m/Y H:i') : date('d/m/Y H:i');
                
                return view('thermal-printer', [
                    'items' => $items,
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'total' => $finalTotal,
                    'transaction_id' => $id,
                    'transaction_date' => $transactionDate,
                    'payment_method' => $paymentMethod,
                    'tenant_name' => $tenant->name ?? 'TOKO CONTOH',
                    'tenant_phone' => $tenant->phone ?? '081234567890',
                    'tenant_address' => $tenant->address ?? 'Jl. Contoh No. 123',
                    'cashier_name' => auth()->user()->name ?? 'Admin'
                ]);
            }
        }
        
        // Jika tidak ada ID atau transaksi tidak ditemukan, gunakan data dari session
        $items = session('print_items', []);
        $subtotal = (float) session('print_subtotal', 0);
        $discount = (float) ($request->get('discount') ?? session('print_discount', 0));
        $total = (float) session('print_total', 0);

        if (!$subtotal) {
            foreach ($items as $item) {
                $subtotal += ($item['price'] * $item['quantity']);
            }
        }
        if (!$total) {
            $total = max(0, $subtotal - $discount);
        }

        $transaction_id = session('print_transaction_id', 'TRX' . date('YmdHis'));
        $transaction_date = session('print_transaction_date', date('d/m/Y H:i'));
        $payment_method = session('print_payment_method', 'Tunai');
        $buyer_name = session('print_buyer_name', '');
        $cashier_name = session('print_cashier_name', auth()->user()->name ?? 'Admin');
        
        // Ambil data tenant
        $tenant = Tenant::find(auth()->user()->tenant_id);
        
        return view('thermal-printer', [
            'items' => $items,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'transaction_id' => $transaction_id,
            'transaction_date' => $transaction_date,
            'payment_method' => $payment_method,
            'buyer_name' => $buyer_name,
            'cashier_name' => $cashier_name,
            'tenant_name' => $tenant->name ?? 'TOKO CONTOH',
            'tenant_phone' => $tenant->phone ?? '081234567890',
            'tenant_address' => $tenant->address ?? 'Jl. Contoh No. 123'
        ]);
    }
}
