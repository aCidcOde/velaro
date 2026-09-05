<?php

/*
[Modulo: app/Services/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Monta a tela de pagamento do lote a Velaro: conferencia, meios B2B habilitados e comprovante — sem gateway.
*/

namespace App\Services\Portal;

use App\Http\Requests\Portal\PagamentoLoteRequest;
use App\Models\Order;
use App\Models\OrderBatch;
use App\Models\Payment;
use App\Models\Reseller;
use App\Support\PixBrCode;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

/**
 * A tela de pagamento do lote **exibe** a cobranca; ela nao a processa.
 *
 * A cobranca nasce no faturamento da Velaro (`payments`, uma linha por lote) e
 * chega aqui pronta: o que o lojista ve e a linha digitavel que ja existe, o
 * codigo Pix montado a partir da chave configurada e os dados bancarios do
 * beneficiario. Nao ha gateway, nao ha chamada externa e nao ha rota de escrita —
 * confirmar o pagamento e ato do banco, e a baixa entra pela conciliacao do
 * Painel Master.
 *
 * Duas consequencias praticas disso, ambas deliberadas:
 *
 * 1. **Nenhum numero de fachada.** Sem chave Pix, sem conta bancaria ou sem
 *    cobranca emitida, o bloco correspondente mostra a pendencia. Um boleto
 *    inventado seria dinheiro indo para lugar nenhum.
 * 2. **O comprovante e leitura.** O arquivo aparece quando o financeiro ja o
 *    anexou (`payments.receipt_path`); enviar um novo e pelo chamado, que e a
 *    unica rota de escrita que o Portal oferece para isso.
 */
class LotePagamentoService
{
    /** Seis linhas por pagina, como a paginacao do prototipo declara. */
    private const PEDIDOS_POR_PAGINA = 6;

    /** Lado do QR Code em pixels — o `.qrbox` do design system reduz para 186px. */
    private const QR_TAMANHO = 220;

    /** Zona de silencio do QR, em modulos: quatro e o minimo que a norma pede. */
    private const QR_MARGEM = 4;

    public function __construct(
        private readonly FinanceiroService $financeiro,
        private readonly FinanceiroApresentacao $ui,
    ) {}

    /**
     * Dados de `/portal/financeiro/lotes/{batch}/pagamento`.
     *
     * O lote ja chegou conferido: `{batch}` e resolvido pelo bind escopado de
     * `ResellerScope`, que devolve 404 — nunca 403 — para o lote de outro lojista.
     *
     * @return array<string, mixed>
     */
    public function montar(OrderBatch $lote, PagamentoLoteRequest $request): array
    {
        $cobranca = $this->cobranca($lote);
        $metodo = $request->metodo($cobranca?->method);
        $resumo = $this->financeiro->resumoDoLote($lote);
        $pedidos = $this->pedidos($lote, $request->pagina());

        return [
            'lote' => $lote,
            'resumo' => $resumo,
            'metodo' => $metodo,
            'metodoRotulo' => $this->ui->rotuloDoMeio($metodo),
            // O `if` da view escolhe o bloco do meio selecionado; a constante fica
            // aqui, e o Blade nao precisa conhecer o namespace do model.
            'ehPix' => $metodo === Payment::METHOD_PIX,
            'ehBoleto' => $metodo === Payment::METHOD_BOLETO,
            'ehTransferencia' => $metodo === Payment::METHOD_BANK_TRANSFER,
            'meios' => $this->financeiro->meios($lote, $metodo),
            'passos' => $this->passos($lote, $resumo),
            'totais' => $resumo['totais'],
            'cobranca' => $cobranca,
            'pedidos' => $pedidos,
            'linhasDePedido' => $this->financeiro->linhasDePedido($pedidos),
            'pix' => $this->pix($lote, $resumo),
            'boleto' => $this->boleto($lote, $resumo, $cobranca),
            'transferencia' => $this->transferencia($lote, $resumo),
            'comprovante' => $this->comprovante($cobranca),
            'beneficiario' => $this->beneficiario(),
        ];
    }

