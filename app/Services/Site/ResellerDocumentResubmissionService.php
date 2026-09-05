<?php

/*
[Modulo: app/Services/Site]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Reenvio de documentos em Aguardando informacoes: grava os arquivos e devolve a solicitacao para a fila de analise.
*/

namespace App\Services\Site;

use App\Http\Requests\Site\ResellerDocumentResubmissionRequest;
use App\Models\Reseller;
use App\Models\ResellerStatusEvent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * A contraparte da acao "Solicitar informacoes adicionais" do Painel Master
 * (tela 3.11), que ate aqui nao tinha resposta possivel do outro lado.
 *
 * Regra 5 da tela 1.6: o documento reenviado registra evento em
 * `reseller_status_events` e devolve a solicitacao para `pending`. As tres
 * escritas — arquivos, evento e status — vao juntas numa transacao: um cadastro
 * que voltasse para a fila sem o arquivo anexado faria a equipe reabrir a analise
 * para reler exatamente o material que a levou a pedir mais informacao.
 *
 * O estado de origem NAO e conferido aqui: quem o exige e o `authorize()` do
 * {@see ResellerDocumentResubmissionRequest}, um degrau
 * antes, onde a negativa vira 403 em vez de excecao.
 */
class ResellerDocumentResubmissionService
{
    public function __construct(private readonly ResellerDocumentStorage $documents) {}

    /**
     * @param  array<string, UploadedFile>  $documents  tipo de `reseller_documents` => arquivo
     */
    public function resubmit(Reseller $reseller, array $documents): Reseller
    {
        return DB::transaction(function () use ($reseller, $documents): Reseller {
            $this->documents->store($reseller, $documents);

            ResellerStatusEvent::create([
                'reseller_id' => $reseller->id,
                'from_status' => $reseller->status,
                'to_status' => Reseller::STATUS_PENDING,
                // Sem ator: quem agiu foi o proprio lojista, e `actor_id` guarda a
                // decisao humana da equipe Velaro sobre o cadastro.
                'actor_id' => null,
                'note' => 'Documentos reenviados pelo lojista.',
            ]);

            $reseller->forceFill(['status' => Reseller::STATUS_PENDING])->save();

            // Nenhuma linha nova em `reseller_verifications`: a regra 5 pede
            // evento e volta para `pending`, e a triagem automatica de CNPJ/CNAE
            // ja concluiu — o que voltou para a fila e a decisao humana. Abrir uma
            // verificacao nova aqui zeraria na tela um resultado que continua
            // valendo, e quem decide reprocessar a triagem e a equipe.

            return $reseller;
        });
    }
}
