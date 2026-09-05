<?php

/*
[Modulo: lang/pt_BR]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Rotulos em portugues dos status do revendedor, tipo de cadastro, documentos, consentimentos e regras de preco.
*/

return [
    'status' => [
        'pending' => 'Pendente',
        'awaiting_info' => 'Aguardando informações',
        'approved' => 'Aprovado',
        'rejected' => 'Reprovado',
        'inactive' => 'Inativo',
    ],

    'registration_type' => [
        'automatic' => 'Automático',
        'manual' => 'Manual',
    ],

    'document_type' => [
        'articles_of_incorporation' => 'Contrato social',
        'partner_id_document' => 'Documento do sócio',
        'cnpj_card' => 'Cartão CNPJ',
    ],

    'consent_type' => [
        'terms' => 'Termos de Uso',
        'privacy_policy' => 'Política de Privacidade',
    ],

    'verification_status' => [
        'pending' => 'Pendente',
    ],

    'pricing_model' => [
        'multiplier' => 'Multiplicador',
        'percent' => 'Percentual',
    ],

    'price_rule_scope' => [
        'global' => 'Global',
        'collection' => 'Por coleção',
        'product' => 'Por produto',
    ],

    'price_rule_mode' => [
        'multiplier' => 'Multiplicador',
        'percent' => 'Percentual',
        'manual' => 'Manual',
        'promo' => 'Promocional',
    ],

    'rounding' => [
        'up_099' => 'Para cima (0,99)',
    ],
];
