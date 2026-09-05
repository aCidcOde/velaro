{{--
[Modulo: resources/views/components/velaro/solicitacao]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Bloco de reenvio de documentos: aparece so em Aguardando informacoes e posta para a mesma rota nas duas telas.
--}}
@props(['reseller', 'documentos'])
{{-- Regra 4 da tela 1.6: fora de `awaiting_info` o bloco nao existe — o lojista
     nao reenvia documento por conta propria. O `authorize()` do Form Request
     repete a condicao do lado do servidor, porque esconder o formulario nao
     fecha o endereco. --}}
@if ($documentos !== null)
    <div class="card">
        <div class="card__head">
            <h2 class="title">Reenvio de documentos</h2>
        </div>

        <p class="notice notice--gold">
            <x-velaro.icon name="info" />
            <span>
                <strong>Nossa equipe pediu mais informações.</strong>
                {{ $documentos['pedido'] ?? 'Anexe o documento solicitado para que a análise continue.' }}
            </span>
        </p>

        <form method="POST"
              action="{{ route('site.solicitacao.documentos', ['reseller' => $reseller->protocol]) }}"
              enctype="multipart/form-data">
            @csrf

            {{-- O input de arquivo fica visivel, ao contrario do cartao da tela
                 1.4: la um script escreve o nome do arquivo escolhido, e ele vive
                 dentro daquela pagina. Aqui o proprio controle nativo faz esse
                 trabalho, e o bloco funciona igual nas duas telas que o incluem,
                 sem depender de JavaScript nenhum. --}}
            <div class="fgrid fgrid--3" style="margin-top:var(--space-4)">
                @foreach ($documentos['tipos'] as $campo => $rotulo)
                    <label class="upload" @error($campo) style="border-color:var(--color-error-500)" @enderror>
                        <span class="upload__ic"><x-velaro.icon name="upload" /></span>
                        <strong>{{ $rotulo }}</strong>
                        <small>PDF, PNG ou JPG · máx. {{ intdiv($documentos['maxKb'], 1024) }}MB</small>
                        <input class="input input--compact" type="file" name="{{ $campo }}"
                               accept=".pdf,.png,.jpg,.jpeg">
                        @error($campo)<small class="field__message">{{ $message }}</small>@enderror
                    </label>
                @endforeach
            </div>

            <div class="row row--wrap" style="margin-top:var(--space-4)">
                <button class="btn btn--gold" type="submit">
                    <x-velaro.icon name="upload" /> Enviar documentos
                </button>
                <small class="fhint">Enviado o documento, sua solicitação volta para a fila de análise.</small>
            </div>
        </form>
    </div>
@endif
