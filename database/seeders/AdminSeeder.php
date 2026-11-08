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
        $name = config('vanora.admin_name') ?: 'Admin';

        if (! $email || ! $password || strlen($password) < 12) {
            throw new RuntimeException(
                'Isi ADMIN_EMAIL dan ADMIN_PASSWORD (minimal 12 karakter) di Environment.'
            );
        }

        User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
            ]
        );
    }
}
