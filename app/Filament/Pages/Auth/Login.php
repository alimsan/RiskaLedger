<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;

class Login extends BaseLogin
{
    public function schema(array $arguments = []): array
{
    $currentYear = now()->year; // Tambahkan definisi $currentYear di sini

    return [
        // Komponen default
        $this->getEmailFormComponent(),
        $this->getPasswordFormComponent(),

        // Section baru untuk field tambahan
        \Filament\Forms\Components\Section::make('Pilih Periode')
            ->description('Silakan pilih bulan, tanggal, dan tahun')
            ->schema([
                // Pilih bulan
                Select::make('month')
                    ->label('Bulan')
                    ->columnSpanFull()
                    ->options([
                        '01' => 'Januari',
                        '02' => 'Februari',
                        '03' => 'Maret',
                        '04' => 'April',
                        '05' => 'Mei',
                        '06' => 'Juni',
                        '07' => 'Juli',
                        '08' => 'Agustus',
                        '09' => 'September',
                        '10' => 'Oktober',
                        '11' => 'November',
                        '12' => 'Desember',
                    ])
                    ->required(),

                // Grid untuk tanggal dan tahun
                Grid::make(2)
                    ->schema([
                        Select::make('day')
                            ->label('Tanggal')
                            ->options(function () {
                                return array_combine(
                                    range(1, 31),
                                    array_map(function($day) {
                                        return str_pad($day, 2, '0', STR_PAD_LEFT);
                                    }, range(1, 31))
                                );
                            })
                            ->required(),

                        Select::make('year')
                            ->label('Tahun')
                            ->options(function () {
                                $currentYear = now()->year;
                                return array_combine(
                                    range($currentYear - 5, $currentYear + 5),
                                    range($currentYear - 5, $currentYear + 5)
                                );
                            })
                            ->default($currentYear)
                            ->required(),
                    ])
            ])
    ];
}

public function authenticate(): LoginResponse|null
{
    // Simpan data ke session
    $state = $this->form->getState();

    session([
        'selected_month' => $state['month'] ?? null,
        'selected_day' => $state['day'] ?? null,
        'selected_year' => $state['year'] ?? null
    ]);

    // Proses login
    return parent::authenticate();
}
}
