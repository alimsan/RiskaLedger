<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\mCashInOut;
use App\Models\CashInOutType;
use App\Models\Tenant;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use App\Filament\Resources\CustomCashInOutTableResource;

class CashInOutDetailController extends Controller
{
    /**
     * Mendapatkan detail transaksi berdasarkan tipe
     */
    public function getDetail(Request $request, $type)
    {
        // Validasi input
        $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'month' => 'required|string'
        ]);

        // Ambil tenant ID dari request
        $tenantId = $request->input('tenant_id');
        $selectedMonth = $request->input('month', now()->format('Y-m'));

        // Cari tipe berdasarkan code
        $cashInOutType = CashInOutType::where('code', strtoupper($type))
            ->orWhere('code', $type)
            ->firstOrFail();

        try {
            // Parse tanggal dari bulan yang dipilih
            $date = Carbon::createFromFormat('Y-m', $selectedMonth);
            $startDate = $date->copy()->startOfMonth();
            $endDate = $date->copy()->endOfMonth();
        } catch (\Exception $e) {
            // Default ke bulan ini jika ada error
            $startDate = now()->startOfMonth();
            $endDate = now()->endOfMonth();
        }

        // Ambil data berdasarkan type_id, tenant_id, dan range tanggal
        $items = mCashInOut::where('type_id', $cashInOutType->id)
            ->where('tenant_id', $tenantId)
            ->whereDate('waktu', '>=', $startDate)
            ->whereDate('waktu', '<=', $endDate)
            ->orderBy('waktu', 'asc')
            ->get()
            ->map(function ($item) {
                // Format data untuk response
                return [
                    'id' => $item->id,
                    'nama_barang' => $item->nama_barang,
                    'deksripsi' => $item->deksripsi,
                    'nilai' => $item->nilai,
                    'formatted_nilai' => number_format($item->nilai, 0, ',', '.'),
                    'waktu' => $item->waktu,
                    'formatted_date' => Carbon::parse($item->waktu)->format('d-m-Y'),
                    'type_id' => $item->type_id
                ];
            });

        return response()->json($items);
    }

    /**
     * Ekspor data ke Excel
     */
    public function export(Request $request)
    {
        // Validasi input
        $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'month' => 'required|string'
        ]);

        // Ambil tenant ID dan bulan yang dipilih
        $tenantId = $request->input('tenant_id');
        $selectedMonth = $request->input('month', now()->format('Y-m'));

        // Ambil data tenant
        $tenant = Tenant::findOrFail($tenantId);

        try {
            // Parse tanggal dari bulan yang dipilih
            $date = Carbon::createFromFormat('Y-m', $selectedMonth);
            $startDate = $date->copy()->startOfMonth()->format('Y-m-d');
            $endDate = $date->copy()->endOfMonth()->format('Y-m-d');
        } catch (\Exception $e) {
            // Default ke bulan ini jika ada error
            $startDate = now()->startOfMonth()->format('Y-m-d');
            $endDate = now()->endOfMonth()->format('Y-m-d');
        }

        // Ambil data untuk laporan
        $records = CustomCashInOutTableResource::getTableData($startDate, $endDate, $tenantId);
        $totalData = CustomCashInOutTableResource::getTotaldata($tenantId);

        // Ambil tipe pendapatan dan pengeluaran aktif
        $incomeTypes = CashInOutType::where('is_income', true)
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->get();

        $expenseTypes = CashInOutType::where('is_income', false)
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->get();

        // Buat spreadsheet baru
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Arus Kas');

        // Judul laporan
        $sheet->setCellValue('A1', 'LAPORAN ARUS KAS - ' . $tenant->name);
        $sheet->setCellValue('A2', 'Periode: ' . Carbon::parse($selectedMonth)->format('F Y'));
        $sheet->mergeCells('A1:E1');
        $sheet->mergeCells('A2:E2');

        // Header
        $row = 4;
        $sheet->setCellValue('A' . $row, 'Tanggal');
        $sheet->setCellValue('B' . $row, 'Penjualan');

        $col = 'C';
        // Header untuk tipe pendapatan
        foreach ($incomeTypes as $type) {
            $sheet->setCellValue($col . $row, $type->name);
            $col++;
        }

        // Header untuk tipe pengeluaran
        foreach ($expenseTypes as $type) {
            $sheet->setCellValue($col . $row, $type->name);
            $col++;
        }

        $sheet->setCellValue($col . $row, 'Jumlah');

        // Data
        $row++;
        foreach ($records as $record) {
            $sheet->setCellValue('A' . $row, $record->tanggal);
            $sheet->setCellValue('B' . $row, $record->penjualan);

            $col = 'C';
            // Data untuk tipe pendapatan
            foreach ($incomeTypes as $type) {
                $code = strtolower($type->code);
                $sheet->setCellValue($col . $row, $record->$code ?? 0);
                $col++;
            }

            // Data untuk tipe pengeluaran
            foreach ($expenseTypes as $type) {
                $code = strtolower($type->code);
                $sheet->setCellValue($col . $row, $record->$code ?? 0);
                $col++;
            }

            $sheet->setCellValue($col . $row, $record->tb1_jumlah);
            $row++;
        }

        // Total
        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->setCellValue('B' . $row, $totalData['total_penjualan']);

        $col = 'C';
        // Total untuk tipe pendapatan
        foreach ($incomeTypes as $type) {
            $code = strtolower($type->code);
            $key = 'total_' . $code;
            $sheet->setCellValue($col . $row, $totalData[$key] ?? 0);
            $col++;
        }

        // Total untuk tipe pengeluaran
        foreach ($expenseTypes as $type) {
            $code = strtolower($type->code);
            $key = 'total_' . $code;
            $sheet->setCellValue($col . $row, $totalData[$key] ?? 0);
            $col++;
        }

        $sheet->setCellValue($col . $row, $totalData['total_laba']);

        // Ringkasan
        $row += 2;
        $sheet->setCellValue('A' . $row, 'Ringkasan:');
        $row++;
        $sheet->setCellValue('A' . $row, 'Total Pendapatan:');
        $sheet->setCellValue('B' . $row, $totalData['total_income']);
        $row++;
        $sheet->setCellValue('A' . $row, 'Total Pengeluaran:');
        $sheet->setCellValue('B' . $row, $totalData['total_pengeluaran']);
        $row++;
        $sheet->setCellValue('A' . $row, 'Total Laba:');
        $sheet->setCellValue('B' . $row, $totalData['total_laba']);

        // Format angka
        $lastColumn = $col;
        for ($i = 4; $i <= $row; $i++) {
            for ($j = 'B'; $j <= $lastColumn; $j++) {
                $sheet->getStyle($j . $i)->getNumberFormat()->setFormatCode('#,##0');
            }
        }

        // Buat file Excel
        $writer = new Xlsx($spreadsheet);
        $filename = 'Laporan_Arus_Kas_' . $tenant->name . '_' . Carbon::parse($selectedMonth)->format('F_Y') . '.xlsx';

        // Simpan ke temporary file
        $temp_file = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($temp_file);

        // Return file untuk didownload
        return response()->download($temp_file, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Mengubah tenant yang dipilih untuk melihat laporan
     */
    public function selectTenant(Request $request)
    {
        // Validasi request
        $request->validate([
            'tenant_id' => 'nullable|exists:tenants,id',
        ]);

        $tenantId = $request->input('tenant_id');

        // Simpan tenant_id yang dipilih ke session
        session(['selected_tenant_id' => $tenantId]);

        // Redirect kembali ke halaman tabel dengan cara yang lebih aman
        return redirect('/admin/custom-cash-in-out-tables');
    }
}
