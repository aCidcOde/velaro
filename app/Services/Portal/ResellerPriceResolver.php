<?php

/*
[Modulo: app/Services/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Resolve o preco B2C do lojista sobre o custo Velaro, com prioridade explicita entre as regras.
*/

namespace App\Services\Portal;

use App\Models\Product;
use App\Models\ResellerPriceRule;
use App\Models\ResellerPriceSetting;
use Illuminate\Support\Collection;

/**
 * O service dedicado que a regra 3 da tela 2.7 exige.
 *
 * O preço ao consumidor **é do revendedor**, não da Velaro: `products.price` é o
 * custo B2B que o lojista paga, e o que a vitrine mostra sai daqui. Duas coisas
 * precisam ser verdade ao mesmo tempo:
 *
 * - o lojista vê o próprio custo (é a tela em que ele descobre quanto paga);
 * - a margem de um lojista não pode ser lida por outro, porque a regra de preço
 *   é o segredo comercial dele. Por isso este resolvedor nunca consulta a
 *   tabela: ele recebe as regras **já escopadas** por `ResellerScope`.
 *
 * ## Prioridade
 *
 * A cascata é explícita e sempre a mesma — do mais específico para o mais geral,
 * e a primeira regra ativa encontrada decide sozinha:
 *
 * 1. regra de `scope = product` para aquele produto;
 * 2. regra de `scope = collection` para a coleção do produto;
 * 3. regra de `scope = global` do revendedor;
 * 4. o padrão de `reseller_price_settings` (multiplicador ou margem global).
 *
 * Empate dentro do mesmo escopo é desfeito por `priority` (maior primeiro) e,
 * persistindo, pelo id mais novo — a regra cadastrada por último ganha.
 */
final class ResellerPriceResolver
{
    /**
     * Piso da faixa "margem crítica" da legenda do painel Resumo de margens
     * (tela 2.7 §5). Acima dele e abaixo da margem mínima do lojista a margem é
     * "baixa"; da mínima para cima é "ideal".
     */
    public const CRITICAL_MARGIN = 20.0;

    public const STATUS_IDEAL = 'ideal';

    public const STATUS_LOW = 'low';

    public const STATUS_CRITICAL = 'critical';

    /**
     * De onde saiu o preço — a tela mostra isso na coluna de origem e o teste
     * usa para provar que a cascata respeitou a ordem.
     */
    public const ORIGIN_SETTING = 'setting';

    public const ORIGIN_GLOBAL = ResellerPriceRule::SCOPE_GLOBAL;

    public const ORIGIN_COLLECTION = ResellerPriceRule::SCOPE_COLLECTION;

    public const ORIGIN_PRODUCT = ResellerPriceRule::SCOPE_PRODUCT;

    /**
     * Regras por escopo, já ordenadas pela prioridade de desempate.
     *
     * @var array<string, list<ResellerPriceRule>>
     */
    private array $regras = [
        ResellerPriceRule::SCOPE_PRODUCT => [],
        ResellerPriceRule::SCOPE_COLLECTION => [],
        ResellerPriceRule::SCOPE_GLOBAL => [],
    ];

    /**
     * @param  iterable<int, ResellerPriceRule>  $regras  exceções do lojista, já escopadas
     */
    public function __construct(
        private readonly ResellerPriceSetting $configuracao,
        iterable $regras = [],
    ) {
        /** @var Collection<int, ResellerPriceRule> $ativas */
        $ativas = (new Collection($regras))
            ->filter(static fn (ResellerPriceRule $regra): bool => (bool) $regra->is_active)
            // Maior prioridade primeiro; empatou, a regra mais nova decide.
            ->sortByDesc(static fn (ResellerPriceRule $regra): array => [
                (int) $regra->priority,
                (int) $regra->getKey(),
            ])
            ->values();

        foreach ($ativas as $regra) {
            $escopo = (string) $regra->scope;

            if (array_key_exists($escopo, $this->regras)) {
                $this->regras[$escopo][] = $regra;
            }
        }
    }

    /**
     * Preço sugerido e as duas leituras de rentabilidade do produto.
     *
     * `margin` é margem sobre o preço de venda e `markup` é sobre o custo — são
     * números diferentes da mesma venda, e a tela 2.7 mostra as duas colunas
     * lado a lado justamente porque confundir uma com a outra é o erro clássico
     * de precificação. Com custo 100 e preço 200: margem 50%, markup 100%.
     *
     * @return array{cost: float, price: float, margin: float, markup: float, status: string, origin: string, rule: ResellerPriceRule|null}
     */
    public function resolve(Product $produto): array
    {
        $custo = (float) $produto->price;
        $regra = $this->regraPara($produto);

        $preco = $regra instanceof ResellerPriceRule
            ? $this->precoPelaRegra($custo, $regra)
            : $this->precoPelaConfiguracao($custo);

        // `reseller_price_rules.rounding` é nulável: a exceção só troca a política
        // quando declara uma; senão vale a da configuração do lojista.
        $arredondamento = $regra instanceof ResellerPriceRule && is_string($regra->rounding) && $regra->rounding !== ''
            ? $regra->rounding
            : (string) $this->configuracao->rounding;

        $preco = $this->arredondar($preco, $arredondamento);

        return [
            'cost' => round($custo, 2),
            'price' => $preco,
            'margin' => $this->margem($custo, $preco),
            'markup' => $this->markup($custo, $preco),
            'status' => $this->status($this->margem($custo, $preco)),
            'origin' => $regra instanceof ResellerPriceRule ? (string) $regra->scope : self::ORIGIN_SETTING,
            'rule' => $regra,
        ];
    }

