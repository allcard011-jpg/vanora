<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Database\Seeders\AdminSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;

class BootstrapProduction extends Command
{
    protected $signature = 'vanora:bootstrap';

    protected $description = 'Mengisi data awal pada database baru (sekali) dan memastikan akun admin ada. Aman dijalankan berulang.';

    private const MARKER = 'initial_seed_done';

    public function handle(): int
    {
        if (Setting::get(self::MARKER) === null) {
            $this->info('Database baru: mengisi data awal...');
            $this->call('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);
            Setting::put(self::MARKER, now()->toIso8601String());

            return self::SUCCESS;
        }

        $this->info('Data awal sudah ada. Memastikan akun admin...');
        $this->call('db:seed', ['--class' => AdminSeeder::class, '--force' => true]);

        return self::SUCCESS;
    }
}
