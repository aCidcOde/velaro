<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Cria parametro chave/valor por grupo; states trazem nome, telefone e as regras de gravacao do prototipo.
*/

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // `settings.key` e UNIQUE e o formato e pontuado pelo grupo: company.nome,
        // contact.telefone, gravacao.max_chars. O sufixo aleatorio existe so para dar
        // folga ao UNIQUE; as chaves reais do prototipo vivem nos states abaixo.
        $group = fake()->randomElement(['company', 'contact', 'gravacao']);

        return [
            'group' => $group,
            'key' => $group.'.'.fake()->unique()->lexify('campo_?????'),
            'value' => 'Valor padrão desta configuração.',
            // `type` nao tem constante no model; 'string' e o default da migration.
            'type' => 'string',
            'is_public' => false,
        ];
    }

    /**
     * Nome fantasia exibido no site publico e no rodape.
     */
    public function companyNome(): static
    {
        return $this->state(fn (array $attributes): array => [
            'group' => 'company',
            'key' => 'company.nome',
            'value' => 'Velaro Alianças',
            'type' => 'string',
            'is_public' => true,
        ]);
    }

    /**
     * Telefone de atendimento do rodape do site publico.
     */
    public function contactTelefone(): static
    {
        return $this->state(fn (array $attributes): array => [
            'group' => 'contact',
            'key' => 'contact.telefone',
            'value' => '+55 (16) 99487-7800',
            'type' => 'string',
            'is_public' => true,
        ]);
    }

    /**
     * Limite de caracteres da gravacao — o "11/20 caracteres" do prototipo.
     */
    public function gravacaoMaxChars(): static
    {
        return $this->state(fn (array $attributes): array => [
            'group' => 'gravacao',
            'key' => 'gravacao.max_chars',
            'value' => '20',
            'type' => 'string',
            'is_public' => true,
        ]);
    }

    /**
     * Adicional de gravacao discriminado no carrinho: R$ 30,00.
     */
    public function gravacaoPreco(): static
    {
        return $this->state(fn (array $attributes): array => [
            'group' => 'gravacao',
            'key' => 'gravacao.preco',
            'value' => '30.00',
            'type' => 'string',
            'is_public' => true,
        ]);
    }

    /**
     * Configuracao legivel pelo site publico.
     */
    public function publica(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_public' => true,
        ]);
    }
}
