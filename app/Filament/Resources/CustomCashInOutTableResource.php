<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomCashInOutTableResource\Pages;
use App\Models\mCashInOut;
use App\Models\CashInOutType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\Filter;
use Carbon\Carbon;
use Illuminate\Support\Str;

class CustomCashInOutTableResource extends Resource
{
    protected static ?string $model = mCashInOut::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Tabel Rekap';
    protected static ?string $modelLabel = 'Tabel Rekap';
    protected static ?string $pluralModelLabel = 'Tabel Rekap';


    public static function canCreate(): bool
    {
        return false;
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Form tidak digunakan untuk resource ini
            ]);
    }

    protected static function getTotalByType(array $types, $tanggal, $tenantId = null)
    {
        // Pastikan tipe valid
        if (empty($types)) {
            \Log::warning("Array tipe kosong di getTotalByType");
            return 0;
        }

        // Ambil type_id dari type code
        $typeIds = CashInOutType::whereIn('code', $types)->pluck('id');

        // Jika tidak ada tipe yang ditemukan, return 0
        if ($typeIds->isEmpty()) {
            \Log::warning("Tidak ada type_id ditemukan untuk codes: " . implode(', ', $types));
            return 0;
        }

        $query = mCashInOut::whereIn('type_id', $typeIds)
            ->whereDate('waktu', $tanggal);

        // Filter berdasarkan tenant jika ada
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->sum('nilai');
    }

    protected static function getTotalNilai(array $types, $tenantId = null)
    {
        // Pastikan tipe valid
        if (empty($types)) {
            \Log::warning("Array tipe kosong di getTotalNilai");
            return 0;
        }

        // Ambil type_id dari type code
        $typeIds = CashInOutType::whereIn('code', $types)->pluck('id');

        // Jika tidak ada tipe yang ditemukan, return 0
        if ($typeIds->isEmpty()) {
            \Log::warning("Tidak ada type_id ditemukan untuk codes: " . implode(', ', $types));
            return 0;
        }

        $query = mCashInOut::whereIn('type_id', $typeIds);

        // Filter berdasarkan tenant jika ada
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $selectedMonth = session('selected_month');
        if ($selectedMonth) {
            try {
                // Parse tanggal dari session
                $date = \Carbon\Carbon::parse($selectedMonth);

                // Set range tanggal untuk bulan yang dipilih
                $startDate = $date->copy()->startOfMonth()->startOfDay();
                $endDate = $date->copy()->endOfMonth()->endOfDay();

                // Filter berdasarkan range tanggal
                $query->whereBetween('waktu', [
                    $startDate->toDateTimeString(),
                    $endDate->toDateTimeString()
                ]);
            } catch (\Exception $e) {
                // Log error jika ada masalah dengan format tanggal
                \Log::error('Error in getTotalNilai: ' . $e->getMessage());
            }
        }

        return $query->sum('nilai');
    }

    public static function getTableData($startDate, $endDate, $tenantId = null)
    {
        // Inisialisasi query dasar
        $query = mCashInOut::query()
            ->with('type') // Eager load type relation
            ->whereDate('waktu', '>=', $startDate)
            ->whereDate('waktu', '<=', $endDate)
            ->orderBy('waktu', 'asc');

        // Filter berdasarkan tenant jika ada
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        // Ambil data kasbok
        $cash_in_out = $query->get();

        // Buat array dari rentang tanggal
        $dates = new \DatePeriod(
            new \DateTime($startDate),
            new \DateInterval('P1D'),
            (new \DateTime($endDate))->modify('+1 day')
        );

        // Definisikan tipe pengeluaran dan pendapatan
        $expenseTypes = CashInOutType::where('is_income', false)
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->get();

        $incomeTypes = CashInOutType::where('is_income', true)
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->get();

        // Bentuk untuk pencarian cepat tipe berdasarkan ID
        $typeById = [];
        foreach ($incomeTypes as $type) {
            $typeById[$type->id] = $type;
        }
        foreach ($expenseTypes as $type) {
            $typeById[$type->id] = $type;
        }

        $result = [];

        foreach ($dates as $date) {
            $date_str = $date->format('Y-m-d');
            $tanggal = $date->format('d-m-Y');

            // Inisialisasi data untuk tanggal ini
            $data = new \stdClass();
            $data->tanggal = $tanggal;
            $data->penjualan = 0;
            $data->total_qty = 0; // Inisialisasi total quantity

            // Untuk debugging jika tanggal 02-05-2025
            $debugInfo = [];
            $isDebugDate = ($date_str == '2025-05-02');

            if ($isDebugDate) {
                \Log::info("======== MEMULAI DEBUG TANGGAL 02-05-2025 ========");
            }

            // Inisialisasi kolom untuk tipe pendapatan
            foreach ($incomeTypes as $type) {
                $code = strtolower($type->code);
                $data->$code = 0;
            }

            // Inisialisasi kolom untuk tipe pengeluaran
            foreach ($expenseTypes as $type) {
                $code = strtolower($type->code);
                $data->$code = 0;
            }

            // Jumlah = pendapatan - pengeluaran
            $data->tb1_jumlah = 0;

            // Tracking transaksi untuk mencegah duplikasi
            $processedTransactions = [];

            // Filter data untuk tanggal ini
            $filtered_data = $cash_in_out->filter(function ($item) use ($date_str) {
                return date('Y-m-d', strtotime($item->waktu)) == $date_str;
            });

            if ($isDebugDate) {
                \Log::info("Jumlah transaksi pada 02-05-2025: " . $filtered_data->count());
            }

            // Hitung penjualan dan kategorikan transaksi
            $totalPendapatanHariIni = 0;
            $totalPengeluaranHariIni = 0;

            // Hitung total quantity dari transaction_items berdasarkan tanggal saja
            $totalQty = \App\Models\TransactionItems::whereDate('waktu', $date_str)
                ->join('items', 'transaction_items.item_id', '=', 'items.id')
                ->where('items.tenant_id', $tenantId)
                ->sum('quantity');

            $data->total_qty = $totalQty;

            foreach ($filtered_data as $item) {
                try {
                    // Pastikan type_id ada dan valid
                    if (!$item->type_id || !isset($typeById[$item->type_id])) {
                        \Log::warning("Item ID {$item->id} memiliki type_id tidak valid: {$item->type_id}");
                        continue;
                    }

                    // Cek apakah transaksi ini sudah diproses (mencegah duplikasi)
                    $transactionKey = $item->id . '-' . $item->type_id;
                    if (in_array($transactionKey, $processedTransactions)) {
                        if ($isDebugDate) {
                            \Log::warning("Melewati transaksi duplikat: " . $transactionKey);
                        }
                        continue;
                    }
                    $processedTransactions[] = $transactionKey;

                    $type = $typeById[$item->type_id];
                    $code = strtolower($type->code);

                    // Debug info
                    if ($isDebugDate) {
                        $debugInfo[] = [
                            'id' => $item->id,
                            'type_id' => $item->type_id,
                            'type_name' => $type->name,
                            'code' => $code,
                            'is_income' => $type->is_income ? 'Ya' : 'Tidak',
                            'nilai' => $item->nilai,
                            'waktu' => $item->waktu
                        ];
                    }

                    // Langsung hitung pendapatan atau pengeluaran
                    if ($type->is_income) {
                        $totalPendapatanHariIni += $item->nilai;
                        $data->penjualan += $item->nilai;
                    } else {
                        $totalPengeluaranHariIni += $item->nilai;
                    }

                    // Tambahkan ke kategori yang sesuai
                    if (property_exists($data, $code)) {
                        $data->$code += $item->nilai;
                    } else {
                        \Log::warning("Property $code tidak ditemukan untuk type_id {$item->type_id}");
                    }
                } catch (\Exception $e) {
                    \Log::error("Error processing item: " . $e->getMessage(), [
                        'item_id' => $item->id ?? 'unknown',
                        'date' => $date_str
                    ]);
                }
            }

            // PERBAIKAN: Gunakan langsung total pendapatan - total pengeluaran
            // tanpa menghitung ulang dari kategori untuk menghindari duplikasi
            $data->tb1_jumlah = $totalPendapatanHariIni - $totalPengeluaranHariIni;

            // Hanya untuk verifikasi dan debugging
            $totalPendapatan = 0;
            $totalPengeluaran = 0;
            $logItemPengeluaran = [];

            // Hitung total pengeluaran untuk log/debugging
            foreach ($expenseTypes as $type) {
                $code = strtolower($type->code);
                if (property_exists($data, $code) && $data->$code > 0) {
                    $totalPengeluaran += $data->$code;
                    if ($isDebugDate) {
                        \Log::info("Pengeluaran {$type->name} ({$code}): " . $data->$code);
                        $logItemPengeluaran[] = "Tipe: {$type->name}, Kode: {$code}, Nilai: {$data->$code}";
                    }
                }
            }

            // Tambahkan log untuk debugging
            if ($isDebugDate) {
                \Log::info("HASIL PERBAIKAN - Tanggal: " . $tanggal .
                           ", Pendapatan: " . $totalPendapatanHariIni .
                           ", Pengeluaran: " . $totalPengeluaranHariIni .
                           ", tb1_jumlah: " . $data->tb1_jumlah);
                \Log::info("VERIFIKASI - Penjualan: " . $data->penjualan . ", Total Pengeluaran (verifikasi): " . $totalPengeluaran);

                if (empty($logItemPengeluaran)) {
                    \Log::info("DETAIL PENGELUARAN - Tanggal: " . $tanggal . " - Tidak ada pengeluaran");
                } else {
                    \Log::info("DETAIL PENGELUARAN - Tanggal: " . $tanggal . ", Items: " . implode(", ", $logItemPengeluaran));
                }

                // Log semua transaksi pada tanggal ini
                foreach ($debugInfo as $index => $info) {
                    \Log::info("TRANSAKSI #{$index} - " . json_encode($info, JSON_PRETTY_PRINT));
                }

                \Log::info("======== SELESAI DEBUG TANGGAL 02-05-2025 ========");
            }

            $result[] = $data;
        }

        return $result;
    }

    public static function getTotaldata($tenantId = null)
    {
        // Ambil tipe pendapatan dan pengeluaran yang aktif
        $incomeTypes = CashInOutType::where('is_income', true)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get();

        $expenseTypes = CashInOutType::where('is_income', false)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get();

        // Bentuk untuk pencarian cepat tipe berdasarkan ID
        $typeById = [];
        foreach ($incomeTypes as $type) {
            $typeById[$type->id] = $type;
        }
        foreach ($expenseTypes as $type) {
            $typeById[$type->id] = $type;
        }

        $result = [];
        $result['total_penjualan'] = 0;
        $result['total_pengeluaran'] = 0;
        $result['total_income'] = 0;
        $result['total_qty'] = 0; // Inisialisasi total quantity

        // Inisialisasi total untuk semua tipe pendapatan
        foreach ($incomeTypes as $type) {
            $code = strtolower($type->code);
            $result['total_' . $code] = 0;
        }

        // Inisialisasi total untuk semua tipe pengeluaran
        foreach ($expenseTypes as $type) {
            $code = strtolower($type->code);
            $result['total_' . $code] = 0;
        }

        // Ambil bulan yang dipilih dari session
        $selectedMonth = session('selected_month', now()->format('Y-m'));

        try {
            // Parse tanggal dari session atau gunakan bulan ini
            $date = Carbon::createFromFormat('Y-m', $selectedMonth);
            $startDate = $date->copy()->startOfMonth();
            $endDate = $date->copy()->endOfMonth();
        } catch (\Exception $e) {
            // Default ke bulan ini jika ada error
            $startDate = now()->startOfMonth();
            $endDate = now()->endOfMonth();
            \Log::error("Error parsing date in getTotaldata: " . $e->getMessage());
        }

        // Query data dengan filter tenant dan tanggal
        $query = mCashInOut::query()
            ->with('type') // Eager load type
            ->whereDate('waktu', '>=', $startDate)
            ->whereDate('waktu', '<=', $endDate);

        // Filter berdasarkan tenant jika ada
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        // Ambil data
        $cashinouts = $query->get();

        // Hitung total quantity untuk periode ini berdasarkan range tanggal
        $result['total_qty'] = \App\Models\TransactionItems::whereBetween('waktu', [
                $startDate->startOfDay()->toDateTimeString(),
                $endDate->endOfDay()->toDateTimeString()
            ])
            ->join('items', 'transaction_items.item_id', '=', 'items.id')
            ->where('items.tenant_id', $tenantId)
            ->sum('quantity');

        // Hitung total per kategori
        foreach ($cashinouts as $item) {
            try {
                // Pastikan type_id ada dan valid
                if (!$item->type_id || !isset($typeById[$item->type_id])) {
                    \Log::warning("Item ID {$item->id} memiliki type_id tidak valid: {$item->type_id}");
                    continue;
                }

                $type = $typeById[$item->type_id];
                $code = strtolower($type->code);

                // Cek apakah ini pendapatan
                if ($type->is_income) {
                    $result['total_income'] += $item->nilai;
                    $result['total_penjualan'] += $item->nilai;
                } else {
                    $result['total_pengeluaran'] += $item->nilai;
                }

                // Tambahkan ke kategori yang sesuai
                $key = 'total_' . $code;
                if (isset($result[$key])) {
                    $result[$key] += $item->nilai;
                } else {
                    \Log::warning("Key $key tidak ditemukan untuk type_id {$item->type_id}");
                }
            } catch (\Exception $e) {
                \Log::error("Error processing item in getTotaldata: " . $e->getMessage(), [
                    'item_id' => $item->id ?? 'unknown'
                ]);
            }
        }

        // Hitung laba kotor
        $result['total_laba'] = $result['total_income'] - $result['total_pengeluaran'];

        // Ambil data tenant dan pembagian hasil
        $tenant = \App\Models\Tenant::with('profitSharings')
            ->where('id', $tenantId)
            ->first();

        if ($tenant) {
            $profitSharings = $tenant->profitSharings()
                ->where('is_active', true)
                ->orderBy('is_default', 'desc')
                ->get();

            // Tambahkan data pembagian hasil ke array data
            foreach ($profitSharings as $sharing) {
                $key = 'total_laba_' . Str::slug($sharing->name);
                $result[$key] = $result['total_laba'] * ($sharing->percentage / 100);
            }

            // Jika tidak ada pembagian hasil, gunakan default 80/20
            if ($profitSharings->isEmpty()) {
                $result['total_laba_80'] = $result['total_laba'] * 0.8;
                $result['total_laba_20'] = $result['total_laba'] * 0.2;
            }
        } else {
            // Fallback ke 80/20 jika tidak ada tenant
            $result['total_laba_80'] = $result['total_laba'] * 0.8;
            $result['total_laba_20'] = $result['total_laba'] * 0.2;
        }

        return $result;
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        // Admin/superadmin dan user dengan tenant_id dapat mengakses resource ini
        return $user && ($user->isAdministrator() || $user->tenant_id);
    }

    public static function table(Table $table): Table
    {
        $selectedMonth = session('selected_month', now()->format('Y-m'));

        // Ambil user yang sedang login
        $user = auth()->user();

        // Tentukan tenant_id berdasarkan role
        $tenantId = null;

        if ($user->isAdministrator()) {
            // Untuk admin, ambil tenant_id dari session jika ada
            $tenantId = session('selected_tenant_id');
        } else {
            // Untuk tenant, gunakan tenant_id mereka
            $tenantId = $user->tenant_id;
        }

        // Parse tanggal
        try {
            $date = Carbon::createFromFormat('Y-m', $selectedMonth);
            $startDate = $date->copy()->startOfMonth()->format('Y-m-d');
            $endDate = $date->copy()->endOfMonth()->format('Y-m-d');
        } catch (\Exception $e) {
            // Default ke bulan ini jika ada error parsing
            $startDate = now()->startOfMonth()->format('Y-m-d');
            $endDate = now()->endOfMonth()->format('Y-m-d');

            // Log error untuk debugging
            \Log::error("Error parsing date: " . $e->getMessage());
        }

        // Get tenant data
        $tenant = \App\Models\Tenant::find($tenantId);

        // Ambil data profit sharing untuk tenant
        $profitSharings = collect([]);
        if ($tenant) {
            try {
                $profitSharings = \App\Models\ProfitSharing::where('tenant_id', $tenant->id)
                    ->where('is_active', true)
                    ->orderBy('percentage', 'desc')
                    ->get();
            } catch (\Exception $e) {
                \Log::error("Error getting profit sharing: " . $e->getMessage());
            }
        }

        // Gunakan view yang berbeda berdasarkan role
        if ($user->isAdministrator()) {
        return $table
            ->view('filament.resources.custom-cash-in-out-table.table', [
                    'records' => static::getTableData($startDate, $endDate, $tenantId),
                    'totalData' => static::getTotaldata($tenantId),
                    'expenseTypes' => CashInOutType::where('is_income', false)
                                        ->where('is_active', true)
                                        ->when($tenantId, function($query) use ($tenantId) {
                                            $query->where(function($q) use ($tenantId) {
                                                $q->where('tenant_id', $tenantId)
                                                  ->orWhereNull('tenant_id');
                                            });
                                        }, function($query) {
                                            $query->whereNull('tenant_id');
                                        })
                                        ->orderBy('sort_order')
                                        ->get(),
                    'incomeTypes' => CashInOutType::where('is_income', true)
                                        ->where('is_active', true)
                                        ->when($tenantId, function($query) use ($tenantId) {
                                            $query->where(function($q) use ($tenantId) {
                                                $q->where('tenant_id', $tenantId)
                                                  ->orWhereNull('tenant_id');
                                            });
                                        }, function($query) {
                                            $query->whereNull('tenant_id');
                                        })
                                        ->orderBy('sort_order')
                                        ->get(),
                    'profitSharings' => $profitSharings,
                    'tenant' => $tenant,
                    'tenantId' => $tenantId,
                    'tenantName' => $tenant ? $tenant->name : 'Semua Tenant',
                    'formattedMonth' => Carbon::parse($selectedMonth)->format('F Y'),
                ])
                ->headerActions([])
                ->filters([
                    Filter::make('month')
                        ->form([
                            Forms\Components\DatePicker::make('month')
                                ->label('Bulan')
                                ->default(now())
                                ->displayFormat('F Y')
                                ->format('Y-m')
                        ])
                        ->query(function (Builder $query, array $data): Builder {
                            if (isset($data['month'])) {
                                session(['selected_month' => $data['month']]);
                                return $query;
                            }
                            return $query;
                        }),
                ])
                ->actions([])
                ->bulkActions([])
                ->poll('');
        } else {
            // Tetap gunakan view tenant untuk non-admin
            return $table
                ->view('filament.resources.custom-cash-in-out-table.tenant-table', [
                    'records' => static::getTableData($startDate, $endDate, $tenantId),
                    'totalData' => static::getTotaldata($tenantId),
                    'expenseTypes' => CashInOutType::where('is_income', false)
                                        ->where('is_active', true)
                                        ->where(function($query) use ($tenantId) {
                                            $query->where('tenant_id', $tenantId)
                                                  ->orWhereNull('tenant_id');
                                        })
                                        ->orderBy('sort_order')
                                        ->get(),
                    'incomeTypes' => CashInOutType::where('is_income', true)
                                        ->where('is_active', true)
                                        ->where(function($query) use ($tenantId) {
                                            $query->where('tenant_id', $tenantId)
                                                  ->orWhereNull('tenant_id');
                                        })
                                        ->orderBy('sort_order')
                                        ->get(),
                    'profitSharings' => $profitSharings,
                    'tenant' => $tenant,
                    'tenantId' => $tenantId,
                    'tenantName' => $tenant ? $tenant->name : '',
                    'formattedMonth' => Carbon::parse($selectedMonth)->format('F Y'),
                ])
                ->headerActions([])
            ->filters([
                    Filter::make('month')
                        ->form([
                            Forms\Components\DatePicker::make('month')
                                ->label('Bulan')
                                ->default(now())
                                ->displayFormat('F Y')
                                ->format('Y-m')
                        ])
                        ->query(function (Builder $query, array $data): Builder {
                            if (isset($data['month'])) {
                                session(['selected_month' => $data['month']]);
                                return $query;
                            }
                            return $query;
                        }),
                ])
                ->actions([])
                ->bulkActions([])
                ->poll('');
        }
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomCashInOutTables::route('/'),
            'create' => Pages\CreateCustomCashInOutTable::route('/create'),
            'edit' => Pages\EditCustomCashInOutTable::route('/{record}/edit'),
        ];
    }
}
