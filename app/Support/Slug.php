<?php

namespace App\Support;

use Illuminate\Support\Str;

class Slug
{
    /** Slug unik untuk sebuah model; menambah -2, -3, dst. bila bentrok. */
    public static function unique(string $text, string $modelClass, ?int $ignoreId = null): string
    {
        $base = Str::slug($text) ?: 'item';
        $slug = $base;
        $i = 2;

        while ($modelClass::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
