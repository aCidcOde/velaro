<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Monta a triagem automatica de CNPJ e CNAE com score; states dao consulta pendente, aprovada e reprovada.
*/

namespace Database\Factories;

use App\Models\Reseller;
use App\Models\ResellerVerification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Consulta automática do CNPJ/CNAE do cadastro.
 *
 * ⚠ `status` fica sempre em `ResellerVerification::STATUS_PENDING`, a única constante que o
 * model declara e o default da migration. Os states `approved()` e `rejected()` descrevem o
 * desfecho pelos quatro booleanos, pelo `score` e pelo `result` — não por `status`, porque o
 * vocabulário de desfecho ainda não está acordado no model.
 *
 * As quatro colunas booleanas (`cnpj_valido`, `empresa_ativa`, `cnaes_compativeis`,
 * `documentacao_enviada`) e o `raw_payload` da Receita Federal seguem em pt-BR: não constam
 * do mapa de anglicização e o payload espelha os campos do provedor.
 *
 * @extends Factory<ResellerVerification>
 */
class ResellerVerificationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cnpjValido = fake()->boolean(85);
        $empresaAtiva = fake()->boolean(85);
        $cnaesCompativeis = fake()->boolean(75);
        $documentacaoEnviada = fake()->boolean(70);

        return [
            'reseller_id' => Reseller::factory(),
            'status' => ResellerVerification::STATUS_PENDING,
            'cnpj_valido' => $cnpjValido,
            'empresa_ativa' => $empresaAtiva,
            'cnaes_compativeis' => $cnaesCompativeis,
            'documentacao_enviada' => $documentacaoEnviada,
            'score' => $this->score($cnpjValido, $empresaAtiva, $cnaesCompativeis, $documentacaoEnviada),
            'result' => $this->result($cnpjValido, $empresaAtiva, $cnaesCompativeis, $documentacaoEnviada),
            'raw_payload' => $this->rawPayload($empresaAtiva),
            'checked_at' => now()->subMinutes(fake()->numberBetween(1, 4320)),
        ];
    }

    /**
     * Consulta ainda não executada — enfileirada, sem resultado.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ResellerVerification::STATUS_PENDING,
            'cnpj_valido' => null,
            'empresa_ativa' => null,
            'cnaes_compativeis' => null,
            'documentacao_enviada' => null,
            'score' => null,
            'result' => null,
            'raw_payload' => null,
            'checked_at' => null,
        ]);
    }

    /**
     * Os quatro checks verdes e score cheio. `status` permanece em `STATUS_PENDING`.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'cnpj_valido' => true,
            'empresa_ativa' => true,
            'cnaes_compativeis' => true,
            'documentacao_enviada' => true,
            'score' => $this->score(true, true, true, true),
            'result' => $this->result(true, true, true, true),
            'raw_payload' => $this->rawPayload(true),
            'checked_at' => now(),
        ]);
    }

    /**
     * O inverso: os quatro checks reprovados e score zero. `status` permanece em `STATUS_PENDING`.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'cnpj_valido' => false,
            'empresa_ativa' => false,
            'cnaes_compativeis' => false,
            'documentacao_enviada' => false,
            'score' => $this->score(false, false, false, false),
            'result' => $this->result(false, false, false, false),
            'raw_payload' => $this->rawPayload(false),
            'checked_at' => now(),
        ]);
    }

    /**
     * Score 0-100: cada um dos quatro checks vale 25 pontos.
     */
    private function score(
        bool $cnpjValido,
        bool $empresaAtiva,
        bool $cnaesCompativeis,
        bool $documentacaoEnviada,
    ): int {
        return ((int) $cnpjValido + (int) $empresaAtiva + (int) $cnaesCompativeis + (int) $documentacaoEnviada) * 25;
    }

    /**
     * @return array<string, mixed>
     */
    private function result(
        bool $cnpjValido,
        bool $empresaAtiva,
        bool $cnaesCompativeis,
        bool $documentacaoEnviada,
    ): array {
        return [
            'checks' => [
                'cnpj_valido' => $cnpjValido,
                'empresa_ativa' => $empresaAtiva,
                'cnaes_compativeis' => $cnaesCompativeis,
                'documentacao_enviada' => $documentacaoEnviada,
            ],
            'score' => $this->score($cnpjValido, $empresaAtiva, $cnaesCompativeis, $documentacaoEnviada),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function rawPayload(bool $empresaAtiva): array
    {
        return [
            'fonte' => 'receita_federal',
            'situacao_cadastral' => $empresaAtiva ? 'ATIVA' : 'BAIXADA',
            'cnae_principal' => '4783-1/01',
            'abertura' => fake()->date('Y-m-d', '-2 years'),
            'consultado_em' => now()->toIso8601String(),
        ];
    }
}
