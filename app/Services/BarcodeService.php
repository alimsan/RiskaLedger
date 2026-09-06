<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Exception;

class BarcodeService
{
    /**
     * Generate barcode unik (12 digit: 899 + 9 angka acak) yang belum digunakan produk lain
     */
    public static function generateUniqueBarcode(?int $tenantId = null): string
    {
        do {
            $code = '899' . str_pad((string) random_int(100000000, 999999999), 9, '0', STR_PAD_LEFT);
            
            $query = Item::where('barcode', $code);
            if ($tenantId) {
                $query->where('tenant_id', $tenantId);
            }
            $exists = $query->exists();
        } while ($exists);

        return $code;
    }

    /**
     * Mendapatkan binary PNG Barcode (Code 128)
     */
    public static function getPng(string $code, int $widthFactor = 2, int $totalHeight = 45): ?string
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        try {
            $generator = new BarcodeGeneratorPNG();
            return $generator->getBarcode($code, BarcodeGeneratorPNG::TYPE_CODE_128, $widthFactor, $totalHeight);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Mendapatkan representasi Base64 PNG Barcode (Code 128)
     */
    public static function getPngBase64(string $code, int $widthFactor = 2, int $totalHeight = 45): ?string
    {
        $pngData = self::getPng($code, $widthFactor, $totalHeight);
        return $pngData ? base64_encode($pngData) : null;
    }

    /**
     * Render widget HTML barcode + angka di bawahnya untuk tampilan tabel datatable
     * Menggunakan PNG base64 murni agar kebal terhadap mode gelap (dark mode) dan stylesheet CSS
     */
    public static function renderHtml(string $code, int $widthFactor = 2, int $height = 34): string
    {
        $base64 = self::getPngBase64($code, $widthFactor, $height);
        if (!$base64) {
            return '<span class="text-xs text-gray-400 italic">Format invalid</span>';
        }

        return '
        <div class="inline-flex flex-col items-center justify-center px-3 py-1.5 rounded-md shadow-sm" style="background-color: #ffffff !important; min-width: 140px; max-width: 180px; border: 1px solid #d1d5db !important;">
            <img src="data:image/png;base64,' . $base64 . '" alt="Barcode ' . htmlspecialchars($code) . '" style="display: block; height: ' . $height . 'px; max-width: 100%; object-fit: contain; margin: 0 auto; background: transparent;" />
            <span style="color: #111827 !important; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 11px; font-weight: 700; letter-spacing: 1.5px; margin-top: 3px; display: block; text-align: center;">' . htmlspecialchars($code) . '</span>
        </div>';
    }

    /**
     * Generate & download PDF lembaran label barcode rapi siap potong & tempel
     *
     * @param iterable|Item[] $items
     * @param int|string $copies int fixed count or 'by_stock'
     * @param bool $includePrice
     * @return StreamedResponse
     */
    public static function downloadPdf($items, $copies = 1, bool $includePrice = true): StreamedResponse
    {
        $tenantId = auth()->user()->tenant_id ?? null;
        $tenant = $tenantId ? Tenant::find($tenantId) : null;
        $tenantName = $tenant ? $tenant->name : 'POS Store';

        $labels = [];

        foreach ($items as $item) {
            // Abaikan item tanpa barcode
            $barcode = trim((string) $item->barcode);
            if ($barcode === '') {
                continue;
            }

            $count = ($copies === 'by_stock') ? max(1, (int) $item->stock) : max(1, (int) $copies);
            $barcodeBase64 = self::getPngBase64($barcode, 2, 45);

            if (!$barcodeBase64) {
                continue;
            }

            for ($i = 0; $i < $count; $i++) {
                $labels[] = [
                    'name' => $item->name,
                    'price' => $item->price,
                    'barcode' => $barcode,
                    'barcode_base64' => $barcodeBase64,
                    'tenant_name' => $tenantName,
                ];
            }
        }

        $pdf = Pdf::loadView('barcodes.labels', [
            'labels' => $labels,
            'includePrice' => $includePrice,
            'tenantName' => $tenantName,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $filename = 'label_barcode_' . date('Ymd_His') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }
}
