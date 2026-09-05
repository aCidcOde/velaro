<?php

/*
 * Rotas da plataforma B2B Velaro — o contrato entre as 31 telas contratadas (e
 * as 33 internas) e o código. Os caminhos são os que docs/mockups/_mapa.py
 * declara em cada tela; os nomes seguem <ambiente>.<tela>[.<ação>].
 *
 * Quatro ambientes:
 *   site     — público, na raiz
 *   portal   — Parceiro Premium aprovado (middleware reseller)
 *   vitrine  — white label do revendedor, pública, em /loja/{slug}
 *   backend  — Perfil Master (gate access-backend)
 *
 * Login/senha ficam com o Fortify (/login, /forgot-password, …).
 */

use App\Http\Controllers\Backend;
use App\Http\Controllers\Portal;
use App\Http\Controllers\Site;
use App\Http\Controllers\Vitrine;
use Illuminate\Support\Facades\Route;

// ─────────────────────────────── SITE PÚBLICO ───────────────────────────────
Route::name('site.')->group(function (): void {
    Route::get('/', Site\HomeController::class)->name('home');
    Route::get('sobre', Site\SobreController::class)->name('sobre');
    Route::get('catalogo/{colecao?}', [Site\CatalogoController::class, 'index'])->name('catalogo');
    Route::get('produto/{product:slug}', [Site\CatalogoController::class, 'produto'])->name('produto');
    Route::get('seja-revendedor', [Site\CadastroController::class, 'create'])->name('cadastro');
    Route::post('seja-revendedor', [Site\CadastroController::class, 'store'])->name('cadastro.store');
    Route::get('solicitacao/{reseller:protocolo}/enviada', [Site\SolicitacaoController::class, 'enviada'])->name('solicitacao.enviada');
    Route::get('solicitacao/{reseller:protocolo}', [Site\SolicitacaoController::class, 'status'])->name('solicitacao.status');
    Route::get('solicitacao/{reseller:protocolo}/aprovado', [Site\SolicitacaoController::class, 'aprovado'])->name('solicitacao.aprovado');
    Route::get('privacidade', [Site\LegalController::class, 'privacidade'])->name('privacidade');
    Route::get('termos', [Site\LegalController::class, 'termos'])->name('termos');
});

// ─────────────────────────────── PORTAL DO LOJISTA ───────────────────────────────
Route::prefix('portal')
    ->name('portal.')
    ->middleware(['auth', 'not_blocked', 'verified', 'reseller'])
    ->group(function (): void {
        Route::get('/', Portal\DashboardController::class)->name('dashboard');
        Route::get('catalogo', [Portal\CatalogoController::class, 'index'])->name('catalogo');
        Route::get('clientes', [Portal\ClientesController::class, 'index'])->name('clientes.index');
        Route::get('clientes/{customer}', [Portal\ClientesController::class, 'show'])->name('clientes.show');
        Route::get('financeiro', [Portal\FinanceiroController::class, 'index'])->name('financeiro.index');
        Route::get('financeiro/notas', [Portal\FinanceiroController::class, 'notas'])->name('financeiro.notas');
        Route::get('financeiro/lotes/{batch}/pagamento', [Portal\FinanceiroController::class, 'pagamento'])->name('financeiro.pagamento');
        Route::get('pedidos', [Portal\PedidosController::class, 'index'])->name('pedidos.index');
        Route::get('pedidos/{order:public_number}', [Portal\PedidosController::class, 'show'])->name('pedidos.show');
        Route::get('loja', [Portal\LojaController::class, 'edit'])->name('loja.edit');
        Route::put('loja', [Portal\LojaController::class, 'update'])->name('loja.update');
        Route::get('precos', [Portal\PrecosController::class, 'edit'])->name('precos.edit');
        Route::put('precos', [Portal\PrecosController::class, 'update'])->name('precos.update');
        Route::get('suporte', [Portal\SuporteController::class, 'index'])->name('suporte.index');
        Route::get('suporte/novo', [Portal\SuporteController::class, 'create'])->name('suporte.create');
        Route::post('suporte', [Portal\SuporteController::class, 'store'])->name('suporte.store');
        Route::get('suporte/{ticket:code}', [Portal\SuporteController::class, 'show'])->name('suporte.show');
        Route::get('ajuda', Portal\AjudaController::class)->name('ajuda');
        // "Vitrine para clientes" no menu abre a loja do próprio revendedor.
        Route::get('vitrine', Portal\VitrineRedirectController::class)->name('vitrine');
    });

// ─────────────────────────────── VITRINE WHITE LABEL ───────────────────────────────
Route::prefix('loja/{store:slug}')
    ->name('vitrine.')
    ->group(function (): void {
        Route::get('/', [Vitrine\LojaController::class, 'index'])->name('index');
        Route::get('produto/{product:slug}', [Vitrine\LojaController::class, 'produto'])->name('produto');
        Route::get('carrinho', [Vitrine\LojaController::class, 'carrinho'])->name('carrinho');
        Route::post('carrinho/finalizar', [Vitrine\LojaController::class, 'finalizar'])->name('finalizar');
        Route::get('pedido/{order:public_number}', [Vitrine\LojaController::class, 'confirmado'])->name('confirmado');
    });

