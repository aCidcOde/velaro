<?php

/*
[Modulo: app/Support]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Formata dinheiro e percentual no padrao pt-BR das telas do Portal, num lugar so.
*/

namespace App\Support;

/**
 * As telas do portal mostram muito número: custo Velaro, margem, markup, preço
 * sugerido. Formatar em cada view espalharia `number_format(..., ',', '.')` por
 * dezenas de linhas e a primeira que esquecesse o separador sairia com ponto de
 * milhar americano no meio da tabela de preços.
 */
final class ValorPtBr
{
    /**
     * `1234.5` vira `R$ 1.234,50`.
     */
    public static function moeda(float $valor): string
    {
        return 'R$ '.number_format($valor, 2, ',', '.');
    }

    /**
     * `48.7` vira `48,7%`. A tela 2.7 usa uma casa nos KPIs e na tabela.
     */
    public static function percentual(float $valor, int $casas = 1): string
    {
        return number_format($valor, $casas, ',', '.').'%';
    }

    /**
     * Numero sem zero a direita: `3.60` vira `3,6`, `3.00` vira `3`.
     */
    public static function numero(float $valor, int $casas = 2): string
    {
        return rtrim(rtrim(number_format($valor, $casas, ',', '.'), '0'), ',');
    }

    /**
     * Multiplicador como o stepper da tela 2.6 o mostra: `3.60` vira `3,6x`.
     */
    public static function multiplicador(float $valor): string
    {
        return self::numero($valor).'x';
    }
}
