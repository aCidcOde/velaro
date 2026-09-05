<?php

/*
[Modulo: app/Services/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Monta o painel do lojista ainda nao aprovado: acompanhamento da solicitacao, reenvio de documentos e caminho de regularizacao.
*/

namespace App\Services\Portal;

use App\Http\Requests\Site\ResellerRegistrationRequest;
use App\Models\Reseller;
use App\Models\ResellerDocument;
use App\Services\Site\ResellerStatusService;
use App\Support\EstagioDoLojista;

/**
 * O painel do primeiro estagio da jornada.
 *
 * Nada aqui e conteudo novo: as etapas, o resultado da triagem automatica e a
 * linha do tempo saem do {@see ResellerStatusService}, o mesmo que alimenta a
 * tela 1.6 no site. O que muda e a moldura — em vez de uma pagina publica
 * alcancada por link transacional, o mesmo acompanhamento passa a ser a primeira
 * tela do painel, dentro do login que o lojista acabou de criar.
 *
 * O {@see PainelLojistaService} continua responsavel pelo estagio aprovado e nao
 * foi tocado: sao dois conteudos do mesmo endereco, escolhidos pelo estagio.
 */
class JornadaDoLojistaService
{
    /**
     * Os tres uploads da tela 1.4, que sao tambem os tres do reenvio (regra 4 da
     * tela 1.6): campo do formulario => rotulo.
     *
     * @var array<string, string>
     */
    public const TIPOS_DE_DOCUMENTO = [
        ResellerDocument::TYPE_ARTICLES_OF_INCORPORATION => 'Contrato social',
        ResellerDocument::TYPE_PARTNER_ID_DOCUMENT => 'Documento do sócio',
        ResellerDocument::TYPE_CNPJ_CARD => 'Cartão CNPJ',
    ];

    public function __construct(private readonly ResellerStatusService $status) {}

    /**
     * @return array<string, mixed>
     */
    public function montar(Reseller $reseller): array
    {
        $estagio = EstagioDoLojista::de($reseller);

        return [
            'reseller' => $reseller,
            'estagio' => $estagio,
            'identificacao' => $this->identificacao($reseller),
            'painel' => $this->painel($reseller),
            'steps' => $this->status->steps($reseller, 'Bloqueado até aprovação'),
            'rotulosDasEtapas' => [
                'received' => 'Cadastro recebido',
                'verification' => 'Validação automática',
                'approval' => 'Aprovação final Velaro',
                'access' => 'Acesso liberado',
            ],
            'checks' => $this->status->verificationChecks($reseller),
            'timeline' => $this->status->timeline($reseller),
            'lastUpdated' => $this->status->lastUpdatedLabel($reseller),
            'proximasEtapas' => $this->proximasEtapas($estagio),
            'documentos' => $this->documentosPedidos($reseller),
            'regularizacao' => $this->regularizacao($reseller, $estagio),
        ];
    }

    /**
     * A barra de identificacao da tela 1.6, com uma diferenca: aqui o lojista ja
     * esta logado, entao "Login vinculado" deixa de ser promessa e vira o
     * endereco da conta com que ele entrou.
     *
     * @return list<array{icone: string, rotulo: string, valor: string}>
     */
    private function identificacao(Reseller $reseller): array
    {
        return [
            ['icone' => 'store', 'rotulo' => 'Parceiro', 'valor' => (string) $reseller->legal_name],
            ['icone' => 'shield', 'rotulo' => 'Protocolo', 'valor' => (string) $reseller->protocol],
            ['icone' => 'user', 'rotulo' => 'Responsável', 'valor' => (string) $reseller->contact_name],
            ['icone' => 'mail', 'rotulo' => 'Login vinculado', 'valor' => (string) $reseller->email],
            ['icone' => 'clock', 'rotulo' => 'Última atualização', 'valor' => $this->status->lastUpdatedLabel($reseller)],
        ];
    }

