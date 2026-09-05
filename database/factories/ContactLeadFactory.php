<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Gera lead do Fale Conosco do site com origem e mensagem; states cobrem lead atendido e lead sem e-mail.
*/

namespace Database\Factories;

use App\Models\ContactLead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactLead>
 */
class ContactLeadFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // O "Fale conosco" do site publico: grava lead, nao cria revendedor nem acesso.
        // `origin` guarda a pagina de partida e `status` fica no default da migration
        // ('new'), porque o model nao declara constante para a fila de atendimento.
        return [
            'name' => fake()->randomElement(['Maria', 'João', 'Ana', 'Carlos', 'Juliana', 'Rafael', 'Patrícia', 'Bruno'])
                .' '.fake()->randomElement(['Silva', 'Souza', 'Oliveira', 'Pereira', 'Costa', 'Almeida', 'Ribeiro']),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('(##) 9####-####'),
            'company' => fake()->randomElement(['Joalheria', 'Ótica e Joias', 'Casa das Alianças', 'Relojoaria'])
                .' '.fake()->randomElement(['Tomazelli', 'Andrade', 'Bianchi', 'Moretti', 'Siqueira', 'Nogueira']),
            'subject' => fake()->randomElement([
                'Quero ser revendedor',
                'Solicitar atendimento',
                'Falar com especialista',
                'Dúvida sobre o catálogo',
            ]),
            'message' => 'Tenho loja física e gostaria de conhecer as condições comerciais para revenda de alianças.',
            'origin' => 'home',
            'status' => 'new',
            'handled_by' => null,
        ];
    }

    /**
     * Lead vindo do CTA "Vamos crescer juntos?" da pagina Sobre.
     */
    public function fromAboutPage(): static
    {
        return $this->state(fn (array $attributes): array => [
            'origin' => 'sobre',
        ]);
    }

    /**
     * Lead ja puxado por alguem do painel — a fila anda por `handled_by`.
     */
    public function handledBy(?User $user = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'handled_by' => $user instanceof User ? $user->getKey() : User::factory(),
            'handled_at' => now(),
        ]);
    }

    /**
     * Lead so com telefone: o formulario nao exige e-mail.
     */
    public function withoutEmail(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email' => null,
        ]);
    }
}
