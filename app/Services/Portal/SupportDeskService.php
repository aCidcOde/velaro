<?php

/*
[Modulo: app/Services/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 2.8: fila de chamados do lojista, abertura e a thread sem as observacoes internas da Velaro.
*/

namespace App\Services\Portal;

use App\Models\Customer;
use App\Models\Order;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Site\SiteContentService;
use App\Support\ResellerScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * O atendimento é **Velaro ↔ revendedor** (regra 2 da tela 2.8). O consumidor
 * final entra na conversa apenas como pessoa vinculada ao pedido, e não fala.
 *
 * ## A regra mais sensível desta tela
 *
 * `support_messages.is_internal_note = true` é a anotação que a equipe Velaro
 * escreve para si mesma dentro do chamado — combinar prazo com a produção,
 * lembrar que o lojista já reclamou disso antes. Ela **nunca** pode chegar ao
 * revendedor (regra 3 da tela 2.8), e por isso o corte acontece aqui, na
 * consulta, e não na view: uma view que esquece um `@if` vaza o texto inteiro no
 * HTML, e o `@if` certo ainda deixaria a nota exposta em qualquer dump de erro, no
 * cache de página e no JSON de qualquer futura API. O único caminho do portal
 * até a thread é {@see conversa()}, e ele filtra no SQL.
 */
class SupportDeskService
{
    /** Chamados por página, como o rodapé da tabela do protótipo mostra. */
    public const PER_PAGE = 5;

    /**
     * Janelas do filtro "Período". O protótipo abre em "Últimos 90 dias".
     *
     * A chave é a quantidade de dias — e o PHP converte `'30'` em `int` numa
     * chave de array, por isso `array-key` e não `string`. Quem compara a chave
     * com o filtro precisa converter (`(string) $valor`): o filtro chega da query
     * string e é sempre texto.
     *
     * @var array<array-key, string>
     */
    public const PERIODS = [
        '30' => 'Últimos 30 dias',
        '90' => 'Últimos 90 dias',
        '180' => 'Últimos 6 meses',
        '365' => 'Últimos 12 meses',
        'todos' => 'Todo o período',
    ];

    public const PERIOD_DEFAULT = '90';

    /**
     * Rótulo e chip de cada status. As chaves são as constantes do model — a
     * view não conhece nenhuma string de status.
     *
     * @var array<string, array{rotulo: string, chip: string}>
     */
    public const STATUS_LABELS = [
        SupportTicket::STATUS_OPEN => ['rotulo' => 'Aberto', 'chip' => 'chip--neutral'],
        SupportTicket::STATUS_IN_PROGRESS => ['rotulo' => 'Em atendimento', 'chip' => 'chip--info'],
        SupportTicket::STATUS_AWAITING_CUSTOMER => ['rotulo' => 'Aguardando retorno', 'chip' => 'chip--warn'],
        SupportTicket::STATUS_UNDER_REVIEW => ['rotulo' => 'Em análise', 'chip' => 'chip--violet'],
        SupportTicket::STATUS_ANSWERED => ['rotulo' => 'Respondido', 'chip' => 'chip--ok'],
        SupportTicket::STATUS_RESOLVED => ['rotulo' => 'Resolvido', 'chip' => 'chip--ok'],
    ];

    /**
     * @var array<string, array{rotulo: string, chip: string}>
     */
    public const PRIORITY_LABELS = [
        SupportTicket::PRIORITY_HIGH => ['rotulo' => 'Alta', 'chip' => 'chip--danger'],
        SupportTicket::PRIORITY_MEDIUM => ['rotulo' => 'Média', 'chip' => 'chip--warn'],
        SupportTicket::PRIORITY_LOW => ['rotulo' => 'Baixa', 'chip' => 'chip--ok'],
    ];

    /** Prefixo do protocolo. O número é sequencial por ano: `SUP-2026-0821`. */
    private const CODE_PREFIX = 'SUP';

    /** Tentativas de gravação antes de desistir do código sequencial. */
    private const CODE_ATTEMPTS = 5;

    /** Disco e pasta dos anexos — privados, nunca servidos direto pela web. */
    private const ATTACHMENT_DISK = 'local';

    private const ATTACHMENT_DIR = 'suporte';

    public function __construct(private readonly SiteContentService $conteudo) {}

