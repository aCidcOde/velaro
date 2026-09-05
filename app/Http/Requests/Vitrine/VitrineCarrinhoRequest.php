<?php

/*
[Modulo: app/Http/Requests/Vitrine]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Normaliza a acao que a URL do carrinho da vitrine carrega: somar, tirar, mexer no stepper ou escolher a gravacao.
*/

namespace App\Http\Requests\Vitrine;

use App\Services\Vitrine\VitrineCarrinhoService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * O carrinho é uma tela `GET`, e as ações do painel chegam nela como parâmetro
 * de URL — o grupo `vitrine.` tem uma rota `POST` só, a de registrar o pedido.
 *
 * Como no filtro da vitrine, aqui **nada reprova**: a loja é pública, o link
 * pode ter envelhecido no histórico do tablet, e uma página de erro no meio do
 * atendimento é pior do que uma ação que simplesmente não acontece. Tudo é
 * podado em {@see prepareForValidation()} e as regras abaixo são rede de
 * segurança; o service é quem decide se a ação faz sentido e devolve o aviso.
 */
class VitrineCarrinhoRequest extends FormRequest
{
    /** Teto do slug da peça — `products.slug` é `varchar(255)`. */
    private const LIMITE_PECA = 255;

    /** `product_variants.ring_size` é `varchar(20)`. */
    private const LIMITE_ARO = 20;

    /**
     * A vitrine é o único ambiente público e sem login do sistema. Não há
     * permissão a checar: o que decide se a loja abre é ela estar publicada, e
     * isso é regra de negócio, verificada no controller.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'acao' => ['nullable', 'string', 'max:20'],
            'peca' => ['nullable', 'string', 'max:'.self::LIMITE_PECA],
            'aro' => ['nullable', 'string', 'max:'.self::LIMITE_ARO],
            'quantidade' => ['nullable', 'integer', 'min:0', 'max:'.VitrineCarrinhoService::QUANTIDADE_MAXIMA],
        ];
    }

    /**
     * A ação já resolvida para o service.
     *
     * @return array{acao: string|null, peca: string|null, aro: string|null, quantidade: int|null, gravacao: bool|null}
     */
    public function acao(): array
    {
        $acao = $this->input('acao');
        $acao = is_string($acao) && in_array($acao, VitrineCarrinhoService::ACOES, true) ? $acao : null;
        $quantidade = $this->input('quantidade');
        $gravacao = $this->input('gravacao');

        return [
            'acao' => $acao,
            'peca' => $this->texto('peca', self::LIMITE_PECA),
            'aro' => $this->texto('aro', self::LIMITE_ARO),
            'quantidade' => is_numeric($quantidade)
                ? max(0, min(VitrineCarrinhoService::QUANTIDADE_MAXIMA, (int) $quantidade))
                : null,
            // `?gravacao=1` liga, `?gravacao=0` desliga. Ausente é nulo, e a ação
            // de gravação nem chega a ser pedida.
            'gravacao' => $gravacao === null ? null : filter_var($gravacao, FILTER_VALIDATE_BOOLEAN),
        ];
    }

    /**
     * Há ação a executar nesta requisição? É o que faz o controller responder
     * com redirect (padrão PRG) em vez de desenhar a tela direto.
     */
    public function temAcao(): bool
    {
        return $this->acao()['acao'] !== null;
    }

    private function texto(string $campo, int $limite): ?string
    {
        $valor = $this->input($campo);

        if (! is_string($valor)) {
            return null;
        }

        $valor = mb_substr(trim($valor), 0, $limite);

        return $valor === '' ? null : $valor;
    }

    /**
     * Espaço em branco vira ausência, e a quantidade é presa na faixa útil antes
     * de qualquer regra rodar.
     */
    protected function prepareForValidation(): void
    {
        $query = $this->query->all();
        $limpo = [];

        foreach (['acao', 'peca', 'aro'] as $campo) {
            if (array_key_exists($campo, $query)) {
                $valor = $query[$campo];
                $limpo[$campo] = is_string($valor) && trim($valor) !== '' ? trim($valor) : null;
            }
        }

        if (array_key_exists('quantidade', $query)) {
            $quantidade = $query['quantidade'];
            $limpo['quantidade'] = is_numeric($quantidade)
                ? (string) max(0, min(VitrineCarrinhoService::QUANTIDADE_MAXIMA, (int) $quantidade))
                : null;
        }

        if ($limpo !== []) {
            $this->merge($limpo);
        }
    }
}
