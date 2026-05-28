<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\mCashInOut;
use App\Models\CashInOutType;

class BBMakassarController extends Controller
{
    public function getDetails(Request $request)
    {
        $date = $request->input('date');

        if (!$date) {
            return response()->json(['error' => 'Tanggal tidak valid'], 400);
        }

        // Cari Tipe BB_MAKASSAR
        $typeId = CashInOutType::where('code', 'BB_MAKASSAR')->first()?->id;

        if (!$typeId) {
            return response()->json([], 200);
        }

        // Cari tenant_id jika ada
        $tenant_id = $request->input('tenant_id');

        $query = mCashInOut::where('type_id', $typeId)
            ->whereDate('waktu', $date);

        // Filter berdasarkan tenant jika ada
        if ($tenant_id) {
            $query->where('tenant_id', $tenant_id);
        }

        $details = $query->orderBy('waktu')
            ->select(['id', 'waktu', 'keterangan', 'nilai'])
            ->get();

        return response()->json($details, 200);
    }
}
