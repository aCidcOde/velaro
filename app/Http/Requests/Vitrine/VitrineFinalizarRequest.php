<?php

/*
[Modulo: app/Http/Requests/Vitrine]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Valida o fechamento do pedido no balcao da vitrine: identificacao do cliente final, gravacao e carrinho com peca.
*/

namespace App\Http\Requests\Vitrine;

use App\Models\ResellerStore;
use App\Rules\Cpf;
use App\Services\Vitrine\VitrineCarrinhoService;
use App\Services\Vitrine\VitrineCatalogoService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * O único `POST` da vitrine — e ele **não cobra nada**.
 *
 * O que este formulário fecha é o atendimento presencial: identifica o cliente
 * final na carteira do lojista, confirma a gravação e registra o pedido. O
 * pagamento acontece no caixa da loja (regra 2 da tela 2.10), então não há campo
 * de cartão, de Pix nem de link — e não haverá.
 *
 * Aqui, ao contrário das telas de leitura da vitrine, a validação **reprova**:
 * um nome errado ou um CPF inválido viram ficha errada na carteira do lojista e
 * aviso de retirada que não chega em ninguém.
 */
class VitrineFinalizarRequest extends FormRequest
{
    /** `customers.document`, com a máscara canônica de CPF. */
    private const LIMITE_DOCUMENTO = 20;

    /** `customers.phone` é `varchar(50)`. */
    private const LIMITE_TELEFONE = 50;

    /** `order_item_engravings.text` é `varchar(255)`. */
    private const LIMITE_GRAVACAO = 255;

    public function __construct(
        private readonly VitrineCarrinhoService $carrinho,
        private readonly VitrineCatalogoService $catalogo,
    ) {
        parent::__construct();
    }

