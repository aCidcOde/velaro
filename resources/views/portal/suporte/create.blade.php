{{--
/*
[Modulo: resources/views/portal/suporte]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Abertura de chamado (mockup 42): assunto, categoria, vinculo com pedido e cliente, e a descricao.
*/
--}}
<x-velaro.layouts.portal title="Abrir chamado" titulo="Suporte">

<div class="page-head">
  <div>
    <h1 class="display-md">Abrir chamado</h1>
    <p class="lede">Descreva o que aconteceu. Chamados vinculados a um pedido são respondidos mais rápido,
      porque o time já enxerga o lote, a produção e a nota fiscal.</p>
  </div>
  <div class="row row--wrap">
    <a class="btn btn--secondary" href="{{ route('portal.suporte.index') }}">← Voltar para o suporte</a>
    <a class="btn btn--secondary" href="{{ route('portal.ajuda') }}">
      <x-velaro.icon name="book" /> Central de ajuda</a>
  </div>
</div>

@if($errors->any())
  <p class="notice notice--danger" style="margin-bottom:var(--space-4)" role="alert">
    <x-velaro.icon name="info" /><span>Revise os campos destacados para abrir o chamado.</span></p>
@endif

<div class="card">
  <div class="card__head"><h2 class="title">Abrir novo chamado</h2></div>

  <form method="POST" action="{{ route('portal.suporte.store') }}" enctype="multipart/form-data">
    @csrf

    <div class="fgrid fgrid--2" style="align-items:start">
      <div class="field field--full" @error('subject') data-state="error" @enderror>
        <label for="chamado-assunto">Assunto<i class="req">*</i></label>
        <input class="input" type="text" id="chamado-assunto" name="subject" maxlength="255" required
               value="{{ old('subject') }}" placeholder="Ex.: aliança recebida com aro errado">
        @error('subject')<small class="field__message">{{ $message }}</small>@enderror
      </div>

      <div class="field" @error('category') data-state="error" @enderror>
        <label for="chamado-categoria">Categoria<i class="req">*</i></label>
        <select class="select" id="chamado-categoria" name="category" required>
          <option value="">Selecione…</option>
          @foreach($categorias as $categoria)
            <option value="{{ $categoria }}" @selected(old('category') === $categoria)>{{ $categoria }}</option>
          @endforeach
        </select>
        @error('category')<small class="field__message">{{ $message }}</small>@enderror
        <small class="fhint">{{ implode(' · ', $categorias) }}</small>
      </div>

      {{-- Só os pedidos DESTA loja entram na lista, e o Form Request confere o
           dono de novo no servidor: um `select` é apenas uma sugestão do
           navegador. --}}
      <div class="field" @error('order_id') data-state="error" @enderror>
        <label for="chamado-pedido">Pedido relacionado</label>
        <select class="select" id="chamado-pedido" name="order_id">
          <option value="">Nenhum</option>
          @foreach($opcoes['pedidos'] as $pedido)
            <option value="{{ $pedido['id'] }}"
                    @selected((int) old('order_id', $pedidoSugerido) === $pedido['id'])>{{ $pedido['rotulo'] }}</option>
          @endforeach
        </select>
        @error('order_id')<small class="field__message">{{ $message }}</small>@enderror
        <small class="fhint">Opcional — vincula o chamado ao pedido</small>
      </div>

      <div class="field" @error('priority') data-state="error" @enderror>
        <label for="chamado-prioridade">Prioridade<i class="req">*</i></label>
        <select class="select" id="chamado-prioridade" name="priority" required>
          @foreach($prioridades as $valor => $texto)
            <option value="{{ $valor }}"
                    @selected(old('priority', $prioridadePadrao) === $valor)>{{ $texto['rotulo'] }}</option>
          @endforeach
        </select>
        @error('priority')<small class="field__message">{{ $message }}</small>@enderror
      </div>

      <div class="field" @error('customer_id') data-state="error" @enderror>
        <label for="chamado-cliente">Cliente final vinculado</label>
        <select class="select" id="chamado-cliente" name="customer_id">
          <option value="">Nenhum</option>
          @foreach($opcoes['clientes'] as $cliente)
            <option value="{{ $cliente['id'] }}"
                    @selected((int) old('customer_id') === $cliente['id'])>{{ $cliente['rotulo'] }}</option>
          @endforeach
        </select>
        @error('customer_id')<small class="field__message">{{ $message }}</small>@enderror
        <small class="fhint">Aparece só como pessoa vinculada; não participa da conversa</small>
      </div>
    </div>

    <div class="field" @error('body') data-state="error" @enderror>
      <label for="chamado-descricao">Descrição<i class="req">*</i></label>
      <textarea class="textarea" id="chamado-descricao" name="body" rows="6" required maxlength="5000"
                placeholder="Conte o que aconteceu, com número do pedido, aro, acabamento e o que você espera como solução.">{{ old('body') }}</textarea>
      @error('body')<small class="field__message">{{ $message }}</small>@enderror
    </div>

    {{-- Fora de `.field` de propósito: `.field > label` é flex e achataria o
         `.upload`, que é a caixa de arrastar-e-soltar do design system. --}}
    <div style="margin-top:var(--space-4)">
      <label class="upload" for="chamado-anexos">
        <span class="upload__ic"><x-velaro.icon name="upload" /></span>
        <strong>Anexar fotos ou documentos</strong>
        <small>PNG, JPG ou PDF · até 5 MB por arquivo · no máximo 5 arquivos</small>
        <input class="input" type="file" id="chamado-anexos" name="anexos[]" multiple
               accept="image/png,image/jpeg,application/pdf"
               style="max-width:100%;margin-top:var(--space-2);font-size:var(--text-xs)">
      </label>
      @error('anexos')<small class="field__message">{{ $message }}</small>@enderror
      @error('anexos.*')<small class="field__message">{{ $message }}</small>@enderror
    </div>

    <div class="row row--wrap" style="margin-top:var(--space-4)">
      <button class="btn btn--primary" type="submit"><x-velaro.icon name="support" /> Abrir chamado</button>
      <a class="btn btn--secondary" href="{{ route('portal.suporte.index') }}">Cancelar</a>
      <a class="btn btn--secondary" href="https://wa.me/{{ preg_replace('/\D+/', '', $canais['whatsapp']) }}"
         target="_blank" rel="noopener"><x-velaro.icon name="whats" /> Falar no WhatsApp</a>
    </div>

    <p class="notice notice--gold"><x-velaro.icon name="info" /><span>O atendimento ocorre entre
      <strong>a Velaro e a sua loja</strong>. O cliente final aparece apenas como pessoa vinculada ao pedido
      e não participa da conversa.</span></p>
  </form>
</div>

</x-velaro.layouts.portal>
