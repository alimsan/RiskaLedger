<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HasilAkhirResource\Pages;
use App\Filament\Resources\HasilAkhirResource\RelationManagers;
use App\Models\mCashInOut;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
            ->view('filament.resources.custom-cash-in-out-table.hasil', [
                'totald' => static::getTotaldata()
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
