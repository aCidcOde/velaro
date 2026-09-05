<?php

/*
[Modulo: database/factories]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Monta artigo da central de ajuda com pergunta e resposta reais; states dao guia, video e rascunho.
*/

namespace Database\Factories;

use App\Models\HelpArticle;
use App\Models\HelpCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HelpArticle>
 */
class HelpArticleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // `help_category_id` e nullable, mas e ele que da sentido ao artigo:
        // na Central de ajuda todo artigo aparece dentro de uma categoria.
        // Pergunta e resposta andam juntas — os pares sao os do bloco "Perguntas
        // frequentes" do mockup 43-portal-ajuda. `video_url` e `file_path` sao
        // exclusivos dos tipos video/guia e viram state.
        /** @var array{0: string, 1: string} $faq */
        $faq = fake()->randomElement([
            [
                'Quando o meu pedido entra em produção?',
                'Assim que o lote da semana é quitado. A produção leva 5 dias úteis, mais 2 dias '
                    .'úteis de transporte até a sua loja.',
            ],
            [
                'Posso pagar um pedido isolado, fora do lote?',
                'Não. A cobrança é sempre por lote semanal. Um pedido feito depois do fechamento '
                    .'entra automaticamente no lote seguinte.',
            ],
            [
                'Quem emite a nota fiscal para o meu cliente?',
                'Você. A Velaro emite a NF-e da venda B2B contra o CNPJ da sua loja; a nota da '
                    .'venda ao consumidor final é responsabilidade da sua loja.',
            ],
            [
                'O cliente vê que a fábrica é a Velaro?',
                'Não. A vitrine é white label: sai com a marca da sua loja, e as mensagens de '
                    .'retirada são enviadas em nome dela.',
            ],
            [
                'Como corrijo o preço de um único produto?',
                'Em Preços e margens, ative "Permitir edição manual por produto" e edite o preço '
                    .'sugerido direto na linha da tabela.',
            ],
            [
                'O consumidor final consegue comprar pelo site da Velaro?',
                'Não. O consumidor não tem login na plataforma e não paga a Velaro. Ele escolhe na '
                    .'sua vitrine e paga no caixa da sua loja.',
            ],
        ]);

        return [
            'help_category_id' => HelpCategory::factory(),
            'type' => HelpArticle::TYPE_FAQ,
            'title' => $faq[0],
            'slug' => $this->slugFor($faq[0]),
            'excerpt' => 'Resposta rápida para uma das dúvidas mais comuns do Portal do Lojista.',
            'body' => $faq[1],
            'position' => fake()->numberBetween(0, 20),
            'is_published' => true,
        ];
    }

    /**
     * Pergunta frequente — o bloco "Perguntas frequentes" da Central de ajuda.
     */
    public function faq(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => HelpArticle::TYPE_FAQ,
            'video_url' => null,
            'file_path' => null,
        ]);
    }

    /**
     * Guia ou manual em PDF — o bloco "Guias e manuais". O conteudo mora no arquivo,
     * entao o corpo do artigo fica vazio.
     */
    public function guia(): static
    {
        return $this->state(function (array $attributes): array {
            $title = (string) fake()->randomElement([
                'Manual do Portal do Lojista',
                'Guia rápido: primeiro pedido em 10 minutos',
                'Checklist de abertura da vitrine',
                'Tabela de aros e equivalências',
            ]);
            $slug = $this->slugFor($title);

            return [
                'type' => HelpArticle::TYPE_GUIA,
                'title' => $title,
                'slug' => $slug,
                'excerpt' => 'Material em PDF para download na Central de ajuda.',
                'body' => null,
                'file_path' => 'help/guias/'.$slug.'.pdf',
                'video_url' => null,
            ];
        });
    }

    /**
     * Video tutorial — o bloco "Videos tutoriais". O conteudo mora no video.
     */
    public function video(): static
    {
        return $this->state(function (array $attributes): array {
            $title = (string) fake()->randomElement([
                'Tour pelo Portal do Lojista',
                'Montando o primeiro pedido no catálogo',
                'Fechando e pagando o lote semanal',
                'Publicando a sua vitrine white label',
            ]);

            return [
                'type' => HelpArticle::TYPE_VIDEO,
                'title' => $title,
                'slug' => $this->slugFor($title),
                'excerpt' => 'Vídeo tutorial da Central de ajuda do Portal do Lojista.',
                'body' => null,
                'video_url' => 'https://www.youtube.com/watch?v='.fake()->unique()->lexify('???????????'),
                'file_path' => null,
            ];
        });
    }

    /**
     * Artigo escrito mas ainda invisivel na Central de ajuda.
     */
    public function naoPublicado(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_published' => false,
        ]);
    }

    /**
     * Artigo solto, fora das seis categorias da Central.
     */
    public function semCategoria(): static
    {
        return $this->state(fn (array $attributes): array => [
            'help_category_id' => null,
        ]);
    }

    /**
     * Slug derivado do titulo, com sufixo numerico para dar folga ao UNIQUE
     * de `help_articles.slug` — a lista de titulos do prototipo e curta.
     */
    private function slugFor(string $title): string
    {
        return Str::slug($title).'-'.fake()->unique()->numerify('####');
    }
}
