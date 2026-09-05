{{--
[Modulo: resources/views/backend/velaro/pedidos]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 3.6 (novo pedido) — o atendimento que a Velaro registra em nome do revendedor, por telefone ou WhatsApp.
--}}
<x-velaro.layouts.master title="Novo pedido interno">

  <a class="link-gold" href="{{ route('backend.pedidos.index') }}">← Voltar para pedidos</a>

  <div class="page-head">
    <div>
      <h1 class="display-md">Novo pedido interno</h1>
      <p class="lede">Pedido registrado pela Velaro em nome de um revendedor — atendimento por telefone ou WhatsApp.</p>
    </div>
    <div class="row row--wrap">
      <a class="btn btn--secondary btn--sm" href="{{ route('backend.pedidos.index') }}"><x-velaro.icon name="x" /> Cancelar</a>
    </div>
  </div>

  @if($errors->any())
    <p class="notice notice--danger"><x-velaro.icon name="x" /><span>Confira os campos destacados abaixo.</span></p>
  @endif

  {{-- Os cinco passos do prototipo viram indice das secoes do formulario: a
       tela e uma pagina so, sem JS, e o stepper diz onde cada coisa esta. --}}
  <div class="card">
    <ol class="stepper">
      @foreach([
        ['1', 'Revendedor', 'Quem recebe a remessa', 'revendedor'],
        ['2', 'Itens do pedido', 'SKU, aro e gravação', 'itens'],
        ['3', 'Condição comercial', 'Pagamento e lote', 'comercial'],
        ['4', 'Entrega', 'Remessa semanal', 'entrega'],
        ['5', 'Revisão', 'Confirmar e criar', 'revisao'],
      ] as [$numero, $rotulo, $nota, $ancora])
        <li class="step step--todo">
          <span class="step__dot">{{ $numero }}</span>
          <span class="step__lab"><a class="link-gold" href="#{{ $ancora }}">{{ $rotulo }}</a></span>
          <span class="step__note">{{ $nota }}</span>
        </li>
      @endforeach
    </ol>
  </div>

  <form method="POST" action="{{ route('backend.pedidos.store') }}">
    @csrf
    <div class="split">
      <div class="stack">

        <div class="card" id="revendedor">
          <div class="card__head"><h2 class="title">Revendedor</h2>
            <a class="btn btn--secondary btn--sm" href="{{ route('backend.revendedores.index') }}"><x-velaro.icon name="store" /> Ver revendedores</a>
          </div>
          <div class="fgrid fgrid--2">
            <div class="field" @if($errors->has('reseller_id')) data-state="error" @endif>
              <label for="reseller_id">Revendedor<i class="req">*</i></label>
              <select class="select" id="reseller_id" name="reseller_id">
                <option value="">Selecione o revendedor</option>
                @foreach($revendedores as $revendedor)
                  <option value="{{ $revendedor->id }}" @selected(old('reseller_id') == $revendedor->id)>
                    {{ $revendedor->trade_name }}@if($revendedor->code) · {{ $revendedor->code }}@endif — {{ $revendedor->city }}/{{ $revendedor->state }}
                  </option>
                @endforeach
              </select>
              @error('reseller_id')<small class="field__message">{{ $message }}</small>@enderror
            </div>
            <div class="field" @if($errors->has('origin_channel')) data-state="error" @endif>
              <label for="origin_channel">Canal de origem<i class="req">*</i></label>
              <select class="select" id="origin_channel" name="origin_channel">
                @foreach($canais as $chave => $rotulo)
                  <option value="{{ $chave }}" @selected(old('origin_channel') === $chave)>{{ $rotulo }}</option>
                @endforeach
              </select>
              <small class="fhint">Telefone, WhatsApp ou e-mail</small>
              @error('origin_channel')<small class="field__message">{{ $message }}</small>@enderror
            </div>
            <div class="field">
              <label for="atendente">Atendente responsável<i class="req">*</i></label>
              {{-- O ator sai da sessao, nunca do formulario: e ele que vai para
                   `orders.user_id` e para a trilha de auditoria. --}}
              <input class="input" id="atendente" type="text" value="{{ auth()->user()->name }} · Admin" disabled>
              <small class="fhint">Preenchido pelo usuário logado</small>
            </div>
            <div class="field" @if($errors->has('reference')) data-state="error" @endif>
              <label for="reference">Pedido de referência do revendedor</label>
              <input class="input" id="reference" name="reference" type="text" value="{{ old('reference') }}" maxlength="60">
              <small class="fhint">Número do pedido de compra do lojista, se houver</small>
              @error('reference')<small class="field__message">{{ $message }}</small>@enderror
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card__head"><h2 class="title">Cliente final (opcional)</h2></div>
          <div class="fgrid fgrid--2">
            <div class="field"><label for="customer_name">Nome do cliente final</label>
              <input class="input" id="customer_name" name="customer_name" type="text" value="{{ old('customer_name') }}"></div>
            <div class="field"><label for="customer_document">CPF</label>
              <input class="input" id="customer_document" name="customer_document" type="text" value="{{ old('customer_document') }}"></div>
            <div class="field"><label for="customer_phone">Telefone</label>
              <input class="input" id="customer_phone" name="customer_phone" type="text" value="{{ old('customer_phone') }}"></div>
            <div class="field" @if($errors->has('customer_email')) data-state="error" @endif>
              <label for="customer_email">E-mail</label>
              <input class="input" id="customer_email" name="customer_email" type="email" value="{{ old('customer_email') }}">
              @error('customer_email')<small class="field__message">{{ $message }}</small>@enderror
            </div>
          </div>
          <p class="notice notice--info">
            <x-velaro.icon name="info" />
            <span>O consumidor final <strong>não tem login e não paga a Velaro</strong>: ele aparece apenas como pessoa vinculada ao pedido, e a cobrança é sempre Velaro → lojista (Anexo I §5.2).</span>
          </p>
        </div>

        <div class="card" id="itens">
          <div class="card__head"><h2 class="title">Itens do pedido</h2>
            <a class="btn btn--secondary btn--sm" href="{{ route('backend.produtos.index') }}"><x-velaro.icon name="box" /> Ver catálogo</a>
          </div>
          @error('itens')<p class="notice notice--danger"><x-velaro.icon name="x" /><span>{{ $message }}</span></p>@enderror
          <div class="fgrid">
            @for($linha = 0; $linha < 5; $linha++)
              <div class="fgrid fgrid--2" style="grid-column:1/-1">
                <div class="field" @if($errors->has("itens.$linha.product_variant_id")) data-state="error" @endif>
                  <label for="item-{{ $linha }}-sku">Produto / SKU {{ $linha + 1 }}@if($linha === 0)<i class="req">*</i>@endif</label>
                  <select class="select" id="item-{{ $linha }}-sku" name="itens[{{ $linha }}][product_variant_id]">
                    <option value="">Buscar por nome, código ou referência…</option>
                    @foreach($variantes->groupBy(fn ($variante) => $variante->product?->name ?? '—') as $produto => $lista)
                      <optgroup label="{{ $produto }}">
                        @foreach($lista as $variante)
                          <option value="{{ $variante->id }}" @selected(old("itens.$linha.product_variant_id") == $variante->id)>
                            {{ $variante->sku }} · aro {{ $variante->getAttribute('ring_size') }} · {{ \App\Support\ValorPtBr::moeda((float) $variante->product?->price) }}
                          </option>
                        @endforeach
                      </optgroup>
                    @endforeach
                  </select>
                  @error("itens.$linha.product_variant_id")<small class="field__message">{{ $message }}</small>@enderror
                </div>
                <div class="field" @if($errors->has("itens.$linha.quantity")) data-state="error" @endif>
                  <label for="item-{{ $linha }}-qtd">Quantidade</label>
                  <input class="input" id="item-{{ $linha }}-qtd" name="itens[{{ $linha }}][quantity]" type="number" min="1" max="999"
                         value="{{ old("itens.$linha.quantity", 1) }}">
                  @error("itens.$linha.quantity")<small class="field__message">{{ $message }}</small>@enderror
                </div>
                <div class="field field--full" @if($errors->has("itens.$linha.engraving_text")) data-state="error" @endif>
                  <label for="item-{{ $linha }}-gravacao">Gravação interna</label>
                  <input class="input" id="item-{{ $linha }}-gravacao" name="itens[{{ $linha }}][engraving_text]" type="text"
                         maxlength="60" placeholder="Até 20 caracteres" value="{{ old("itens.$linha.engraving_text") }}">
                  <small class="fhint">Só para produtos com gravação habilitada</small>
                  @error("itens.$linha.engraving_text")<small class="field__message">{{ $message }}</small>@enderror
                </div>
              </div>
            @endfor
          </div>
          <p class="notice notice--info">
            <x-velaro.icon name="info" />
            <span>O aro é um SKU próprio: cada linha escolhe produto e tamanho de uma vez. Linha em branco é ignorada.</span>
          </p>
        </div>

        <div class="card" id="comercial">
          <div class="card__head"><h2 class="title">Condição comercial</h2></div>
          <div class="fgrid fgrid--2">
            <div class="field">
              <label for="tabela">Tabela de preço<i class="req">*</i></label>
              <input class="input" id="tabela" type="text" value="Custo B2B Velaro (catálogo mestre)" disabled>
              <small class="fhint">Define o <code>unit_price</code> gravado no item</small>
            </div>
            <div class="field" @if($errors->has('promotion_id')) data-state="error" @endif>
              <label for="promotion_id">Promoção aplicada</label>
              <select class="select" id="promotion_id" name="promotion_id">
                <option value="">Nenhuma</option>
                @foreach($promocoes as $promocao)
                  <option value="{{ $promocao->id }}" @selected(old('promotion_id') == $promocao->id)>
                    {{ $promocao->code }} · {{ $promocao->name }}
                  </option>
                @endforeach
              </select>
              <small class="fhint">A faixa aplicada é a de maior valor mínimo que o subtotal alcança</small>
              @error('promotion_id')<small class="field__message">{{ $message }}</small>@enderror
            </div>
            <div class="field" @if($errors->has('payment_method')) data-state="error" @endif>
              <label for="payment_method">Forma de pagamento<i class="req">*</i></label>
              <select class="select" id="payment_method" name="payment_method">
                @foreach($meiosDePagamento as $chave => $rotulo)
                  <option value="{{ $chave }}" @selected(old('payment_method') === $chave)>{{ $rotulo }}</option>
                @endforeach
              </select>
              @error('payment_method')<small class="field__message">{{ $message }}</small>@enderror
            </div>
            <div class="field" @if($errors->has('batch_id')) data-state="error" @endif>
              <label for="batch_id">Lote de faturamento</label>
              <select class="select" id="batch_id" name="batch_id">
                <option value="">Sem lote — entra na próxima remessa</option>
                @foreach($lotes as $lote)
                  <option value="{{ $lote->id }}" @selected(old('batch_id') == $lote->id)>
                    {{ $lote->code }} · {{ $lote->reseller?->trade_name }} · corte em {{ $lote->cut_date?->format('d/m/Y') }}
                  </option>
                @endforeach
              </select>
              <small class="fhint">O lote precisa ser do mesmo revendedor</small>
              @error('batch_id')<small class="field__message">{{ $message }}</small>@enderror
            </div>
            <div class="field" @if($errors->has('production_days')) data-state="error" @endif>
              <label for="production_days">Prazo de produção<i class="req">*</i></label>
              <input class="input" id="production_days" name="production_days" type="number" min="1" max="180"
                     value="{{ old('production_days', 7) }}">
              <small class="fhint">Em dias úteis</small>
              @error('production_days')<small class="field__message">{{ $message }}</small>@enderror
            </div>
            <div class="field" @if($errors->has('due_date')) data-state="error" @endif>
              <label for="due_date">Vencimento<i class="req">*</i></label>
              <input class="input" id="due_date" name="due_date" type="date" value="{{ old('due_date') }}">
              @error('due_date')<small class="field__message">{{ $message }}</small>@enderror
            </div>
          </div>
        </div>

        <div class="card" id="entrega">
          <div class="card__head"><h2 class="title">Entrega</h2></div>
          <div class="fgrid fgrid--2">
            <div class="field" @if($errors->has('delivery_mode')) data-state="error" @endif>
              <label for="delivery_mode">Modo de entrega<i class="req">*</i></label>
              <select class="select" id="delivery_mode" name="delivery_mode">
                @foreach($modosDeEntrega as $chave => $rotulo)
                  <option value="{{ $chave }}" @selected(old('delivery_mode') === $chave)>{{ $rotulo }}</option>
                @endforeach
              </select>
              @error('delivery_mode')<small class="field__message">{{ $message }}</small>@enderror
            </div>
            <div class="field" @if($errors->has('expected_at')) data-state="error" @endif>
              <label for="expected_at">Previsão de envio<i class="req">*</i></label>
              <input class="input" id="expected_at" name="expected_at" type="date" value="{{ old('expected_at') }}">
              @error('expected_at')<small class="field__message">{{ $message }}</small>@enderror
            </div>
            <div class="field field--full">
              <label for="endereco">Endereço de entrega<i class="req">*</i></label>
              <input class="input" id="endereco" type="text" value="A loja do revendedor escolhido acima" disabled>
              <small class="fhint">A Velaro entrega sempre na loja do lojista, nunca no consumidor (Anexo I §5.6)</small>
            </div>
            <div class="field field--full" @if($errors->has('notes')) data-state="error" @endif>
              <label for="notes">Observações do pedido</label>
              <textarea class="textarea" id="notes" name="notes" rows="3" maxlength="1000">{{ old('notes') }}</textarea>
              @error('notes')<small class="field__message">{{ $message }}</small>@enderror
            </div>
          </div>
        </div>
      </div>

      <div class="stack" id="revisao">
        <div class="card">
          <div class="card__head"><h2 class="title">Resumo do pedido</h2></div>
          <div class="datarow"><span class="datarow__k">Subtotal</span><span class="datarow__v"><span class="num">Calculado ao criar</span></span></div>
          <div class="datarow"><span class="datarow__k">Desconto da promoção</span><span class="datarow__v"><span class="num">Pela faixa da campanha</span></span></div>
          <div class="datarow"><span class="datarow__k">Frete</span><span class="datarow__v"><span class="num">Incluso na remessa</span></span></div>
          {{-- Sem numero para mostrar antes do envio, a linha do total fica em
               texto de apoio: o estilo `money--action` e para valor, e um
               display gigante com uma formula dentro so atrapalha a leitura. --}}
          <div class="datarow"><span class="datarow__k">Total do pedido</span><span class="datarow__v"><small class="muted">Subtotal + gravação − desconto</small></span></div>
        </div>

        <div class="card">
          <div class="card__head"><h2 class="title">Situação inicial</h2></div>
          <div class="datarow"><span class="datarow__k">Status operacional</span><span class="datarow__v"><span class="chip chip--neutral chip--flat">{{ trans('order.operational_status.'.\App\Models\Order::OPERATIONAL_STATUS_REGISTERED, [], 'pt_BR') }}</span></span></div>
          <div class="datarow"><span class="datarow__k">Status financeiro</span><span class="datarow__v"><span class="chip chip--warn chip--flat">{{ trans('order.payment_status.'.\App\Models\Order::PAYMENT_STATUS_PENDING, [], 'pt_BR') }}</span></span></div>
          <div class="datarow"><span class="datarow__k">Criado por</span><span class="datarow__v">{{ auth()->user()->name }} · Admin</span></div>
          <div class="datarow"><span class="datarow__k">Origem</span><span class="datarow__v">Pedido interno</span></div>
          <small class="fhint">Status operacional e status financeiro são independentes (doc 3-6, regra 2).</small>
        </div>

        <div class="card">
          <div class="card__head"><h2 class="title">Ações</h2></div>
          <button class="btn btn--primary" type="submit"><x-velaro.icon name="check" /> Criar pedido</button>
          <a class="btn btn--secondary" href="{{ route('backend.pedidos.index') }}"><x-velaro.icon name="x" /> Cancelar</a>
        </div>

        <p class="notice notice--info">
          <x-velaro.icon name="tag" />
          <span>Mudança de preço <strong>não afeta pedido já criado</strong>: o <code>unit_price</code> é gravado como snapshot no item.</span>
        </p>
        <p class="notice notice--info">
          <x-velaro.icon name="shield" />
          <span>Pedido criado pelo painel interno registra o ator em <code>audit_logs</code> e aparece no histórico como “criado pela Velaro em nome do revendedor”.</span>
        </p>
      </div>
    </div>
  </form>

</x-velaro.layouts.master>
