<?php

/*
[Modulo: app/Http/Controllers/Site]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Formulario publico "Seja um revendedor" (tela 1.4) e a gravacao da solicitacao de lojista.
*/

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureCanTrackReseller;
use App\Http\Requests\Site\ResellerRegistrationRequest;
use App\Services\Site\ResellerRegistrationService;
use App\Support\BrazilianStates;
use App\Support\ResellerContactSources;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class CadastroController extends Controller implements HasMiddleware
{
    /**
     * Campos de arquivo da tela 1.4 => `reseller_documents.type`.
     */
    private const DOCUMENT_FIELDS = [
        'articles_of_incorporation',
        'partner_id_document',
        'cnpj_card',
    ];

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

    public function create(): View
    {
        return view('site.cadastro', [
            'states' => BrazilianStates::all(),
            'contactSources' => ResellerContactSources::options(),
            'maxDocumentKb' => ResellerRegistrationRequest::MAX_DOCUMENT_KB,
        ]);
    }

    public function store(ResellerRegistrationRequest $request, ResellerRegistrationService $service): RedirectResponse
    {
        $documents = [];

        foreach (self::DOCUMENT_FIELDS as $field) {
            $file = $request->file($field);

            if ($file instanceof UploadedFile) {
                $documents[$field] = $file;
            }
        }

        $cnaes = $request->input('cnaes');

        $reseller = $service->register(
            $request->validated(),
            $documents,
            is_array($cnaes) ? array_values(array_filter($cnaes, 'is_array')) : [],
            $request->ip(),
            $request->userAgent(),
        );

        // O navegador que enviou passa a poder abrir o acompanhamento desta
        // solicitacao sem login; qualquer outro precisa se identificar.
        EnsureCanTrackReseller::remember($request, $reseller);

        return redirect()
            ->route('site.solicitacao.enviada', ['reseller' => $reseller->protocol])
            ->with('status', 'Cadastro recebido com sucesso.');
    }
}
