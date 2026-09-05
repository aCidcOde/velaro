{{--
[Modulo: resources/views/backend/velaro/estoque]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 3.4 (52a) — nova movimentacao: entrada, saida, ajuste, producao e reserva de um SKU/aro.
--}}
@php($porProduto = $variantes->groupBy(fn ($variante) => $variante->product?->name ?? '—'))
<x-velaro.layouts.master title="Nova movimentação de estoque">

  <a class="link-gold" href="{{ route('backend.estoque.index') }}">← Voltar para o estoque</a>

  <div class="page-head">
    <div>
      <h1 class="display-md">Nova movimentação de estoque</h1>
      <p class="lede">Entrada, saída, ajuste, produção e reserva de um SKU. O tipo escolhido define os campos obrigatórios.</p>
    </div>
    <div class="row row--wrap">
      <a class="btn btn--secondary btn--sm" href="{{ route('backend.estoque.index') }}"><x-velaro.icon name="x" /> Cancelar</a>
    </div>
  </div>

  @if($errors->any())
    <p class="notice notice--danger"><x-velaro.icon name="x" /><span>Confira os campos destacados abaixo.</span></p>
  @endif

  <div class="split">
    <div class="stack">
      <div class="card">
        {{-- As abas do prototipo sao navegacao: cada tipo recarrega a tela com os
             campos obrigatorios dele. Sem JS, e a forma honesta de trocar de tipo. --}}
        <div class="tabs">
          @foreach($opcoes['tipos'] as $opcaoDeTipo)
            <a class="tab {{ $tipo === $opcaoDeTipo ? 'is-on' : '' }}"
               href="{{ route('backend.estoque.movimentacao', array_merge(request()->query(), ['tipo' => $opcaoDeTipo])) }}">
              {{ trans('stock.movement_type.'.$opcaoDeTipo, [], 'pt_BR') }}
            </a>
          @endforeach
        </div>

        <form method="POST" action="{{ route('backend.estoque.movimentacao.store') }}" style="margin-top:var(--space-5)">
          @csrf
          <div class="fgrid fgrid--2">
            <div class="field" @if($errors->has('type')) data-state="error" @endif>
              <label for="type">Tipo de movimentação<i class="req">*</i></label>
              <select class="select" id="type" name="type">
                @foreach($opcoes['tipos'] as $opcaoDeTipo)
                  <option value="{{ $opcaoDeTipo }}" @selected(old('type', $tipo) === $opcaoDeTipo)>{{ trans('stock.movement_type.'.$opcaoDeTipo, [], 'pt_BR') }}</option>
                @endforeach
              </select>
              <small class="fhint">Grava em <code>stock_movements.type</code></small>
              @error('type')<small class="field__message">{{ $message }}</small>@enderror
            </div>

            <div class="field" @if($errors->has('product_id')) data-state="error" @endif>
              <label for="product_id">Produto / SKU<i class="req">*</i></label>
              <select class="select" id="product_id" name="product_id">
                <option value="">Buscar por nome, código ou referência…</option>
                @foreach($variantes->pluck('product')->filter()->unique('id') as $produto)
                  <option value="{{ $produto->id }}" @selected(old('product_id', $ficha['variante']->product_id ?? null) == $produto->id)>{{ $produto->sku }} · {{ $produto->name }}</option>
                @endforeach
              </select>
              <small class="fhint">Confere se o aro escolhido é mesmo deste produto</small>
              @error('product_id')<small class="field__message">{{ $message }}</small>@enderror
            </div>

            <div class="field" @if($errors->has('product_variant_id')) data-state="error" @endif>
              <label for="product_variant_id">Tamanho (aro)<i class="req">*</i></label>
              <select class="select" id="product_variant_id" name="product_variant_id">
                <option value="">Selecione o tamanho</option>
                @foreach($porProduto as $nomeDoProduto => $lista)
                  <optgroup label="{{ $nomeDoProduto }}">
                    @foreach($lista as $variante)
                      <option value="{{ $variante->id }}" @selected(old('product_variant_id', $ficha['variante']->id ?? null) == $variante->id)>
                        {{ $variante->sku }} · aro {{ $variante->getAttribute('ring_size') }}
                      </option>
                    @endforeach
                  </optgroup>
                @endforeach
              </select>
              <small class="fhint">Cada aro é um SKU próprio em <code>product_variants</code></small>
              @error('product_variant_id')<small class="field__message">{{ $message }}</small>@enderror
            </div>

            <div class="field" @if($errors->has('quantity')) data-state="error" @endif>
              <label for="quantity">Quantidade<i class="req">*</i></label>
              <input class="input" id="quantity" name="quantity" type="number"
                     min="{{ $tipo === \App\Models\StockMovement::TYPE_ADJUSTMENT ? 0 : 1 }}" max="100000"
                     value="{{ old('quantity') }}">
              <small class="fhint">
                {{ $tipo === \App\Models\StockMovement::TYPE_ADJUSTMENT
                    ? 'No ajuste, a quantidade é o novo saldo do aro'
                    : 'Unidades inteiras' }}
              </small>
              @error('quantity')<small class="field__message">{{ $message }}</small>@enderror
            </div>

            <div class="field" @if($errors->has('stock_location_id')) data-state="error" @endif>
              <label for="stock_location_id">
                Local de armazenamento
                @if($tipo !== \App\Models\StockMovement::TYPE_PRODUCTION)<i class="req">*</i>@endif
              </label>
              <select class="select" id="stock_location_id" name="stock_location_id">
                <option value="">Cofre padrão da matriz</option>
                @foreach($opcoes['locais'] as $cofre)
                  <option value="{{ $cofre->id }}" @selected(old('stock_location_id', $ficha['local']->id ?? null) == $cofre->id)>{{ $cofre->name }}</option>
                @endforeach
              </select>
              @error('stock_location_id')<small class="field__message">{{ $message }}</small>@enderror
            </div>

            <div class="field" @if($errors->has('occurred_at')) data-state="error" @endif>
              <label for="occurred_at">Data e hora<i class="req">*</i></label>
              <input class="input" id="occurred_at" name="occurred_at" type="datetime-local"
                     value="{{ old('occurred_at', now()->format('Y-m-d\TH:i')) }}">
              @error('occurred_at')<small class="field__message">{{ $message }}</small>@enderror
            </div>

            <div class="field">
              <label for="documento">Documento de origem</label>
              {{-- Campo do prototipo sem coluna correspondente: `stock_movements`
                   nao tem `document`. Migrations estao fora do territorio desta
                   entrega — o campo fica visivel e desligado, e a lacuna esta
                   reportada como pendencia de escopo em vez de ir para o motivo. --}}
              <input class="input" id="documento" type="text" placeholder="OP-2026-0148" disabled>
              <small class="fhint">Ordem de produção, NF de entrada ou número do pedido — coluna pendente em <code>stock_movements</code></small>
            </div>

            <div class="field" @if($errors->has('reason')) data-state="error" @endif>
              <label for="reason">Motivo<i class="req">*</i></label>
              <input class="input" id="reason" name="reason" type="text" maxlength="255" value="{{ old('reason') }}"
                     placeholder="Ordem de produção concluída">
              <small class="fhint">Grava em <code>stock_movements.reason</code></small>
              @error('reason')<small class="field__message">{{ $message }}</small>@enderror
            </div>

            <div class="field">
              <label for="responsavel">Responsável</label>
              <input class="input" id="responsavel" type="text" value="{{ auth()->user()->name }} · Admin" disabled>
              <small class="fhint">Preenchido pelo usuário logado (<code>actor_id</code>)</small>
            </div>

            @if($tipo === \App\Models\StockMovement::TYPE_RESERVATION)
              <div class="field" @if($errors->has('order_id')) data-state="error" @endif>
                <label for="order_id">Pedido vinculado<i class="req">*</i></label>
                {{-- O operador escolhe pelo numero publico do pedido: `orders.id`
                     e chave interna e nunca vira campo digitado (regra do
                     projeto — a referencia externa e `public_number`). --}}
                <select class="select" id="order_id" name="order_id">
                  <option value="">Selecione o pedido</option>
                  @foreach($pedidos ?? [] as $pedidoEmAberto)
                    <option value="{{ $pedidoEmAberto->id }}" @selected(old('order_id') == $pedidoEmAberto->id)>
                      #{{ $pedidoEmAberto->public_number }} · {{ $pedidoEmAberto->reseller?->trade_name ?? 'sem revendedor' }}@if($pedidoEmAberto->customer) · {{ $pedidoEmAberto->customer->name }}@endif
                    </option>
                  @endforeach
                </select>
                <small class="fhint">A reserva segura peça para um pedido; sem pedido ela não existe</small>
                @error('order_id')<small class="field__message">{{ $message }}</small>@enderror
              </div>
            @endif

            @if($tipo === \App\Models\StockMovement::TYPE_PRODUCTION)
              <div class="field" @if($errors->has('due_date')) data-state="error" @endif>
                <label for="due_date">Prazo previsto<i class="req">*</i></label>
                <input class="input" id="due_date" name="due_date" type="date" value="{{ old('due_date') }}">
                @error('due_date')<small class="field__message">{{ $message }}</small>@enderror
              </div>
              <div class="field" @if($errors->has('priority')) data-state="error" @endif>
                <label for="priority">Prioridade</label>
                <select class="select" id="priority" name="priority">
                  @foreach($opcoes['prioridades'] as $prioridade)
                    <option value="{{ $prioridade }}" @selected(old('priority', \App\Models\ProductionRequest::PRIORITY_NORMAL) === $prioridade)>
                      {{ trans('stock.production_request_priority.'.$prioridade, [], 'pt_BR') }}
                    </option>
                  @endforeach
                </select>
                @error('priority')<small class="field__message">{{ $message }}</small>@enderror
              </div>
            @endif

            <div class="field field--full">
              <label for="observacao">Observação</label>
              <textarea class="textarea" id="observacao" rows="2" disabled placeholder="Lote conferido na entrada."></textarea>
              <small class="fhint">Coluna pendente em <code>stock_movements</code> — o motivo acima é o campo auditado</small>
            </div>
          </div>

          @if($ficha)
            <div style="margin-top:var(--space-5)">
              <span class="eyebrow">Impacto no saldo do aro {{ $ficha['variante']->getAttribute('ring_size') }}</span>
              <div class="grid g4">
                <div class="ministat"><strong>{{ $ficha['onHand'] }}</strong><small>Estoque atual</small></div>
                <div class="ministat"><strong>{{ $ficha['reserved'] }}</strong><small>Reservado</small></div>
                <div class="ministat"><strong>{{ $ficha['available'] }}</strong><small>Antes (disponível)</small></div>
                <div class="ministat"><strong>{{ $ficha['minimum'] }}</strong><small>Mínimo</small></div>
              </div>
              <small class="fhint">O saldo posterior é calculado no registro e gravado em <code>after</code>, ao lado do <code>before</code> acima.</small>
            </div>
          @endif

          <div class="row row--wrap" style="margin-top:var(--space-5)">
            <a class="btn btn--secondary" href="{{ route('backend.estoque.index') }}"><x-velaro.icon name="x" /> Cancelar</a>
            <button class="btn btn--primary" type="submit"><x-velaro.icon name="check" /> Registrar movimentação</button>
          </div>
        </form>
      </div>

      <div class="card">
        <div class="card__head"><h2 class="title">O que cada tipo de movimentação exige</h2></div>
        <div class="table-scroll">
          <table class="table">
            <thead><tr><th>Tipo de movimentação</th><th>Efeito no saldo</th><th>Campos obrigatórios</th><th>Permissão</th></tr></thead>
            <tbody>
              @foreach([
                [\App\Models\StockMovement::TYPE_INBOUND, 'Soma no estoque atual', 'Aro, quantidade e motivo', 'velaro.stock.adjust'],
                [\App\Models\StockMovement::TYPE_OUTBOUND, 'Subtrai do disponível', 'Aro, quantidade e motivo', 'velaro.stock.adjust'],
                [\App\Models\StockMovement::TYPE_ADJUSTMENT, 'Define o novo saldo — grava before e after', 'Aro, quantidade e motivo', 'velaro.stock.adjust'],
                [\App\Models\StockMovement::TYPE_PRODUCTION, 'Abre ordem de produção e alimenta o sob encomenda', 'Aro, quantidade e prazo previsto', 'velaro.stock.request_production'],
                [\App\Models\StockMovement::TYPE_RESERVATION, 'Soma em reservado e reduz o disponível', 'Pedido vinculado, aro e quantidade', 'velaro.stock.adjust'],
              ] as [$linhaTipo, $efeito, $obrigatorios, $permissao])
                <tr>
                  <td>
                    <div class="row" style="gap:8px">
                      <span class="chip {{ \App\Services\Backend\EstoqueService::CHIP_MOVIMENTACAO[$linhaTipo] }} chip--flat">
                        {{ trans('stock.movement_type.'.$linhaTipo, [], 'pt_BR') }}
                      </span>
                    </div>
                  </td>
                  <td>{{ $efeito }}</td>
                  <td>{{ $obrigatorios }}</td>
                  <td><code>{{ $permissao }}</code></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      <p class="notice notice--info">
        <x-velaro.icon name="shield" />
        <span>Ajuste de estoque é ação sensível: o movimento grava <code>before</code> e <code>after</code> e gera registro em <code>audit_logs</code> com o responsável (doc 3-4, regra 3).</span>
      </p>
    </div>

    <div class="stack">
      @if($ficha)
        <div class="card">
          <div class="card__head"><h2 class="title">Item selecionado</h2>
            <span class="chip {{ \App\Services\Backend\EstoqueService::CHIP_SITUACAO[$ficha['situacao']] }}">{{ trans('stock.item_status.'.$ficha['situacao'], [], 'pt_BR') }}</span>
          </div>
          <div class="prod__img" style="height:140px">
            @if($ficha['capa'])
              <img src="{{ $ficha['capa']['src'] }}" alt="{{ $ficha['capa']['alt'] }}" loading="lazy" style="width:100%;height:100%;object-fit:contain">
            @else
              <x-velaro.ring :alt="$ficha['variante']->product?->name ?? 'Par de alianças'" style="width:100%;height:100%;object-fit:contain" />
            @endif
          </div>
          <div class="datarow"><span class="datarow__k"><x-velaro.icon name="tag" /> SKU</span><span class="datarow__v">{{ $ficha['variante']->sku }}</span></div>
          <div class="datarow"><span class="datarow__k"><x-velaro.icon name="sparkle" /> Coleção</span><span class="datarow__v">{{ $ficha['variante']->product?->collection?->name ?? '—' }}</span></div>
          <div class="datarow"><span class="datarow__k"><x-velaro.icon name="diamond" /> Material</span><span class="datarow__v">{{ $ficha['variante']->product?->material?->name ?? '—' }}</span></div>
          <div class="datarow"><span class="datarow__k"><x-velaro.icon name="sparkle" /> Acabamento</span><span class="datarow__v">{{ $ficha['variante']->product?->finish?->name ?? '—' }}</span></div>
          <div class="datarow"><span class="datarow__k"><x-velaro.icon name="box" /> Local</span><span class="datarow__v">{{ $ficha['local']?->name ?? '—' }}</span></div>
          <a class="btn btn--secondary" href="{{ route('backend.estoque.historico', ['variant' => $ficha['variante']->id]) }}">
            <x-velaro.icon name="list" /> Ver movimentações do item
          </a>
        </div>

        <div class="card">
          <div class="card__head"><h2 class="title">Saldo atual do SKU</h2></div>
          <div class="grid g2">
            <div class="ministat"><strong>{{ $ficha['onHand'] }}</strong><small>Estoque atual</small></div>
            <div class="ministat"><strong>{{ $ficha['reserved'] }}</strong><small>Reservado</small></div>
            <div class="ministat"><strong>{{ $ficha['available'] }}</strong><small>Disponível</small></div>
            <div class="ministat"><strong>{{ $ficha['minimum'] }}</strong><small>Mínimo</small></div>
          </div>
          <small class="fhint">Saldo do aro somado em todos os cofres, quando o filtro de local não recorta um deles.</small>
        </div>

        <div class="card">
          <div class="card__head"><h2 class="title">Reposição sugerida</h2></div>
          <p class="lede" style="font-size:var(--text-sm)">
            O item está com reposição
            <strong>{{ trans('stock.restock.'.($ficha['onHand'] <= $ficha['minimum'] ? \App\Services\Backend\EstoqueService::REPOSICAO_PRIORITARIA : \App\Services\Backend\EstoqueService::REPOSICAO_SUGERIDA), [], 'pt_BR') }}</strong>:
            {{ max(0, $ficha['restockPoint'] - $ficha['onHand']) }} unidades para voltar ao ponto de reposição.
          </p>
          <div class="datarow"><span class="datarow__k"><x-velaro.icon name="box" /> Quantidade sugerida</span><span class="datarow__v"><span class="num">{{ max(0, $ficha['restockPoint'] - $ficha['onHand']) }} unidades</span></span></div>
          <div class="datarow"><span class="datarow__k"><x-velaro.icon name="clock" /> Prazo de produção</span><span class="datarow__v">{{ $ficha['variante']->product?->delivery_days ? $ficha['variante']->product->delivery_days.' dias' : '—' }}</span></div>
          @if($podeSolicitarProducao)
            <a class="btn btn--secondary" href="{{ route('backend.estoque.movimentacao', ['variante' => $ficha['variante']->id, 'tipo' => \App\Models\StockMovement::TYPE_PRODUCTION]) }}">
              <x-velaro.icon name="factory" /> Usar sugestão no tipo Produção
            </a>
          @endif
        </div>
      @else
        <div class="card">
          <div class="card__head"><h2 class="title">Item selecionado</h2></div>
          <p class="lede" style="font-size:var(--text-sm)">Escolha o aro no formulário — ou abra esta tela pela gaveta de um item — para ver o saldo atual e a reposição sugerida.</p>
        </div>
      @endif

      <p class="notice notice--info">
        <x-velaro.icon name="lock" />
        <span>Entrada, saída, ajuste e reserva exigem <code>velaro.stock.adjust</code>; abrir ordem de produção exige <code>velaro.stock.request_production</code> (doc 3-4, seção 2).</span>
      </p>
    </div>
  </div>

</x-velaro.layouts.master>
