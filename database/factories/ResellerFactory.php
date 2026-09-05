<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Gera lojista com CNPJ, endereco e protocolo pendente de analise; states cobrem aprovacao, recusa e inativacao.
*/

namespace Database\Factories;

use App\Models\Reseller;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reseller>
 */
class ResellerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->randomElement([
            'João', 'Maria', 'Carlos', 'Ana', 'Paulo',
            'Luciana', 'Rafael', 'Beatriz', 'Marcelo', 'Fernanda',
        ]);

        $lastName = fake()->randomElement([
            'Tomazelli', 'Ferreira', 'Andrade', 'Bittencourt', 'Moraes',
            'Rezende', 'Vasconcelos', 'Siqueira', 'Camargo', 'Nogueira',
        ]);

        $trade = fake()->randomElement(['Alianças', 'Joias', 'Joalheria', 'Ourivesaria']);
        $legalSuffix = fake()->randomElement(['Ltda', 'ME', 'EIRELI', '& Cia']);

        /** @var array{0: string, 1: string} $place */
        $place = fake()->randomElement([
            ['São Paulo', 'SP'],
            ['Campinas', 'SP'],
            ['Rio de Janeiro', 'RJ'],
            ['Belo Horizonte', 'MG'],
            ['Porto Alegre', 'RS'],
            ['Curitiba', 'PR'],
            ['Florianópolis', 'SC'],
            ['Salvador', 'BA'],
            ['Recife', 'PE'],
            ['Fortaleza', 'CE'],
            ['Goiânia', 'GO'],
        ]);

        $street = fake()->randomElement([
            'Rua XV de Novembro', 'Avenida Sete de Setembro', 'Rua das Flores',
            'Avenida Getúlio Vargas', 'Rua Marechal Deodoro', 'Rua Dom Pedro II',
            'Travessa São João', 'Avenida Barão do Rio Branco',
        ]);

        return [
            // Formato do protocolo conforme a tela de acompanhamento: VEL-2026-0148.
            'protocol' => 'VEL-'.now()->format('Y').'-'.fake()->unique()->numerify('####'),
            'code' => null,
            'legal_name' => $lastName.' '.$trade.' '.$legalSuffix,
            'trade_name' => $trade.' '.$lastName,
            'cnpj' => fake()->unique()->numerify('##.###.###/0001-##'),
            'state_registration' => fake()->numerify('###.###.###.###'),
            'contact_name' => $firstName.' '.$lastName,
            'contact_cpf' => fake()->numerify('###.###.###-##'),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('(##) 3###-####'),
            'whatsapp' => fake()->numerify('(##) 9####-####'),
            'postal_code' => fake()->numerify('#####-###'),
            'street' => $street,
            'street_number' => (string) fake()->numberBetween(10, 4999),
            'address_complement' => fake()->boolean(30) ? 'Sala '.fake()->numberBetween(1, 90) : null,
            'district' => fake()->randomElement([
                'Centro', 'Jardim América', 'Vila Nova', 'Santa Cecília', 'Bela Vista', 'Boa Vista',
            ]),
            'city' => $place[0],
            'state' => $place[1],
            // `contact_source` fica de fora: e um select na tela 1.4, mas o vocabulario ainda nao
            // esta acordado (o model nao declara constante e a coluna e nullable sem default).
            // Quando virar constante no model, entra aqui ou num state.
            'registration_type' => Reseller::REGISTRATION_TYPE_AUTOMATIC,
            'notes' => null,
            'internal_notes' => null,
            'status' => Reseller::STATUS_PENDING,
            'approved_at' => null,
            'approved_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ];
    }

    /**
     * Cadastro recém-enviado, ainda sem análise.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Reseller::STATUS_PENDING,
            'code' => null,
            'approved_at' => null,
            'approved_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);
    }

    /**
     * Revendedor homologado: ganha código de revenda (formato VEL-02412) e trilha de aprovação.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Reseller::STATUS_APPROVED,
            'code' => 'VEL-'.fake()->unique()->numerify('#####'),
            'approved_at' => now()->subDays(fake()->numberBetween(1, 180)),
            'approved_by' => User::factory()->admin(),
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Reseller::STATUS_REJECTED,
            'code' => null,
            'approved_at' => null,
            'approved_by' => null,
            'rejected_at' => now()->subDays(fake()->numberBetween(1, 90)),
            'rejection_reason' => fake()->randomElement([
                'CNPJ com situação cadastral irregular na Receita Federal.',
                'CNAE principal incompatível com o comércio de joias.',
                'Documentação societária ilegível ou incompleta.',
            ]),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Reseller::STATUS_INACTIVE,
        ]);
    }

    /**
     * Cadastro digitado pela equipe interna, não pelo formulário público.
     */
    public function manualRegistration(): static
    {
        return $this->state(fn (array $attributes): array => [
            'registration_type' => Reseller::REGISTRATION_TYPE_MANUAL,
            'internal_notes' => 'Cadastro lançado pela equipe comercial.',
        ]);
    }
}
