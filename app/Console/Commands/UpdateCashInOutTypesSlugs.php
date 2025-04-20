<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CashInOutType;
use Illuminate\Support\Str;
use App\Models\Tenant;

class UpdateCashInOutTypesSlugs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-cash-in-out-types-slugs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update slugs for all CashInOutType models and assign default tenant';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating CashInOutType slugs...');

        // Ambil tenant default
        $tenant = Tenant::first();

        if (!$tenant) {
            $this->error('No tenant found. Please run tenant seeder first.');
            return 1;
        }

        // Ambil semua tipe transaksi
        $types = CashInOutType::all();

        $bar = $this->output->createProgressBar(count($types));
        $bar->start();

        foreach ($types as $type) {
            // Update slug jika belum ada
            if (empty($type->slug)) {
                $type->slug = Str::slug($type->name);
            }

            // Assign tenant jika belum ada
            if (empty($type->tenant_id)) {
                $type->tenant_id = $tenant->id;
            }

            $type->save();

            $bar->advance();
        }

        $bar->finish();

        $this->newLine();
        $this->info('All slugs updated successfully!');

        return 0;
    }
}
