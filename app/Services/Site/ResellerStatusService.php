<?php

/*
[Modulo: app/Services/Site]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Traduz o estado do cadastro em stepper, checklist da triagem e linha do tempo das telas 1.5, 1.6 e 1.7.
*/

namespace App\Services\Site;

use App\Models\Reseller;
use App\Models\ResellerStatusEvent;
use App\Models\ResellerVerification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class ResellerStatusService
{
    /**
     * Memoria por revendedor: a tela 1.6 pede o mesmo registro em tres blocos e
     * a mesma lista de eventos em dois. Uma consulta de cada, nao quatro.
     *
     * @var array<int, ResellerVerification|null>
     */
    private array $verifications = [];

    /**
     * @var array<int, Collection<int, ResellerStatusEvent>>
     */
    private array $events = [];

    /**
     * As quatro etapas do stepper, na ordem das telas 1.5 e 1.6. O rotulo fica na
     * view porque muda de tela para tela; aqui sai so a chave, o estado e a nota.
     *
     * @return array<int, array{key: string, state: string, note: string, dot: string}>
     */
    public function steps(Reseller $reseller, string $lockedNote = 'Aguardando'): array
    {
        $verification = $this->latestVerification($reseller);
        $verified = $verification instanceof ResellerVerification && $verification->checked_at !== null;
        $decided = in_array($reseller->status, [Reseller::STATUS_APPROVED, Reseller::STATUS_REJECTED], true);
        $approved = $reseller->status === Reseller::STATUS_APPROVED;

        $verificationState = $decided || $verified ? 'done' : 'now';
        $approvalState = $decided ? 'done' : ($verified ? 'now' : 'todo');
        $accessState = $approved ? 'done' : 'locked';

        return [
            $this->step('received', 'done', '1'),
            $this->step('verification', $verificationState, '2'),
            $this->step('approval', $approvalState, '3'),
            $this->step('access', $accessState, $approved ? '✓' : '🔒', $lockedNote),
        ];
    }

    /**
     * As cinco verificacoes da triagem automatica da tela 1.6. Cada linha do
     * prototipo tem uma origem no banco: tres booleanos de `reseller_verifications`
     * e, para a compatibilidade com o segmento e a analise de documentos, as chaves
     * correspondentes do `result` — que e onde o job de verificacao deposita o
     * detalhe da analise.
     *
     * `documentacao_enviada` NAO alimenta a ultima linha: ela e o registro de que
     * os arquivos chegaram no request, e nasce verdadeira. Usa-la aqui faria a tela
     * anunciar "Analise complementar de documentos: Concluido" no minuto do envio,
     * antes de qualquer analise existir.
     *
     * @return array<int, array{label: string, state: string, note: string, icon: string}>
     */
    public function verificationChecks(Reseller $reseller): array
    {
        $verification = $this->latestVerification($reseller);
        $result = $verification instanceof ResellerVerification && is_array($verification->result)
            ? $verification->result
            : [];

        $segment = $result['segment_compatible'] ?? null;
        $documents = $result['documents_reviewed'] ?? null;

        return [
            $this->check('Consulta de CNPJ', $verification?->cnpj_valido),
            $this->check('Validação de CNAE', $verification?->cnaes_compativeis),
            $this->check('Compatibilidade com o segmento', $segment, 'Em análise'),
            $this->check('Verificação de dados cadastrais', $verification?->empresa_ativa),
            $this->check('Análise complementar de documentos', $documents, 'Em processamento'),
        ];
    }

    /**
     * Linha do tempo da solicitacao, montada sobre `reseller_status_events`.
     *
     * @return array<int, array{label: string, note: string|null, when: string, state: string}>
     */
    public function timeline(Reseller $reseller): array
    {
        $events = $this->statusEvents($reseller);
        $open = $reseller->status === Reseller::STATUS_PENDING;
        $last = $events->count() - 1;

        return $events->values()->map(function (ResellerStatusEvent $event, int $index) use ($last, $open): array {
            return [
                'label' => $this->eventLabel($event),
                'note' => $event->note,
                'when' => $this->shortTime($event->created_at),
                'state' => $index === $last && $open ? 'now' : 'done',
            ];
        })->all();
    }

    /**
     * "Hoje, 10:42" enquanto for hoje; a data cheia depois disso.
     */
    public function lastUpdatedLabel(Reseller $reseller): string
    {
        $event = $this->statusEvents($reseller)->last();
        $moment = $event instanceof ResellerStatusEvent ? $event->created_at : $reseller->updated_at;

        if (! $moment instanceof Carbon) {
            return '—';
        }

        return $moment->isToday()
            ? 'Hoje, '.$moment->format('H:i')
            : $moment->format('d/m/Y H:i');
    }

    public function latestVerification(Reseller $reseller): ?ResellerVerification
    {
        if (! array_key_exists($reseller->id, $this->verifications)) {
            $this->verifications[$reseller->id] = $reseller->verifications()->latest('id')->first();
        }

        return $this->verifications[$reseller->id];
    }

    /**
     * Eventos da solicitacao em ordem cronologica, uma vez por request.
     *
     * @return Collection<int, ResellerStatusEvent>
     */
    private function statusEvents(Reseller $reseller): Collection
    {
        return $this->events[$reseller->id] ??= $reseller->statusEvents()
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array{key: string, state: string, note: string, dot: string}
     */
    private function step(string $key, string $state, string $dot, string $lockedNote = 'Aguardando'): array
    {
        $notes = [
            'done' => 'Concluído',
            'now' => 'Em andamento',
            'todo' => 'Aguardando',
            'locked' => $lockedNote,
        ];

        return [
            'key' => $key,
            'state' => $state,
            'note' => $notes[$state] ?? 'Aguardando',
            'dot' => $state === 'done' ? '✓' : $dot,
        ];
    }

    /**
     * @return array{label: string, state: string, note: string, icon: string}
     */
    private function check(string $label, mixed $value, string $pendingNote = 'Em processamento'): array
    {
        if ($value === true) {
            return ['label' => $label, 'state' => 'ok', 'note' => 'Concluído', 'icon' => 'check'];
        }

        if ($value === false) {
            return ['label' => $label, 'state' => 'fail', 'note' => 'Não aprovado', 'icon' => 'x'];
        }

        return ['label' => $label, 'state' => 'wait', 'note' => $pendingNote, 'icon' => 'clock'];
    }

    private function eventLabel(ResellerStatusEvent $event): string
    {
        return match ($event->to_status) {
            Reseller::STATUS_PENDING => $event->from_status === null ? 'Cadastro recebido' : 'Cadastro devolvido para análise',
            // Sem este caso a linha do tempo anunciava "Status atualizado" no
            // evento que mais precisa de nome: e ele que explica ao lojista por
            // que a analise parou e o que a equipe esta esperando dele.
            Reseller::STATUS_AWAITING_INFO => 'Informações adicionais solicitadas',
            Reseller::STATUS_APPROVED => 'Cadastro aprovado',
            Reseller::STATUS_REJECTED => 'Cadastro reprovado',
            Reseller::STATUS_INACTIVE => 'Cadastro inativado',
            default => 'Status atualizado',
        };
    }

    private function shortTime(mixed $moment): string
    {
        if (! $moment instanceof Carbon) {
            return '—';
        }

        return $moment->isToday() ? $moment->format('H:i') : $moment->format('d/m H:i');
    }
}
