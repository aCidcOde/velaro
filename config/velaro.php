<?php

/*
[Modulo: config]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Parametros do modulo Velaro lidos via config(), nunca por env() fora daqui.
*/

return [

    /*
    | Senha das contas de demonstracao do VelaroSeeder.
    |
    | Precisa morar num arquivo de config, e nao ser lida com env() direto no
    | seeder: `php artisan config:cache` — que o pipeline de deploy roda a cada
    | publicacao — faz env() devolver null fora dos arquivos de config, e o
    | seeder cairia silenciosamente no fallback publico do repositorio.
    |
    | O fallback vale so fora de producao, para o clone recem-feito conseguir
    | entrar sem configurar nada. Em producao o seeder nem semeia demonstracao
    | (ver a guarda em VelaroSeeder::run), mas a senha fica configuravel de
    | qualquer forma para que homologacao nunca use a do repositorio.
    */
    'seed' => [
        'reseller_password' => env('RESELLER_SEED_PASSWORD', 'lojista-velaro'),
    ],

];
