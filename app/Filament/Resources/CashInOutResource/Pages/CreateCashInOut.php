<?php

namespace App\Filament\Resources\CashInOutResource\Pages;

use App\Filament\Resources\CashInOutResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use App\Models\mCashInOut;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class CreateCashInOut extends CreateRecord
{
    protected static string $resource = CashInOutResource::class;

    protected function getRedirectUrl(): string
    {
        // Redirect ke halaman list CashInOutResource
        return $this->getResource()::getUrl('index');
    }

    // Nonaktifkan redirect ke halaman edit setelah create
    protected function getCreatedNotificationAction(): ?\Filament\Notifications\Actions\Action
    {
        return null;
    }

    protected function handleRecordCreation(array $data): EloquentModel
    {
        // Tidak perlu membuat record karena kita akan menggunakan proses custom
        return new mCashInOut();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        // Jika user bukan administrator, paksa tenant_id ke tenant user
        if (!$user->isAdministrator()) {
            $data['tenant_id'] = $user->tenant_id;
        }

        return $data;
    }

    // Custom save behavior
    public function create(bool $another = false): void
    {
        $this->authorizeAccess();

        try {
            $this->callHook('beforeValidate');
            $data = $this->form->getState();
            $this->callHook('afterValidate');

            $this->callHook('beforeCreate');

            DB::beginTransaction();

            $user = auth()->user();
            $tenant_id = $user->isAdministrator() ? $data['tenant_id'] : $user->tenant_id;

            // Cek apakah ada entries
            if (empty($data['cash_in_out_entries'])) {
                Notification::make()
                    ->title('Gagal menyimpan data')
                    ->body('Minimal harus ada satu entri transaksi.')
                    ->danger()
                    ->send();

                return;
            }

            // Simpan setiap entri sebagai record terpisah
            foreach ($data['cash_in_out_entries'] as $entry) {
                mCashInOut::create([
                    'tenant_id' => $tenant_id,
                    'type_id' => $entry['type_id'],
                    'nilai' => $entry['nilai'],
                    'waktu' => $entry['waktu'],
                    'nama_barang' => $entry['nama_barang'] ?? null,
                    'keterangan' => $entry['deksripsi'] ?? null,
                    'deksripsi' => $entry['deksripsi'] ?? null,
                ]);
            }

            DB::commit();

            $this->callHook('afterCreate');

            // Ganti getSavedNotification dengan Notification langsung
            Notification::make()
                ->title('Data berhasil disimpan')
                ->success()
                ->send();

            // Redirect ke halaman list jika tidak memilih create another
            if (! $another) {
                $this->redirect($this->getRedirectUrl());

                return;
            }

            // Otherwise, refresh the form
            $this->form->fill();

        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->title('Gagal menyimpan data')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
