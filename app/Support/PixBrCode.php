<?php

/*
[Modulo: app/Support]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Monta o payload Pix copia e cola (EMV BR Code) da cobranca do lote, sem gateway e sem chamada externa.
*/

namespace App\Support;

use Normalizer;

/**
 * O "Pix copia e cola" da tela 2.4 e um dado **derivado**, nao uma resposta de
 * API: o BR Code do Banco Central e uma string EMV montada a partir da chave do
 * beneficiario, do nome, da cidade, do valor e de um identificador. Nada aqui
 * conversa com banco nenhum — e por isso que a tela de pagamento consegue ser
 * uma tela de exibicao e ainda assim mostrar um codigo que o app do banco le.
 *
 * A alternativa seria imprimir a string do prototipo. Um payload de fachada e
 * pior que nenhum: o lojista tenta pagar, o app recusa, e a culpa cai no
 * financeiro da Velaro. Sem chave configurada esta classe devolve `null` e a
 * tela mostra a pendencia de configuracao.
 */
final class PixBrCode
{
    /** Identificador do arranjo Pix dentro do campo 26 (Merchant Account Information). */
    private const GUI = 'br.gov.bcb.pix';

    /** Limites do padrao EMV para nome e cidade do recebedor. */
    private const MAX_NOME = 25;

    private const MAX_CIDADE = 15;

    /** O `txid` do campo 62-05 aceita ate 25 caracteres alfanumericos. */
    private const MAX_TXID = 25;

    /**
     * Payload completo, com CRC16 no fim. Devolve `null` quando falta a chave —
     * o unico campo que nao tem substituto razoavel.
     */
    public static function payload(?string $chave, string $nome, string $cidade, float $valor, string $identificador): ?string
    {
        $chave = trim((string) $chave);

        if ($chave === '') {
            return null;
        }

        $parcial = self::campo('00', '01')
            .self::campo('26', self::campo('00', self::GUI).self::campo('01', $chave))
            .self::campo('52', '0000')
            .self::campo('53', '986')
            .self::campo('54', number_format($valor, 2, '.', ''))
            .self::campo('58', 'BR')
            .self::campo('59', self::texto($nome, self::MAX_NOME))
            .self::campo('60', self::texto($cidade, self::MAX_CIDADE))
            .self::campo('62', self::campo('05', self::txid($identificador)))
            // O campo 63 entra no calculo do CRC com o proprio cabecalho ("6304").
            .'6304';

        return $parcial.self::crc16($parcial);
    }

    /**
     * Cada campo EMV e `id + tamanho em dois digitos + valor`.
     */
    private static function campo(string $id, string $valor): string
    {
        // Tamanho em bytes, nao em caracteres: quem le o payload le uma sequencia
        // de bytes, e um acento que escapasse contaria dois.
        return $id.str_pad((string) strlen($valor), 2, '0', STR_PAD_LEFT).$valor;
    }

    /**
     * O BR Code nao carrega acento: "Alianças" vira "ALIANCAS" antes de entrar no
     * payload, senao o tamanho declarado no campo nao bate com o que o leitor conta.
     */
    private static function texto(string $valor, int $limite): string
    {
        $semAcento = $valor;

        if (class_exists(Normalizer::class)) {
            // FORM_D separa a letra do acento; o filtro seguinte descarta o acento.
            $semAcento = (string) Normalizer::normalize($valor, Normalizer::FORM_D);
        } elseif (function_exists('iconv')) {
            $semAcento = (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $valor);
        }

        $ascii = (string) preg_replace('/[^\x20-\x7E]/', '', $semAcento);

        return mb_strtoupper(substr(trim($ascii), 0, $limite));
    }

    private static function txid(string $identificador): string
    {
        $limpo = (string) preg_replace('/[^A-Za-z0-9]/', '', $identificador);

        // "***" e o coringa do padrao para cobranca sem identificador proprio.
        return $limpo === '' ? '***' : mb_substr($limpo, 0, self::MAX_TXID);
    }

    /**
     * CRC16/CCITT-FALSE (polinomio 0x1021, valor inicial 0xFFFF), em hexadecimal
     * maiusculo de quatro digitos — exatamente o que o campo 63 do BR Code espera.
     */
    private static function crc16(string $payload): string
    {
        $crc = 0xFFFF;

        foreach (str_split($payload) as $caractere) {
            $crc ^= ord($caractere) << 8;

            for ($bit = 0; $bit < 8; $bit++) {
                $crc = ($crc & 0x8000) !== 0
                    ? (($crc << 1) ^ 0x1021) & 0xFFFF
                    : ($crc << 1) & 0xFFFF;
            }
        }

        return mb_strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
