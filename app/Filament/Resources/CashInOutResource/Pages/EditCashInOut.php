<?php

namespace App\Filament\Resources\CashInOutResource\Pages;

use App\Filament\Resources\CashInOutResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use App\Models\mCashInOut;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class EditCashInOut extends EditRecord
{
    protected static string $resource = CashInOutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Tidak perlu mutasi data karena sudah ditangani di fillForm()
        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Kita akan menggunakan custom save
        return $record;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();

        // Jika user bukan administrator, paksa tenant_id ke tenant user
        if (!$user->isAdministrator()) {
            $data['tenant_id'] = $user->tenant_id;
        }

        return $data;
    }

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        $user = auth()->user();
        $record = $this->getRecord();

        // Jika bukan admin dan mencoba akses tenant lain, tolak
        if (!$user->isAdministrator() && $record->tenant_id !== $user->tenant_id) {
            abort(403, 'Anda tidak memiliki akses ke data ini.');
        }
    }

    public function mount($record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->authorizeAccess();

        $this->fillForm();

        $this->previousUrl = url()->previous();
    }

    // Isi form dengan data dari database
    protected function fillForm(): void
    {
        $record = $this->getRecord();

        $entries = [];

        // Cari record dengan id yang sama dengan record saat ini
        if ($record) {
            $entries[] = [
                'nama_barang' => $record->nama_barang,
                'type_id' => $record->type_id,
                'nilai' => $record->nilai,
                'waktu' => $record->waktu,
                'deksripsi' => $record->keterangan ?? $record->deksripsi,
            ];
        }

        $data = [
            'tenant_id' => $record->tenant_id,
            'cash_in_out_entries' => $entries,
        ];

        $this->form->fill($data);
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        $this->authorizeAccess();

        try {
            $this->callHook('beforeValidate');
            $data = $this->form->getState();
            $this->callHook('afterValidate');

            $this->callHook('beforeSave');

            DB::beginTransaction();

            $record = $this->getRecord();
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

            // Hapus record lama
            if ($record) {
                $record->delete();
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

            $this->callHook('afterSave');

            if ($shouldSendSavedNotification) {
                Notification::make()
                    ->title('Data berhasil disimpan')
                    ->success()
                    ->send();
            }

            if ($shouldRedirect) {
                $this->redirect($this->getRedirectUrl());
            }

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
