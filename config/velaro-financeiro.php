<?php

/*
[Modulo: config]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Dados de cobranca da Velaro contra o lojista: prazo do lote, deposito das notas e beneficiario dos meios B2B.
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Hora limite do vencimento do lote
    |--------------------------------------------------------------------------
    |
    | `order_batches.due_date` guarda apenas o dia. O horario de corte e o mesmo
    | para todo lote — e o "as 18h" que a tela 2.4 repete em cada prazo —, entao
    | ele mora aqui e nao numa coluna replicada linha a linha.
    |
    */

    'hora_limite' => 18,

    /*
    |--------------------------------------------------------------------------
    | Deposito das notas fiscais
    |--------------------------------------------------------------------------
    |
    | `invoices.pdf_path` e `invoices.xml_path` sao caminhos relativos; o disco
    | onde eles moram e configuracao de infraestrutura. So um disco publico
    | consegue gerar URL de download direto — em disco privado a acao aparece
    | desabilitada, porque nao ha rota de download no Portal.
    |
    */

    'notas' => [
        'disco' => env('VELARO_INVOICE_DISK', 'public'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Beneficiario da cobranca B2B
    |--------------------------------------------------------------------------
    |
    | Quem recebe e a Velaro; quem paga e o lojista. Nome e cidade sao a
    | identidade publica da fabrica e tem padrao. **Chave Pix, CNPJ e conta
    | bancaria nao tem**: dado bancario com valor inventado no codigo e dinheiro
    | indo para lugar nenhum. Sem configuracao a tela mostra o aviso de
    | pendencia, nunca um numero de fachada.
    |
    */

    'beneficiario' => [
        'razao_social' => env('VELARO_PAYEE_NAME', 'Velaro Alianças Indústria e Comércio Ltda'),
        'cidade' => env('VELARO_PAYEE_CITY', 'SAO PAULO'),
        'cnpj' => env('VELARO_PAYEE_CNPJ'),
        'pix_chave' => env('VELARO_PIX_KEY'),
        'banco_codigo' => env('VELARO_BANK_CODE'),
        'banco_nome' => env('VELARO_BANK_NAME'),
        'agencia' => env('VELARO_BANK_BRANCH'),
        'conta' => env('VELARO_BANK_ACCOUNT'),
    ],

];
