<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Services\BarcodeService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BarcodeController extends Controller
{
    /**
     * Preview barcode secara realtime dalam format gambar PNG murni
     */
    public function preview(Request $request)
    {
        $code = trim((string) $request->query('code', ''));
        if ($code === '') {
            return response('', Response::HTTP_NO_CONTENT);
        }

        $png = BarcodeService::getPng($code, 2, 45);
        if (!$png) {
            return response('Invalid barcode', Response::HTTP_BAD_REQUEST);
        }

        return response($png, Response::HTTP_OK, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-cache, private',
        ]);
    }

    /**
     * Export / Download PDF lembaran label barcode rapi
     */
    public function exportPdf(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            abort(403, 'Akses tidak diizinkan');
        }

        $ids = $request->query('ids');
        $copies = $request->query('copies', 1);
        $includePrice = (bool) $request->query('include_price', 1);
        $filter = $request->query('filter', 'all');

        $query = Item::query()->whereNotNull('barcode')->where('barcode', '!=', '');

        if (!$user->isAdministrator()) {
            $query->where('tenant_id', $user->tenant_id);
        }

        if (!empty($ids)) {
            $idArray = is_array($ids) ? $ids : explode(',', $ids);
            $query->whereIn('id', $idArray);
        } else {
            if ($filter === 'with_stock') {
                $query->where('stock', '>', 0);
            }
            $query->where('is_active', true);
        }

        $items = $query->orderBy('name', 'asc')->get();

        if ($items->isEmpty()) {
            return back()->with('error', 'Tidak ada produk yang memiliki barcode untuk dicetak.');
        }

        return BarcodeService::downloadPdf($items, $copies, $includePrice);
    }
}
