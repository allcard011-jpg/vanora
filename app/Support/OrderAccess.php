<?php

namespace App\Support;

/** Daftar nomor pesanan yang boleh dilihat oleh session pelanggan ini. */
class OrderAccess
{
    private const KEY = 'viewable_orders';
    private const LIMIT = 20;

    public static function grant(string $orderNumber): void
    {
        $list = array_values(array_unique([...session(self::KEY, []), $orderNumber]));

        session([self::KEY => array_slice($list, -self::LIMIT)]);
    }

    public static function allows(string $orderNumber): bool
    {
        return in_array($orderNumber, session(self::KEY, []), true);
    }
}
