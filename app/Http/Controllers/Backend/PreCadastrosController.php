<?php

/*
[Modulo: app/Http/Controllers/Backend]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 3.11 do Painel Master: fila de solicitacoes de lojista e as tres decisoes sobre cada uma.
*/

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\AprovarPreCadastroRequest;
use App\Http\Requests\Backend\ReprovarPreCadastroRequest;
use App\Http\Requests\Backend\SolicitarInformacoesPreCadastroRequest;
use App\Models\Reseller;
use App\Services\Backend\PreCadastroService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PreCadastrosController extends Controller
{
    public function __construct(private readonly PreCadastroService $preCadastros) {}

    public function index(Request $request): View
    {
        $this->exigirPermissao($request, 'velaro.prospects.view');

        return view('backend.velaro.pre-cadastros.index', [
            'kpis' => $this->preCadastros->kpis(),
            'solicitacoes' => $this->preCadastros->fila($request->only(['status', 'busca', 'periodo'])),
            'filtros' => [
                'status' => $request->string('status')->toString(),
                'busca' => $request->string('busca')->toString(),
                'periodo' => $request->input('periodo', 30),
            ],
        ]);
    }

    public function show(Request $request, Reseller $reseller): View
    {
        $this->exigirPermissao($request, 'velaro.prospects.view');

        $reseller->load([
            'cnaes',
            'documents',
            'verifications' => fn ($q) => $q->latest('checked_at'),
            'statusEvents' => fn ($q) => $q->with('actor')->latest('created_at'),
        ]);

        return view('backend.velaro.pre-cadastros.show', [
            'reseller' => $reseller,
            'verificacao' => $reseller->verifications->first(),
            'podeDecidir' => in_array($reseller->status, PreCadastroService::STATUS_EM_FILA, true),
            // O projeto resolve permissao granular por `hasBackendPermission`, nao por
            // Gate: `can()` sem gate definido devolve false em silencio. Os tres botoes
            // saem daqui em vez de um @can que nunca acenderia.
            'podeAprovar' => $request->user()?->hasBackendPermission('velaro.prospects.approve') === true,
            'podeReprovar' => $request->user()?->hasBackendPermission('velaro.prospects.reject') === true,
            'podePedirInfo' => $request->user()?->hasBackendPermission('velaro.prospects.request_info') === true,
        ]);
    }

    private function exigirPermissao(Request $request, string $permissao): void
    {
        abort_unless($request->user()?->hasBackendPermission($permissao) === true, 403);
    }

    public function aprovar(AprovarPreCadastroRequest $request, Reseller $reseller): RedirectResponse
    {
        $this->preCadastros->aprovar($reseller, $request->user(), $request->string('justificativa')->toString());

        return redirect()
            ->route('backend.pre-cadastros.show', $reseller)
            ->with('status', 'Cadastro aprovado. O lojista já pode acessar a plataforma e realizar pedidos.');
    }

    public function reprovar(ReprovarPreCadastroRequest $request, Reseller $reseller): RedirectResponse
    {
        $this->preCadastros->reprovar($reseller, $request->user(), $request->string('justificativa')->toString());

        return redirect()
            ->route('backend.pre-cadastros.show', $reseller)
            ->with('status', 'Cadastro reprovado. A justificativa foi registrada.');
    }

    public function solicitarInformacoes(SolicitarInformacoesPreCadastroRequest $request, Reseller $reseller): RedirectResponse
    {
        $this->preCadastros->solicitarInformacoes($reseller, $request->user(), $request->string('justificativa')->toString());

        return redirect()
            ->route('backend.pre-cadastros.show', $reseller)
            ->with('status', 'Solicitação devolvida ao lojista, que já pode reenviar os documentos.');
    }
}
