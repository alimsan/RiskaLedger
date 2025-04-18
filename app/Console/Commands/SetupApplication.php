<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SetupApplication extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:setup-application {--fresh : Refresh database} {--seed : Seed database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup aplikasi CashInOut dengan migrasi dan seeder';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai setup aplikasi Cash In Out...');

        // Migrasi database
        if ($this->option('fresh')) {
            $this->info('Menjalankan fresh migration...');
            Artisan::call('migrate:fresh', ['--force' => true]);
            $this->info(Artisan::output());
        } else {
            $this->info('Menjalankan migrasi...');
            Artisan::call('migrate', ['--force' => true]);
            $this->info(Artisan::output());
        }

        // Seed database
        if ($this->option('seed') || $this->option('fresh')) {
            $this->info('Menjalankan seeder...');
            Artisan::call('db:seed', ['--force' => true]);
            $this->info(Artisan::output());
        }

        // Migrasi data type ke type_id
        $this->info('Menjalankan migrasi data dari type ke type_id...');
        Artisan::call('app:migrate-cash-in-out-type-data', ['--force' => true]);
        $this->info(Artisan::output());

        // Cache clear
        $this->info('Membersihkan cache...');
        Artisan::call('cache:clear');
        $this->info(Artisan::output());

        // View clear
        $this->info('Membersihkan view cache...');
        Artisan::call('view:clear');
        $this->info(Artisan::output());

        // Config clear
        $this->info('Membersihkan config cache...');
        Artisan::call('config:clear');
        $this->info(Artisan::output());

        // Route clear
        $this->info('Membersihkan route cache...');
        Artisan::call('route:clear');
        $this->info(Artisan::output());

        $this->info('Setup aplikasi selesai!');
        $this->info('Anda dapat login dengan kredensial:');
        $this->info('Email: admin@example.com');
        $this->info('Password: 123.dmn');
    }
}
