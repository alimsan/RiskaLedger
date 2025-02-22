<?php

namespace App\Filament\Widgets;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Widgets\Widget;
use Filament\Forms;

class DateFilterWidget extends Widget implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static string $view = 'filament.widgets.date-filter-widget';

    public ?string $selectedMonth = null;

    protected function getFormSchema(): array
    {
        return [
            DatePicker::make('selectedMonth')
                ->label('Pilih Bulan')
                ->default(now()->firstOfMonth())
                ->format('Y-m-d')
                ->displayFormat('F Y')
                ->closeOnDateSelection()
                ->reactive()
                ->afterStateUpdated(function ($state) {
                    $date = \Carbon\Carbon::parse($state)->firstOfMonth()->format('Y-m-d');
                    session(['selected_month' => $date]);
                    $this->dispatch('dateFilterChanged', state: $date);
                }),
        ];
    }

    public function mount(): void
    {
        $savedDate = session('selected_month');

        if ($savedDate) {
            $this->selectedMonth = $savedDate;
        } else {
            $defaultDate = now()->firstOfMonth()->format('Y-m-d');
            $this->selectedMonth = $defaultDate;
            session(['selected_month' => $defaultDate]);
        }

        $this->form->fill([
            'selectedMonth' => $this->selectedMonth,
        ]);
    }
}
