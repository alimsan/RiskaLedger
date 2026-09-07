<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\mCashInOut;
use App\Models\Piutang;
use App\Models\Tenant;
use App\Models\TransactionItems;
use App\Models\CashInOutType;
use App\Models\Vendor;
use Barryvdh\DomPDF\Facade\Pdf;

class ReceiptController extends Controller
{
    /**
     * Download nota PDF kasir berdasarkan jenis transaksi dan ID
     */
    public function download($type, $id)
    {
        $user = auth()->user();
        if (!$user) {
            abort(403, 'Akses tidak diizinkan');
        }

        $tenantId = $user->tenant_id;
        $tenant = Tenant::find($tenantId);

        $items = [];
        $total = 0;
        $transactionDate = now()->format('d-m-Y H:i:s');
        $paymentMethod = 'N/A';
        $isReceivable = ($type === 'piutang');
        $vendorName = null;
        $notes = '';

        if ($type === 'piutang') {
            $record = Piutang::where('tenant_id', $tenantId)->find($id);
            if (!$record) {
                abort(404, 'Transaksi piutang tidak ditemukan');
            }
            $total = $record->total_utang;
            $transactionDate = $record->waktu ? $record->waktu->format('d-m-Y H:i:s') : now()->format('d-m-Y H:i:s');
            $paymentMethod = CashInOutType::find($record->type_id)->name ?? 'Piutang';
            $vendorName = Vendor::find($record->vendor_id)->nama_vendor ?? null;
        } else {
            $record = mCashInOut::where('tenant_id', $tenantId)->find($id);
            if (!$record) {
                abort(404, 'Transaksi penjualan tidak ditemukan');
            }
            $total = $record->nilai;
            $transactionDate = $record->waktu ? $record->waktu->format('d-m-Y H:i:s') : now()->format('d-m-Y H:i:s');
            $paymentMethod = CashInOutType::find($record->type_id)->name ?? 'Tunai';
            $notes = $record->keterangan ?? '';
        }

        $trxItems = TransactionItems::where('transaction_id', $id)
            ->where('transaction_type', $type)
            ->get();

        $subtotal = 0;
        foreach ($trxItems as $tItem) {
            $product = \App\Models\Item::find($tItem->item_id);
            $items[] = [
                'name' => $product ? $product->name : ('Item #' . $tItem->item_id),
                'price' => $tItem->price,
                'quantity' => $tItem->quantity,
            ];
            $subtotal += $tItem->price * $tItem->quantity;
        }

        $discount = max(0, $subtotal - $total);

        $receiptData = [
            'tenant_name' => $tenant->name ?? 'Toko',
            'tenant_phone' => $tenant->phone ?? '-',
            'tenant_address' => $tenant->address ?? '-',
            'nota_color' => $tenant->nota_colour ?? '#4a8c36',
            'transaction_date' => $transactionDate,
            'transaction_id' => $id,
            'payment_method' => $paymentMethod,
            'is_receivable' => $isReceivable,
            'vendor' => $vendorName,
            'notes' => $notes,
            'items' => $items,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
        ];

        $pdf = Pdf::loadView('receipts.kasir', $receiptData);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'nota_' . $type . '_' . $id . '.pdf');
    }
}
