<?php

/*
[Modulo: app/Services/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Vocabulario visual do financeiro do lojista: moeda, prazo do lote, rotulo da semana e os chips de status.
*/

namespace App\Services\Portal;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderBatch;
use App\Models\Payment;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * As tres telas do financeiro (2.4, notas e pagamento) mostram os mesmos numeros
 * em lugares diferentes: o total do lote aparece no KPI, na tabela, no drawer e
 * no resumo do pagamento. Formatar em cada view faria a mesma regra divergir em
 * quatro pontos — o rotulo do lote e o caso mais claro disso, porque ele nao e um
 * campo do banco e sim uma leitura do `code`.
 *
 * Nada aqui consulta banco: e traducao de valor em texto, e por isso e a unica
 * camada do lote em que faz sentido escrever string literal de interface.
 */
final class FinanceiroApresentacao
{
    /**
     * Meses por extenso fixos em pt-BR. `translatedFormat()` seguiria
     * `APP_LOCALE`, que no `.env.example` do scaffold e `en`: a competencia da
     * nota sairia "May/2026" numa tela que o contrato escreve "Maio/2026".
     *
     * @var array<int, string>
     */
    private const MESES = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
    ];

    /**
     * O lote cobre a semana que termina no corte. `cut_date` e o ultimo dia, e o
     * primeiro e seis dias antes — e assim que "24/2026" vira "15/05 a 21/05".
     */
    private const DIAS_DA_SEMANA_DO_LOTE = 6;

    public function moeda(float|int|string|null $valor): string
    {
        return 'R$ '.number_format((float) $valor, 2, ',', '.');
    }

    /**
     * As tres formatacoes de data aceitam o valor cru do model.
     *
     * O motivo e pratico: as colunas de data do modulo Velaro nasceram em
     * portugues e foram renomeadas pela migration de traducao, e o leitor de
     * migrations da analise estatica nao acompanha o rename — ele enxerga
     * `cut_date` e `issued_at` como `string`. Normalizar na entrada evita espalhar
     * conversao por seis pontos de chamada e ainda torna o metodo imune a data
     * vinda de consulta crua.
     */
    public function data(mixed $valor): string
    {
        return $this->paraData($valor)?->format('d/m/Y') ?? '—';
    }

    public function hora(mixed $valor): string
    {
        return $this->paraData($valor)?->format('H:i') ?? '—';
    }

    public function competencia(mixed $valor): string
    {
        $data = $this->paraData($valor);

        if ($data === null) {
            return '—';
        }

        return self::MESES[(int) $data->format('n')].'/'.$data->format('Y');
    }

    /**
     * Converte para Carbon o que der; devolve `null` para vazio e para lixo.
     */
    public function paraData(mixed $valor): ?Carbon
    {
        if ($valor instanceof CarbonInterface) {
            return Carbon::instance($valor);
        }

        if (! is_string($valor) || trim($valor) === '') {
            return null;
        }

        return Carbon::parse($valor);
    }

    /**
     * O horario de corte do vencimento — o "às 18h" que a tela 2.4 repete em todo
     * prazo. `due_date` guarda so o dia; a hora vem da configuracao.
     */
    public function horaLimite(): int
    {
        return (int) config('velaro-financeiro.hora_limite', 18);
    }

    public function rotuloDaHoraLimite(): string
    {
        return 'às '.$this->horaLimite().'h';
    }

    /**
     * Instante exato em que o lote vence: o dia de `due_date` na hora de corte.
     * E o que separa "vence hoje" de "venceu".
     */
    public function dataLimite(OrderBatch $lote): ?Carbon
    {
        return $this->paraData($lote->due_date)?->setTime($this->horaLimite(), 0);
    }

    public function prazoCompleto(OrderBatch $lote): string
    {
        $limite = $this->dataLimite($lote);

        return $limite === null ? '—' : $limite->format('d/m/Y').' '.$this->rotuloDaHoraLimite();
    }

    /**
     * "24/2026" — a semana e o ano do lote, como o contrato numera o faturamento.
     *
     * O numero mora no `code` (`LOTE-2026-W24-VEL02412`), que e escrito no
     * fechamento e nao se recalcula depois. Quando o codigo nao segue o formato,
     * a semana ISO do corte responde no lugar — nunca uma linha sem rotulo.
     */
    public function rotuloDoLote(OrderBatch $lote): string
    {
        $codigo = (string) $lote->code;

        if (preg_match('/^LOTE-(\d{4})-W(\d{1,2})/', $codigo, $partes) === 1) {
            return str_pad($partes[2], 2, '0', STR_PAD_LEFT).'/'.$partes[1];
        }

        $corte = $this->paraData($lote->cut_date);

        if ($corte === null) {
            return $codigo;
        }

        return $corte->format('W').'/'.$corte->format('o');
    }

    /** "15/05/2026 a 21/05/2026" — a semana coberta pelo lote. */
    public function periodoDoLote(OrderBatch $lote): string
    {
        $fim = $this->paraData($lote->cut_date);

        if ($fim === null) {
            return '—';
        }

        $inicio = $fim->copy()->subDays(self::DIAS_DA_SEMANA_DO_LOTE);

        return $inicio->format('d/m/Y').' a '.$fim->format('d/m/Y');
    }

    /**
     * O lote esta quitado? `paid_at` e a autoridade — e o fato consumado da baixa,
     * e nao depende de o `status` textual ter sido atualizado junto.
     */
    public function loteQuitado(OrderBatch $lote): bool
    {
        return $lote->paid_at !== null || $lote->status === Order::PAYMENT_STATUS_PAID;
    }

    /** O lote ja foi fechado? `cut_date` no passado e o corte da semana consumado. */
    public function loteFechado(OrderBatch $lote): bool
    {
        return $this->paraData($lote->cut_date)?->isPast() === true;
    }

    public function loteVencido(OrderBatch $lote): bool
    {
        $limite = $this->dataLimite($lote);

        return ! $this->loteQuitado($lote) && $limite !== null && $limite->isPast();
    }

    /**
     * Chip do lote na tabela "Lotes anteriores".
     *
     * @return array{rotulo: string, classe: string}
     */
    public function statusDoLote(OrderBatch $lote): array
    {
        if ($this->loteQuitado($lote)) {
            return ['rotulo' => 'Pago', 'classe' => 'chip--ok'];
        }

        if ($this->loteVencido($lote)) {
            return ['rotulo' => 'Vencido', 'classe' => 'chip--danger'];
        }

        return ['rotulo' => 'Em aberto', 'classe' => 'chip--warn'];
    }

    /**
     * Coluna "Status do pagamento" da tela 2.4. Os rotulos sao os do prototipo;
     * as chaves, as constantes de `Order` — nunca a string crua.
     *
     * @return array{rotulo: string, classe: string}
     */
    public function statusDoPagamento(?string $status): array
    {
        return match ($status) {
            Order::PAYMENT_STATUS_PAID => ['rotulo' => 'Pago', 'classe' => 'chip--ok'],
            Order::PAYMENT_STATUS_AWAITING_CLEARANCE => ['rotulo' => 'Aguardando compensação', 'classe' => 'chip--warn'],
            Order::PAYMENT_STATUS_OVERDUE => ['rotulo' => 'Vencido', 'classe' => 'chip--danger'],
            Order::PAYMENT_STATUS_REFUNDED => ['rotulo' => 'Estornado', 'classe' => 'chip--info'],
            Order::PAYMENT_STATUS_CANCELED => ['rotulo' => 'Cancelado', 'classe' => 'chip--neutral'],
            default => ['rotulo' => 'Aguardando pagamento', 'classe' => 'chip--warn'],
        };
    }

    /**
     * @return array{rotulo: string, classe: string}
     */
    public function statusDaNota(?string $status): array
    {
        return match ($status) {
            Invoice::STATUS_AUTHORIZED => ['rotulo' => 'Autorizada', 'classe' => 'chip--ok'],
            Invoice::STATUS_CANCELED => ['rotulo' => 'Cancelada', 'classe' => 'chip--danger'],
            default => ['rotulo' => 'Pendente', 'classe' => 'chip--warn'],
        };
    }

    public function rotuloDoMeio(?string $meio): string
    {
        return match ($meio) {
            Payment::METHOD_PIX => 'PIX',
            Payment::METHOD_BOLETO => 'Boleto bancário',
            Payment::METHOD_BANK_TRANSFER => 'Transferência bancária',
            default => '—',
        };
    }

    /**
     * Iniciais do avatar do cliente final — duas letras, como no prototipo.
     */
    public function iniciais(?string $nome): string
    {
        $palavras = preg_split('/\s+/u', trim((string) $nome)) ?: [];
        $palavras = array_values(array_filter($palavras, static fn (string $parte): bool => $parte !== ''));

        if ($palavras === []) {
            return '—';
        }

        $primeira = mb_substr($palavras[0], 0, 1);
        $ultima = count($palavras) > 1 ? mb_substr((string) end($palavras), 0, 1) : '';

        return mb_strtoupper($primeira.$ultima);
    }

    /**
     * "12 pedidos" / "1 pedido" — o contador aparece em cinco lugares diferentes.
     */
    public function contagemDePedidos(int $quantidade): string
    {
        return $quantidade.($quantidade === 1 ? ' pedido' : ' pedidos');
    }
}
