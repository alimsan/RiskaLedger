<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\mCashInOut;
use App\Models\CashInOutType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class YearlyChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Grafik Penghasilan & Pengeluaran (Tahun Ini)';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        // Dapatkan tenant ID dari user yang sedang login (gunakan current_tenant_id)
        $tenantId = auth()->user()->getCurrentTenantId();

        // Buat array untuk bulan (1-12)
        $months = range(1, 12);
        $currentYear = Carbon::now()->year;

        $incomeData = [];
        $expenseData = [];
        $labels = [];

        // Dapatkan tipe pendapatan dan pengeluaran
        $incomeTypes = CashInOutType::where('is_income', true)
            ->where('is_active', true)
            ->when($tenantId, function($query) use ($tenantId) {
                $query->where(function($q) use ($tenantId) {
                    $q->where('tenant_id', $tenantId)
                      ->orWhereNull('tenant_id');
                });
            })
            ->pluck('id');

        $expenseTypes = CashInOutType::where('is_income', false)
            ->where('is_active', true)
            ->when($tenantId, function($query) use ($tenantId) {
                $query->where(function($q) use ($tenantId) {
                    $q->where('tenant_id', $tenantId)
                      ->orWhereNull('tenant_id');
                });
            })
            ->pluck('id');

        // Query untuk mendapatkan data penghasilan dan pengeluaran per bulan
        foreach ($months as $month) {
            $startDate = Carbon::createFromDate($currentYear, $month, 1)->startOfMonth();
            $endDate = Carbon::createFromDate($currentYear, $month, 1)->endOfMonth();

            // Query untuk pendapatan
            $income = mCashInOut::whereIn('type_id', $incomeTypes)
                ->when($tenantId, function($query) use ($tenantId) {
                    $query->where('tenant_id', $tenantId);
                })
                ->whereDate('waktu', '>=', $startDate)
                ->whereDate('waktu', '<=', $endDate)
                ->sum('nilai');

            // Query untuk pengeluaran
            $expense = mCashInOut::whereIn('type_id', $expenseTypes)
                ->when($tenantId, function($query) use ($tenantId) {
                    $query->where('tenant_id', $tenantId);
                })
                ->whereDate('waktu', '>=', $startDate)
                ->whereDate('waktu', '<=', $endDate)
                ->sum('nilai');

            $incomeData[] = $income;
            $expenseData[] = $expense;
            $labels[] = Carbon::createFromDate($currentYear, $month, 1)->translatedFormat('F');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Penghasilan',
                    'data' => $incomeData,
                    'backgroundColor' => '#36A2EB',
                    'borderColor' => '#36A2EB',
                ],
                [
                    'label' => 'Pengeluaran',
                    'data' => $expenseData,
                    'backgroundColor' => '#FF6384',
                    'borderColor' => '#FF6384',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    public static function canView(): bool
    {
        return auth()->user() && (auth()->user()->isAdministrator() || auth()->user()->getCurrentTenantId());
    }
}
