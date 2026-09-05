<?php

/*
[Modulo: app/Services/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Monta a central de ajuda do portal: biblioteca vinda de help_categories e help_articles, FAQ da plataforma e canais.
*/

namespace App\Services\Portal;

use App\Models\HelpArticle;
use App\Models\HelpCategory;
use App\Services\Portal\Concerns\FormataDados;
use App\Services\Site\LegalDocumentService;
use App\Services\Site\SiteContentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;

/**
 * Central de ajuda do Portal do Lojista (`43-portal-ajuda.html`).
 *
 * A tela tem duas metades, de origens diferentes de propósito:
 *
 * 1. **A biblioteca** — categorias, artigo em destaque, guias em PDF e vídeos —
 *    vem de `help_categories` e `help_articles`, as tabelas que a régua da 2.8
 *    nomeia. É conteúdo editorial: muda sem deploy, e por isso mora no banco.
 *    Base sem artigo publicado devolve biblioteca vazia, e a tela diz isso em
 *    vez de fingir conteúdo.
 *
 * 2. **O FAQ operacional** — como funciona o lote semanal, quem emite a nota,
 *    por que o consumidor não compra da Velaro — fica versionado aqui no código,
 *    como em {@see LegalDocumentService}. São as regras do
 *    contrato, iguais para todo lojista: mudar uma delas é mudar o produto, e
 *    isso tem de passar por revisão de código, não por um formulário de CMS.
 *
 * Nada nesta tela é escopado por revendedor porque nada nela é do revendedor: é
 * documentação da plataforma. O que o portal garante aqui é o público — o
 * consumidor final não tem login e não chega a esta página.
 */
class CentralAjudaService
{
    use FormataDados;

    /** Palavras por minuto usadas na estimativa "6 min de leitura". */
    private const PALAVRAS_POR_MINUTO = 200;

    /** Quantos artigos a coluna "Mais lidos" mostra. */
    private const MAIS_LIDOS = 5;

    public function __construct(private readonly SiteContentService $conteudo) {}

    /**
     * @return array<string, mixed>
     */
    public function montar(?string $busca = null): array
    {
        $categorias = $this->categorias();
        $destaque = $busca === null ? $this->destaque() : null;

        return [
            'busca' => $busca,
            'resultados' => $busca === null ? null : $this->buscar($busca),
            'categorias' => $this->cartoesDeCategoria($categorias),
            'buscasComuns' => $this->buscasComuns(),
            'destaque' => $destaque,
            'naCategoria' => $this->naCategoria($destaque),
            'maisLidos' => $this->maisLidos($destaque),
            'faq' => $this->faq($busca),
            'guias' => $this->guias(),
            'videos' => $this->videos(),
            'atalhos' => $this->atalhos(),
            'atendimento' => $this->atendimento(),
            'temBiblioteca' => $categorias->isNotEmpty(),
        ];
    }

    /**
     * Busca da central. Varre título, resumo e corpo dos artigos publicados — a
     * mesma coisa que o lojista leria na tela, e nada além dela.
     *
     * @return list<array{titulo: string, categoria: string, tipo: string, minutos: int}>
     */
    private function buscar(string $termo): array
    {
        $padrao = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $termo).'%';

