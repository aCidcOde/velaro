<?php

/*
[Modulo: app/Support]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Traduz o status do revendedor no estagio da jornada que o painel e o menu lateral precisam mostrar.
*/

namespace App\Support;

use App\Models\Reseller;
use App\Services\Portal\JornadaDoLojistaService;
use Illuminate\Support\Facades\Route;

/**
 * Um lojista, um login, um painel — e o painel muda conforme o estagio.
 *
 * Esta classe e a resposta unica para "em que ponto da jornada este lojista
 * esta?", consultada tanto pelo painel ({@see JornadaDoLojistaService})
 * quanto pelo layout do portal, que precisa saber o que o menu abre e o que o
 * menu apenas anuncia.
 */
final class EstagioDoLojista
{
    /** Pre-cadastro em curso: `pending` e `awaiting_info`. */
    public const ANALISE = 'analise';

    /** Parceiro Premium habilitado: o portal inteiro. */
    public const APROVADO = 'aprovado';

    /** Fim de linha por ora: `rejected` e `inactive`. */
    public const ENCERRADO = 'encerrado';

    /** Usuario sem vinculo — nao chega ao portal, mas o layout nao pode explodir. */
    public const SEM_VINCULO = 'sem_vinculo';

    private function __construct(
        public readonly ?Reseller $reseller,
        public readonly string $chave,
    ) {}

    public static function de(?Reseller $reseller): self
    {
        if ($reseller === null) {
            return new self(null, self::SEM_VINCULO);
        }

        return new self($reseller, match ($reseller->status) {
            Reseller::STATUS_APPROVED => self::APROVADO,
            Reseller::STATUS_REJECTED, Reseller::STATUS_INACTIVE => self::ENCERRADO,
            default => self::ANALISE,
        });
    }

    public function aprovado(): bool
    {
        return $this->chave === self::APROVADO;
    }

    /**
     * Reprovado ou inativo: fim de linha por ora, e o painel mostra o motivo e o
     * caminho de volta em vez do acompanhamento.
     */
    public function encerrado(): bool
    {
        return $this->chave === self::ENCERRADO;
    }

    public function aguardaDocumentos(): bool
    {
        return $this->reseller?->status === Reseller::STATUS_AWAITING_INFO;
    }

    /**
     * O que aparece no lugar de "Parceiro Premium" enquanto o titulo nao existe.
     */
    public function rotuloDoPlano(): string
    {
        return match ($this->reseller?->status) {
            Reseller::STATUS_APPROVED => 'Parceiro Premium ◆',
            Reseller::STATUS_AWAITING_INFO => 'Aguardando seus documentos',
            Reseller::STATUS_REJECTED => 'Cadastro reprovado',
            Reseller::STATUS_INACTIVE => 'Cadastro inativo',
            Reseller::STATUS_PENDING => 'Pré-cadastro em análise',
            default => 'Sem cadastro vinculado',
        };
    }

    /**
     * Os itens do menu que este estagio ainda nao abre.
     *
     * A lista NAO e escrita a mao: ela pergunta ao roteador quais das rotas do
     * menu carregam o middleware `reseller`, o mesmo que responde 403 a quem nao
     * foi aprovado. Assim o cadeado do menu e a porta trancada sao a mesma
     * decisao — abrir uma rota no `routes/velaro.php` destranca o item sozinho, e
     * nao existe a versao em que o menu convida para uma tela que devolve 403.
     *
     * @return list<string>
     */
    public function rotasBloqueadas(): array
    {
        if ($this->aprovado()) {
            return [];
        }

        $bloqueadas = [];

        /** @var array<int, array{0: string, 1: string, 2: string}> $itens */
        $itens = config('velaro-nav.portal', []);

        foreach ($itens as [, , $nome]) {
            $rota = Route::getRoutes()->getByName($nome);

            if ($rota === null) {
                continue;
            }

            // `gatherMiddleware()` lista o que a rota declara; a exclusao por
            // `withoutMiddleware()` mora em outra lista, e sem descontar uma da
            // outra o menu trancaria um item que a rota abriu.
            $middleware = array_diff($rota->gatherMiddleware(), $rota->excludedMiddleware());

            if (in_array('reseller', $middleware, true)) {
                $bloqueadas[] = $nome;
            }
        }

        return $bloqueadas;
    }

    /**
     * Por que o item esta trancado — sai no `title` de cada item do menu e na
     * nota abaixo dele. O item continua visivel de proposito: as telas em ordem
     * contam a historia da jornada, e o lojista precisa ver o que o espera.
     */
    public function motivoDoBloqueio(): string
    {
        return match ($this->chave) {
            self::ANALISE => 'Disponível quando seu cadastro for aprovado.',
            self::ENCERRADO => 'Indisponível enquanto o cadastro não for regularizado.',
            default => 'Disponível para lojistas aprovados.',
        };
    }
}
