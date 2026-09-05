<?php

/*
[Modulo: app/Services/Portal/Concerns]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Formata dinheiro, data, hora e iniciais no padrao pt-BR usado pelas telas do Portal do Lojista.
*/

namespace App\Services\Portal\Concerns;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * As telas do portal recebem do service texto pronto, nunca model cru: quem
 * decide como um valor aparece é o service, e a view só imprime. Isso mantém a
 * mesma moeda, a mesma data e a mesma sigla de avatar em Clientes e em Pedidos —
 * as duas telas leem daqui.
 */
trait FormataDados
{
    /** O travessão que o protótipo usa no lugar de campo vazio. */
    protected const VAZIO = '—';

    /**
     * O idioma em que o Portal do Lojista fala.
     *
     * `APP_LOCALE` é `en` no scaffold, e a interface do portal é escrita em
     * pt-BR — título, rótulo de campo, aviso, tudo. Sem fixar o idioma aqui,
     * metade da tela sairia em português (o que está no Blade) e a outra metade
     * em inglês (o que vem de `lang/`), com "Em produção" ao lado de "Pending".
     * Fixar a consulta em `pt_BR` mantém o vocabulário do domínio num arquivo só,
     * em vez de espalhar a mesma lista de rótulos por dois lugares — e continua
     * valendo se o padrão da aplicação virar pt-BR um dia.
     */
    protected const IDIOMA = 'pt_BR';

    /**
     * `R$ 1.234,56` — o formato de toda coluna de valor do portal.
     *
     * Aceita string porque as colunas de dinheiro têm cast `decimal:2` e chegam
     * do banco como string; o cast é o que preserva a precisão, e converter para
     * float só aqui, na borda de apresentação, é o ponto seguro para fazê-lo.
     */
    protected function dinheiro(float|int|string|null $valor): string
    {
        return 'R$ '.number_format((float) $valor, 2, ',', '.');
    }

    /**
     * Normaliza em Carbon o que chega da camada de dados.
     *
     * O tipo é `mixed` de propósito, e não `?DateTimeInterface`: um atributo de
     * model chega aqui de três formas diferentes e todas são legítimas — Carbon
     * quando o cast do model se aplica, **string crua** quando o valor veio de
     * uma subconsulta `addSelect` (o driver devolve o texto do banco, sem passar
     * por cast) e nulo quando a coluna está vazia. Converter uma vez, aqui, é o
     * que impede a mesma checagem de se repetir em cada campo de data das telas.
     */
    protected function momento(mixed $valor): ?Carbon
    {
        if ($valor instanceof DateTimeInterface) {
            return Carbon::instance($valor);
        }

        if (! is_string($valor) || trim($valor) === '') {
            return null;
        }

        try {
            return Carbon::parse($valor);
        } catch (Throwable) {
            // Texto que não é data não vira exceção na tela: vira campo vazio.
            return null;
        }
    }

    protected function data(mixed $valor): ?string
    {
        return $this->momento($valor)?->format('d/m/Y');
    }

    protected function dataHora(mixed $valor): ?string
    {
        return $this->momento($valor)?->format('d/m/Y H:i');
    }

    protected function hora(mixed $valor): ?string
    {
        return $this->momento($valor)?->format('H:i');
    }

    /**
     * `16/05 10:32` — a forma curta do histórico de atualizações da tela 2.5.
     */
    protected function diaHora(mixed $valor): ?string
    {
        return $this->momento($valor)?->format('d/m H:i');
    }

    /**
     * `Maio/2026` — a competência da nota fiscal.
     */
    protected function competencia(mixed $valor): ?string
    {
        $momento = $this->momento($valor);

        return $momento === null ? null : ucfirst($momento->locale('pt_BR')->isoFormat('MMMM/YYYY'));
    }

    /**
     * `Hoje, 10:32` no KPI "Último cadastro" da tela 2.3, como o protótipo escreve.
     * Fora de ontem e hoje a data completa diz mais do que "há 8 dias".
     */
    protected function dataRelativa(mixed $valor): string
    {
        $momento = $this->momento($valor);

        if ($momento === null) {
            return self::VAZIO;
        }

        if ($momento->isToday()) {
            return 'Hoje, '.$momento->format('H:i');
        }

        if ($momento->isYesterday()) {
            return 'Ontem, '.$momento->format('H:i');
        }

        return $momento->format('d/m/Y');
    }

    /**
     * As duas letras do avatar: iniciais do primeiro e do último nome
     * ("Maria Silva" → MS). Nome de uma palavra só usa as duas primeiras letras.
     */
    protected function iniciais(?string $nome): string
    {
        $partes = preg_split('/\s+/', trim((string) $nome)) ?: [];
        $partes = array_values(array_filter($partes, static fn (string $parte): bool => $parte !== ''));

        if ($partes === []) {
            return '??';
        }

        if (count($partes) === 1) {
            return mb_strtoupper(mb_substr($partes[0], 0, 2));
        }

        return mb_strtoupper(mb_substr($partes[0], 0, 1).mb_substr((string) end($partes), 0, 1));
    }

    /**
     * `São Paulo / SP` — a segunda linha da célula CLIENTE. Some inteira quando
     * o cadastro não tem cidade, em vez de imprimir uma barra solta.
     */
    protected function cidadeUf(?string $cidade, ?string $uf): ?string
    {
        $partes = array_values(array_filter(
            [trim((string) $cidade), mb_strtoupper(trim((string) $uf))],
            static fn (string $parte): bool => $parte !== '',
        ));

        return $partes === [] ? null : implode(' / ', $partes);
    }

    /**
     * Normaliza atributo de model — que chega como `mixed` — em texto.
     */
    /**
     * Rótulo do vocabulário do domínio (`lang/pt_BR/order.php` e vizinhos).
     *
     * Chave sem tradução devolve nulo, e não o slug em inglês: quem chama decide
     * o que mostrar no lugar, em vez de a tela imprimir `awaiting_clearance` na
     * cara do lojista.
     */
    protected function rotulo(string $chave): ?string
    {
        $traducao = trans($chave, [], self::IDIOMA);

        return is_string($traducao) && $traducao !== $chave ? $traducao : null;
    }

    protected function texto(mixed $valor): string
    {
        return is_scalar($valor) ? trim((string) $valor) : '';
    }

    protected function textoOuNulo(mixed $valor): ?string
    {
        $texto = $this->texto($valor);

        return $texto === '' ? null : $texto;
    }
}
