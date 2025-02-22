<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomCashInOutTableResource\Pages;
use App\Filament\Resources\CustomCashInOutTableResource\RelationManagers;
use App\Models\mCashInOut;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;


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

            ]);
    }
    protected static function getTotalByType(array $types, $tanggal)
    {
        return mCashInOut::whereIn('type', $types)
            ->whereDate('waktu', $tanggal)
            ->sum('nilai');
    }
    protected static function getTotalNilai(array $types)
    {
        $query = mCashInOut::whereIn('type', $types);

        $selectedMonth = session('selected_month');
        if ($selectedMonth) {
            try {
                // Parse tanggal dari session
                $date = \Carbon\Carbon::parse($selectedMonth);

                // Set range tanggal untuk bulan yang dipilih
                $startDate = $date->copy()->startOfMonth()->startOfDay();
                $endDate = $date->copy()->endOfMonth()->endOfDay();

                // Debug untuk memastikan range tanggal benar
                // \Log::info('Date Range', [
                //     'selected_month' => $selectedMonth,
                //     'start_date' => $startDate->toDateTimeString(),
                //     'end_date' => $endDate->toDateTimeString()
                // ]);

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

        // Debug untuk melihat query yang dijalankan
        // \Log::info('SQL Query', [
        //     'sql' => $query->toSql(),
        //     'bindings' => $query->getBindings()
        // ]);

        return $query->sum('nilai');
    }
    public static function getTableData(string $startDate, string $endDate): array
    {
        $dates = collect(range(strtotime($startDate), strtotime($endDate), 86400))
            ->map(function ($timestamp) {
                return date('Y-m-d', $timestamp);
            });

        // Ambil data Makassar BB
        $makassarDetails = mCashInOut::where('type', 'BB_MAKASSAR')
            ->whereBetween('waktu', [$startDate, $endDate])
            ->get()
            ->map(function ($item) {
                return [
                    'tanggal' => $item->waktu->format('Y-m-d'),
                    'nilai' => $item->nilai
                ];
            });

        $data = [];
        $totalPenjualan = 0;
        $totalBBaku = 0;
        $totalPeralatan = 0;
        foreach ($dates as $date) {
            $data[] = (object) [
                'tanggal' => $date,
                'penjualan' => static::getTotalByType(['QRIS', 'TUNAI'], $date),
                'qris' => static::getTotalByType(['QRIS'], $date),
                'tunai' => static::getTotalByType(['TUNAI'], $date),
                'b_baku' => static::getTotalByType(['B_BAKU'], $date),
                'peralatan' => static::getTotalByType(['PERALATAN'], $date),
                'band' => static::getTotalByType(['BAND'], $date),
                'listrik' => static::getTotalByType(['LISTRIK'], $date),
                'gas' => static::getTotalByType(['GAS'], $date),
                'refund' => static::getTotalByType(['REFUND'], $date),
                'kasbon' => static::getTotalByType(['KASBON'], $date),
                'owner' => static::getTotalByType(['OWNER'], $date),
                'compliment' => static::getTotalByType(['COMPLIMENT'], $date),
                'bpjs' => static::getTotalByType(['BPJS'], $date),
                'tb1_jumlah' => static::getTotalByType([
                    'TUNAI',
                ], $date) - static::getTotalByType([
                                'B_BAKU',
                                'PERALATAN',
                                'BAND',
                                'LISTRIK',
                                'GAS',
                                'REFUND',
                                'KASBON',
                                'OWNER',
                                'COMPLIMENT',
                                'BPJS'
                            ], $date),
                'makassar_details' => $makassarDetails->where('tanggal', $date)->values()->toArray()
            ];
        }
        return $data;
    }
    public static function getTotaldata(): array
    {
        $data['total_penjualan'] = static::getTotalNilai(['QRIS', 'TUNAI']);
        $data['total_qris'] = static::getTotalNilai(['QRIS']);
        $data['total_tunai'] = static::getTotalNilai(['TUNAI']);
        $data['total_bbaku'] = static::getTotalNilai(['B_BAKU']);
        $data['total_peralatan'] = static::getTotalNilai(['PERALATAN']);
        $data['total_band'] = static::getTotalNilai(['BAND']);
        $data['total_listrik'] = static::getTotalNilai(['LISTRIK']);
        $data['total_gas'] = static::getTotalNilai(['GAS']);
        $data['total_refund'] = static::getTotalNilai(['REFUND']);
        $data['total_kasbon'] = static::getTotalNilai(['KASBON']);
        $data['total_owner'] = static::getTotalNilai(['OWNER']);
        $data['total_compliment'] = static::getTotalNilai(['COMPLIMENT']);
        $data['total_bpjs'] = static::getTotalNilai(['BPJS']);
        $data['total_makassar_bb'] = static::getTotalNilai(['BB_MAKASSAR']);
        $data['total_pajak'] = static::getTotalNilai(['PAJAK']);
        $data['total_tax'] = static::getTotalNilai(['TAX']);
        $data['total_gaji'] = static::getTotalNilai(['GAJI']);
        $data['total_cucipiring'] = static::getTotalNilai(['GAJI_C_PIRING']);
        $data['total_pengeluaran'] = $data['total_bbaku'] + $data['total_peralatan'] + $data['total_band'] + $data['total_listrik']
            + $data['total_gas'] + $data['total_refund'] + $data['total_kasbon'] + $data['total_owner'] + $data['total_compliment']
            + $data['total_bpjs'] + $data['total_makassar_bb'] + $data['total_pajak'] + $data['total_tax'] + $data['total_gaji'] + $data['total_cucipiring'];
        $data['total_laba'] = static::getTotalNilai(['QRIS', 'TUNAI']) - $data['total_pengeluaran'];
        $data['total_laba_80'] = $data['total_laba'] * 0.8;
        $data['total_laba_20'] = $data['total_laba'] * 0.2;
        return $data;
    }
    public static function table(Table $table): Table
    {
        $selectedMonth = session('selected_month');
        if ($selectedMonth) {
            try {
                $selectedMonth = \Carbon\Carbon::parse($selectedMonth)->format('Y-m');
                $date = \Carbon\Carbon::createFromFormat('Y-m', $selectedMonth);
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

        return $table
            ->view('filament.resources.custom-cash-in-out-table.table', [
                'records' => static::getTableData($startDate, $endDate),
                'totald' => static::getTotaldata()
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Export Excel')
                    ->action(function () use ($startDate, $endDate) {
                        $data = static::getTableData($startDate, $endDate);
                        $totald = static::getTotaldata();

                        $spreadsheet = new Spreadsheet();
                        $sheet = $spreadsheet->getActiveSheet();

                        // Set headers
                        $headers = [
                            'Tanggal', 'Penjualan', 'QRIS', 'Tunai', 'Bahan Baku',
                            'Peralatan', 'Band', 'Listrik', 'Gas', 'Refund',
                            'Kasbon', 'Owner', 'Compliment', 'BPJS', 'Jumlah'
                        ];

                        foreach ($headers as $key => $header) {
                            $col = chr(65 + $key); // A, B, C, etc.
                            $sheet->setCellValue($col . '1', $header);
                            // Style header
                            $sheet->getStyle($col . '1')->getFont()->setBold(true);
                        }

                        // Fill data
                        $row = 2;
                        foreach ($data as $item) {
                            $sheet->setCellValue('A' . $row, $item->tanggal);
                            $sheet->setCellValue('B' . $row, 'Rp ' . number_format($item->penjualan, 0, ',', '.'));
                            $sheet->setCellValue('C' . $row, 'Rp ' . number_format($item->qris, 0, ',', '.'));
                            $sheet->setCellValue('D' . $row, 'Rp ' . number_format($item->tunai, 0, ',', '.'));
                            $sheet->setCellValue('E' . $row, 'Rp ' . number_format($item->b_baku, 0, ',', '.'));
                            $sheet->setCellValue('F' . $row, 'Rp ' . number_format($item->peralatan, 0, ',', '.'));
                            $sheet->setCellValue('G' . $row, 'Rp ' . number_format($item->band, 0, ',', '.'));
                            $sheet->setCellValue('H' . $row, 'Rp ' . number_format($item->listrik, 0, ',', '.'));
                            $sheet->setCellValue('I' . $row, 'Rp ' . number_format($item->gas, 0, ',', '.'));
                            $sheet->setCellValue('J' . $row, 'Rp ' . number_format($item->refund, 0, ',', '.'));
                            $sheet->setCellValue('K' . $row, 'Rp ' . number_format($item->kasbon, 0, ',', '.'));
                            $sheet->setCellValue('L' . $row, 'Rp ' . number_format($item->owner, 0, ',', '.'));
                            $sheet->setCellValue('M' . $row, 'Rp ' . number_format($item->compliment, 0, ',', '.'));
                            $sheet->setCellValue('N' . $row, 'Rp ' . number_format($item->bpjs, 0, ',', '.'));
                            $sheet->setCellValue('O' . $row, 'Rp ' . number_format($item->tb1_jumlah, 0, ',', '.'));

                            // Set format cells sebagai text untuk mempertahankan format angka
                            $sheet->getStyle('B'.$row.':O'.$row)->getNumberFormat()->setFormatCode('@');
                            $row++;
                        }

                        // Add totals row
                        $totalRow = $row;
                        $sheet->setCellValue('A' . $totalRow, 'TOTAL');
                        $sheet->setCellValue('B' . $totalRow, 'Rp ' . number_format($totald['total_penjualan'], 0, ',', '.'));
                        $sheet->setCellValue('C' . $totalRow, 'Rp ' . number_format($totald['total_qris'], 0, ',', '.'));
                        $sheet->setCellValue('D' . $totalRow, 'Rp ' . number_format($totald['total_tunai'], 0, ',', '.'));
                        $sheet->setCellValue('E' . $totalRow, 'Rp ' . number_format($totald['total_bbaku'], 0, ',', '.'));
                        $sheet->setCellValue('F' . $totalRow, 'Rp ' . number_format($totald['total_peralatan'], 0, ',', '.'));
                        $sheet->setCellValue('G' . $totalRow, 'Rp ' . number_format($totald['total_band'], 0, ',', '.'));
                        $sheet->setCellValue('H' . $totalRow, 'Rp ' . number_format($totald['total_listrik'], 0, ',', '.'));
                        $sheet->setCellValue('I' . $totalRow, 'Rp ' . number_format($totald['total_gas'], 0, ',', '.'));
                        $sheet->setCellValue('J' . $totalRow, 'Rp ' . number_format($totald['total_refund'], 0, ',', '.'));
                        $sheet->setCellValue('K' . $totalRow, 'Rp ' . number_format($totald['total_kasbon'], 0, ',', '.'));
                        $sheet->setCellValue('L' . $totalRow, 'Rp ' . number_format($totald['total_owner'], 0, ',', '.'));
                        $sheet->setCellValue('M' . $totalRow, 'Rp ' . number_format($totald['total_compliment'], 0, ',', '.'));
                        $sheet->setCellValue('N' . $totalRow, 'Rp ' . number_format(collect($data)->sum('bpjs'), 0, ',', '.'));
                        $sheet->setCellValue('O' . $totalRow, 'Rp ' . number_format(collect($data)->sum('tb1_jumlah'), 0, ',', '.'));

                        // Style total row
                        $sheet->getStyle('A' . $totalRow . ':O' . $totalRow)->getFont()->setBold(true);

                        // Auto-size columns
                        foreach (range('A', 'O') as $col) {
                            $sheet->getColumnDimension($col)->setAutoSize(true);
                        }

                        // Create the Excel file
                        $writer = new Xlsx($spreadsheet);

                        // Save to temp file and return response
                        $temp_file = tempnam(sys_get_temp_dir(), 'cash-flow');
                        $writer->save($temp_file);

                        return response()->download($temp_file, 'cash-flow-' . now()->format('Y-m-d') . '.xlsx')
                            ->deleteFileAfterSend(true);
                    })
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
            'index' => Pages\ListCustomCashInOutTables::route('/'),
            'create' => Pages\CreateCustomCashInOutTable::route('/create'),
            'edit' => Pages\EditCustomCashInOutTable::route('/{record}/edit'),
        ];
    }
}
