<?php

namespace App\Support;

class Phone
{
    /** Ubah 0812…, +62 812…, 812… menjadi 62812…; null jika bukan nomor seluler Indonesia yang masuk akal. */
    public static function normalize(?string $input): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $input);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        return preg_match('/^628[0-9]{8,11}$/', $digits) === 1 ? $digits : null;
    }

    /** 6285… menjadi 085… untuk ditampilkan. */
    public static function display(string $normalized): string
    {
        return str_starts_with($normalized, '62') ? '0'.substr($normalized, 2) : $normalized;
    }
}
