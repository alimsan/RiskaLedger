<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\mCashInOut;
use App\Models\CashInOutType;
use App\Models\Piutang;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class IncomeExpenseStatWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Dapatkan tenant ID dari user yang sedang login
        $tenantId = auth()->user()->tenant_id;

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

        // Tanggal untuk bulan ini
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // Tanggal untuk bulan lalu
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();

        // Query pendapatan bulan ini
        $income = mCashInOut::whereIn('type_id', $incomeTypes)
            ->when($tenantId, function($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })
            ->whereDate('waktu', '>=', $startOfMonth)
            ->whereDate('waktu', '<=', $endOfMonth)
            ->sum('nilai');

        // Query pendapatan bulan lalu
        $lastMonthIncome = mCashInOut::whereIn('type_id', $incomeTypes)
            ->when($tenantId, function($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })
            ->whereDate('waktu', '>=', $startOfLastMonth)
            ->whereDate('waktu', '<=', $endOfLastMonth)
            ->sum('nilai');

        // Query pengeluaran bulan ini
        $expense = mCashInOut::whereIn('type_id', $expenseTypes)
            ->when($tenantId, function($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })
            ->whereDate('waktu', '>=', $startOfMonth)
            ->whereDate('waktu', '<=', $endOfMonth)
            ->sum('nilai');

        // Query pengeluaran bulan lalu
        $lastMonthExpense = mCashInOut::whereIn('type_id', $expenseTypes)
            ->when($tenantId, function($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })
            ->whereDate('waktu', '>=', $startOfLastMonth)
            ->whereDate('waktu', '<=', $endOfLastMonth)
            ->sum('nilai');

        // Hitung perubahan persentase
        $incomePercentage = $lastMonthIncome > 0
            ? round((($income - $lastMonthIncome) / $lastMonthIncome) * 100, 2)
            : ($income > 0 ? 100 : 0);

        $expensePercentage = $lastMonthExpense > 0
            ? round((($expense - $lastMonthExpense) / $lastMonthExpense) * 100, 2)
            : ($expense > 0 ? 100 : 0);

        // Hitung laba
        $profit = $income - $expense;
        $lastMonthProfit = $lastMonthIncome - $lastMonthExpense;
        $profitPercentage = $lastMonthProfit != 0
            ? round((($profit - $lastMonthProfit) / abs($lastMonthProfit)) * 100, 2)
            : ($profit > 0 ? 100 : 0);

        // Format angka ke dalam format rupiah
        $formattedIncome = 'Rp ' . number_format($income, 0, ',', '.');
        $formattedExpense = 'Rp ' . number_format($expense, 0, ',', '.');
        $formattedProfit = 'Rp ' . number_format($profit, 0, ',', '.');

        // Format nama bulan
        $currentMonth = Carbon::now()->translatedFormat('F');
        $lastMonth = Carbon::now()->subMonth()->translatedFormat('F');

        // Query untuk Piutang yang lunas bulan ini
        $paidDebt = Piutang::where('lunas', true)
            ->when($tenantId, function($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })
            ->whereDate('waktu', '>=', $startOfMonth)
            ->whereDate('waktu', '<=', $endOfMonth)
            ->sum('total_utang');

        // Query untuk Piutang yang belum lunas bulan ini
        $unpaidDebt = Piutang::where('lunas', false)
            ->when($tenantId, function($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })
            ->whereDate('waktu', '>=', $startOfMonth)
            ->whereDate('waktu', '<=', $endOfMonth)
            ->sum('total_utang');

        // Query untuk Piutang yang lunas bulan lalu
        $lastMonthPaidDebt = Piutang::where('lunas', true)
            ->when($tenantId, function($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })
            ->whereDate('waktu', '>=', $startOfLastMonth)
            ->whereDate('waktu', '<=', $endOfLastMonth)
            ->sum('total_utang');

        // Query untuk Piutang yang belum lunas bulan lalu
        $lastMonthUnpaidDebt = Piutang::where('lunas', false)
            ->when($tenantId, function($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })
            ->whereDate('waktu', '>=', $startOfLastMonth)
            ->whereDate('waktu', '<=', $endOfLastMonth)
            ->sum('total_utang');

        // Hitung perubahan persentase untuk piutang lunas
        $paidDebtPercentage = $lastMonthPaidDebt > 0
            ? round((($paidDebt - $lastMonthPaidDebt) / $lastMonthPaidDebt) * 100, 2)
            : ($paidDebt > 0 ? 100 : 0);

        // Hitung perubahan persentase untuk piutang belum lunas
        $unpaidDebtPercentage = $lastMonthUnpaidDebt > 0
            ? round((($unpaidDebt - $lastMonthUnpaidDebt) / $lastMonthUnpaidDebt) * 100, 2)
            : ($unpaidDebt > 0 ? 100 : 0);

        // Hitung total piutang dan persentase belum lunas
        $totalDebt = $paidDebt + $unpaidDebt;
        $unpaidPercentage = $totalDebt > 0 ? round(($unpaidDebt / $totalDebt) * 100, 2) : 0;

        // Format angka ke dalam format rupiah
        $formattedPaidDebt = 'Rp ' . number_format($paidDebt, 0, ',', '.');
        $formattedUnpaidDebt = 'Rp ' . number_format($unpaidDebt, 0, ',', '.');

        // Buat format HTML untuk tampilan nilai dengan warna
        $onGoingValue = new HtmlString(
            '<span class="text-base" style="color: #10b981;">' . $formattedPaidDebt . '</span>  ' .
            '<span class="text-base" style="color: #ef4444;">' . $formattedUnpaidDebt . '</span>'
        );

        // Buat format HTML untuk tampilan deskripsi dengan warna
        $onGoingDescription = new HtmlString(
            '<span>✅ ' . ($paidDebtPercentage >= 0 ? '+' : '') . $paidDebtPercentage . '% - ' .
            '❌ ' . ($unpaidDebtPercentage >= 0 ? '+' : '') . $unpaidDebtPercentage . '%</span>'
        );

        return [
            Stat::make('Total Pemasukan ' . $currentMonth, $formattedIncome)
                ->description($incomePercentage >= 0 ? 'Naik ' . $incomePercentage . '% dari ' . $lastMonth : 'Turun ' . abs($incomePercentage) . '% dari ' . $lastMonth)
                ->descriptionIcon($incomePercentage >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($incomePercentage >= 0 ? 'success' : 'danger')
                ->chart($this->getIncomeChartData()),

            Stat::make('Total Pengeluaran ' . $currentMonth, $formattedExpense)
                ->description($expensePercentage >= 0 ? 'Naik ' . $expensePercentage . '% dari ' . $lastMonth : 'Turun ' . abs($expensePercentage) . '% dari ' . $lastMonth)
                ->descriptionIcon($expensePercentage >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($expensePercentage >= 0 ? 'danger' : 'success')
                ->chart($this->getExpenseChartData()),

            Stat::make('Total Laba ' . $currentMonth, $formattedProfit)
                ->description($profitPercentage >= 0 ? 'Naik ' . $profitPercentage . '% dari ' . $lastMonth : 'Turun ' . abs($profitPercentage) . '% dari ' . $lastMonth)
                ->descriptionIcon($profitPercentage >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($profitPercentage >= 0 ? 'success' : 'danger')
                ->chart($this->getProfitChartData()),

            Stat::make('Piutang (' . $unpaidPercentage . '% belum lunas)', $onGoingValue)
                ->description($onGoingDescription)
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('gray')
                ->chart($this->getOnGoingChartData()),
        ];
    }

    protected function getIncomeChartData(): array
    {
        // Dapatkan tenant ID dari user yang sedang login
        $tenantId = auth()->user()->tenant_id;

        // Dapatkan tipe pendapatan
        $incomeTypes = CashInOutType::where('is_income', true)
            ->where('is_active', true)
            ->when($tenantId, function($query) use ($tenantId) {
                $query->where(function($q) use ($tenantId) {
                    $q->where('tenant_id', $tenantId)
                      ->orWhereNull('tenant_id');
                });
            })
            ->pluck('id');

        // Data untuk 6 bulan terakhir
        $months = collect(range(0, 5))->map(function ($i) {
            return Carbon::now()->subMonths($i)->startOfMonth();
        })->reverse();

        return $months->map(function ($month) use ($tenantId, $incomeTypes) {
            $startDate = $month->copy()->startOfMonth();
            $endDate = $month->copy()->endOfMonth();

            return mCashInOut::whereIn('type_id', $incomeTypes)
                ->when($tenantId, function($query) use ($tenantId) {
                    $query->where('tenant_id', $tenantId);
                })
                ->whereDate('waktu', '>=', $startDate)
                ->whereDate('waktu', '<=', $endDate)
                ->sum('nilai');
        })->toArray();
    }

    protected function getExpenseChartData(): array
    {
        // Dapatkan tenant ID dari user yang sedang login
        $tenantId = auth()->user()->tenant_id;

        // Dapatkan tipe pengeluaran
        $expenseTypes = CashInOutType::where('is_income', false)
            ->where('is_active', true)
            ->when($tenantId, function($query) use ($tenantId) {
                $query->where(function($q) use ($tenantId) {
                    $q->where('tenant_id', $tenantId)
                      ->orWhereNull('tenant_id');
                });
            })
            ->pluck('id');

        // Data untuk 6 bulan terakhir
        $months = collect(range(0, 5))->map(function ($i) {
            return Carbon::now()->subMonths($i)->startOfMonth();
        })->reverse();

        return $months->map(function ($month) use ($tenantId, $expenseTypes) {
            $startDate = $month->copy()->startOfMonth();
            $endDate = $month->copy()->endOfMonth();

            return mCashInOut::whereIn('type_id', $expenseTypes)
                ->when($tenantId, function($query) use ($tenantId) {
                    $query->where('tenant_id', $tenantId);
                })
                ->whereDate('waktu', '>=', $startDate)
                ->whereDate('waktu', '<=', $endDate)
                ->sum('nilai');
        })->toArray();
    }

    protected function getProfitChartData(): array
    {
        // Dapatkan tenant ID dari user yang sedang login
        $tenantId = auth()->user()->tenant_id;

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

        // Data untuk 6 bulan terakhir
        $months = collect(range(0, 5))->map(function ($i) {
            return Carbon::now()->subMonths($i)->startOfMonth();
        })->reverse();

        return $months->map(function ($month) use ($tenantId, $incomeTypes, $expenseTypes) {
            $startDate = $month->copy()->startOfMonth();
            $endDate = $month->copy()->endOfMonth();

            $income = mCashInOut::whereIn('type_id', $incomeTypes)
                ->when($tenantId, function($query) use ($tenantId) {
                    $query->where('tenant_id', $tenantId);
                })
                ->whereDate('waktu', '>=', $startDate)
                ->whereDate('waktu', '<=', $endDate)
                ->sum('nilai');

            $expense = mCashInOut::whereIn('type_id', $expenseTypes)
                ->when($tenantId, function($query) use ($tenantId) {
                    $query->where('tenant_id', $tenantId);
                })
                ->whereDate('waktu', '>=', $startDate)
                ->whereDate('waktu', '<=', $endDate)
                ->sum('nilai');

            return $income - $expense;
        })->toArray();
    }

    protected function getOnGoingChartData(): array
    {
        // Dapatkan tenant ID dari user yang sedang login
        $tenantId = auth()->user()->tenant_id;

        // Data untuk 6 bulan terakhir
        $months = collect(range(0, 5))->map(function ($i) {
            return Carbon::now()->subMonths($i)->startOfMonth();
        })->reverse();

        return $months->map(function ($month) use ($tenantId) {
            $startDate = $month->copy()->startOfMonth();
            $endDate = $month->copy()->endOfMonth();

            // Dapatkan total Piutang lunas untuk bulan ini
            $paidDebt = Piutang::where('lunas', true)
                ->when($tenantId, function($query) use ($tenantId) {
                    $query->where('tenant_id', $tenantId);
                })
                ->whereDate('waktu', '>=', $startDate)
                ->whereDate('waktu', '<=', $endDate)
                ->sum('total_utang');

            // Dapatkan total Piutang belum lunas untuk bulan ini
            $unpaidDebt = Piutang::where('lunas', false)
                ->when($tenantId, function($query) use ($tenantId) {
                    $query->where('tenant_id', $tenantId);
                })
                ->whereDate('waktu', '>=', $startDate)
                ->whereDate('waktu', '<=', $endDate)
                ->sum('total_utang');

            // Grafik akan menampilkan selisih antara utang lunas dan belum lunas
            return $paidDebt - $unpaidDebt;
        })->toArray();
    }

    public static function canView(): bool
    {
        return auth()->user() && (auth()->user()->isAdministrator() || auth()->user()->tenant_id);
    }
}
