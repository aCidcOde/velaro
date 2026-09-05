<?php

/*
[Modulo: app/Support]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Vocabulario do select "Origem do contato" do cadastro de lojista e o rotulo de cada chave.
*/

namespace App\Support;

class ResellerContactSources
{
    /**
     * Chave gravada em `resellers.contact_source` => rotulo exibido nas telas 1.4, 1.5 e 1.6.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'site' => 'Site',
            'indicacao_lojista' => 'Indicação de lojista parceiro',
            'representante' => 'Representante comercial',
            'instagram' => 'Instagram',
            'whatsapp' => 'WhatsApp',
            'feira' => 'Feira ou evento do setor',
            'outro' => 'Outro',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(self::options());
    }

    public static function label(?string $key): ?string
    {
        if ($key === null || $key === '') {
            return null;
        }

        return self::options()[$key] ?? $key;
    }
}
