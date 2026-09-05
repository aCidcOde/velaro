<?php

/*
[Modulo: app/Http/Requests/Site]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Valida o reenvio de documentos do lojista: so em Aguardando informacoes, so os tres tipos, so PDF ou imagem.
*/

namespace App\Http\Requests\Site;

use App\Models\Reseller;
use App\Services\Portal\JornadaDoLojistaService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Regra 4 da tela 1.6: o reenvio existe SO em `awaiting_info`.
 *
 * O bloco some da tela fora desse estado, mas esconder o formulario nao fecha o
 * endpoint — quem ja viu o campo uma vez sabe o endereco. Por isso o estado e
 * conferido aqui, no `authorize()`: um POST para a solicitacao de um cadastro ja
 * aprovado, reprovado ou em analise responde 403, venha de onde vier.
 */
class ResellerDocumentResubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reseller = $this->route('reseller');

        return $reseller instanceof Reseller
            && $reseller->status === Reseller::STATUS_AWAITING_INFO;
    }

    /**
     * Os mesmos tres tipos e o mesmo limite do cadastro (5MB, PDF/PNG/JPG) — sao
     * os mesmos arquivos, pedidos de novo. Aqui todos sao opcionais, porque a
     * equipe costuma pedir so o que faltou; o que nao pode e o envio vazio.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $arquivo = ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:'.ResellerRegistrationRequest::MAX_DOCUMENT_KB];

        $tipos = array_keys(JornadaDoLojistaService::TIPOS_DE_DOCUMENTO);
        $regras = [];

        foreach ($tipos as $indice => $tipo) {
            // Um dos tres basta, mas pelo menos um e obrigatorio: sem isto o
            // botao "Enviar documentos" devolveria a solicitacao para a fila sem
            // anexar nada, e a equipe reabriria a analise para reler o mesmo
            // material. A exigencia mora no primeiro campo para o lojista ver uma
            // mensagem, e nao tres.
            $outros = array_values(array_diff($tipos, [$tipo]));

            $regras[$tipo] = $indice === 0
                ? array_merge(['required_without_all:'.implode(',', $outros)], $arquivo)
                : $arquivo;
        }

        return $regras;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return JornadaDoLojistaService::TIPOS_DE_DOCUMENTO;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required_without_all' => 'Anexe pelo menos um documento para reenviar.',
        ];
    }
}
