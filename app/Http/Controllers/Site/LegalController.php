<?php

/*
[Modulo: app/Http/Controllers/Site]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Politica de Privacidade e Termos de Uso: texto institucional versionado com o codigo.
*/

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\Site\LegalDocumentService;
use Illuminate\Contracts\View\View;

class LegalController extends Controller
{
    public function privacidade(LegalDocumentService $documentos): View
    {
        return $this->render(
            $documentos,
            $documentos->privacyPolicy(),
            'site.termos',
            'Ler os Termos de Uso',
        );
    }

    public function termos(LegalDocumentService $documentos): View
    {
        return $this->render(
            $documentos,
            $documentos->termsOfUse(),
            'site.privacidade',
            'Ler a Política de Privacidade',
        );
    }

    /**
     * Os dois documentos tem a mesma casca — hero com o selo da versao, barra de
     * identificacao, indice lateral e corpo numerado —, entao dividem a view.
     *
     * @param  array{titulo: string, resumo: string, secoes: list<array{id: string, titulo: string, corpo: string}>}  $documento
     */
    private function render(
        LegalDocumentService $documentos,
        array $documento,
        string $rotaAlternativa,
        string $rotuloAlternativo,
    ): View {
        return view('site.legal', [
            'documento' => $documento,
            'identidade' => $documentos->identity(),
            'selo' => $documentos->stamp(),
            'aviso' => LegalDocumentService::AUDIENCE_NOTE,
            'versao' => LegalDocumentService::VERSION,
            'dpo' => LegalDocumentService::DPO_EMAIL,
            'rotaAlternativa' => $rotaAlternativa,
            'rotuloAlternativo' => $rotuloAlternativo,
        ]);
    }
}
