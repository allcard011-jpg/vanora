<?php

namespace App\Support;

class Rupiah
{
    public static function format(int $amount): string
    {
        return 'Rp'.number_format($amount, 0, ',', '.');
    }
}
