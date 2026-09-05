<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Anexa um dos tres documentos obrigatorios do cadastro em PDF; state troca por foto tirada no celular.
*/

namespace Database\Factories;

use App\Models\Reseller;
use App\Models\ResellerDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResellerDocument>
 */
class ResellerDocumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = (string) fake()->randomElement([
            ResellerDocument::TYPE_CONTRATO_SOCIAL,
            ResellerDocument::TYPE_DOCUMENTO_SOCIO,
            ResellerDocument::TYPE_CARTAO_CNPJ,
        ]);

        return [
            'reseller_id' => Reseller::factory(),
            'type' => $type,
            'original_name' => str_replace('_', '-', $type).'.pdf',
            'disk' => 'local',
            'path' => 'revendedores/documentos/'.fake()->uuid().'.pdf',
            'size_bytes' => fake()->numberBetween(48_000, 4_200_000),
            'mime' => 'application/pdf',
        ];
    }

    public function contratoSocial(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ResellerDocument::TYPE_CONTRATO_SOCIAL,
            'original_name' => 'contrato-social.pdf',
        ]);
    }

    public function documentoSocio(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ResellerDocument::TYPE_DOCUMENTO_SOCIO,
            'original_name' => 'documento-socio.pdf',
        ]);
    }

    public function cartaoCnpj(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ResellerDocument::TYPE_CARTAO_CNPJ,
            'original_name' => 'cartao-cnpj.pdf',
        ]);
    }

    /**
     * Foto/scan enviado pelo celular, em vez do PDF.
     */
    public function imagem(): static
    {
        return $this->state(fn (array $attributes): array => [
            'original_name' => 'documento-'.fake()->numerify('####').'.jpg',
            'path' => 'revendedores/documentos/'.fake()->uuid().'.jpg',
            'mime' => 'image/jpeg',
            'size_bytes' => fake()->numberBetween(180_000, 2_800_000),
        ]);
    }
}
