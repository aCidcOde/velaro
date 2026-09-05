{{--
[Modulo: resources/views/backend/velaro/estoque]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Gaveta da tela 3.4: ficha do item, saldo por faixa de aro, reservas, ultimas movimentacoes e ajuste rapido.
--}}
@php($primeiraVariante = $variantes->first())
<aside class="drawer">
  <header class="drawer__head">
    <div><h2 class="title">{{ $produto->name }}</h2></div>
    <span class="chip {{ \App\Services\Backend\EstoqueService::CHIP_SITUACAO[$situacao] }}">{{ trans('stock.item_status.'.$situacao, [], 'pt_BR') }}</span>
    <a class="drawer__x" href="{{ route('backend.estoque.index', \Illuminate\Support\Arr::except(request()->query(), ['produto'])) }}" aria-label="Fechar">
      <x-velaro.icon name="x" />
    </a>
  </header>

  <div class="drawer__body">
    <div class="prod__img" style="height:150px">
      @if($capa)
        <img src="{{ $capa['src'] }}" alt="{{ $capa['alt'] }}" loading="lazy" style="width:100%;height:100%;object-fit:contain">
      @else
        <x-velaro.ring :alt="$produto->name" style="width:100%;height:100%;object-fit:contain" />
      @endif
    </div>

    <div class="datarow"><span class="datarow__k"><x-velaro.icon name="tag" /> SKU</span><span class="datarow__v">{{ $produto->sku ?? '—' }}</span></div>
    <div class="datarow"><span class="datarow__k"><x-velaro.icon name="sparkle" /> Coleção</span><span class="datarow__v">{{ $produto->collection?->name ?? '—' }}</span></div>
    <div class="datarow"><span class="datarow__k"><x-velaro.icon name="diamond" /> Material</span><span class="datarow__v">{{ $produto->material?->name ?? '—' }}</span></div>
    <div class="datarow"><span class="datarow__k"><x-velaro.icon name="sparkle" /> Acabamento</span><span class="datarow__v">{{ $produto->finish?->name ?? '—' }}</span></div>
    <div class="datarow"><span class="datarow__k"><x-velaro.icon name="box" /> Local de armazenamento</span><span class="datarow__v">{{ $local?->name ?? '—' }}</span></div>

    <div>
      <span class="eyebrow">Estoque por tamanho</span>
      <div class="table-scroll">
        <table class="table">
          <thead>
            <tr><th>Tamanho</th><th class="cell-num">Estoque atual</th><th class="cell-num">Reservado</th><th class="cell-num">Disponível</th><th class="cell-num">Mínimo</th></tr>
          </thead>
          <tbody>
            @forelse($porFaixa as $faixa)
              <tr>
                <td><span class="num">{{ $faixa['rotulo'] }}</span></td>
                <td class="cell-num"><span class="num">{{ $faixa['onHand'] }}</span></td>
                <td class="cell-num"><span class="num">{{ $faixa['reserved'] }}</span></td>
                <td class="cell-num"><span class="num">{{ $faixa['available'] }}</span></td>
                <td class="cell-num"><span class="num">{{ $faixa['minimum'] }}</span></td>
              </tr>
            @empty
              <tr><td colspan="5"><small class="muted">Este produto ainda não tem aro cadastrado.</small></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if($primeiraVariante)
      <div class="row row--wrap">
        @if($podeAjustar)
          <a class="btn btn--secondary btn--sm" href="{{ route('backend.estoque.movimentacao', ['variante' => $primeiraVariante->id, 'tipo' => \App\Models\StockMovement::TYPE_ADJUSTMENT]) }}">
            <x-velaro.icon name="edit" /> Ajustar estoque
          </a>
          <a class="btn btn--secondary btn--sm" href="{{ route('backend.estoque.movimentacao', ['variante' => $primeiraVariante->id, 'tipo' => \App\Models\StockMovement::TYPE_INBOUND]) }}">
            <x-velaro.icon name="arrow-down" /> Registrar entrada
          </a>
        @endif
        @if($podeSolicitarProducao)
          <a class="btn btn--primary btn--sm" href="{{ route('backend.estoque.movimentacao', ['variante' => $primeiraVariante->id, 'tipo' => \App\Models\StockMovement::TYPE_PRODUCTION]) }}">
            <x-velaro.icon name="factory" /> Solicitar produção
          </a>
        @endif
      </div>
    @endif

    <div class="grid g2">
      <div class="ministat">
        <strong>{{ $reserved }}</strong><small>unidades reservadas</small>
        @if($primeiraVariante)
          <a class="link-gold" href="{{ route('backend.estoque.historico', ['variant' => $primeiraVariante->id, 'tipo' => \App\Models\StockMovement::TYPE_RESERVATION]) }}">Ver reservas →</a>
        @endif
      </div>
      <div class="ministat">
        <strong>{{ max(0, $restockPoint - $onHand) }}</strong><small>unidades sugeridas</small>
        @if($primeiraVariante && $podeSolicitarProducao)
          <a class="link-gold" href="{{ route('backend.estoque.movimentacao', ['variante' => $primeiraVariante->id, 'tipo' => \App\Models\StockMovement::TYPE_PRODUCTION]) }}">Gerar pedido →</a>
        @endif
      </div>
    </div>

    <div>
      <span class="eyebrow">Últimas movimentações</span>
      @forelse($movimentacoes as $movimento)
        <div class="datarow">
          <span class="datarow__k">
            <x-velaro.icon name="refresh" />
            <span>
              <strong style="display:block;color:var(--ink)">{{ trans('stock.movement_type.'.$movimento->type, [], 'pt_BR') }}</strong>
              <small>{{ $movimento->order?->public_number ? 'Pedido #'.$movimento->order->public_number : ($movimento->actor?->name ?? 'Sistema') }}</small>
            </span>
          </span>
          <span class="datarow__v">
            <span class="{{ $movimento->after >= $movimento->before ? 'kpi__delta--up' : 'kpi__delta--down' }}">
              {{ $movimento->after >= $movimento->before ? '+' : '−' }}{{ abs($movimento->after - $movimento->before) }} unidades
            </span><br>
            <small class="muted">{{ $movimento->created_at?->format('d/m/Y H:i') }}</small>
          </span>
        </div>
      @empty
        <p class="lede" style="font-size:var(--text-sm)">Nenhuma movimentação registrada para este item.</p>
      @endforelse
      @if($primeiraVariante)
        <a class="link-gold" href="{{ route('backend.estoque.historico', ['variant' => $primeiraVariante->id]) }}">Ver todas →</a>
      @endif
    </div>

    @if($podeAjustar && $primeiraVariante)
      <div>
        <span class="eyebrow">Ajuste manual rápido</span>
        {{-- Ajuste define o novo saldo do aro, e nao um delta: e o que o doc 3.4
             (regra 3) manda gravar em `before`/`after`. Motivo e obrigatorio —
             acao sensivel exige justificativa registrada (Anexo I §7). --}}
        <form method="POST" action="{{ route('backend.estoque.movimentacao.store') }}" class="stack">
          @csrf
          <input type="hidden" name="type" value="{{ \App\Models\StockMovement::TYPE_ADJUSTMENT }}">
          <input type="hidden" name="product_id" value="{{ $produto->id }}">
          <div class="field">
            <label for="gaveta-aro">Tamanho (aro)<i class="req">*</i></label>
            <select class="select select--compact" id="gaveta-aro" name="product_variant_id">
              @foreach($variantes as $variante)
                <option value="{{ $variante->id }}">{{ $variante->sku }} · aro {{ $variante->getAttribute('ring_size') }}</option>
              @endforeach
            </select>
          </div>
          <div class="field">
            <label for="gaveta-local">Local<i class="req">*</i></label>
            <select class="select select--compact" id="gaveta-local" name="stock_location_id">
              @foreach($locais as $cofre)
                <option value="{{ $cofre->id }}" @selected($local && $local->id === $cofre->id)>{{ $cofre->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="field">
            <label for="gaveta-qtd">Novo saldo (unidades)<i class="req">*</i></label>
            <input class="input input--compact" id="gaveta-qtd" name="quantity" type="number" min="0" value="0">
          </div>
          <div class="field">
            <label for="gaveta-motivo">Motivo<i class="req">*</i></label>
            <input class="input input--compact" id="gaveta-motivo" name="reason" type="text" maxlength="255"
                   placeholder="Inventário — divergência de contagem">
          </div>
          <button class="btn btn--primary btn--sm" type="submit"><x-velaro.icon name="check" /> Aplicar ajuste</button>
        </form>
        <small class="fhint">Ajuste de estoque é ação sensível: gera registro em <code>audit_logs</code> com valor anterior e posterior.</small>
      </div>
    @endif
  </div>
</aside>
