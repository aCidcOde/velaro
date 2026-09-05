<?php

/*
[Modulo: app/Http/Controllers/Backend]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 3.10 do Painel Master: base de revendedores, ficha do lojista e cadastro manual com aprovacao na tela.
*/

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\CadastrarRevendedorRequest;
use App\Models\Reseller;
use App\Services\Backend\RevendedorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RevendedoresController extends Controller
{
    public function __construct(private readonly RevendedorService $revendedores) {}

    public function index(Request $request): View
    {
        $this->exigirPermissao($request, 'velaro.resellers.view');

        return view('backend.velaro.revendedores.index', [
            'kpis' => $this->revendedores->kpis(),
            'revendedores' => $this->revendedores->listar($request->only(['status', 'busca'])),
            'filtros' => [
                'status' => $request->string('status')->toString(),
                'busca' => $request->string('busca')->toString(),
            ],
            'podeCadastrar' => $request->user()?->hasBackendPermission('velaro.resellers.create') === true,
        ]);
    }

    public function show(Request $request, Reseller $reseller): View
    {
        $this->exigirPermissao($request, 'velaro.resellers.view');

        $reseller->load([
            'cnaes',
            'documents',
            'consents',
            'store',
            'verifications' => fn ($q) => $q->latest('checked_at'),
            'statusEvents' => fn ($q) => $q->with('actor')->latest('created_at'),
        ]);

        return view('backend.velaro.revendedores.show', [
            'reseller' => $reseller,
            'verificacao' => $reseller->verifications->first(),
            'podeAprovar' => $request->user()?->hasBackendPermission('velaro.resellers.approve') === true,
            // A permissao existe no catalogo, mas "ver como revendedor" ainda nao tem
            // rota nem sessao de impersonate: a tela mostra o estado real em vez de
            // um botao que nao leva a lugar nenhum.
            'podeImpersonar' => $request->user()?->hasBackendPermission('velaro.resellers.impersonate') === true,
        ]);
    }

    public function store(CadastrarRevendedorRequest $request): RedirectResponse
    {
        /** @var array<string, mixed> $dados */
        $dados = $request->validated();
        /** @var list<array{code: string, description?: string|null}> $cnaes */
        $cnaes = $dados['cnaes'] ?? [];
        unset($dados['cnaes']);

        $reseller = $this->revendedores->cadastrarManualmente($dados, $cnaes, $request->user());

        return redirect()
            ->route('backend.revendedores.show', $reseller)
            ->with('status', 'Cadastro manual criado. Aprove o revendedor para liberar o acesso.');
    }

    private function exigirPermissao(Request $request, string $permissao): void
    {
        abort_unless($request->user()?->hasBackendPermission($permissao) === true, 403);
    }
}
