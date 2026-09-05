<?php

/*
[Modulo: app/Http/Requests/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Valida a identidade da vitrine da tela 2.6, com slug e dominio unicos ignorando a propria loja.
*/

namespace App\Http\Requests\Portal;

use App\Models\ResellerPriceSetting;
use App\Models\ResellerStore;
use App\Services\Portal\StoreProfileService;
use App\Support\ResellerScope;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LojaUpdateRequest extends FormRequest
{
    /** Intenções do rodapé da tela: salvar sem publicar, ou salvar publicando. */
    public const ACTION_SAVE = 'salvar';

    public const ACTION_PUBLISH = 'publicar';

    /** Hex de 6 dígitos com `#`, que é o formato que a coluna de 9 caracteres guarda. */
    private const HEX = '/^#[0-9A-Fa-f]{6}$/';

    /** Teto do upload da logo — "PNG ou JPG · Máx. 2MB" na seção 5. */
    private const LOGO_MAX_KB = 2048;

    /** O banner é 1920×600px; 5 MB cobrem um JPG dessa proporção com folga. */
    private const BANNER_MAX_KB = 5120;

    /**
     * O middleware `reseller` já barrou quem não é lojista aprovado, e o escopo
     * garante que a loja alterada é a do próprio. Não há segunda autorização.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza antes de validar.
     *
     * O domínio chega do campo com prefixo `https://` fixo ao lado, então o que o
     * lojista digita pode vir com esquema, com `www.` e com barra no fim — três
     * formas do mesmo domínio que a coluna UNIQUE trataria como distintas.
     */
    protected function prepareForValidation(): void
    {
        $dominio = $this->string('domain')->trim()->lower()->toString();
        $dominio = (string) preg_replace('#^https?://#', '', $dominio);
        $dominio = rtrim($dominio, '/');

        $slug = $this->string('slug')->trim()->lower()->toString();

        $this->merge([
            'name' => $this->string('name')->trim()->squish()->toString(),
            'slogan' => $this->textoOuNulo('slogan'),
            'slug' => $slug,
            'domain' => $dominio === '' ? null : $dominio,
            'phone' => $this->textoOuNulo('phone'),
            'whatsapp' => $this->textoOuNulo('whatsapp'),
            'email' => $this->string('email')->trim()->lower()->toString() ?: null,
            'address' => $this->textoOuNulo('address'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $loja = $this->lojaAtual();

        return [
            'name' => ['required', 'string', 'max:255'],
            'slogan' => ['nullable', 'string', 'max:255'],

            // `slug` e `domain` são UNIQUE no banco e são as duas portas de
            // entrada da vitrine. Sem o `ignore` o lojista não conseguiria salvar
            // a própria loja: o valor que ele já tem colidiria com ele mesmo.
            'slug' => [
                'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('reseller_stores', 'slug')->ignore($loja),
            ],
            'domain' => [
                'nullable', 'string', 'max:255',
                'regex:/^[a-z0-9.-]+\.[a-z]{2,}$/',
                Rule::unique('reseller_stores', 'domain')->ignore($loja),
            ],

            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'string', 'email:filter', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],

            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:'.self::LOGO_MAX_KB],
            'banner' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:'.self::BANNER_MAX_KB],

            'color_primary' => ['required', 'string', 'regex:'.self::HEX],
            'color_secondary' => ['required', 'string', 'regex:'.self::HEX],
            'color_background' => ['required', 'string', 'regex:'.self::HEX],
            'color_text' => ['required', 'string', 'regex:'.self::HEX],

            'own_brand_only' => ['nullable', 'boolean'],
            'hide_supplier_brand' => ['nullable', 'boolean'],
            'show_prices' => ['nullable', 'boolean'],
            'pickup_only' => ['nullable', 'boolean'],
            'payment_in_store' => ['nullable', 'boolean'],

            // Bloco ② da tela: a regra de preço mora em `reseller_price_settings`
            // e é a mesma linha que a tela 2.7 edita. Os dois formulários gravam
            // pelo mesmo service, para não existirem duas verdades sobre a margem.
            'pricing_model' => ['required', Rule::in(ResellerPriceSetting::PRICING_MODELS)],
            'multiplier' => ['required', 'numeric', 'min:1', 'max:99.99'],
            'apply_to_all' => ['nullable', 'boolean'],
            'allow_manual_override' => ['nullable', 'boolean'],
            'allow_promotional_prices' => ['nullable', 'boolean'],

            'action' => ['nullable', Rule::in([self::ACTION_SAVE, self::ACTION_PUBLISH])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome da loja.',
            'slug.required' => 'Informe o endereço da loja na Velaro.',
            'slug.alpha_dash' => 'O endereço da loja aceita apenas letras, números e hífen.',
            'slug.unique' => 'Este endereço já está em uso por outra loja. Escolha outro.',
            'domain.regex' => 'Informe um domínio válido, como minhaloja.com.br.',
            'domain.unique' => 'Este domínio já está em uso por outra loja.',
            'email.email' => 'Informe um e-mail válido.',
            'logo.max' => 'A logo pode ter no máximo 2 MB.',
            'logo.mimes' => 'A logo precisa ser PNG ou JPG.',
            'banner.mimes' => 'O banner precisa ser PNG ou JPG.',
            'color_primary.regex' => 'Use uma cor no formato #RRGGBB.',
            'color_secondary.regex' => 'Use uma cor no formato #RRGGBB.',
            'color_background.regex' => 'Use uma cor no formato #RRGGBB.',
            'color_text.regex' => 'Use uma cor no formato #RRGGBB.',
            'multiplier.min' => 'O fator de multiplicação não pode ser menor que 1.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome da loja',
            'slogan' => 'slogan',
            'slug' => 'endereço da loja',
            'domain' => 'domínio',
            'phone' => 'telefone',
            'whatsapp' => 'WhatsApp',
            'email' => 'e-mail',
            'address' => 'endereço',
            'color_primary' => 'cor primária',
            'color_secondary' => 'cor secundária',
            'color_background' => 'cor de fundo',
            'color_text' => 'cor do texto',
            'multiplier' => 'fator de multiplicação',
        ];
    }

    /**
     * Campos de `reseller_stores`, prontos para o service.
     *
     * Os toggles saem de um `checkbox`: ausente significa desligado, e não
     * "não mexeu". Por isso todos são resolvidos aqui, e nenhum fica de fora do
     * payload — um `fill()` parcial deixaria o toggle desligado com o valor
     * antigo.
     *
     * @return array<string, mixed>
     */
    public function dadosDaLoja(): array
    {
        $dados = [
            'name' => $this->string('name')->toString(),
            'slogan' => $this->input('slogan'),
            'slug' => $this->string('slug')->toString(),
            'domain' => $this->input('domain'),
            'phone' => $this->input('phone'),
            'whatsapp' => $this->input('whatsapp'),
            'email' => $this->input('email'),
            'address' => $this->input('address'),
            'color_primary' => mb_strtoupper($this->string('color_primary')->toString()),
            'color_secondary' => mb_strtoupper($this->string('color_secondary')->toString()),
            'color_background' => mb_strtoupper($this->string('color_background')->toString()),
            'color_text' => mb_strtoupper($this->string('color_text')->toString()),
        ];

        foreach (array_keys(StoreProfileService::TOGGLES) as $toggle) {
            $dados[$toggle] = $this->boolean($toggle);
        }

        return $dados;
    }

    /**
     * O recorte de precificação do bloco ②, na forma que
     * `ResellerPriceSetting` espera.
     *
     * @return array<string, mixed>
     */
    public function dadosDePreco(): array
    {
        return [
            'pricing_model' => $this->string('pricing_model')->toString(),
            'multiplier' => (float) $this->input('multiplier'),
            'apply_to_all' => $this->boolean('apply_to_all'),
            'allow_manual_override' => $this->boolean('allow_manual_override'),
            'allow_promotional_prices' => $this->boolean('allow_promotional_prices'),
        ];
    }

    public function querPublicar(): bool
    {
        return $this->string('action')->toString() === self::ACTION_PUBLISH;
    }

    /**
     * A loja do próprio lojista, ou nulo quando ela ainda não existe — é o que a
     * regra `unique` precisa ignorar.
     */
    private function lojaAtual(): ?ResellerStore
    {
        return ResellerScope::current()->store();
    }

    private function textoOuNulo(string $campo): ?string
    {
        $valor = $this->string($campo)->trim()->squish()->toString();

        return $valor === '' ? null : $valor;
    }
}
