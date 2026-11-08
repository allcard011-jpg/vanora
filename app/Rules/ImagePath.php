<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ImagePath implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $valid = is_string($value)
            && ! str_contains($value, '..')
            && preg_match('#^(https://\S+|images/[A-Za-z0-9_\-./]+)$#', $value) === 1;

        if (! $valid) {
            $fail('Isi dengan alamat https://… atau path seperti images/products/nama.jpg.');
        }
    }
}
