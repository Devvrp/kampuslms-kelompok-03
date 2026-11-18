<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class MaxOriginalFileNameLength implements ValidationRule
{
    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail
    ): void {
        if (
            $value instanceof UploadedFile
            && mb_strlen($value->getClientOriginalName(), 'UTF-8') > 255
        ) {
            $fail('Nama berkas maksimal 255 karakter.');
        }
    }
}