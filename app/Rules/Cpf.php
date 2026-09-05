<?php

/*
[Modulo: app/Rules]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Valida CPF pelos dois digitos verificadores (modulo 11), com ou sem mascara.
*/

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Cpf implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_scalar($value)) {
            $fail('Informe um CPF válido.');

            return;
        }

        $digits = (string) preg_replace('/\D/', '', (string) $value);

        if (strlen($digits) !== 11 || preg_match('/^(\d)\1{10}$/', $digits) === 1) {
            $fail('Informe um CPF válido.');

            return;
        }

        foreach ([9, 10] as $length) {
            if ($digits[$length] !== self::checkDigit($digits, $length)) {
                $fail('Informe um CPF válido.');

                return;
            }
        }
    }

    private static function checkDigit(string $digits, int $length): string
    {
        $sum = 0;

        for ($index = 0; $index < $length; $index++) {
            $sum += (int) $digits[$index] * ($length + 1 - $index);
        }

        $remainder = ($sum * 10) % 11;

        return (string) ($remainder === 10 ? 0 : $remainder);
    }
}
