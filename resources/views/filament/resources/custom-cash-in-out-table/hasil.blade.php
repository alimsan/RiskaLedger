@php
    // Ubah format tanggal - pastikan kita mendapatkan format yang benar dari session
    $selectedMonth = session('selected_month');
    if ($selectedMonth) {
        // Coba parse dan format dengan benar
        try {
            // Jika format Y-m-d (dari DateFilterWidget)
            if (strlen($selectedMonth) > 7) {
                $date = \Carbon\Carbon::parse($selectedMonth);
                $selectedMonth = $date->format('Y-m');
            }
        } catch (\Exception $e) {
            \Log::error("Error parsing date: " . $e->getMessage());
            $selectedMonth = now()->format('Y-m');
        }
    } else {
        $selectedMonth = now()->format('Y-m');
    }

    // Tenant hanya bisa melihat data mereka sendiri
    $tenantId = auth()->user()->tenant_id;
    $tenant = \App\Models\Tenant::find($tenantId);

    // Ambil tipe pendapatan dan pengeluaran yang harus ditampilkan di hasil akhir
    $displayTypes = \App\Models\CashInOutType::where('show_akhir', true)
                    ->where('is_active', true)
                    ->where(function($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId)
                              ->orWhereNull('tenant_id');
                    })
                    ->orderBy('sort_order')
                    ->get();

    // Ambil semua tipe profit sharing untuk tenant ini
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

    $formattedMonth = \Carbon\Carbon::parse($selectedMonth)->format('F Y');
@endphp

<div class="p-4">
    <div class="mb-4 text-center">
        <h1 class="text-2xl font-bold">Laporan Hasil Akhir</h1>
        <p>{{ $tenant ? $tenant->name : '' }} - Periode: {{ $formattedMonth }}</p>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full border-collapse">
            <tbody>
                <tr>
                    <th class="p-2 border text-left">Penjualan</th>
                    <th class="p-2 border text-left" style="background-color: #cccf15;">{{ 'Rp ' . number_format($totald['total_penjualan'], 0, ',', '.') }}</th>
                </tr>

                @foreach($displayTypes as $type)
                    @php
                        $code = strtolower($type->code);
                        $totalKey = 'total_' . $code;
                    @endphp
                    @if(isset($totald[$totalKey]))
                        <tr>
                            <td class="p-2 border cursor-pointer" x-on:click="$dispatch('open-modal', { id: '{{ $code }}-detail' })">
                                {{ $type->name }}
                            </td>
                            <td class="p-2 border cursor-pointer hover:bg-gray-100" x-on:click="$dispatch('open-modal', { id: '{{ $code }}-detail' })">
                                {{ 'Rp ' . number_format($totald[$totalKey], 0, ',', '.') }}
                            </td>
                        </tr>
                    @endif
                @endforeach

                <tr>
                    <td class="p-2 border">Total Pengeluaran</td>
                    <td class="p-2 border" style="background-color: #cf9d15;">{{ 'Rp ' . number_format($totald['total_pengeluaran'], 0, ',', '.') }}</td>
                </tr>

                <tr>
                    <td class="p-2 border">Laba Bersih</td>
                    <td class="p-2 border" style="background-color: #cccf15;">
                        {{ 'Rp ' . number_format($totald['total_laba'], 0, ',', '.') }}
                    </td>
                </tr>

                @foreach($profitSharings as $sharing)
                    @php
                        $key = 'total_laba_' . \Illuminate\Support\Str::slug($sharing->name);
                        $value = $totald[$key] ?? ($sharing->percentage / 100 * $totald['total_laba']);
                    @endphp
                    <tr>
                        <td class="p-2 border">{{ $sharing->name }} ({{ $sharing->percentage }}%)</td>
                        <td class="p-2 border">{{ 'Rp ' . number_format($value, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal untuk setiap tipe yang bisa diklik -->
@foreach($displayTypes as $type)
    @php
        $code = strtolower($type->code);
    @endphp
    <x-filament::modal id="{{ $code }}-detail" width="md">
        <x-slot name="header">
            <h2 class="font-bold">Detail {{ $type->name }}</h2>
        </x-slot>

        <table class="w-full">
            <thead>
                <tr>
                    <th class="text-left p-2 border">Tanggal</th>
                    <th class="text-left p-2 border">Nama</th>
                    <th class="text-left p-2 border">Nilai</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $selectedMonth = session('selected_month');
                    $date = \Carbon\Carbon::parse($selectedMonth);

                    $typeDetails = \App\Models\mCashInOut::where('type_id', $type->id)
                        ->where('tenant_id', $tenantId)
                        ->whereBetween('waktu', [
                            $date->copy()->startOfMonth()->startOfDay(),
                            $date->copy()->endOfMonth()->endOfDay()
                        ])
                        ->orderBy('waktu')
                        ->get();
                @endphp

                @foreach($typeDetails as $detail)
                    <tr>
                        <td class="p-2 border">{{ \Carbon\Carbon::parse($detail->waktu)->format('d-m-Y') }}</td>
                        <td class="p-2 border">{{ $detail->nama_barang ?: '-' }}</td>
                        <td class="p-2 border">Rp {{ number_format($detail->nilai, 0, ',', '.') }}</td>
                    </tr>
                @endforeach

                @if($typeDetails->isEmpty())
                    <tr>
                        <td colspan="3" class="p-2 border text-center">Tidak ada data</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </x-filament::modal>
@endforeach
