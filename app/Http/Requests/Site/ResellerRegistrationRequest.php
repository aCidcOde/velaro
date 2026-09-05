<?php

/*
[Modulo: app/Http/Requests/Site]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Valida o formulario publico da tela 1.4: empresa, endereco, acesso, tres uploads e tres aceites.
*/

namespace App\Http\Requests\Site;

use App\Rules\Cnpj;
use App\Rules\Cpf;
use App\Support\BrazilianStates;
use App\Support\ResellerContactSources;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ResellerRegistrationRequest extends FormRequest
{
    /**
     * Tamanho maximo de cada documento, em kilobytes (5MB, como diz a tela).
     */
    public const MAX_DOCUMENT_KB = 5120;

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
            'legal_name' => ['required', 'string', 'max:255'],
            'trade_name' => ['required', 'string', 'max:255'],
            // Sem `withoutTrashed()`: o indice unico de `resellers.cnpj` e simples e
            // continua valendo para o revendedor com soft delete. Ignorar o excluido
            // aqui passaria a validacao e estouraria uma violacao de chave no INSERT.
            'cnpj' => ['required', 'string', 'max:20', new Cnpj, Rule::unique('resellers', 'cnpj')],
            'state_registration' => ['nullable', 'string', 'max:30'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_cpf' => ['required', 'string', 'max:20', new Cpf],

            'postal_code' => ['required', 'string', 'max:12'],
            'street' => ['required', 'string', 'max:255'],
            'street_number' => ['required', 'string', 'max:30'],
            'address_complement' => ['nullable', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', Rule::in(BrazilianStates::codes())],

            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'whatsapp' => ['required', 'string', 'max:30'],
            'contact_source' => ['required', 'string', Rule::in(ResellerContactSources::keys())],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
            'notes' => ['nullable', 'string', 'max:300'],

            'articles_of_incorporation' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:'.self::MAX_DOCUMENT_KB],
            'partner_id_document' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:'.self::MAX_DOCUMENT_KB],
            'cnpj_card' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:'.self::MAX_DOCUMENT_KB],

            'accept_business' => ['accepted'],
            'accept_verification' => ['accepted'],
            'accept_terms' => ['accepted'],

            // O prototipo nao coleta CNAE — quem preenche e a verificacao automatica.
            // O campo fica aceito para quando o cadastro chegar com os CNAEs ja informados.
            'cnaes' => ['nullable', 'array', 'max:10'],
            'cnaes.*.code' => ['required', 'string', 'max:20'],
            'cnaes.*.description' => ['nullable', 'string', 'max:255'],
            'cnaes.*.is_primary' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'legal_name' => 'razão social',
            'trade_name' => 'nome fantasia',
            'cnpj' => 'CNPJ',
            'state_registration' => 'inscrição estadual',
            'contact_name' => 'nome do responsável',
            'contact_cpf' => 'CPF do responsável',
            'postal_code' => 'CEP',
            'street' => 'endereço',
            'street_number' => 'número',
            'address_complement' => 'complemento',
            'district' => 'bairro',
            'city' => 'cidade',
            'state' => 'UF',
            'email' => 'e-mail',
            'whatsapp' => 'WhatsApp',
            'contact_source' => 'origem do contato',
            'password' => 'senha',
            'notes' => 'observações',
            'articles_of_incorporation' => 'contrato social',
            'partner_id_document' => 'documento do sócio',
            'cnpj_card' => 'cartão do CNPJ',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cnpj.unique' => 'Já existe um cadastro com este CNPJ. Fale com nossa equipe.',
            'email.unique' => 'Já existe uma conta com este e-mail. Use outro ou faça login.',
            'password.confirmed' => 'A confirmação de senha não confere.',
            'accept_business.accepted' => 'É preciso declarar que você é lojista ou empresa formalizada.',
            'accept_verification.accepted' => 'É preciso autorizar a validação automática do CNPJ e do CNAE.',
            'accept_terms.accepted' => 'É preciso aceitar a Política de Privacidade e os Termos de Uso.',
        ];
    }

    /**
     * Normaliza documento, CEP, UF e e-mail antes da validacao. O banco guarda o
     * numero mascarado (como o prototipo mostra), entao a mascara precisa ser
     * canonica para a checagem de unicidade valer alguma coisa.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(array_filter([
            'cnpj' => self::mask($this->input('cnpj'), '##.###.###/####-##', 14),
            'contact_cpf' => self::mask($this->input('contact_cpf'), '###.###.###-##', 11),
            'postal_code' => self::mask($this->input('postal_code'), '#####-###', 8),
            'state' => is_string($this->input('state')) ? strtoupper(trim($this->input('state'))) : null,
            'email' => is_string($this->input('email')) ? strtolower(trim($this->input('email'))) : null,
        ], static fn (?string $value): bool => $value !== null));
    }

    /**
     * Aplica a mascara quando a quantidade de digitos bate; caso contrario devolve
     * o valor como veio, para a regra de digito verificador acusar o erro.
     */
    private static function mask(mixed $value, string $pattern, int $length): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $digits = (string) preg_replace('/\D/', '', (string) $value);

        if (strlen($digits) !== $length) {
            return (string) $value;
        }

        $masked = '';
        $index = 0;

        foreach (str_split($pattern) as $character) {
            $masked .= $character === '#' ? $digits[$index++] : $character;
        }

        return $masked;
    }
}
