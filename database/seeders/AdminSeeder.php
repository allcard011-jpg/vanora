<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('vanora.admin_email');
        $password = config('vanora.admin_password');

        if (! $email || ! $password || strlen($password) < 12) {
            throw new RuntimeException('Isi ADMIN_EMAIL dan ADMIN_PASSWORD (minimal 12 karakter) di .env sebelum seeding.');
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => config('vanora.admin_name'),
                'password' => Hash::make($password),
            ]
        );
    }
}
