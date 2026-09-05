<?php

/*
[Modulo: app/Http/Controllers/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 2.8: fila de chamados do lojista, abertura de chamado e a thread do atendimento.
*/

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\SuporteFiltroRequest;
use App\Http\Requests\Portal\SuporteStoreRequest;
use App\Models\SupportTicket;
use App\Services\Portal\SupportDeskService;
use App\Support\ResellerScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

/**
 * Suporte — chamados.
 *
 * O `{ticket:code}` de `show` chega já verificado: {@see ResellerScope} resolve
 * o binding e o chamado de outro lojista some com **404**, não 403 — o `code` é
 * sequencial por ano (`SUP-2026-0821`) e a diferença de status deixaria varrer a
 * faixa para medir a fila de atendimento do concorrente.
 *
 * A conversa exibida vem sempre de {@see SupportDeskService::conversa()}, que
 * corta `is_internal_note` no SQL: a observação interna da Velaro nunca sai da
 * consulta, quanto mais da view.
 */
class SuporteController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly ResellerScope $escopo,
        private readonly SupportDeskService $suporte,
    ) {}

    /**
     * Abrir chamado cria registro e dispara atendimento: anda com throttle, como
     * todo formulário de escrita da plataforma.
     *
     * @return list<Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('throttle:10,1', only: ['store']),
        ];
    }

    public function index(SuporteFiltroRequest $request): View
    {
        return view('portal.suporte.index', $this->suporte->montarIndice($this->escopo, $request->filtros()) + [
            'statusDisponiveis' => SupportDeskService::STATUS_LABELS,
            'prioridades' => SupportDeskService::PRIORITY_LABELS,
            'categorias' => SupportTicket::CATEGORIES,
            'periodos' => SupportDeskService::PERIODS,
            'porPagina' => SupportDeskService::PER_PAGE,
        ]);
    }

    public function create(Request $request): View
    {
        $pedido = $request->query('pedido');

        return view('portal.suporte.create', [
            'opcoes' => $this->suporte->opcoesDeVinculo($this->escopo),
            // "Abrir chamado" a partir da tela de um pedido já chega com ele
            // selecionado; o valor é apenas sugestão e o Form Request confere o
            // dono de novo.
            'pedidoSugerido' => is_numeric($pedido) ? (int) $pedido : null,
            'categorias' => SupportTicket::CATEGORIES,
            'prioridades' => SupportDeskService::PRIORITY_LABELS,
            'prioridadePadrao' => SupportTicket::PRIORITY_MEDIUM,
            'canais' => $this->suporte->canais(),
        ]);
    }

    public function store(SuporteStoreRequest $request): RedirectResponse
    {
        $chamado = $this->suporte->abrir($this->escopo, $request->autor(), $request->dados(), $request->anexos());

        return redirect()
            ->route('portal.suporte.show', $chamado)
            ->with('status', 'Chamado '.$chamado->code.' aberto. A equipe Velaro responde em até 1 dia útil.');
    }

    public function show(SupportTicket $ticket): View
    {
        return view('portal.suporte.show', $this->suporte->montarChamado($ticket, $this->escopo));
    }
}
