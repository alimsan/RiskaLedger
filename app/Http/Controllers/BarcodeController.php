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

    /**
     * Halaman antarmuka pencetakan label thermal Bluetooth (NIIMBOT B1)
     */
    public function thermalLabel(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            abort(403, 'Akses tidak diizinkan');
        }

        // Ambil konfigurasi dari query string atau session
        $payload = session('thermal_label_payload', []);
        $fromSession = (bool) $request->query('from_session', false) || (bool) $request->query('session', false);

        $ids = $request->query('ids', $fromSession ? ($payload['ids'] ?? null) : null);
        $copiesParam = $request->query('copies', $fromSession ? ($payload['copies'] ?? 1) : 1);
        $includePrice = filter_var(
            $request->query('include_price', $fromSession ? ($payload['include_price'] ?? true) : true),
            FILTER_VALIDATE_BOOLEAN
        );
        $labelSize = $request->query('label_size', $fromSession ? ($payload['label_size'] ?? '50x30') : '50x30');
        $density = (int) $request->query('density', $fromSession ? ($payload['density'] ?? 2) : 2);
        $filter = $request->query('filter', $fromSession ? ($payload['filter'] ?? 'all') : 'all');

        $query = Item::query()->whereNotNull('barcode')->where('barcode', '!=', '');

        if (!$user->isAdministrator()) {
            $query->where('tenant_id', $user->tenant_id);
        }

        if (!empty($ids)) {
            $idArray = is_array($ids) ? $ids : explode(',', (string) $ids);
            $query->whereIn('id', array_filter($idArray));
        } else {
            if ($filter === 'with_stock') {
                $query->where('stock', '>', 0);
            }
            $query->where('is_active', true);
        }

        $items = $query->orderBy('name', 'asc')->get();

        if ($items->isEmpty()) {
            return redirect()->route('filament.admin.resources.items.index')
                ->with('error', 'Tidak ada produk dengan barcode yang dapat dicetak.');
        }

        $itemsData = $items->map(function (Item $item) use ($copiesParam) {
            $itemCopies = $copiesParam === 'by_stock' 
                ? max(1, (int) $item->stock) 
                : max(1, (int) $copiesParam);

            $barcodeCode = trim((string) $item->barcode);
            $base64Barcode = BarcodeService::getPngBase64($barcodeCode, 2, 50);

            return [
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category ?? 'Produk',
                'barcode' => $barcodeCode,
                'price' => (float) $item->price,
                'formatted_price' => 'Rp ' . number_format((float) $item->price, 0, ',', '.') . ',00',
                'stock' => (int) $item->stock,
                'copies' => $itemCopies,
                'barcode_base64' => $base64Barcode ? 'data:image/png;base64,' . $base64Barcode : '',
            ];
        })->filter(fn ($item) => !empty($item['barcode_base64']))->values();

        $tenant = $user->tenant_id ? \App\Models\Tenant::find($user->tenant_id) : null;
        $storeName = $tenant ? $tenant->name : 'POS Store';

        return view('barcodes.thermal-label', [
            'itemsData' => $itemsData,
            'defaultCopies' => $copiesParam,
            'includePrice' => $includePrice,
            'labelSize' => $labelSize,
            'density' => $density,
            'storeName' => $storeName,
        ]);
    }
}
