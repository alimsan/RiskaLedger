<?= session('selected_month') ?>
<div class="overflow-x-auto">
    <table class="w-full border-collapse">
        {{-- <thead>
            <tr>
                <th class="p-2 border text-left">Penjualan</th>
                <th class="p-2 border text-left">Nilai</th>
            </tr>
        </thead> --}}
        <tbody>
            <tr>
                <th class="p-2 border text-left">Penjualan</th>
                <th class="p-2 border text-left"style="background-color: #cccf15;">{{ 'Rp ' . number_format($totald['total_penjualan'], 0, ',', '.') }}</th>
            </tr>
            <tr>
                <td class="p-2 border">Pajak</td>
                <td class="p-2 border">{{ 'Rp ' . number_format($totald['total_pajak'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="p-2 border cursor-pointer" x-on:click="$dispatch('open-modal', { id: 'makassar-detail' })">Pengeluaran Bahan Baku</td>
                <td class="p-2 border cursor-pointer hover:bg-gray-100"x-on:click="$dispatch('open-modal', { id: 'makassar-detail' })">{{ 'Rp ' . number_format($totald['total_bbaku']+$totald['total_makassar_bb'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="p-2 border">Pengeluaran Peralatan</td>
                <td class="p-2 border">{{ 'Rp ' . number_format($totald['total_peralatan'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="p-2 border">Band</td>
                <td class="p-2 border">{{ 'Rp ' . number_format($totald['total_band'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="p-2 border">Listrik</td>
                <td class="p-2 border">{{ 'Rp ' . number_format($totald['total_listrik'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="p-2 border">Gas</td>
                <td class="p-2 border">{{ 'Rp ' . number_format($totald['total_gas'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="p-2 border">Tax 5%</td>
                <td class="p-2 border">{{ 'Rp ' . number_format($totald['total_tax'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="p-2 border">GAJI</td>
                <td class="p-2 border">{{ 'Rp ' . number_format($totald['total_gaji'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="p-2 border">GAJI CUCI PIRING</td>
                <td class="p-2 border">{{ 'Rp ' . number_format($totald['total_cucipiring'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="p-2 border">COMPLIMENT</td>
                <td class="p-2 border">{{ 'Rp ' . number_format($totald['total_compliment'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="p-2 border">KASBON</td>
                <td class="p-2 border">{{ 'Rp ' . number_format($totald['total_kasbon'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="p-2 border">BPJS</td>
                <td class="p-2 border">{{ 'Rp ' . number_format($totald['total_bpjs'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="p-2 border">Owner</td>
                <td class="p-2 border">{{ 'Rp ' . number_format($totald['total_owner'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="p-2 border">Refund</td>
                <td class="p-2 border">{{ 'Rp ' . number_format($totald['total_refund'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="p-2 border">Total Pengeluaran</td>
                <td class="p-2 border">{{ 'Rp ' . number_format($totald['total_pengeluaran'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="p-2 border">Laba Bersih</td>
                <td class="p-2 border" style="background-color: #cccf15;">
                    {{ 'Rp ' . number_format($totald['total_laba'], 0, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td class="p-2 border">Laba Bersih 80%</td>
                <td class="p-2 border">{{ 'Rp ' . number_format($totald['total_laba_80'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="p-2 border">Laba Bersih 20%</td>
                <td class="p-2 border">{{ 'Rp ' . number_format($totald['total_laba_20'], 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
</div>

<x-filament::modal id="makassar-detail" width="md">
    <x-slot name="header">
        <h2 class="font-bold">Detail Pengeluaran Bahan Baku Makassar</h2>
    </x-slot>

    <table class="w-full">
        <thead>
            <tr>
                <th class="text-left p-2 border">Tanggal</th>
                <th class="text-left p-2 border">Nilai</th>
            </tr>
        </thead>
        <tbody>
            @php
                $selectedMonth = session('selected_month');
                $date = \Carbon\Carbon::parse($selectedMonth);

                $makassarDetails = \App\Models\mCashInOut::where('type', 'BB_MAKASSAR')
                    ->whereBetween('waktu', [
                        $date->copy()->startOfMonth()->startOfDay(),
                        $date->copy()->endOfMonth()->endOfDay()
                    ])
                    ->orderBy('waktu')
                    ->get();
            @endphp

            @foreach($makassarDetails as $detail)
                <tr>
                    <td class="p-2 border">{{ \Carbon\Carbon::parse($detail->waktu)->format('d-m-Y') }}</td>
                    <td class="p-2 border">Rp {{ number_format($detail->nilai, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</x-filament::modal>
