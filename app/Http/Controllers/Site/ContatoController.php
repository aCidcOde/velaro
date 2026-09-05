<?php

/*
[Modulo: app/Http/Controllers/Site]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 1.8: mostra o Fale Conosco e grava o contato como lead da fila comercial.
*/

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\ContatoStoreRequest;
use App\Services\Site\ContactLeadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ContatoController extends Controller implements HasMiddleware
{
    public function __construct(private readonly ContactLeadService $leads) {}

    /**
     * Formulario aberto na internet: o envio anda com throttle.
     *
     * @return list<Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('throttle:5,1', only: ['store']),
        ];
    }

    public function create(Request $request): View
    {
        $origem = $request->query('origem');
        $assunto = $request->query('assunto');

        return view('site.contato', [
            'canais' => $this->leads->channels(),
            'assuntos' => $this->leads->subjects(),
            'origem' => $this->leads->resolveOrigin(is_string($origem) ? $origem : null),
            'assuntoSelecionado' => $this->leads->resolveSubject(is_string($assunto) ? $assunto : null),
        ]);
    }

    public function store(ContatoStoreRequest $request): RedirectResponse
    {
        // Lead, e so lead: nao nasce revendedor, nao nasce usuario, nao nasce chamado.
        $this->leads->register(
            $request->leadPayload(),
            $request->ip(),
            $request->userAgent(),
        );

        return redirect()
            ->route('site.contato')
            ->with('status', 'Mensagem enviada. O time comercial da Velaro responde em até 1 dia útil.');
    }
}
