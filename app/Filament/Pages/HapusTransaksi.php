<?php

namespace App\Filament\Pages;

use App\Models\mCashInOut;
use App\Models\Piutang;
use App\Models\TransactionItems;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class HapusTransaksi extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-trash';
    protected static ?string $navigationLabel = 'Hapus Transaksi';
    protected static ?string $title = 'Hapus Transaksi';
    protected static ?string $slug = 'hapus-transaksi';
    protected static ?string $navigationGroup = 'Transaksi MOD';

    protected static string $view = 'filament.pages.hapus-transaksi';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();
        
        if (in_array($user->role, ['superadmin', 'admin', 'owner']) || $user->hasRole(['superadmin', 'admin', 'owner'])) {
            return true;
        }

        return false;
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        $user = auth()->user();
        $isSuperAdminOrAdmin = in_array($user->role, ['superadmin', 'admin']) || $user->hasRole(['superadmin', 'admin']);

        $schema = [];

        if ($isSuperAdminOrAdmin) {
            $schema[] = Select::make('tenant_id')
                ->label('Tenant')
                ->options(\App\Models\Tenant::pluck('name', 'id'))
                ->required()
                ->searchable()
                ->preload();
        }

        $schema[] = Select::make('tabel')
            ->label('Tabel Transaksi')
            ->options([
                'cash_in_out' => 'Pengeluaran/Pemasukan (cash_in_out)',
                'piutangs' => 'Piutang (piutangs)',
            ])
            ->required();

        $schema[] = DatePicker::make('tanggal')
            ->label('Tanggal Transaksi')
            ->required()
            ->native(false)
            ->displayFormat('d M Y')
            ->closeOnDateSelection();

        return $form
            ->schema([
                Section::make('Pilih Data Transaksi yang Akan Dihapus')
                    ->description('Tindakan ini akan menghapus data transaksi beserta riwayat pada transaction_items secara permanen.')
                    ->schema($schema)
            ])
            ->statePath('data');
    }

    public function hapusData(): void
    {
        $data = $this->form->getState();
        $user = auth()->user();
        $isSuperAdminOrAdmin = in_array($user->role, ['superadmin', 'admin']) || $user->hasRole(['superadmin', 'admin']);
        
        $tenantId = $isSuperAdminOrAdmin ? $data['tenant_id'] : $user->tenant_id;
        $tabel = $data['tabel'];
        $tanggal = $data['tanggal'];

        $count = 0;

        DB::beginTransaction();

        try {
            if ($tabel === 'cash_in_out') {
                $records = mCashInOut::where('tenant_id', $tenantId)
                    ->whereDate('waktu', $tanggal)
                    ->get();

                foreach ($records as $record) {
                    // Hapus riwayat pada transaction_items (penjualan)
                    TransactionItems::where('transaction_id', $record->id)
                        ->where('transaction_type', 'penjualan')
                        ->delete();

                    $record->delete();
                    $count++;
                }
            } elseif ($tabel === 'piutangs') {
                $records = Piutang::where('tenant_id', $tenantId)
                    ->whereDate('waktu', $tanggal)
                    ->get();

                foreach ($records as $record) {
                    // Hapus riwayat pada transaction_items (piutang)
                    TransactionItems::where('transaction_id', $record->id)
                        ->where('transaction_type', 'piutang')
                        ->delete();

                    $record->delete();
                    $count++;
                }
            }

            DB::commit();

            // Mencatat log aktivitas penghapusan massal
            activity('hapus_transaksi_massal')
                ->causedBy($user)
                ->withProperties([
                    'tenant_id' => $tenantId,
                    'tabel' => $tabel,
                    'tanggal' => $tanggal,
                    'jumlah_dihapus' => $count,
                ])
                ->log("User {$user->name} melakukan penghapusan massal pada tabel {$tabel} sebanyak {$count} data untuk tanggal {$tanggal}");

            Notification::make()
                ->title('Berhasil!')
                ->body("Berhasil menghapus {$count} data transaksi dari tabel {$tabel} untuk tanggal {$tanggal}.")
                ->success()
                ->send();

            $this->form->fill();
        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->title('Gagal!')
                ->body("Terjadi kesalahan: " . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