    /**
     * Preço de um custo qualquer pelo padrão do lojista, ignorando as exceções.
     *
     * É a conta da tabela "Exemplo de cálculo" da tela 2.6: ela ilustra o modelo
     * escolhido (multiplicador ou percentual), não o preço de um produto real.
     */
    public function precoPadrao(float $custo): float
    {
        return $this->arredondar(
            $this->precoPelaConfiguracao($custo),
            (string) $this->configuracao->rounding,
        );
    }

    /**
     * Margem sobre o preço de venda, em pontos percentuais.
     */
    public function margem(float $custo, float $preco): float
    {
        if ($preco <= 0.0) {
            return 0.0;
        }

        return round((($preco - $custo) / $preco) * 100, 2);
    }

    /**
     * Markup sobre o custo, em pontos percentuais. Custo zero não tem markup
     * definido (dividiria por zero), e não é o mesmo que markup nulo.
     */
    public function markup(float $custo, float $preco): float
    {
        if ($custo <= 0.0) {
            return 0.0;
        }

        return round((($preco - $custo) / $custo) * 100, 2);
    }

    /**
     * Faixa da margem contra as metas do próprio lojista.
     */
    public function status(float $margem): string
    {
        if ($margem >= (float) $this->configuracao->margin_min) {
            return self::STATUS_IDEAL;
        }

        return $margem < self::CRITICAL_MARGIN ? self::STATUS_CRITICAL : self::STATUS_LOW;
    }

    /**
     * Preço de venda que entrega a margem pedida — a conta que a coluna
     * "Preço sugerido" faz quando o lojista digita uma margem.
     *
     * Margem de 100% (ou mais) é impossível: seria vender com custo zero. Nesse
     * caso o preço fica no custo, e a tela mostra a margem real resultante em
     * vez de um número inventado.
     */
    public function precoParaMargem(float $custo, float $margem): float
    {
        if ($margem >= 100.0) {
            return round($custo, 2);
        }

        return round($custo / (1 - ($margem / 100)), 2);
    }

    /**
     * A primeira regra ativa da cascata, do escopo mais específico para o mais
     * geral. Nenhuma regra encontrada devolve nulo e o preço cai na configuração.
     */
    private function regraPara(Product $produto): ?ResellerPriceRule
    {
        foreach ($this->regras[ResellerPriceRule::SCOPE_PRODUCT] as $regra) {
            if ((int) $regra->product_id === (int) $produto->getKey()) {
                return $regra;
            }
        }

        $colecao = $produto->collection_id;

        if ($colecao !== null) {
            foreach ($this->regras[ResellerPriceRule::SCOPE_COLLECTION] as $regra) {
                if ((int) $regra->collection_id === (int) $colecao) {
                    return $regra;
                }
            }
        }

        return $this->regras[ResellerPriceRule::SCOPE_GLOBAL][0] ?? null;
    }

    private function precoPelaRegra(float $custo, ResellerPriceRule $regra): float
    {
        $valor = (float) $regra->value;

        return match ((string) $regra->mode) {
            ResellerPriceRule::MODE_MULTIPLIER => round($custo * $valor, 2),
            ResellerPriceRule::MODE_PERCENT => $this->precoParaMargem($custo, $valor),
            // Manual e promocional são preço fechado, não fator: o lojista
            // digitou o número que quer na vitrine.
            ResellerPriceRule::MODE_MANUAL, ResellerPriceRule::MODE_PROMO => round($valor, 2),
            default => round($custo, 2),
        };
    }

    private function precoPelaConfiguracao(float $custo): float
    {
        if ((string) $this->configuracao->pricing_model === ResellerPriceSetting::PRICING_MODEL_PERCENT) {
            return $this->precoParaMargem($custo, (float) $this->configuracao->margin_global);
        }

        return round($custo * (float) $this->configuracao->multiplier, 2);
    }

    /**
     * Como o preço aparece na loja. O arredondamento é sempre para cima: puxar
     * o preço para baixo comeria a margem que o lojista acabou de definir.
     */
    private function arredondar(float $preco, string $politica): float
    {
        if ($preco <= 0.0) {
            return 0.0;
        }

        return match ($politica) {
            ResellerPriceSetting::ROUNDING_UP_099 => $this->terminadoEm($preco, 0.99),
            ResellerPriceSetting::ROUNDING_UP_090 => $this->terminadoEm($preco, 0.90),
            ResellerPriceSetting::ROUNDING_UP_INTEGER => (float) ceil($preco),
            ResellerPriceSetting::ROUNDING_NEAREST_10 => (float) (ceil($preco / 10) * 10),
            default => round($preco, 2),
        };
    }

    /**
     * Menor valor `>= $preco` cujos centavos são os pedidos: 154,50 com 0,99
     * vira 154,99; 154,995 vira 155,99.
     */
    private function terminadoEm(float $preco, float $centavos): float
    {
        $candidato = floor($preco) + $centavos;

        return round($candidato >= $preco ? $candidato : $candidato + 1, 2);
    }
}
