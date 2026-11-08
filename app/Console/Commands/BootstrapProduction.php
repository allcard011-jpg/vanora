<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Database\Seeders\AdminSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;

class BootstrapProduction extends Command
{
    protected $signature = 'vanora:bootstrap';

    protected $description = 'Mengisi data awal database dan memastikan akun admin tersedia.';

    private const MARKER = 'initial_seed_done';

    public function handle(): int
    {
        if (Setting::get(self::MARKER) === null) {
            $this->info('Database baru: mengisi data awal...');

            $result = $this->call('db:seed', [
                '--class' => DatabaseSeeder::class,
                '--force' => true,
            ]);

            if ($result !== self::SUCCESS) {
                $this->error('Pengisian data awal gagal. Marker tidak disimpan.');
                return self::FAILURE;
            }

            Setting::put(self::MARKER, now()->toIso8601String());

            $this->info('Data awal berhasil disimpan.');

            return self::SUCCESS;
        }

        $this->info('Data awal sudah tersedia. Memastikan akun admin...');

        $result = $this->call('db:seed', [
            '--class' => AdminSeeder::class,
            '--force' => true,
        ]);

        if ($result !== self::SUCCESS) {
            $this->error('Pemeriksaan akun admin gagal.');
            return self::FAILURE;
        }

        $this->info('Bootstrap Vanora berhasil.');

        return self::SUCCESS;
    }
}
