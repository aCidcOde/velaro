<?php

/*
[Modulo: app/Http/Requests/Site]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Valida o Fale Conosco do site publico, com o aceite LGPD obrigatorio para enviar.
*/

namespace App\Http\Requests\Site;

use App\Services\Site\ContactLeadService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContatoStoreRequest extends FormRequest
{
    /**
     * Formulario aberto na internet: sem gate; o throttle vem do controller,
     * porque routes/velaro.php e contrato fechado das 13 rotas do site.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Apara o que o visitante digitou antes de validar, para que a regra veja o
     * mesmo texto que vai para o banco — e-mail colado com espaco sobrando era
     * recusado como invalido.
     */
    protected function prepareForValidation(): void
    {
        $company = $this->string('company')->trim()->squish()->toString();
        $origin = $this->string('origin')->trim()->lower()->toString();

        $this->merge([
            'name' => $this->string('name')->trim()->squish()->toString(),
            'email' => $this->string('email')->trim()->lower()->toString(),
            'phone' => $this->string('phone')->trim()->squish()->toString(),
            'company' => $company === '' ? null : $company,
            'subject' => $this->string('subject')->trim()->lower()->toString(),
            'message' => $this->string('message')->trim()->toString(),
            'origin' => $origin === '' ? null : $origin,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:filter', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9()+\-.\s]{10,30}$/'],
            'company' => ['nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', Rule::in(array_keys(ContactLeadService::SUBJECTS))],
            'message' => ['required', 'string', 'max:1000'],
            // O aceite e obrigatorio para enviar: sem ele nao ha lead.
            'consent' => ['accepted'],
            'origin' => ['nullable', 'string', Rule::in(ContactLeadService::ORIGINS)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe seu nome.',
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'phone.required' => 'Informe um telefone ou WhatsApp para retorno.',
            'phone.regex' => 'Informe o telefone no formato (00) 00000-0000.',
            'subject.required' => 'Selecione o assunto.',
            'subject.in' => 'Selecione um dos assuntos da lista.',
            'message.required' => 'Escreva sua mensagem.',
            'message.max' => 'A mensagem pode ter no máximo 1.000 caracteres.',
            'consent.accepted' => 'É preciso aceitar a Política de Privacidade para enviar a mensagem.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'email' => 'e-mail',
            'phone' => 'telefone / WhatsApp',
            'company' => 'empresa',
            'subject' => 'assunto',
            'message' => 'mensagem',
            'consent' => 'aceite da Política de Privacidade',
        ];
    }

    /**
     * Payload tipado do lead. Mantem o controller fino e entrega ao service os
     * campos ja aparados — `company` vazia volta como nulo, e nao como string.
     *
     * @return array{name: string, email: string, phone: string, company: string|null, subject: string, message: string, origin: string|null}
     */
    public function leadPayload(): array
    {
        $company = $this->string('company')->toString();
        $origin = $this->string('origin')->toString();

        return [
            'name' => $this->string('name')->toString(),
            'email' => $this->string('email')->toString(),
            'phone' => $this->string('phone')->toString(),
            'company' => $company === '' ? null : $company,
            'subject' => $this->string('subject')->toString(),
            'message' => $this->string('message')->toString(),
            'origin' => $origin === '' ? null : $origin,
        ];
    }
}
