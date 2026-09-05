<?php

/*
[Modulo: app/Http/Requests/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Valida e normaliza os filtros da lista de pedidos do portal: busca, periodo, os dois status, lote e gravacao.
*/

namespace App\Http\Requests\Portal;

use App\Services\Portal\StatusDoPedido;
use App\Support\ResellerScope;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Barra de filtros da tela 2.5 (33-portal-pedidos.html), mais o `?pedido=` que
 * abre a gaveta lateral.
 *
 * Nenhuma regra aqui consulta o banco. O motivo é o mesmo que vale para o resto
 * do portal: quem decide o que o lojista enxerga é {@see ResellerScope},
 * e um `exists` nesta camada olharia a tabela inteira — inclusive o lote e o
 * pedido de outro revendedor. Filtro que não casa com nada devolve lista vazia;
 * é o service, já escopado, que resolve o `?pedido=` em registro.
 */
class PedidosFiltroRequest extends FormRequest
{
    /** Janelas do select "Período", em dias. `0` é "Todos os períodos". */
    public const PERIODOS = [30, 90, 180, 365, 0];

    /** O protótipo abre a tela em "Últimos 90 dias". */
    public const PERIODO_PADRAO = 90;

    /** Opções do seletor "8 por página" do rodapé da tabela. */
    public const POR_PAGINA = [8, 16, 32];

    public const POR_PAGINA_PADRAO = 8;

    /** `?page=999999` viraria um OFFSET gigante no MySQL. */
    private const PAGINA_MAXIMA = 10000;

    /**
     * O middleware `reseller` já barrou quem não é lojista aprovado; aqui não há
     * segunda decisão de acesso a tomar.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'periodo' => ['nullable', Rule::in(self::PERIODOS)],
            'status' => ['nullable', Rule::in(StatusDoPedido::CADEIA_OPERACIONAL)],
            'pagamento' => ['nullable', Rule::in(StatusDoPedido::STATUS_PAGAMENTO)],
            'lote' => ['nullable', 'string', 'max:60'],
            'gravacao' => ['nullable', Rule::in(['sim', 'nao'])],
            'pedido' => ['nullable', 'string', 'max:40'],
            'por_pagina' => ['nullable', Rule::in(self::POR_PAGINA)],
            'page' => ['nullable', 'integer', 'min:1', 'max:'.self::PAGINA_MAXIMA],
        ];
    }

    /**
     * Filtros prontos para o service.
     *
     * @return array{q: string|null, periodo: int, status: string|null, pagamento: string|null, lote: string|null, gravacao: string|null, pedido: string|null, porPagina: int}
     */
    public function filtros(): array
    {
        $periodo = $this->input('periodo');
        $porPagina = $this->input('por_pagina');

        return [
            'q' => $this->textoOuNulo('q'),
            'periodo' => is_numeric($periodo) ? (int) $periodo : self::PERIODO_PADRAO,
            'status' => $this->textoOuNulo('status'),
            'pagamento' => $this->textoOuNulo('pagamento'),
            'lote' => $this->textoOuNulo('lote'),
            'gravacao' => $this->textoOuNulo('gravacao'),
            'pedido' => $this->textoOuNulo('pedido'),
            'porPagina' => is_numeric($porPagina) ? (int) $porPagina : self::POR_PAGINA_PADRAO,
        ];
    }

    /**
     * Conserta o que chegou torto em vez de recusar a página.
     *
     * A lista de pedidos é a tela para onde o lojista volta o dia inteiro, e ela
     * é alcançada por link salvo, por atalho do KPI e pelo botão "voltar" do
     * navegador. Um 422 por causa de `?periodo=abc` deixaria o lojista sem a
     * própria carteira; valor fora do vocabulário simplesmente deixa de filtrar.
     */
    protected function prepareForValidation(): void
    {
        $query = $this->query->all();
        $limpo = [];

        foreach (['q', 'status', 'pagamento', 'lote', 'gravacao', 'pedido'] as $campo) {
            if (! array_key_exists($campo, $query)) {
                continue;
            }

            $valor = $query[$campo];
            $limpo[$campo] = is_string($valor) && trim($valor) !== '' ? mb_substr(trim($valor), 0, 120) : null;
        }

        foreach (['status' => StatusDoPedido::CADEIA_OPERACIONAL, 'pagamento' => StatusDoPedido::STATUS_PAGAMENTO, 'gravacao' => ['sim', 'nao']] as $campo => $vocabulario) {
            if (isset($limpo[$campo]) && ! in_array($limpo[$campo], $vocabulario, true)) {
                $limpo[$campo] = null;
            }
        }

        $limpo['periodo'] = $this->numeroDaLista('periodo', self::PERIODOS, self::PERIODO_PADRAO);
        $limpo['por_pagina'] = $this->numeroDaLista('por_pagina', self::POR_PAGINA, self::POR_PAGINA_PADRAO);

        if (array_key_exists('page', $query)) {
            $pagina = $query['page'];
            // O paginador lê `page` direto da request: podar só na validação não
            // o protegeria.
            $limpo['page'] = is_numeric($pagina)
                ? (string) max(1, min(self::PAGINA_MAXIMA, (int) $pagina))
                : '1';
        }

        $this->merge($limpo);
    }

    /**
     * @param  list<int>  $permitidos
     */
    private function numeroDaLista(string $campo, array $permitidos, int $padrao): int
    {
        $valor = $this->query->get($campo);

        if (! is_numeric($valor) || ! in_array((int) $valor, $permitidos, true)) {
            return $padrao;
        }

        return (int) $valor;
    }

    private function textoOuNulo(string $campo): ?string
    {
        $valor = $this->input($campo);

        return is_string($valor) && $valor !== '' ? $valor : null;
    }
}