    /**
     * A cobranca do lote. Ha no maximo uma por meio; a do meio escolhido tem
     * preferencia, e na ausencia dela vale a mais recente — e ela que carrega a
     * linha digitavel, o identificador e o comprovante.
     */
    public function cobranca(OrderBatch $lote): ?Payment
    {
        return $lote->payments()->latest('id')->first();
    }

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function pedidos(OrderBatch $lote, int $pagina = 1): LengthAwarePaginator
    {
        return $lote->orders()
            ->with(['customer', 'batch'])
            ->latest('created_at')
            ->orderByDesc('id')
            ->paginate(self::PEDIDOS_POR_PAGINA, ['*'], 'page', $pagina)
            ->withQueryString();
    }

    /**
     * Os cinco passos do topo. O estado sai do proprio lote — nao ha coluna de
     * "etapa": lote fechado e `cut_date` no passado, compensacao e `paid_at`, e
     * liberacao para producao e o primeiro pedido que saiu de "registrado".
     *
     * @param  array<string, mixed>  $resumo
     * @return list<array{rotulo: string, nota: string, estado: string}>
     */
    public function passos(OrderBatch $lote, array $resumo): array
    {
        $quitado = $this->ui->loteQuitado($lote);
        $fechado = $this->ui->loteFechado($lote);
        $emProducao = $lote->orders()
            ->where('operational_status', '!=', Order::OPERATIONAL_STATUS_REGISTERED)
            ->exists();

        return [
            [
                'rotulo' => 'Lote fechado',
                'nota' => $this->ui->data($lote->cut_date),
                'estado' => $fechado ? 'done' : 'now',
            ],
            [
                'rotulo' => 'Conferência dos pedidos',
                'nota' => $resumo['pedidos_rotulo'].' · '.$resumo['total_formatado'],
                'estado' => $fechado ? 'done' : 'todo',
            ],
            [
                'rotulo' => 'Forma de pagamento',
                'nota' => $quitado ? 'Concluída' : 'Você está aqui',
                'estado' => $quitado ? 'done' : 'now',
            ],
            [
                'rotulo' => 'Compensação',
                'nota' => $quitado ? $this->ui->data($lote->paid_at) : 'Até 1 dia útil',
                'estado' => $quitado ? 'done' : 'todo',
            ],
            [
                'rotulo' => 'Liberação para produção',
                'nota' => $emProducao ? 'Pedidos liberados' : 'Automática após a baixa',
                'estado' => $emProducao ? 'done' : 'todo',
            ],
        ];
    }

    /**
     * Bloco do Pix: payload copia e cola, QR e os dados do recebedor.
     *
     * O codigo e **derivado** da chave configurada (ver {@see PixBrCode}), nao a
     * resposta de uma API. Sem chave configurada nao ha codigo e a tela avisa —
     * imprimir um payload de fachada faria o app do banco recusar o pagamento com
     * a culpa caindo no financeiro da Velaro.
     *
     * @param  array<string, mixed>  $resumo
     * @return array<string, mixed>
     */
    public function pix(OrderBatch $lote, array $resumo): array
    {
        $beneficiario = $this->beneficiario();
        $identificador = (string) $lote->code;

        $payload = PixBrCode::payload(
            $beneficiario['pix_chave'],
            $beneficiario['razao_social'],
            $beneficiario['cidade'],
            (float) $resumo['total'],
            $identificador,
        );

        return [
            'disponivel' => $payload !== null,
            'payload' => $payload,
            'qr' => $payload === null ? null : $this->qrCode($payload),
            'beneficiario' => $beneficiario['razao_social'],
            'identificador' => $identificador,
            'valor' => $resumo['total_formatado'],
            'validade' => $resumo['prazo'],
        ];
    }

    /**
     * Bloco do boleto. A linha digitavel e a que o faturamento gravou em
     * `payments.external_id` — o Portal nao emite boleto nem calcula digito
     * verificador; sem cobranca emitida, o bloco diz exatamente isso.
     *
     * @param  array<string, mixed>  $resumo
     * @return array<string, mixed>
     */
    public function boleto(OrderBatch $lote, array $resumo, ?Payment $cobranca): array
    {
        $doBoleto = $cobranca !== null && $cobranca->method === Payment::METHOD_BOLETO ? $cobranca : null;
        $linha = $doBoleto?->external_id;
        $vencimento = $doBoleto === null ? null : $doBoleto->due_date;

        return [
            'disponivel' => $linha !== null && trim($linha) !== '',
            'linha_digitavel' => $linha,
            'vencimento' => $this->ui->data($vencimento ?? $this->ui->dataLimite($lote)),
            'valor' => $resumo['total_formatado'],
            'compensacao' => 'Até 1 dia útil após o pagamento',
        ];
    }