    /**
     * O cartao escuro "Status atual". Em `rejected` o texto e a justificativa que
     * a equipe registrou na recusa — a decisao final e humana e vem com motivo.
     *
     * @return array{titulo: string, texto: string}
     */
    private function painel(Reseller $reseller): array
    {
        return match ($reseller->status) {
            Reseller::STATUS_AWAITING_INFO => [
                'titulo' => 'Aguardando seus documentos',
                'texto' => 'Nossa equipe precisa de mais um documento para concluir a análise. '
                    .'Envie o que foi pedido e sua solicitação volta para a fila automaticamente.',
            ],
            Reseller::STATUS_REJECTED => [
                'titulo' => 'Cadastro reprovado',
                'texto' => (string) ($reseller->rejection_reason ?: 'A equipe Velaro não aprovou este cadastro. '
                    .'Fale conosco para entender os próximos passos.'),
            ],
            Reseller::STATUS_INACTIVE => [
                'titulo' => 'Cadastro inativo',
                'texto' => 'Este cadastro está inativo e o acesso de Parceiro Premium está suspenso. '
                    .'Fale com nossa equipe para reativá-lo.',
            ],
            default => [
                'titulo' => 'Em validação automática',
                'texto' => 'Nossa IA já concluiu parte da análise. Assim que esta etapa for finalizada, '
                    .'seu cadastro seguirá para aprovação final da equipe Velaro.',
            ],
        };
    }

    /**
     * @return list<string>
     */
    private function proximasEtapas(EstagioDoLojista $estagio): array
    {
        if ($estagio->encerrado()) {
            return [];
        }

        return [
            'Conclusão da validação automática com IA.',
            'Aprovação final da equipe Velaro, com justificativa registrada.',
            'Acesso liberado: catálogo com custo de parceiro, pedidos, financeiro e sua vitrine.',
        ];
    }

    /**
     * O bloco de reenvio, so em `awaiting_info`.
     *
     * `pedido` e a justificativa que o Master escreveu ao acionar "Solicitar
     * informacoes adicionais" (tela 3.11) — o evento de status que levou a
     * solicitacao a este estado. Sem ela o lojista veria um campo de upload sem
     * saber o que anexar.
     *
     * Fora de `awaiting_info` o bloco nao existe: o lojista nao reenvia documento
     * por conta propria.
     *
     * Publico porque a tela 1.6 no site monta o mesmo bloco: o pedido e o
     * formulario de reenvio sao um so, vistos de dois lugares.
     *
     * @return array{pedido: string|null, tipos: array<string, string>, maxKb: int}|null
     */
    public function documentosPedidos(Reseller $reseller): ?array
    {
        if (! EstagioDoLojista::de($reseller)->aguardaDocumentos()) {
            return null;
        }

        $pedido = $reseller->statusEvents()
            ->where('to_status', Reseller::STATUS_AWAITING_INFO)
            ->latest('id')
            ->value('note');

        return [
            'pedido' => is_string($pedido) && $pedido !== '' ? $pedido : null,
            'tipos' => self::TIPOS_DE_DOCUMENTO,
            // O mesmo limite do cadastro: os arquivos sao os mesmos tres.
            'maxKb' => ResellerRegistrationRequest::MAX_DOCUMENT_KB,
        ];
    }

    /**
     * O caminho de volta para quem foi reprovado ou inativado. Reprovado e
     * inativo terminam no mesmo lugar — falar com a equipe —, mas por motivos
     * diferentes, e o texto diz qual e qual.
     *
     * @return list<array{icone: string, titulo: string, texto: string}>
     */
    private function regularizacao(Reseller $reseller, EstagioDoLojista $estagio): array
    {
        if (! $estagio->encerrado()) {
            return [];
        }

        if ($reseller->status === Reseller::STATUS_INACTIVE) {
            return [
                ['icone' => 'support', 'titulo' => 'Peça a reativação', 'texto' => 'Fale com nossa equipe informando o protocolo deste cadastro.'],
                ['icone' => 'doc', 'titulo' => 'Confirme os dados da empresa', 'texto' => 'CNPJ, endereço e responsável precisam estar atualizados na reativação.'],
                ['icone' => 'lock', 'titulo' => 'Seu login continua seu', 'texto' => 'Nada é apagado: reativado o cadastro, o mesmo acesso volta a abrir o portal completo.'],
            ];
        }

        return [
            ['icone' => 'info', 'titulo' => 'Entenda o motivo', 'texto' => 'A justificativa da recusa está no cartão ao lado, registrada por quem analisou.'],
            ['icone' => 'support', 'titulo' => 'Fale com nossa equipe', 'texto' => 'Boa parte das recusas se resolve com um documento legível ou um dado corrigido.'],
            ['icone' => 'refresh', 'titulo' => 'Peça uma nova análise', 'texto' => 'Corrigido o que motivou a recusa, a equipe devolve sua solicitação para a fila.'],
        ];
    }
}
