<?php

/*
[Modulo: app/Rules]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Valida CNPJ pelos dois digitos verificadores (modulo 11), com ou sem mascara.
*/

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Cnpj implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_scalar($value)) {
            $fail('Informe um CNPJ válido.');

            return;
        }

        $digits = (string) preg_replace('/\D/', '', (string) $value);

        if (strlen($digits) !== 14 || preg_match('/^(\d)\1{13}$/', $digits) === 1) {
            $fail('Informe um CNPJ válido.');

            return;
        }

        foreach ([12, 13] as $position) {
            if ($digits[$position] !== self::checkDigit($digits, $position)) {
                $fail('Informe um CNPJ válido.');

                return;
            }
        }
    }

    /**
     * Pesos do CNPJ: 5..2 seguidos de 9..2 para o primeiro digito e 6..2 seguidos
     * de 9..2 para o segundo.
     */
    private static function checkDigit(string $digits, int $position): string
    {
        $weight = $position === 12 ? 5 : 6;
        $sum = 0;

        for ($index = 0; $index < $position; $index++) {
            $sum += (int) $digits[$index] * $weight;
            $weight = $weight === 2 ? 9 : $weight - 1;
        }

        $remainder = $sum % 11;

        return (string) ($remainder < 2 ? 0 : 11 - $remainder);
    }
}