    /**
     * Tudo o que a tela 2.8 mostra, já escopado pelo revendedor.
     *
     * @param  array{q: string|null, status: string|null, categoria: string|null, periodo: string}  $filtros
     * @return array<string, mixed>
     */
    public function montarIndice(ResellerScope $escopo, array $filtros): array
    {
        return [
            'chamados' => $this->listar($escopo, $filtros),
            'numeros' => $this->numeros($escopo),
            'canais' => $this->canais(),
            'filtros' => $filtros,
        ];
    }

    /**
     * A fila do lojista. Nasce de `$escopo->tickets()`, então o
     * `WHERE reseller_id` é estrutural: não há como esquecê-lo aqui.
     *
     * @param  array{q: string|null, status: string|null, categoria: string|null, periodo: string}  $filtros
     * @return LengthAwarePaginator<int, SupportTicket>
     */
    public function listar(ResellerScope $escopo, array $filtros): LengthAwarePaginator
    {
        $consulta = $escopo->tickets()
            ->with(['order', 'customer'])
            ->orderByDesc('updated_at');

        $busca = $filtros['q'] ?? null;

        if (is_string($busca) && $busca !== '') {
            $consulta->where(function (Builder $interna) use ($busca): void {
                $interna->where('code', 'like', '%'.$busca.'%')
                    ->orWhere('subject', 'like', '%'.$busca.'%')
                    // A busca também alcança o texto da conversa — mas só as
                    // mensagens que o revendedor pode ler. Sem este filtro, uma
                    // busca por um trecho de nota interna revelaria que ela
                    // existe naquele chamado, e isso já é o vazamento.
                    ->orWhereHas('messages', function (Builder $mensagens) use ($busca): void {
                        $mensagens->where('is_internal_note', false)
                            ->where('body', 'like', '%'.$busca.'%');
                    });
            });
        }

        $status = $filtros['status'] ?? null;

        if (is_string($status) && $status !== '') {
            $consulta->where('status', $status);
        }

        $categoria = $filtros['categoria'] ?? null;

        if (is_string($categoria) && $categoria !== '') {
            $consulta->where('category', $categoria);
        }

        $dias = $filtros['periodo'];

        if ($dias !== 'todos' && ctype_digit($dias)) {
            $consulta->where('created_at', '>=', Carbon::now()->subDays((int) $dias));
        }

        return $consulta->paginate(self::PER_PAGE)->withQueryString();
    }

    /**
     * Os quatro números do painel "Status do suporte".
     *
     * @return array{total: int, em_atendimento: int, aguardando: int, respondidos: int}
     */
    public function numeros(ResellerScope $escopo): array
    {
        /** @var array<string, int> $porStatus */
        $porStatus = $escopo->tickets()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return [
            'total' => array_sum($porStatus),
            'em_atendimento' => $porStatus[SupportTicket::STATUS_IN_PROGRESS] ?? 0,
            'aguardando' => $porStatus[SupportTicket::STATUS_AWAITING_CUSTOMER] ?? 0,
            'respondidos' => ($porStatus[SupportTicket::STATUS_ANSWERED] ?? 0)
                + ($porStatus[SupportTicket::STATUS_RESOLVED] ?? 0),
        ];
    }

