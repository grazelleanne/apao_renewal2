<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Reject empty, whitespace-only, control, and Unicode format characters. */
class NoInvisibleCharacters implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || preg_match('/^\s*$/u', $value) === 1) {
            $fail('The :attribute field cannot be blank.');
            return;
        }

        if (preg_match('/[\p{C}]/u', $value) === 1) {
            $fail('The :attribute field contains invisible or control characters.');
        }
    }
}