    /**
     * Bloco da transferencia. Conta bancaria sem configuracao nao vira numero
     * inventado: o bloco fica indisponivel e manda falar com o financeiro.
     *
     * @param  array<string, mixed>  $resumo
     * @return array<string, mixed>
     */
    public function transferencia(OrderBatch $lote, array $resumo): array
    {
        $beneficiario = $this->beneficiario();

        $completo = $beneficiario['banco_nome'] !== null
            && $beneficiario['agencia'] !== null
            && $beneficiario['conta'] !== null;

        return [
            'disponivel' => $completo,
            'favorecido' => $beneficiario['razao_social'],
            'cnpj' => $beneficiario['cnpj'],
            'banco' => $beneficiario['banco_codigo'] === null
                ? $beneficiario['banco_nome']
                : $beneficiario['banco_codigo'].' · '.$beneficiario['banco_nome'],
            'agencia' => $beneficiario['agencia'],
            'conta' => $beneficiario['conta'],
            'identificacao' => 'Lote '.$resumo['rotulo'].' · cód. revendedor '.$this->codigoDoLojista($lote),
        ];
    }

    /**
     * `order_batches.reseller_id` e obrigatorio, mas a relacao pode nao carregar
     * (registro removido em cascata manual). O traco e melhor que um erro fatal.
     */
    private function codigoDoLojista(OrderBatch $lote): string
    {
        $revendedor = $lote->reseller;

        return $revendedor instanceof Reseller ? (string) $revendedor->code : '—';
    }

    /**
     * O comprovante que o financeiro anexou a baixa, quando existe.
     *
     * @return array<string, mixed>
     */
    public function comprovante(?Payment $cobranca): array
    {
        $caminho = $cobranca?->receipt_path;

        if ($caminho === null || trim($caminho) === '') {
            return ['disponivel' => false, 'nome' => null, 'data' => null, 'url' => null];
        }

        $disco = (string) config('velaro-financeiro.notas.disco', 'public');
        $configuracao = config('filesystems.disks.'.$disco);
        $publico = is_array($configuracao) && ($configuracao['visibility'] ?? null) === 'public';

        return [
            'disponivel' => true,
            'nome' => basename($caminho),
            'data' => $this->ui->data($cobranca?->paid_at),
            'url' => $publico ? Storage::disk($disco)->url($caminho) : null,
        ];
    }

    /**
     * Quem recebe. Nome e cidade tem padrao (identidade publica da fabrica);
     * chave Pix, CNPJ e conta nao — ver `config/velaro-financeiro.php`.
     *
     * @return array{razao_social: string, cidade: string, cnpj: string|null, pix_chave: string|null, banco_codigo: string|null, banco_nome: string|null, agencia: string|null, conta: string|null}
     */
    public function beneficiario(): array
    {
        /** @var array<string, mixed> $configurado */
        $configurado = config('velaro-financeiro.beneficiario', []);

        $texto = static function (mixed $valor): ?string {
            return is_string($valor) && trim($valor) !== '' ? trim($valor) : null;
        };

        return [
            'razao_social' => $texto($configurado['razao_social'] ?? null) ?? 'Velaro',
            'cidade' => $texto($configurado['cidade'] ?? null) ?? 'SAO PAULO',
            'cnpj' => $texto($configurado['cnpj'] ?? null),
            'pix_chave' => $texto($configurado['pix_chave'] ?? null),
            'banco_codigo' => $texto($configurado['banco_codigo'] ?? null),
            'banco_nome' => $texto($configurado['banco_nome'] ?? null),
            'agencia' => $texto($configurado['agencia'] ?? null),
            'conta' => $texto($configurado['conta'] ?? null),
        ];
    }

    /**
     * QR do payload Pix, em SVG. O plano de fundo e branco de proposito: o
     * `.qrbox` acompanha o tema e um QR sobre superficie escura deixa de ser
     * legivel para a camera.
     */
    private function qrCode(string $payload): string
    {
        $svg = (new Writer(
            new ImageRenderer(
                new RendererStyle(self::QR_TAMANHO, self::QR_MARGEM, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(6, 17, 15))),
                new SvgImageBackEnd,
            )
        ))->writeString($payload);

        // O writer devolve o SVG com declaracao XML; ela nao pode entrar no meio
        // de um documento HTML.
        return trim(substr($svg, (int) strpos($svg, "\n") + 1));
    }
}
