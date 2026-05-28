@php
    $startOfMonth = \Carbon\Carbon::parse(session('selected_month', now()->format('Y-m')))->startOfMonth();
    $endOfMonth = \Carbon\Carbon::parse(session('selected_month', now()->format('Y-m')))->endOfMonth();

    // Ambil tenant_id
    $tenantId = session('selected_tenant_id');
    $user = auth()->user();

    // Untuk tenant, selalu gunakan tenant_id mereka
    if (!$user->isAdministrator()) {
        $tenantId = $user->tenant_id;
    }

    // Ambil semua tenant untuk dropdown (hanya untuk admin)
    $availableTenants = [];
    if ($user->isAdministrator()) {
        $availableTenants = \App\Models\Tenant::where('is_active', true)
                             ->orderBy('name')
                             ->get();
    }

    // Ambil tipe pendapatan dan pengeluaran yang aktif sesuai tenant yang dipilih
    // Jika admin melihat semua tenant (tenant_id null), ambil semua tipe global (tenant_id null)
    $typeQuery = function($query) use ($tenantId) {
        if ($tenantId) {
            $query->where(function($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)
                  ->orWhereNull('tenant_id');
            });
        } else {
            $query->whereNull('tenant_id');
        }
        return $query;
    };

    $incomeTypes = \App\Models\CashInOutType::where('is_income', true)
                    ->where('is_active', true)
                    ->where($typeQuery)
                    ->orderBy('sort_order')
                    ->get();

    $expenseTypes = \App\Models\CashInOutType::where('is_income', false)
                    ->where('is_active', true)
                    ->where($typeQuery)
                    ->orderBy('sort_order')
                    ->get();

    $tenant = \App\Models\Tenant::find($tenantId);
    $tenantName = $tenant ? $tenant->name : 'Semua Tenant';

    $formattedMonth = \Carbon\Carbon::parse(session('selected_month', now()->format('Y-m')))->format('F Y');

    $records = App\Filament\Resources\CustomCashInOutTableResource::getTableData(
        $startOfMonth->format('Y-m-d'),
        $endOfMonth->format('Y-m-d'),
        $tenantId
    );

    $totalData = App\Filament\Resources\CustomCashInOutTableResource::getTotaldata($tenantId);

    // Ambil semua sharing profit jika ada tenant terpilih
    $profitSharings = collect([]);
    if ($tenant) {
        $profitSharings = $tenant->profitSharings()
            ->where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->get();

        // Jika tidak ada sharing profit, buat default 80/20
        if ($profitSharings->isEmpty()) {
            $defaultSharing = new \stdClass();
            $defaultSharing->name = 'Owner 80%';
            $defaultSharing->percentage = 80;
            $defaultSharing->slug = 'owner-80';

            $tenantSharing = new \stdClass();
            $tenantSharing->name = 'Tenant 20%';
            $tenantSharing->percentage = 20;
            $tenantSharing->slug = 'tenant-20';

            $profitSharings = collect([$defaultSharing, $tenantSharing]);
        }
    }

    // Definisikan headerActions dengan array kosong untuk mencegah error jika tidak ada
    $headerActions = isset($headerActions) && is_array($headerActions) ? $headerActions : [];
@endphp

<div class="filament-resources-table-container bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-300 dark:border-gray-700">
    <div class="flex items-center justify-between p-2 dark:text-white">
        <div>
            <div class="text-xl font-bold">
                Data Arus Kas Harian
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                {{ $tenantName }} - Periode: {{ $formattedMonth }}
            </div>
        </div>
        <div class="flex gap-3 items-center">
            <!-- Tenant Selector untuk Admin -->
            @if($user->isAdministrator())
