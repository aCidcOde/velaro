<?php

/*
[Modulo: app/Http/Controllers/Vitrine]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Vitrine white label do lojista: loja, ficha, carrinho de balcao e comprovante — sem login e sem marca Velaro.
*/

namespace App\Http\Controllers\Vitrine;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vitrine\VitrineCarrinhoRequest;
use App\Http\Requests\Vitrine\VitrineFiltroRequest;
use App\Http\Requests\Vitrine\VitrineFinalizarRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\ResellerStore;
use App\Services\Vitrine\VitrineCarrinhoService;
use App\Services\Vitrine\VitrineCatalogoService;
use App\Services\Vitrine\VitrinePedidoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * O ambiente que o **consumidor final** abre, na loja física do revendedor.
 *
 * Não há login aqui, e não há como haver: o consumidor não tem conta na
 * plataforma — ele existe como `customers` na carteira do lojista, e só depois
 * de comprar. O que decide se a página abre é a loja estar publicada, e quem
 * decide isso é {@see VitrineCatalogoService::assertPublicada()}.
 *
 * O controller é fino de propósito. As três regras do ambiente (zero marca
 * Velaro, preço B2C e nenhum processamento de pagamento) moram nos services,
 * onde são estruturais; se elas dependessem de um `if` aqui, a próxima tela do
 * grupo poderia esquecê-las.
 */
class LojaController extends Controller
{
    public function __construct(
        private readonly VitrineCatalogoService $vitrine,
        private readonly VitrineCarrinhoService $carrinho,
        private readonly VitrinePedidoService $pedidos,
    ) {}

    /**
     * Tela 2.9 — a vitrine: banner da loja, abas de categoria e a grade de peças
     * com o preço ao consumidor.
     */
    public function index(VitrineFiltroRequest $request, ResellerStore $store): View
    {
        $this->vitrine->assertPublicada($store);

        return view('vitrine.index', [
            ...$this->vitrine->montarIndice($store, $request->filtros()),
            'sacola' => $this->carrinho->sacola($store),
        ]);
    }

    /**
     * Ficha da peça — galeria, ficha técnica e disponibilidade por aro.
     *
     * O `{product:slug}` da rota resolve qualquer produto do catálogo da fábrica;
     * o service reabre a peça dentro do catálogo **desta loja** e devolve 404
     * quando ela não está lá. É a mesma resposta de "não existe", de propósito:
     * o que o lojista escolheu não expor não existe na loja dele.
     */
    public function produto(VitrineFiltroRequest $request, ResellerStore $store, Product $product): View
    {
        $this->vitrine->assertPublicada($store);

        return view('vitrine.produto', [
            ...$this->vitrine->montarProduto($store, $product, ['visitante' => $request->visitante()]),
            'sacola' => $this->carrinho->sacola($store),
        ]);
    }

    /**
     * Tela 2.10 — o carrinho do balcão, ao lado da grade, como o protótipo do
     * tablet mostra.
     *
     * A mesma rota atende dois papéis. Quando a URL traz uma ação (somar peça,
     * mexer no stepper, tirar linha, escolher a gravação) ela é aplicada e a
     * resposta é um **redirect** para o endereço limpo: é o padrão PRG, e é o que
     * impede um F5 de repetir a ação. Sem ação, a tela é desenhada.
     *
     * As ações chegam por `GET` porque o grupo `vitrine.` tem uma rota `POST`
     * só — a de registrar o pedido — e ela é a única coisa que grava no banco.
     */
    public function carrinho(VitrineCarrinhoRequest $request, ResellerStore $store): View|RedirectResponse
    {
        $this->vitrine->assertPublicada($store);

        if ($request->temAcao()) {
            $aviso = $this->carrinho->aplicar($store, $request->acao());

            return redirect()
                ->route('vitrine.carrinho', $store)
                ->with(VitrineCarrinhoService::CHAVE_AVISO, $aviso);
        }

        return view('vitrine.carrinho', [
            // A grade continua na tela: o atendimento é presencial e o vendedor
            // segue escolhendo peças com o cliente enquanto o carrinho cresce.
            ...$this->vitrine->montarIndice($store, ['categoria' => null, 'visitante' => null]),
            'carrinho' => $this->carrinho->montarPainel($store),
            'sacola' => $this->carrinho->sacola($store),
        ]);
    }

    /**
     * Registra o pedido do balcão e leva ao comprovante.
     *
     * **Não há pagamento aqui** (regra 2 da tela 2.10): o pedido nasce em
     * `draft`, com o pagamento pendente, e o dinheiro é recebido no caixa da
     * loja. O que este método faz é gravar — cliente, itens, gravação e as
     * quatro linhas de valor — e mandar o vendedor para a via da loja.
     */
    public function finalizar(VitrineFinalizarRequest $request, ResellerStore $store): RedirectResponse
    {
        $pedido = $this->pedidos->registrar($store, $request->dados());

        return redirect()->route('vitrine.confirmado', ['store' => $store, 'order' => $pedido]);
    }

    /**
     * O comprovante do pedido, na marca da loja.
     *
     * Pedido de outra loja — e pedido do próprio lojista que não nasceu nesta
     * vitrine — devolve 404. Não é preciosismo: o pedido B2B do lojista carrega
     * o **custo Velaro** em `order_items.unit_price`, e mostrá-lo ao consumidor
     * seria a regra 2 furada pela porta dos fundos.
     */
    public function confirmado(ResellerStore $store, Order $order): View
    {
        $this->vitrine->assertPublicada($store);

        return view('vitrine.confirmado', [
            ...$this->pedidos->montarConfirmacao($store, $order),
            'sacola' => $this->carrinho->sacola($store),
        ]);
    }
}
