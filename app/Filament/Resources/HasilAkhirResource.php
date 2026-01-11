<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HasilAkhirResource\Pages;
use App\Filament\Resources\HasilAkhirResource\RelationManagers;
use App\Models\mCashInOut;
use App\Models\CashInOutType;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Carbon\Carbon;

class HasilAkhirResource extends Resource
{
    protected static ?string $model = mCashInOut::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Hasil Akhir';
    protected static ?string $modelLabel = 'Hasil Akhir';
    protected static ?string $pluralModelLabel = 'Hasil Akhir';
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    /**
     * Mendapatkan total nilai transaksi berdasarkan type_id
     *
     * @param int $typeId
     * @param int|null $tenantId
     * @return float
     */
    protected static function getTotalByTypeId(int $typeId, ?int $tenantId = null)
    {
        $query = mCashInOut::where('type_id', $typeId);

        // Filter berdasarkan tenant jika disediakan
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $selectedMonth = session('selected_month');
        if ($selectedMonth) {
            try {
                // Parse tanggal dari session
                $date = Carbon::parse($selectedMonth);

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
                \Log::error('Error in getTotalByTypeId: ' . $e->getMessage());
            }
        }

        return $query->sum('nilai');
    }

    /**
     * Mendapatkan total nilai transaksi berdasarkan tipe lama (legacy)
     */
    protected static function getTotalNilai(array $types, ?int $tenantId = null)
    {
        $query = mCashInOut::whereIn('type', $types);

        // Filter berdasarkan tenant jika disediakan
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $selectedMonth = session('selected_month');
        if ($selectedMonth) {
            try {
                // Parse tanggal dari session
                $date = Carbon::parse($selectedMonth);

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

    public static function getTotaldata(?int $tenantId = null): array
    {
        $data = [];

        // Inisialisasi total pemasukan dan pengeluaran
        $data['total_income'] = 0;
        $data['total_pengeluaran'] = 0;

        // Dapatkan tipe transaksi yang aktif
        $query = CashInOutType::where('is_active', true);

        // Filter berdasarkan tenant jika disediakan
        if ($tenantId) {
            $query->where(function($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)
                  ->orWhereNull('tenant_id');
            });
        }

        $types = $query->get();

        // Hitung total untuk setiap tipe
        foreach ($types as $type) {
            $code = strtolower($type->code);
            $totalKey = 'total_' . $code;

            // Dapatkan total untuk tipe ini
            $total = static::getTotalByTypeId($type->id, $tenantId);
            $data[$totalKey] = $total;

            // Tambahkan ke total pemasukan atau pengeluaran
            if ($type->is_income) {
                $data['total_income'] += $total;
            } else {
                $data['total_pengeluaran'] += $total;
            }
        }

        // Support untuk kompatibilitas mundur (legacy)
        $data['total_penjualan'] = $data['total_income'];
        $data['total_qris'] = static::getTotalNilai(['QRIS'], $tenantId);
        $data['total_tunai'] = static::getTotalNilai(['TUNAI'], $tenantId);

        // Hitung laba bersih
        $data['total_laba'] = $data['total_income'] - $data['total_pengeluaran'];

        // Hitung pembagian profit
        if ($tenantId) {
            $tenant = Tenant::find($tenantId);
            if ($tenant) {
                $profitSharings = $tenant->profitSharings()
                    ->where('is_active', true)
                    ->get();

                // Jika profit sharing ditemukan, gunakan untuk menghitung
                if ($profitSharings->isNotEmpty()) {
                    foreach ($profitSharings as $sharing) {
                        $key = 'total_laba_' . \Illuminate\Support\Str::slug($sharing->name);
                        $data[$key] = $data['total_laba'] * ($sharing->percentage / 100);
                    }
                } else {
                    // Default 80/20 jika tidak ada profit sharing
                    $data['total_laba_80'] = $data['total_laba'] * 0.8;
                    $data['total_laba_20'] = $data['total_laba'] * 0.2;
                }
            } else {
                // Default 80/20 jika tenant tidak ditemukan
                $data['total_laba_80'] = $data['total_laba'] * 0.8;
                $data['total_laba_20'] = $data['total_laba'] * 0.2;
            }
        } else {
            // Default 80/20 jika tenant tidak ditentukan
            $data['total_laba_80'] = $data['total_laba'] * 0.8;
            $data['total_laba_20'] = $data['total_laba'] * 0.2;
        }

        return $data;
    }

    public static function table(Table $table): Table
    {
        $selectedMonth = session('selected_month');
        if ($selectedMonth) {
            try {
                $selectedMonth = Carbon::parse($selectedMonth)->format('Y-m');
                $date = Carbon::createFromFormat('Y-m', $selectedMonth);
                $startDate = $date->copy()->startOfMonth()->toDateString();
                $endDate = $date->copy()->endOfMonth()->toDateString();
            } catch (\Exception $e) {
                $startDate = now()->startOfMonth()->toDateString();
                $endDate = now()->endOfMonth()->toDateString();
            }
        } else {
            $startDate = now()->startOfMonth()->toDateString();
            $endDate = now()->endOfMonth()->toDateString();
        }

        // Dapatkan tenant ID dari user yang sedang login
        $tenantId = auth()->user()->getCurrentTenantId();

        return $table
            ->view('filament.resources.custom-cash-in-out-table.hasil', [
                'totald' => static::getTotaldata($tenantId)
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListHasilAkhirs::route('/'),
            'create' => Pages\CreateHasilAkhir::route('/create'),
            'edit' => Pages\EditHasilAkhir::route('/{record}/edit'),
        ];
    }
}
