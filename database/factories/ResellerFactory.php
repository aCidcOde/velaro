<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Gera lojista com CNPJ, endereco e protocolo em pre-cadastro; states cobrem aprovacao, reprovacao e inativacao.
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
        $primeiroNome = fake()->randomElement([
            'João', 'Maria', 'Carlos', 'Ana', 'Paulo',
            'Luciana', 'Rafael', 'Beatriz', 'Marcelo', 'Fernanda',
        ]);

        $sobrenome = fake()->randomElement([
            'Tomazelli', 'Ferreira', 'Andrade', 'Bittencourt', 'Moraes',
            'Rezende', 'Vasconcelos', 'Siqueira', 'Camargo', 'Nogueira',
        ]);

        $ramo = fake()->randomElement(['Alianças', 'Joias', 'Joalheria', 'Ourivesaria']);
        $sufixo = fake()->randomElement(['Ltda', 'ME', 'EIRELI', '& Cia']);

        /** @var array{0: string, 1: string} $local */
        $local = fake()->randomElement([
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

        $logradouro = fake()->randomElement([
            'Rua XV de Novembro', 'Avenida Sete de Setembro', 'Rua das Flores',
            'Avenida Getúlio Vargas', 'Rua Marechal Deodoro', 'Rua Dom Pedro II',
            'Travessa São João', 'Avenida Barão do Rio Branco',
        ]);

        return [
            // Formato do protocolo conforme a tela de acompanhamento: VEL-2026-0148.
            'protocolo' => 'VEL-'.now()->format('Y').'-'.fake()->unique()->numerify('####'),
            'code' => null,
            'razao_social' => $sobrenome.' '.$ramo.' '.$sufixo,
            'nome_fantasia' => $ramo.' '.$sobrenome,
            'cnpj' => fake()->unique()->numerify('##.###.###/0001-##'),
            'inscricao_estadual' => fake()->numerify('###.###.###.###'),
            'responsavel_nome' => $primeiroNome.' '.$sobrenome,
            'responsavel_cpf' => fake()->numerify('###.###.###-##'),
            'email' => fake()->unique()->safeEmail(),
            'telefone' => fake()->numerify('(##) 3###-####'),
            'whatsapp' => fake()->numerify('(##) 9####-####'),
            'cep' => fake()->numerify('#####-###'),
            'logradouro' => $logradouro,
            'numero' => (string) fake()->numberBetween(10, 4999),
            'complemento' => fake()->boolean(30) ? 'Sala '.fake()->numberBetween(1, 90) : null,
            'bairro' => fake()->randomElement([
                'Centro', 'Jardim América', 'Vila Nova', 'Santa Cecília', 'Bela Vista', 'Boa Vista',
            ]),
            'cidade' => $local[0],
            'uf' => $local[1],
            // `origem_contato` fica de fora: e um select na tela 1.4, mas o vocabulario ainda nao
            // esta acordado (o model nao declara constante e a coluna e nullable sem default).
            // Quando virar constante no model, entra aqui ou num state.
            'registration_type' => Reseller::REGISTRATION_TYPE_AUTOMATICO,
            'observacoes' => null,
            'observacoes_internas' => null,
            'status' => Reseller::STATUS_PRE_CADASTRO,
            'approved_at' => null,
            'approved_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ];
    }

    /**
     * Cadastro recém-enviado, ainda sem análise.
     */
    public function preCadastro(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Reseller::STATUS_PRE_CADASTRO,
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
    public function aprovado(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Reseller::STATUS_APROVADO,
            'code' => 'VEL-'.fake()->unique()->numerify('#####'),
            'approved_at' => now()->subDays(fake()->numberBetween(1, 180)),
            'approved_by' => User::factory()->admin(),
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);
    }

    public function reprovado(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Reseller::STATUS_REPROVADO,
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

    public function inativo(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Reseller::STATUS_INATIVO,
        ]);
    }

    /**
     * Cadastro digitado pela equipe interna, não pelo formulário público.
     */
    public function cadastroManual(): static
    {
        return $this->state(fn (array $attributes): array => [
            'registration_type' => Reseller::REGISTRATION_TYPE_MANUAL,
            'observacoes_internas' => 'Cadastro lançado pela equipe comercial.',
        ]);
    }
}