// ─────────────────────────────── PAINEL MASTER ───────────────────────────────
// backend.dashboard, backend.users.*, backend.audit-logs.* e backend.changelog.*
// continuam em routes/web.php (infraestrutura do scaffold, re-skinada).
Route::prefix('backend')
    ->name('backend.')
    ->middleware(['auth', 'not_blocked', 'verified', 'can:access-backend'])
    ->group(function (): void {
        Route::get('clientes', [Backend\ClientesController::class, 'index'])->name('clientes.index');
        Route::get('clientes/{customer}', [Backend\ClientesController::class, 'show'])->name('clientes.show');

        Route::get('configuracoes', [Backend\ConfiguracoesController::class, 'index'])->name('configuracoes.index');
        Route::get('configuracoes/{secao}', [Backend\ConfiguracoesController::class, 'secao'])
            ->whereIn('secao', ['empresa', 'usuarios', 'notificacoes', 'integracoes', 'seguranca', 'financeiro', 'personalizacao', 'backup'])
            ->name('configuracoes.secao');
        Route::put('configuracoes/{secao}', [Backend\ConfiguracoesController::class, 'update'])->name('configuracoes.update');

        Route::get('estoque', [Backend\EstoqueController::class, 'index'])->name('estoque.index');
        Route::get('estoque/movimentacao', [Backend\EstoqueController::class, 'create'])->name('estoque.movimentacao');
        Route::post('estoque/movimentacao', [Backend\EstoqueController::class, 'store'])->name('estoque.movimentacao.store');
        Route::get('estoque/{variant}/historico', [Backend\EstoqueController::class, 'historico'])->name('estoque.historico');

        Route::get('financeiro', [Backend\FinanceiroController::class, 'index'])->name('financeiro.index');
        Route::get('financeiro/recebimento', [Backend\FinanceiroController::class, 'create'])->name('financeiro.recebimento');
        Route::post('financeiro/recebimento', [Backend\FinanceiroController::class, 'store'])->name('financeiro.recebimento.store');
        Route::get('financeiro/lotes/{batch}/nota', [Backend\FinanceiroController::class, 'nota'])->name('financeiro.nota');

        Route::get('pedidos', [Backend\PedidosController::class, 'index'])->name('pedidos.index');
        Route::get('pedidos/novo', [Backend\PedidosController::class, 'create'])->name('pedidos.create');
        Route::post('pedidos', [Backend\PedidosController::class, 'store'])->name('pedidos.store');
        Route::get('pedidos/{order:public_number}', [Backend\PedidosController::class, 'show'])->name('pedidos.show');

        Route::get('produtos', [Backend\ProdutosController::class, 'index'])->name('produtos.index');
        Route::get('produtos/novo', [Backend\ProdutosController::class, 'create'])->name('produtos.create');
        Route::post('produtos', [Backend\ProdutosController::class, 'store'])->name('produtos.store');
        Route::get('produtos/{product}', [Backend\ProdutosController::class, 'show'])->name('produtos.show');

        Route::get('promocoes', [Backend\PromocoesController::class, 'index'])->name('promocoes.index');
        Route::get('promocoes/nova', [Backend\PromocoesController::class, 'create'])->name('promocoes.create');
        Route::post('promocoes', [Backend\PromocoesController::class, 'store'])->name('promocoes.store');
        Route::get('promocoes/{promotion}', [Backend\PromocoesController::class, 'show'])->name('promocoes.show');
        Route::get('promocoes/{promotion}/desempenho', [Backend\PromocoesController::class, 'desempenho'])->name('promocoes.desempenho');

        Route::get('relatorios', [Backend\RelatoriosController::class, 'index'])->name('relatorios.index');
        Route::get('relatorios/vendas', [Backend\RelatoriosController::class, 'vendas'])->name('relatorios.vendas');
        Route::get('relatorios/revendedores', [Backend\RelatoriosController::class, 'revendedores'])->name('relatorios.revendedores');
        Route::get('relatorios/produtos', [Backend\RelatoriosController::class, 'produtos'])->name('relatorios.produtos');
        Route::get('relatorios/agendados', [Backend\RelatoriosController::class, 'agendados'])->name('relatorios.agendados');
        Route::get('relatorios/todos', [Backend\RelatoriosController::class, 'todos'])->name('relatorios.todos');

        Route::get('revendedores', [Backend\RevendedoresController::class, 'index'])->name('revendedores.index');
        Route::post('revendedores', [Backend\RevendedoresController::class, 'store'])->name('revendedores.store');
        Route::get('revendedores/{reseller}', [Backend\RevendedoresController::class, 'show'])->name('revendedores.show');

        Route::get('pre-cadastros', [Backend\PreCadastrosController::class, 'index'])->name('pre-cadastros.index');
        Route::get('pre-cadastros/{reseller}', [Backend\PreCadastrosController::class, 'show'])->name('pre-cadastros.show');

        Route::get('suporte', [Backend\SuporteController::class, 'index'])->name('suporte.index');
        Route::get('suporte/{ticket:code}', [Backend\SuporteController::class, 'show'])->name('suporte.show');
    });
