<?php

/*
[Modulo: app/Http/Controllers/Site]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
As tres telas de acompanhamento do cadastro: confirmacao de envio, status da analise e liberacao.
*/

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureCanTrackReseller;
use App\Models\Reseller;
use App\Services\Site\ResellerStatusService;
use App\Support\ResellerContactSources;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class SolicitacaoController extends Controller implements HasMiddleware
{
    public function __construct(private readonly ResellerStatusService $status) {}

    /**
     * 1.5 e 1.6 imprimem dado pessoal do solicitante e o protocolo e sequencial:
     * so ve quem enviou, quem e dono do login ou o Master. A 1.7 nao mostra dado
     * nenhum e o doc a trata como link transacional.
     *
     * @return list<Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware(EnsureCanTrackReseller::class, only: ['enviada', 'status']),
        ];
    }

    /**
     * Tela 1.5 — confirmacao de recebimento, com protocolo e resumo.
     */
    public function enviada(Reseller $reseller): View
    {
        return view('site.solicitacao.enviada', [
            'reseller' => $reseller,
            'steps' => $this->status->steps($reseller),
            'contactSource' => ResellerContactSources::label($reseller->contact_source),
        ]);
    }

    /**
     * Tela 1.6 — andamento da triagem automatica e linha do tempo da solicitacao.
     */
    public function status(Reseller $reseller): View
    {
        return view('site.solicitacao.status', [
            'reseller' => $reseller,
            'steps' => $this->status->steps($reseller, 'Bloqueado até aprovação'),
            'checks' => $this->status->verificationChecks($reseller),
            'timeline' => $this->status->timeline($reseller),
            'lastUpdated' => $this->status->lastUpdatedLabel($reseller),
            'contactSource' => ResellerContactSources::label($reseller->contact_source),
        ]);
    }

    /**
     * Tela 1.7 — estado aprovado. Quem ainda nao foi aprovado volta para o status.
     */
    public function aprovado(Reseller $reseller): View|RedirectResponse
    {
        if ($reseller->status !== Reseller::STATUS_APPROVED) {
            return redirect()->route('site.solicitacao.status', ['reseller' => $reseller->protocol]);
        }

        return view('site.solicitacao.aprovado', [
            'reseller' => $reseller,
        ]);
    }
}
