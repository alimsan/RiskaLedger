<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TransactionItems;
use Carbon\Carbon;

class TransactionItemsController extends Controller
{
    public function getDetail(Request $request)
    {
        $tenantId = $request->input('tenant_id');
        $date = $request->input('date');
        $month = $request->input('month', Carbon::now()->format('Y-m'));

        // Parse bulan yang dipilih
        try {
            $carbonDate = Carbon::createFromFormat('Y-m', $month);
            $startOfMonth = $carbonDate->copy()->startOfMonth();
            $endOfMonth = $carbonDate->copy()->endOfMonth();
        } catch (\Exception $e) {
            // Default ke bulan ini jika ada error
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();

            // Log error untuk debugging
            \Log::error("Error parsing date in TransactionItemsController: " . $e->getMessage());
        }

        // Query dasar
        $query = TransactionItems::with('item')
            ->whereBetween('waktu', [
                $startOfMonth->startOfDay()->toDateTimeString(),
                $endOfMonth->endOfDay()->toDateTimeString()
            ]);

        // Filter berdasarkan tenant (melalui relasi cash_in_out atau piutang)
        // Kita perlu membuat join atau subquery disini karena tidak ada tenant_id di transaction_items

        // Jika parameter date adalah spesifik tanggal (bukan 'all')
        if ($date !== 'all') {
            try {
                // Format tanggal dari dd-mm-yyyy ke yyyy-mm-dd
                $dateParts = explode('-', $date);
                if (count($dateParts) === 3) {
                    $formattedDate = $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0];
                    $query->whereDate('waktu', $formattedDate);
                }
            } catch (\Exception $e) {
                \Log::error("Error parsing specific date: " . $e->getMessage());
            }
        }

        // Ambil data dengan join untuk mendapatkan tenant_id dan nama item
        $items = $query->join('items', 'transaction_items.item_id', '=', 'items.id')
            ->where('items.tenant_id', $tenantId)
            ->select(
                'transaction_items.*',
                'items.name as item_name'
            )
            ->orderBy('waktu', 'desc')
            ->get();

        // Format data untuk respons JSON
        $result = $items->map(function ($item) {
            return [
                'id' => $item->id,
                'item_id' => $item->item_id,
                'item_name' => $item->item_name,
                'transaction_id' => $item->transaction_id,
                'transaction_type' => $item->transaction_type,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'subtotal' => $item->subtotal,
                'tanggal' => Carbon::parse($item->waktu)->format('d-m-Y'),
            ];
        });

        return response()->json($result);
    }
}