    /**
     * Não há usuário para autorizar: o consumidor final não tem conta e quem
     * opera o tablet está do lado de dentro do balcão. O portão desta tela é a
     * loja estar publicada — verificado em {@see prepareForValidation()}, antes
     * de qualquer regra, para que uma loja fora do ar responda 404 e não erro de
     * formulário.
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
        $limite = $this->limiteDaGravacao();

        return [
            'name' => ['required', 'string', 'max:255'],
            'whatsapp' => ['required', 'string', 'max:'.self::LIMITE_TELEFONE],
            'document' => ['required', 'string', 'max:'.self::LIMITE_DOCUMENTO, new Cpf],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'wedding_date' => ['nullable', 'date'],
            'accept_marketing' => ['nullable', 'boolean'],
            'engraving_text' => [
                $this->gravacaoPedida() ? 'required' : 'nullable',
                'string',
                'max:'.$limite,
            ],
            'engraving_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Carrinho vazio não vira pedido.
     *
     * A checagem fica aqui, e não no service, porque a resposta certa é a mesma
     * de qualquer campo inválido: voltar para o painel com a mensagem ao lado do
     * que falta. Um `POST` de carrinho vazio é o vendedor que apertou duas vezes
     * — ou a aba que ficou aberta depois do pedido anterior.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validador): void {
                $loja = $this->loja();

                if ($loja instanceof ResellerStore && $this->carrinho->vazio($loja)) {
                    $validador->errors()->add(
                        'carrinho',
                        'O carrinho está vazio. Escolha as peças antes de registrar o pedido.',
                    );
                }
            },
        ];
    }

    /**
     * Os dados já normalizados para o service.
     *
     * @return array{nome: string, whatsapp: string, documento: string, email: string|null, dataCasamento: string|null, aceiteMarketing: bool, gravacaoTexto: string|null, gravacaoData: string|null, observacao: string|null}
     */
    public function dados(): array
    {
        $pedida = $this->gravacaoPedida();

        return [
            'nome' => (string) $this->textoOuNulo('name'),
            'whatsapp' => (string) $this->textoOuNulo('whatsapp'),
            'documento' => (string) $this->textoOuNulo('document'),
            'email' => $this->textoOuNulo('email'),
            'dataCasamento' => $this->textoOuNulo('wedding_date'),
            'aceiteMarketing' => $this->boolean('accept_marketing'),
            'gravacaoTexto' => $pedida ? $this->textoOuNulo('engraving_text') : null,
            'gravacaoData' => $pedida ? $this->textoOuNulo('engraving_date') : null,
            'observacao' => $this->textoOuNulo('notes'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome do cliente',
            'whatsapp' => 'WhatsApp',
            'document' => 'CPF',
            'email' => 'e-mail',
            'wedding_date' => 'data do casamento',
            'engraving_text' => 'texto da gravação',
            'engraving_date' => 'data da gravação',
            'notes' => 'observações',
        ];
    }

    /**
     * As mensagens vêm escritas em português aqui, e não do `lang/`.
     *
     * A aplicação roda com `APP_LOCALE=en` (a base do sistema é em inglês), então
     * as mensagens padrão do validador saem em inglês. Isso serve ao Portal e ao
     * Master, que são telas de operação — mas esta é a única tela do sistema cujo
     * leitor é o **consumidor final brasileiro**, no balcão da loja, e ele não
     * pode receber "The CPF field is required." no meio de um atendimento em
     * português. É o mesmo caminho que os formulários públicos do site já usam
     * para o texto que é de tela, e não de vocabulário.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome do cliente.',
            'name.max' => 'O nome do cliente é longo demais.',
            'whatsapp.required' => 'Informe o WhatsApp do cliente — é por ele que sai o aviso de retirada.',
            'whatsapp.max' => 'O WhatsApp informado é longo demais.',
            'document.required' => 'Informe o CPF do cliente.',
            'document.max' => 'O CPF informado é longo demais.',
            'email.email' => 'Confira o e-mail: ele não parece um endereço válido.',
            'wedding_date.date' => 'Confira a data do casamento.',
            'engraving_text.required' => 'Escreva o texto da gravação ou escolha "Não, obrigado".',
            'engraving_text.max' => 'A gravação aceita no máximo :max caracteres nas peças deste pedido.',
            'engraving_date.date' => 'Confira a data da gravação.',
            'notes.max' => 'A observação é longa demais.',
        ];
    }

    /**
     * A gravação só é exigida quando foi pedida **e** há peça no carrinho que a
     * aceita: marcar "sim" num carrinho só de acessórios não cobra nem grava
     * nada, e não faz sentido travar o pedido por causa disso.
     */
    private function gravacaoPedida(): bool
    {
        $loja = $this->loja();

        return $loja instanceof ResellerStore && $this->carrinho->gravacaoAplicavel($loja);
    }

    private function limiteDaGravacao(): int
    {
        $loja = $this->loja();
        $limite = $loja instanceof ResellerStore ? $this->carrinho->limiteDeGravacao($loja) : null;

        return $limite !== null && $limite > 0 ? min($limite, self::LIMITE_GRAVACAO) : self::LIMITE_GRAVACAO;
    }

    private function loja(): ?ResellerStore
    {
        $loja = $this->route('store');

        return $loja instanceof ResellerStore ? $loja : null;
    }

    /**
     * Onde o formulário reprovado volta a cair — o carrinho **desta loja**, e
     * nunca o padrão do Laravel.
     *
     * O padrão é `url()->previous()`, que sem `Referer` cai na raiz da
     * aplicação: o site institucional da Velaro, com logo, "Seja um revendedor"
     * e rodapé da fábrica. Um CPF digitado errado no balcão jogaria o consumidor
     * final direto na marca do fornecedor — vazamento de marca (regra 1 das
     * telas 2.9 e 2.10), e pendência de escopo pelo Anexo I §9.
     *
     * E não é hipótese remota: o tablet da loja abre a vitrine em domínio
     * próprio, e um `POST` cross-origin ou uma política de `Referrer-Policy`
     * mais fechada deixam o cabeçalho de fora. O destino do erro é o mesmo em
     * que o formulário mora — sempre dentro da loja.
     */
    protected function getRedirectUrl(): string
    {
        $loja = $this->loja();

        if ($loja instanceof ResellerStore) {
            return $this->redirector->getUrlGenerator()->route('vitrine.carrinho', $loja);
        }

        return parent::getRedirectUrl();
    }

    private function textoOuNulo(string $campo): ?string
    {
        $valor = $this->input($campo);

        if (! is_string($valor)) {
            return null;
        }

        $valor = trim($valor);

        return $valor === '' ? null : $valor;
    }

    /**
     * Loja fora do ar responde 404 antes de qualquer regra rodar — se a
     * verificação ficasse só no controller, um `POST` inválido para uma loja
     * despublicada voltaria com erro de formulário, e essa diferença de resposta
     * já contaria que o slug existe.
     *
     * Depois disso, normaliza: o CPF é gravado com a máscara canônica (é assim
     * que a carteira do lojista o guarda, e é o que faz o reaproveitamento da
     * ficha funcionar) e o e-mail em minúsculas.
     */
    protected function prepareForValidation(): void
    {
        $loja = $this->loja();

        if ($loja instanceof ResellerStore) {
            $this->catalogo->assertPublicada($loja);
        }

        $documento = $this->input('document');
        $email = $this->input('email');

        $this->merge([
            'document' => is_scalar($documento) ? self::mascararCpf((string) $documento) : null,
            'email' => is_string($email) && trim($email) !== '' ? mb_strtolower(trim($email)) : null,
        ]);
    }

    /**
     * Aplica `###.###.###-##` quando há onze dígitos; fora disso devolve o valor
     * como veio, para a regra do dígito verificador acusar o erro.
     */
    private static function mascararCpf(string $valor): string
    {
        $digitos = (string) preg_replace('/\D+/', '', $valor);

        if (mb_strlen($digitos) !== 11) {
            return trim($valor);
        }

        return mb_substr($digitos, 0, 3).'.'.mb_substr($digitos, 3, 3).'.'
            .mb_substr($digitos, 6, 3).'-'.mb_substr($digitos, 9, 2);
    }
}
