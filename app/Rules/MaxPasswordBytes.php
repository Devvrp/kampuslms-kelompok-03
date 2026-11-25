<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MaxPasswordBytes implements ValidationRule
{
    public const MAX_BYTES = 72;

    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail
    ): void {
        if (is_string($value) && strlen($value) > self::MAX_BYTES) {
            $fail('Kata sandi maksimal 72 byte.');
        }
    }
}