    /**
     * A conversa como o revendedor pode vê-la.
     *
     * O `visibleToReseller` do model é o corte: `is_internal_note = false` entra
     * no SQL e a nota interna não chega nem a ser carregada em memória.
     *
     * @return EloquentCollection<int, SupportMessage>
     */
    public function conversa(SupportTicket $chamado): EloquentCollection
    {
        /** @var EloquentCollection<int, SupportMessage> $mensagens */
        $mensagens = $chamado->messages()
            ->visibleToReseller()
            ->with(['author', 'attachments'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return $mensagens;
    }

    /**
     * A tela do chamado: cabeçalho, conversa, ficha, linha do tempo e anexos.
     *
     * @return array<string, mixed>
     */
    public function montarChamado(SupportTicket $chamado, ResellerScope $escopo): array
    {
        $chamado->loadMissing(['order', 'customer', 'assignee', 'reseller']);

        return [
            'chamado' => $chamado,
            'conversa' => $this->conversa($chamado),
            'anexos' => $chamado->attachments()->orderBy('created_at')->get(),
            'historico' => $chamado->statusEvents()->orderByDesc('created_at')->orderByDesc('id')->get(),
            'status' => $this->rotuloDoStatus((string) $chamado->status),
            'prioridade' => $this->rotuloDaPrioridade((string) $chamado->priority),
            'contato' => $escopo->reseller->contact_name,
            'canais' => $this->canais(),
            'rotulos' => self::STATUS_LABELS,
            'papelVelaro' => SupportMessage::AUTHOR_ROLE_VELARO,
        ];
    }

    /**
     * Abre o chamado com a primeira mensagem da conversa.
     *
     * As duas gravações são uma transação: um chamado sem a mensagem que o
     * originou é um registro que ninguém consegue atender, e a tela do chamado
     * mostraria uma thread vazia.
     *
     * @param  array{subject: string, category: string, priority: string, order_id: int|null, customer_id: int|null, body: string}  $dados
     * @param  list<UploadedFile>  $anexos
     */
    public function abrir(ResellerScope $escopo, User $autor, array $dados, array $anexos = []): SupportTicket
    {
        // O pedido e o cliente vinculados vêm de um `select` — e um `select` é
        // só uma sugestão do navegador. A checagem de dono é refeita aqui, no
        // servidor, e some com 404 quando o id é de outro lojista.
        $pedido = $this->pedidoDoLojista($escopo, $dados['order_id']);
        $cliente = $this->clienteDoLojista($escopo, $dados['customer_id']);

        return DB::transaction(function () use ($escopo, $autor, $dados, $pedido, $cliente, $anexos): SupportTicket {
            $chamado = $this->gravarComCodigo($escopo, [
                'reseller_id' => $escopo->reseller->getKey(),
                'order_id' => $pedido?->getKey(),
                'customer_id' => $cliente?->getKey(),
                'subject' => $dados['subject'],
                'category' => $dados['category'],
                'priority' => $dados['priority'],
                'status' => SupportTicket::STATUS_OPEN,
                'channel' => SupportTicket::CHANNEL_PORTAL,
            ]);

            $mensagem = $chamado->messages()->create([
                'author_id' => $autor->getKey(),
                'author_role' => SupportMessage::AUTHOR_ROLE_RESELLER,
                'body' => $dados['body'],
                // Mensagem aberta pelo lojista nunca é nota interna: a nota é da
                // Velaro sobre o chamado, e o portal não tem como criar uma.
                'is_internal_note' => false,
            ]);

            $this->guardarAnexos($chamado, $mensagem, $anexos);

            $chamado->statusEvents()->create([
                'from_status' => null,
                'to_status' => SupportTicket::STATUS_OPEN,
                'actor_id' => $autor->getKey(),
                'channel' => SupportTicket::CHANNEL_PORTAL,
            ]);

            return $chamado;
        });
    }

    /**
     * Guarda os arquivos da abertura.
     *
     * O disco é o privado (`local`, em `storage/app/private`): o anexo pode ser
     * a nota fiscal ou o espelho de um pedido com o nome do consumidor final, e
     * um caminho público seria adivinhável por quem tivesse a URL.
     *
     * @param  list<UploadedFile>  $anexos
     */
    private function guardarAnexos(SupportTicket $chamado, SupportMessage $mensagem, array $anexos): void
    {
        foreach ($anexos as $arquivo) {
            $caminho = $arquivo->store(self::ATTACHMENT_DIR.'/'.$chamado->getKey(), self::ATTACHMENT_DISK);

            if (! is_string($caminho) || $caminho === '') {
                continue;
            }

            $chamado->attachments()->create([
                'message_id' => $mensagem->getKey(),
                'original_name' => $arquivo->getClientOriginalName(),
                'disk' => self::ATTACHMENT_DISK,
                'path' => $caminho,
                'size_bytes' => $arquivo->getSize() ?: 0,
                'mime' => $arquivo->getClientMimeType(),
            ]);
        }
    }

    /**
     * Pedidos e clientes do lojista para os dois selects opcionais da abertura.
     *
     * @return array{pedidos: list<array{id: int, rotulo: string}>, clientes: list<array{id: int, rotulo: string}>}
     */
    public function opcoesDeVinculo(ResellerScope $escopo): array
    {
        /** @var EloquentCollection<int, Order> $pedidos */
        $pedidos = $escopo->orders()
            ->with('customer')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        /** @var EloquentCollection<int, Customer> $clientes */
        $clientes = $escopo->customers()->orderBy('name')->limit(200)->get();

        return [
            'pedidos' => $pedidos->map(static fn (Order $pedido): array => [
                'id' => (int) $pedido->getKey(),
                'rotulo' => trim((string) $pedido->public_number.' · '.(string) ($pedido->customer->name ?? 'sem cliente')),
            ])->values()->all(),
            'clientes' => $clientes->map(static fn (Customer $cliente): array => [
                'id' => (int) $cliente->getKey(),
                'rotulo' => (string) $cliente->name,
            ])->values()->all(),
        ];
    }

    /**
     * @return array{rotulo: string, chip: string}
     */
    public function rotuloDoStatus(string $status): array
    {
        return self::STATUS_LABELS[$status] ?? ['rotulo' => $status, 'chip' => 'chip--neutral'];
    }

    /**
     * @return array{rotulo: string, chip: string}
     */
    public function rotuloDaPrioridade(string $prioridade): array
    {
        return self::PRIORITY_LABELS[$prioridade] ?? ['rotulo' => $prioridade, 'chip' => 'chip--neutral'];
    }

    /**
     * Canais de atendimento do card lateral. Vêm do grupo `contact` de
     * `settings`, o mesmo que o rodapé do site publica — não há segunda forma da
     * mesma informação.
     *
     * @return array{telefone: string, whatsapp: string, email: string, horario: string}
     */
    public function canais(): array
    {
        $contato = $this->conteudo->contact();
        $telefone = $contato['telefone'] ?? '';

        return [
            'telefone' => $telefone,
            'whatsapp' => $contato['whatsapp'] ?? $telefone,
            'email' => $contato['email'] ?? '',
            'horario' => $contato['horario'] ?? 'Segunda a sexta, das 8h às 18h',
        ];
    }

    /**
     * Grava o chamado com o próximo protocolo do ano.
     *
     * O código é sequencial e a coluna é UNIQUE: duas aberturas simultâneas
     * disputariam o mesmo número. A colisão é resolvida tentando de novo com o
     * próximo — recalcular o máximo a cada tentativa é o que faz a segunda
     * gravação achar o número já ocupado e seguir adiante.
     *
     * @param  array<string, mixed>  $atributos
     */
    private function gravarComCodigo(ResellerScope $escopo, array $atributos): SupportTicket
    {
        for ($tentativa = 0; $tentativa < self::CODE_ATTEMPTS; $tentativa++) {
            $codigo = $this->proximoCodigo($tentativa);

            if (SupportTicket::query()->where('code', $codigo)->exists()) {
                continue;
            }

            return $escopo->tickets()->create($atributos + ['code' => $codigo]);
        }

        // Esgotadas as tentativas o protocolo ganha um sufixo aleatório: um
        // chamado com número feio é melhor do que um chamado que não abre.
        return $escopo->tickets()->create($atributos + [
            'code' => $this->proximoCodigo(0).'-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 4)),
        ]);
    }

    private function proximoCodigo(int $salto): string
    {
        $ano = Carbon::now()->year;
        $prefixo = self::CODE_PREFIX.'-'.$ano.'-';

        /** @var string|null $ultimo */
        $ultimo = SupportTicket::query()
            ->where('code', 'like', $prefixo.'%')
            ->orderByDesc('code')
            ->value('code');

        $sequencia = $ultimo === null
            ? 0
            : (int) substr($ultimo, strlen($prefixo), 4);

        return $prefixo.str_pad((string) ($sequencia + 1 + $salto), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Registro de outro lojista some com 404, nunca 403 — a mesma razão de
     * {@see ResellerScope}: o status diferente confirmaria que o id existe.
     */
    private function pedidoDoLojista(ResellerScope $escopo, ?int $id): ?Order
    {
        if ($id === null) {
            return null;
        }

        /** @var Order $pedido */
        $pedido = $escopo->orders()->findOrFail($id);

        return $pedido;
    }

    private function clienteDoLojista(ResellerScope $escopo, ?int $id): ?Customer
    {
        if ($id === null) {
            return null;
        }

        /** @var Customer $cliente */
        $cliente = $escopo->customers()->findOrFail($id);

        return $cliente;
    }
}