        return $this->publicados()
            ->where(static function (Builder $artigo) use ($padrao): void {
                $artigo->where('title', 'like', $padrao)
                    ->orWhere('excerpt', 'like', $padrao)
                    ->orWhere('body', 'like', $padrao);
            })
            ->with('helpCategory')
            ->get()
            ->map(fn (HelpArticle $artigo): array => [
                'titulo' => $this->texto($artigo->title),
                'categoria' => $this->texto($artigo->helpCategory?->name),
                'tipo' => $this->rotuloDoTipo($this->texto($artigo->type)),
                'minutos' => $this->minutosDeLeitura($this->texto($artigo->body) ?: $this->texto($artigo->excerpt)),
            ])
            ->all();
    }

    /**
     * Rótulo do tipo do artigo, sempre de `lang` — nunca string crua na view.
     * Tipo sem tradução cai no próprio valor, para o artigo aparecer mesmo assim.
     */
    private function rotuloDoTipo(string $tipo): string
    {
        return $this->rotulo('support.article_type.'.$tipo) ?? $tipo;
    }

    /**
     * Categorias ativas com a contagem de artigos publicados. Categoria sem
     * artigo não aparece: um cartão que abre no vazio é pior que a ausência.
     *
     * @return EloquentCollection<int, HelpCategory>
     */
    private function categorias(): EloquentCollection
    {
        // O corte é `whereHas`, não `having`: `withCount` gera uma subconsulta no
        // SELECT, e sem GROUP BY o SQLite recusa a cláusula HAVING.
        return HelpCategory::query()
            ->where('is_active', true)
            ->withCount(['articles as artigos_count' => fn (Builder $artigo): Builder => $artigo->where('is_published', true)])
            ->whereHas('articles', static fn (Builder $artigo): Builder => $artigo->where('is_published', true))
            ->orderBy('position')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  EloquentCollection<int, HelpCategory>  $categorias
     * @return list<array{nome: string, slug: string, artigos: int, icone: string}>
     */
    private function cartoesDeCategoria(EloquentCollection $categorias): array
    {
        return $categorias->map(function (HelpCategory $categoria): array {
            $total = (int) $categoria->getAttribute('artigos_count');

            return [
                'nome' => $this->texto($categoria->name),
                'slug' => $this->texto($categoria->slug),
                'artigos' => $total,
                'icone' => $this->icone($this->texto($categoria->slug)),
            ];
        })->all();
    }

    /**
     * O artigo aberto no corpo da página: o primeiro guia publicado, na ordem
     * editorial. Sem guia publicado a tela não abre artigo nenhum.
     *
     * @return array<string, mixed>|null
     */
    private function destaque(): ?array
    {
        $artigo = $this->publicados()
            ->where('type', HelpArticle::TYPE_GUIDE)
            ->whereNotNull('body')
            ->with('helpCategory')
            ->first();

        if (! $artigo instanceof HelpArticle) {
            return null;
        }

        $corpo = $this->texto($artigo->body);
        $atualizado = $artigo->getAttribute('updated_at');

        return [
            'id' => $artigo->getKey(),
            'categoria' => $this->texto($artigo->helpCategory?->name),
            'categoriaSlug' => $this->texto($artigo->helpCategory?->slug),
            'titulo' => $this->texto($artigo->title),
            'resumo' => $this->texto($artigo->excerpt),
            'corpo' => $corpo,
            'minutos' => $this->minutosDeLeitura($corpo),
            'atualizado' => $atualizado instanceof Carbon ? $atualizado->format('d/m/Y') : null,
        ];
    }

    /**
     * Coluna "Nesta categoria": os irmãos do artigo em destaque.
     *
     * @param  array<string, mixed>|null  $destaque
     * @return list<array{titulo: string, minutos: int, atual: bool}>
     */
    private function naCategoria(?array $destaque): array
    {
        if ($destaque === null) {
            return [];
        }

        $artigos = $this->publicados()
            ->whereHas('helpCategory', fn (Builder $categoria): Builder => $categoria->where('slug', $destaque['categoriaSlug']))
            ->get();

        return $artigos->map(fn (HelpArticle $artigo): array => [
            'titulo' => $this->texto($artigo->title),
            'minutos' => $this->minutosDeLeitura($this->texto($artigo->body) ?: $this->texto($artigo->excerpt)),
            'atual' => $artigo->getKey() === $destaque['id'],
        ])->all();
    }

    /**
     * "Mais lidos": sem métrica de leitura na base, a ordem é a editorial —
     * `position` é justamente o campo com que a Velaro decide o que destacar.
     * O nome da seção descreve a intenção; a fonte está documentada aqui para
     * ninguém confundir com contador de acesso.
     *
     * @param  array<string, mixed>|null  $destaque
     * @return list<array{titulo: string, minutos: int}>
     */
    private function maisLidos(?array $destaque): array
    {
        $consulta = $this->publicados()->whereIn('type', [HelpArticle::TYPE_GUIDE, HelpArticle::TYPE_FAQ]);

        if ($destaque !== null) {
            $consulta->whereKeyNot($destaque['id']);
        }

        return $consulta->limit(self::MAIS_LIDOS)
            ->get()
            ->map(fn (HelpArticle $artigo): array => [
                'titulo' => $this->texto($artigo->title),
                'minutos' => $this->minutosDeLeitura($this->texto($artigo->body) ?: $this->texto($artigo->excerpt)),
            ])
            ->all();
    }

    /**
     * Guias e manuais para download — artigo do tipo `guide` com arquivo.
     *
     * @return list<array{titulo: string, descricao: string, url: string}>
     */
    private function guias(): array
    {
        return $this->publicados()
            ->where('type', HelpArticle::TYPE_GUIDE)
            ->whereNotNull('file_path')
            ->get()
            ->map(fn (HelpArticle $artigo): array => [
                'titulo' => $this->texto($artigo->title),
                'descricao' => $this->texto($artigo->excerpt),
                'url' => asset($this->texto($artigo->file_path)),
            ])
            ->all();
    }

    /**
     * @return list<array{titulo: string, categoria: string, url: string}>
     */
    private function videos(): array
    {
        return $this->publicados()
            ->where('type', HelpArticle::TYPE_VIDEO)
            ->whereNotNull('video_url')
            ->with('helpCategory')
            ->get()
            ->map(fn (HelpArticle $artigo): array => [
                'titulo' => $this->texto($artigo->title),
                'categoria' => $this->texto($artigo->helpCategory?->name),
                'url' => $this->texto($artigo->video_url),
            ])
            ->all();
    }

    /**
     * FAQ operacional da plataforma. São as regras do contrato — o lote semanal,
     * a nota fiscal, o white label —, iguais para todo lojista e versionadas com
     * o código. Fonte: `43-portal-ajuda.html` e o Anexo I §5.
     *
     * Quando há busca, o FAQ responde a ela junto com a biblioteca: metade das
     * dúvidas do lojista está aqui, e deixá-lo de fora do resultado faria a
     * central parecer vazia justamente na pergunta mais comum.
     *
     * @return list<array{pergunta: string, resposta: string}>
     */
    public function faq(?string $busca = null): array
    {
        $perguntas = [
            [
                'pergunta' => 'Quando o meu pedido entra em produção?',
                'resposta' => 'Assim que o lote da semana é quitado. A produção leva 5 dias úteis, mais 2 dias úteis de transporte até a sua loja.',
            ],
            [
                'pergunta' => 'O consumidor final consegue comprar pelo site da Velaro?',
                'resposta' => 'Não. O consumidor não tem login na plataforma e não paga a Velaro. Ele escolhe na sua vitrine e paga no caixa da sua loja.',
            ],
            [
                'pergunta' => 'Posso pagar um pedido isolado, fora do lote?',
                'resposta' => 'Não. A cobrança é sempre por lote semanal. Um pedido feito depois do fechamento entra automaticamente no lote seguinte.',
            ],
            [
                'pergunta' => 'Quem emite a nota fiscal para o meu cliente?',
                'resposta' => 'Você. A Velaro emite a NF-e da venda B2B contra o CNPJ da sua loja; a nota da venda ao consumidor final é responsabilidade da sua loja.',
            ],
            [
                'pergunta' => 'O cliente vê que a fábrica é a Velaro?',
                'resposta' => 'Não. A vitrine é white label: sai com a marca da sua loja, e as mensagens de retirada são enviadas em nome dela.',
            ],
            [
                'pergunta' => 'Como corrijo o preço de um único produto?',
                'resposta' => 'Em Preços e margens, ative “Permitir edição manual por produto” e edite o preço sugerido direto na linha da tabela.',
            ],
        ];

        if ($busca === null) {
            return $perguntas;
        }

        return array_values(array_filter(
            $perguntas,
            static fn (array $item): bool => mb_stripos($item['pergunta'].' '.$item['resposta'], $busca) !== false,
        ));
    }

    /**
     * Chips de "Buscas mais comuns" — atalhos para a busca da própria central.
     *
     * @return list<string>
     */
    private function buscasComuns(): array
    {
        return ['prazo de produção', 'pagar o lote', 'margem ideal', 'baixar XML', 'aro errado', 'publicar vitrine'];
    }

    /**
     * "Atalhos do Portal": as telas que respondem a dúvida no lugar do artigo.
     *
     * @return list<array{icone: string, titulo: string, descricao: string, url: string}>
     */
    private function atalhos(): array
    {
        return [
            ['icone' => 'tag', 'titulo' => 'Preços e margens', 'descricao' => 'Ajuste margem, markup e arredondamento', 'url' => route('portal.precos.edit')],
            ['icone' => 'coin', 'titulo' => 'Financeiro', 'descricao' => 'Lotes, pagamentos e vencimentos', 'url' => route('portal.financeiro.index')],
            ['icone' => 'doc', 'titulo' => 'Notas fiscais', 'descricao' => 'PDF e XML das NF-e emitidas', 'url' => route('portal.financeiro.notas')],
            ['icone' => 'store', 'titulo' => 'Personalização da loja', 'descricao' => 'Identidade, banner e regra de preço', 'url' => route('portal.loja.edit')],
        ];
    }

    /**
     * Horário de atendimento. Vem de `settings`, o mesmo valor que o rodapé do
     * site público mostra — dois lugares dizendo horários diferentes seria pior
     * que qualquer duplicação de código.
     */
    private function atendimento(): string
    {
        $contato = $this->conteudo->group(SiteContentService::GROUP_CONTACT);

        return $contato['horario'] ?? 'Segunda a sexta, das 8h às 18h';
    }

    /**
     * @return Builder<HelpArticle>
     */
    private function publicados(): Builder
    {
        return HelpArticle::query()
            ->where('is_published', true)
            ->orderBy('position')
            ->orderBy('id');
    }

    /**
     * Estimativa de leitura, arredondada para cima e nunca menor que 1 minuto.
     */
    private function minutosDeLeitura(string $corpo): int
    {
        $palavras = str_word_count(strip_tags($corpo));

        return max(1, (int) ceil($palavras / self::PALAVRAS_POR_MINUTO));
    }

    /**
     * Ícone do cartão da categoria, deduzido do slug. Slug desconhecido cai no
     * genérico — categoria nova não quebra a grade.
     */
    private function icone(string $slug): string
    {
        foreach ([
            'primeiros' => 'sparkle',
            'pedido' => 'bag',
            'catalogo' => 'book',
            'financeiro' => 'coin',
            'preco' => 'tag',
            'margem' => 'tag',
            'vitrine' => 'store',
            'loja' => 'store',
            'nota' => 'doc',
            'fiscal' => 'doc',
        ] as $pedaco => $icone) {
            if (str_contains($slug, $pedaco)) {
                return $icone;
            }
        }

        return 'info';
    }
}