<div>
                <form id="tenant-select-form" action="{{ route('filament.resources.custom-cash-in-out-tables.select-tenant') }}" method="post">
                    @csrf
                    <select name="tenant_id" class="border border-gray-300 rounded-md px-3 py-1 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600" onchange="document.getElementById('tenant-select-form').submit()">
                        <option value="">Semua Tenant</option>
                        @foreach($availableTenants as $availableTenant)
                            <option value="{{ $availableTenant->id }}" {{ $tenantId == $availableTenant->id ? 'selected' : '' }}>
                                {{ $availableTenant->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
            @endif

            <!-- Tombol Export Excel jika ada tenant terpilih -->
            @if($tenantId)
            <a href="#" onclick="document.getElementById('export-form').submit();"
               class="inline-flex items-center justify-center font-medium rounded-lg border transition-colors focus:outline-none focus:ring-offset-2 focus:ring-2 focus:ring-inset filament-button h-9 px-4 text-sm text-white shadow focus:ring-white border-transparent bg-success-600 hover:bg-success-500 focus:bg-success-700 focus:ring-offset-success-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 -ml-1 mr-1 filament-button-icon rtl:ml-1 rtl:-mr-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span>Export Excel</span>
            </a>
            <form id="export-form" action="{{ route('filament.resources.custom-cash-in-out-tables.export') }}" method="post" style="display: none;">
                @csrf
                <input type="hidden" name="tenant_id" value="{{ $tenantId }}">
                <input type="hidden" name="month" value="{{ session('selected_month', now()->format('Y-m')) }}">
            </form>
            @endif

            @if (count($headerActions) > 0)
                @foreach ($headerActions as $action)
                    @livewire($action, ['record' => null], key($action))
        @endforeach
            @endif
        </div>
    </div>

    @if(!$tenantId && $user->isAdministrator())
    <div class="p-4 bg-yellow-50 dark:bg-yellow-900 border-l-4 border-yellow-400 dark:border-yellow-600 text-yellow-700 dark:text-yellow-200">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400 dark:text-yellow-300" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p>Silakan pilih tenant untuk melihat laporan arus kas yang lebih spesifik. Tanpa tenant yang dipilih, Anda hanya melihat tipe transaksi global.</p>
            </div>
        </div>
    </div>
    @endif

    <div class="overflow-x-auto relative">
        <table class="w-full text-left text-gray-800 dark:text-gray-200 divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                    <th scope="col" class="px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Tanggal
                    </th>
                    <th scope="col" class="px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Penjualan
                    </th>

                    <!-- Header untuk semua tipe pemasukan -->
                    @foreach($incomeTypes as $type)
                        <th scope="col" class="px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider" title="{{ $type->description }}">
                            {{ $type->name }}
                            @if($user->isAdministrator() && $tenantId && $type->tenant_id === null)
                                <span class="text-xs text-gray-400 font-normal">(Global)</span>
                            @endif
                        </th>
                    @endforeach

                    <!-- Header untuk semua tipe pengeluaran -->
                    @foreach($expenseTypes as $type)
                        <th scope="col" class="px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider" title="{{ $type->description }}">
                            <a href="#" class="text-blue-600 hover:underline dark:text-blue-400"
                               onclick="showDetail('{{ strtolower($type->code) }}', '{{ $type->name }}')">
                                {{ $type->name }}
                                @if($user->isAdministrator() && $tenantId && $type->tenant_id === null)
                                    <span class="text-xs text-gray-400 font-normal">(Global)</span>
                                @endif
                            </a>
                        </th>
                    @endforeach

                    <th scope="col" class="px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Jumlah
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @if(count($records) > 0)
                @foreach($records as $record)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-2 whitespace-nowrap">
                                {{ $record->tanggal }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-right">
                                Rp {{ number_format($record->penjualan, 0, ',', '.') }}
                            </td>

                            <!-- Data untuk setiap tipe pemasukan -->
                            @foreach($incomeTypes as $type)
                                @php $code = strtolower($type->code); @endphp
                                <td class="px-4 py-2 whitespace-nowrap text-right">
                                    Rp {{ isset($record->$code) ? number_format($record->$code, 0, ',', '.') : 0 }}
                                </td>
                            @endforeach

                            <!-- Data untuk setiap tipe pengeluaran -->
                            @foreach($expenseTypes as $type)
                                @php $code = strtolower($type->code); @endphp
                                <td class="px-4 py-2 whitespace-nowrap text-right">
                                    Rp {{ isset($record->$code) ? number_format($record->$code, 0, ',', '.') : 0 }}
                                </td>
                            @endforeach

                            <td class="px-4 py-2 whitespace-nowrap text-right">
                                Rp {{ number_format($record->tb1_jumlah, 0, ',', '.') }}
                            </td>
                </tr>
                @endforeach
                @else
                    <tr>
                        <td colspan="{{ 3 + count($incomeTypes) + count($expenseTypes) }}" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            Tidak ada data untuk ditampilkan
                        </td>
                    </tr>
                @endif
            </tbody>
            <tfoot>
                <tr class="bg-gray-100 dark:bg-gray-700 font-semibold">
                    <td class="px-4 py-2 whitespace-nowrap">
                        TOTAL
                    </td>
                    <td class="px-4 py-2 whitespace-nowrap text-right">
                        Rp {{ number_format($totalData['total_penjualan'], 0, ',', '.') }}
                    </td>

                    <!-- Total untuk setiap tipe pemasukan -->
                    @foreach($incomeTypes as $type)
                        @php
                            $code = strtolower($type->code);
                            $totalKey = 'total_' . $code;
                        @endphp
                        <td class="px-4 py-2 whitespace-nowrap text-right">
                            Rp {{ isset($totalData[$totalKey]) ? number_format($totalData[$totalKey], 0, ',', '.') : 0 }}
                        </td>
                    @endforeach

                    <!-- Total untuk setiap tipe pengeluaran -->
                    @foreach($expenseTypes as $type)
                        @php
                            $code = strtolower($type->code);
                            $totalKey = 'total_' . $code;
                        @endphp
                        <td class="px-4 py-2 whitespace-nowrap text-right">
                            Rp {{ isset($totalData[$totalKey]) ? number_format($totalData[$totalKey], 0, ',', '.') : 0 }}
                        </td>
                    @endforeach

                    <td class="px-4 py-2 whitespace-nowrap text-right">
                        Rp {{ number_format($totalData['total_laba'], 0, ',', '.') }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Tampilkan perhitungan laba hanya jika tenant dipilih -->
@if($tenantId)
<div class="mt-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-300 dark:border-gray-700 p-4">
    <div class="text-lg font-semibold mb-2 dark:text-white">
        Perhitungan Laba
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="border border-gray-200 dark:border-gray-700 p-3 rounded-lg dark:text-white">
            <div class="font-medium">Total Pendapatan</div>
            <div class="text-xl mt-1">Rp {{ number_format($totalData['total_income'], 0, ',', '.') }}</div>
        </div>
        <div class="border border-gray-200 dark:border-gray-700 p-3 rounded-lg dark:text-white">
            <div class="font-medium">Total Pengeluaran</div>
            <div class="text-xl mt-1">Rp {{ number_format($totalData['total_pengeluaran'], 0, ',', '.') }}</div>
        </div>
        <div class="border border-gray-200 dark:border-gray-700 p-3 rounded-lg dark:text-white">
            <div class="font-medium">Total Laba</div>
            <div class="text-xl mt-1">Rp {{ number_format($totalData['total_laba'], 0, ',', '.') }}</div>
        </div>

        <!-- Pembagian Hasil Dinamis -->
        @if(count($profitSharings) > 0)
            @foreach($profitSharings as $sharing)
                @php
                    $key = 'total_laba_' . \Illuminate\Support\Str::slug($sharing->name);
                    $value = $totalData[$key] ?? 0;
                @endphp
                <div class="border border-gray-200 dark:border-gray-700 p-3 rounded-lg dark:text-white">
                    <div class="font-medium">{{ $sharing->name }} ({{ $sharing->percentage }}%)</div>
                    <div class="text-xl mt-1">Rp {{ number_format($value, 0, ',', '.') }}</div>
                </div>
            @endforeach
        @elseif(isset($totalData['total_laba_80']) && isset($totalData['total_laba_20']))
            <div class="border border-gray-200 dark:border-gray-700 p-3 rounded-lg dark:text-white">
                <div class="font-medium">Owner (80%)</div>
                <div class="text-xl mt-1">Rp {{ number_format($totalData['total_laba_80'], 0, ',', '.') }}</div>
            </div>
            <div class="border border-gray-200 dark:border-gray-700 p-3 rounded-lg dark:text-white">
                <div class="font-medium">Tenant (20%)</div>
                <div class="text-xl mt-1">Rp {{ number_format($totalData['total_laba_20'], 0, ',', '.') }}</div>
            </div>
        @endif
    </div>
</div>
@endif

<!-- Modal Detail -->
<div id="detailModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-full max-w-3xl max-h-[80vh] overflow-auto">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold dark:text-white" id="modalTitle">Detail</h2>
            <button onclick="closeModal()" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="modalContent" class="overflow-x-auto dark:text-gray-200">
            <!-- Detail content will be populated here -->
        </div>
    </div>
</div>

<script>
    function showDetail(type, typeName) {
        const modal = document.getElementById('detailModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalContent = document.getElementById('modalContent');

        modalTitle.textContent = 'Detail ' + typeName;
        modalContent.innerHTML = '<div class="flex justify-center"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-gray-900 dark:border-gray-100"></div></div>';
        modal.classList.remove('hidden');

        // Mendapatkan data dari server
        fetch(`/admin/cash-in-out/detail/${type}?tenant_id={{ $tenantId }}&month={{ session('selected_month', now()->format('Y-m')) }}`)
            .then(response => response.json())
            .then(data => {
                let detailHTML = `
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700">
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nama</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Deskripsi</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nilai</th>
            </tr>
        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                `;

                if (data.length === 0) {
                    detailHTML += `
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-center text-sm">Tidak ada data</td>
                        </tr>
                    `;
                } else {
                    let total = 0;
                    data.forEach(item => {
                        total += parseInt(item.nilai);
                        detailHTML += `
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-2 whitespace-nowrap">${item.formatted_date}</td>
                                <td class="px-4 py-2">${item.nama_barang || '-'}</td>
                                <td class="px-4 py-2">${item.deksripsi || '-'}</td>
                                <td class="px-4 py-2 whitespace-nowrap text-right">Rp ${item.formatted_nilai}</td>
                            </tr>
                        `;
                    });

                    detailHTML += `
                        <tr class="bg-gray-100 dark:bg-gray-700 font-semibold">
                            <td colspan="3" class="px-4 py-2 text-right">Total:</td>
                            <td class="px-4 py-2 whitespace-nowrap text-right">Rp ${new Intl.NumberFormat('id-ID').format(total)}</td>
                </tr>
                    `;
                }

                detailHTML += `
        </tbody>
    </table>
                `;

                modalContent.innerHTML = detailHTML;
            })
            .catch(error => {
                console.error('Error:', error);
                modalContent.innerHTML = '<div class="text-red-500 dark:text-red-400">Terjadi kesalahan saat mengambil data.</div>';
            });
    }

    function closeModal() {
        const modal = document.getElementById('detailModal');
        modal.classList.add('hidden');
    }
</script>
