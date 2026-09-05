<?php

/*
[Modulo: app/Http/Requests/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Valida a abertura de chamado da tela 2.8 e prende pedido e cliente vinculados ao proprio lojista.
*/

namespace App\Http\Requests\Portal;

use App\Models\SupportTicket;
use App\Models\User;
use App\Support\ResellerScope;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use RuntimeException;

class SuporteStoreRequest extends FormRequest
{
    /** Quantos arquivos podem subir de uma vez na abertura. */
    private const MAX_ANEXOS = 5;

    /** Teto por arquivo, em KB — os 5 MB que o protótipo anuncia. */
    private const ANEXO_MAX_KB = 5120;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $pedido = $this->input('order_id');
        $cliente = $this->input('customer_id');

        $this->merge([
            'subject' => $this->string('subject')->trim()->squish()->toString(),
            'body' => $this->string('body')->trim()->toString(),
            'order_id' => is_numeric($pedido) ? (int) $pedido : null,
            'customer_id' => is_numeric($cliente) ? (int) $cliente : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $revendedor = ResellerScope::current()->reseller->getKey();

        return [
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(SupportTicket::CATEGORIES)],
            'priority' => ['required', Rule::in(SupportTicket::PRIORITIES)],

            // O `exists` carrega o escopo: um `order_id` de outro lojista é
            // recusado com a mesma mensagem de um id que não existe. Confirmar a
            // existência pela mensagem de erro seria o mesmo vazamento que o 403
            // no route model binding.
            'order_id' => [
                'nullable', 'integer',
                Rule::exists('orders', 'id')->where(
                    static fn (Builder $consulta): Builder => $consulta->where('reseller_id', $revendedor)
                ),
            ],
            'customer_id' => [
                'nullable', 'integer',
                Rule::exists('customers', 'id')->where(
                    static fn (Builder $consulta): Builder => $consulta->where('reseller_id', $revendedor)
                ),
            ],

            'body' => ['required', 'string', 'min:10', 'max:5000'],

            // "Anexar fotos ou documentos · PNG, JPG ou PDF · até 5 MB por
            // arquivo" (mockup 42). O anexo é a prova que o lojista manda para a
            // Velaro — foto da peça errada, espelho do pedido.
            'anexos' => ['nullable', 'array', 'max:'.self::MAX_ANEXOS],
            'anexos.*' => ['file', 'mimes:png,jpg,jpeg,pdf', 'max:'.self::ANEXO_MAX_KB],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'subject.required' => 'Informe o assunto do chamado.',
            'category.required' => 'Selecione a categoria do chamado.',
            'category.in' => 'Selecione uma das categorias da lista.',
            'priority.in' => 'Selecione uma das prioridades da lista.',
            'order_id.exists' => 'Selecione um pedido da sua loja.',
            'customer_id.exists' => 'Selecione um cliente da sua loja.',
            'body.required' => 'Descreva o que aconteceu.',
            'body.min' => 'Descreva o que aconteceu com pelo menos 10 caracteres.',
            'body.max' => 'A descrição pode ter no máximo 5.000 caracteres.',
            'anexos.max' => 'Envie no máximo 5 arquivos por chamado.',
            'anexos.*.mimes' => 'Os anexos precisam ser PNG, JPG ou PDF.',
            'anexos.*.max' => 'Cada anexo pode ter no máximo 5 MB.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'subject' => 'assunto',
            'category' => 'categoria',
            'priority' => 'prioridade',
            'order_id' => 'pedido relacionado',
            'customer_id' => 'cliente final vinculado',
            'body' => 'descrição',
        ];
    }

    /**
     * Arquivos enviados junto da abertura, já filtrados para o que de fato veio.
     *
     * @return list<UploadedFile>
     */
    public function anexos(): array
    {
        $arquivos = $this->file('anexos');

        if (! is_array($arquivos)) {
            return [];
        }

        return array_values(array_filter(
            $arquivos,
            static fn (mixed $arquivo): bool => $arquivo instanceof UploadedFile,
        ));
    }

    /**
     * Quem abriu o chamado. O middleware `auth` do grupo `portal` garante que
     * existe usuário; chegar aqui sem ele é erro de rota, e explodir é melhor do
     * que gravar uma mensagem sem autor na thread.
     */
    public function autor(): User
    {
        $usuario = $this->user();

        if (! $usuario instanceof User) {
            throw new RuntimeException('Abertura de chamado sem usuário autenticado.');
        }

        return $usuario;
    }

    /**
     * @return array{subject: string, category: string, priority: string, order_id: int|null, customer_id: int|null, body: string}
     */
    public function dados(): array
    {
        $pedido = $this->input('order_id');
        $cliente = $this->input('customer_id');

        return [
            'subject' => $this->string('subject')->toString(),
            'category' => $this->string('category')->toString(),
            'priority' => $this->string('priority')->toString(),
            'order_id' => is_int($pedido) ? $pedido : null,
            'customer_id' => is_int($cliente) ? $cliente : null,
            'body' => $this->string('body')->toString(),
        ];
    }
}
