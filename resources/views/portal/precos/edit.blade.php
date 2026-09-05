{{--
/*
[Modulo: resources/views/portal/precos]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 2.7 do Portal: margens do lojista, custo Velaro por produto e o resumo por faixa de margem.
*/
--}}
<x-velaro.layouts.portal title="Preços e margens" titulo="Preços e margens">

<div class="page-head"><div>
  <h1 class="display-md">Preços e margens</h1>
  <p class="lede">Defina suas margens e visualize os preços sugeridos para sua loja.</p>
</div></div>

@if(session('status'))
  <p class="notice notice--ok" style="margin-bottom:var(--space-4)" role="status">
    <x-velaro.icon name="check" /><span>{{ session('status') }}</span></p>
@endif

@if($errors->any())
  <p class="notice notice--danger" style="margin-bottom:var(--space-4)" role="alert">
    <x-velaro.icon name="info" /><span>{{ $errors->first() }}</span></p>
@endif

{{-- Os cinco KPIs do topo. --}}
<section class="grid g5">
  @foreach($kpis as $kpi)
    <div class="card card--compact">
      <div class="kpi">
        <span class="kpi__icon {{ $kpi['tom'] }}"><x-velaro.icon :name="$kpi['icone']" /></span>
        <div>
          <div class="kpi__label">{{ $kpi['rotulo'] }}</div>
          <div class="kpi__value">{{ $kpi['valor'] }}</div>
          <div class="kpi__delta">{{ $kpi['nota'] }}</div>
        </div>
      </div>
    </div>
  @endforeach
</section>

@php($valores = [
  'pricing_model' => $configuracao->pricing_model,
  'multiplier' => number_format((float) $configuracao->multiplier, 2, '.', ''),
  'margin_global' => number_format((float) $configuracao->margin_global, 2, '.', ''),
  'margin_min' => number_format((float) $configuracao->margin_min, 2, '.', ''),
  'margin_ideal' => number_format((float) $configuracao->margin_ideal, 2, '.', ''),
  'margin_max' => number_format((float) $configuracao->margin_max, 2, '.', ''),
  'rounding' => $configuracao->rounding,
  'rule_scope' => $configuracao->rule_scope,
  'apply_to_all' => $configuracao->apply_to_all ? '1' : '0',
  'allow_manual_override' => $configuracao->allow_manual_override ? '1' : '0',
  'allow_promotional_prices' => $configuracao->allow_promotional_prices ? '1' : '0',
])

<div class="split split--wide">
  <div class="stack">

    {{-- ─────────────── CONFIGURAÇÃO GLOBAL ─────────────── --}}
    <div class="card">
      <form method="POST" action="{{ route('portal.precos.update') }}">
        @csrf
        @method('PUT')
        @include('portal.precos.partials.ocultos', ['ocultos' => [
          'pricing_model', 'multiplier', 'margin_min', 'margin_ideal', 'margin_max',
          'apply_to_all', 'allow_manual_override', 'allow_promotional_prices',
        ], 'valores' => $valores])

        <div class="fgrid fgrid--3" style="align-items:start">
          <div class="field" @error('margin_global') data-state="error" @enderror>
            <label for="preco-margem-global">Margem global padrão<i class="req">*</i></label>
            <span class="input-shell input-shell--suffix">
              <input class="input" type="number" id="preco-margem-global" name="margin_global" required
                     min="0" max="99.99" step="0.01" value="{{ old('margin_global', $valores['margin_global']) }}">
              <span class="input-shell__suffix">%</span>
            </span>
            @error('margin_global')<small class="field__message">{{ $message }}</small>@enderror
            <small class="fhint">Aplicada quando não houver regra específica</small>
          </div>

          <div class="field" @error('rounding') data-state="error" @enderror>
            <label for="preco-arredondamento">Arredondamento de preços<i class="req">*</i></label>
            <select class="select" id="preco-arredondamento" name="rounding" required>
              @foreach($arredondamentos as $valor => $rotulo)
                <option value="{{ $valor }}" @selected(old('rounding', $valores['rounding']) === $valor)>{{ $rotulo }}</option>
              @endforeach
            </select>
            @error('rounding')<small class="field__message">{{ $message }}</small>@enderror
            <small class="fhint">Como os preços serão exibidos na loja</small>
          </div>

          <div class="field" @error('rule_scope') data-state="error" @enderror>
            <label for="preco-regra">Regra de preço<i class="req">*</i></label>
            <select class="select" id="preco-regra" name="rule_scope" required>
              @foreach($alcances as $valor => $rotulo)
                <option value="{{ $valor }}" @selected(old('rule_scope', $valores['rule_scope']) === $valor)>{{ $rotulo }}</option>
              @endforeach
            </select>
            @error('rule_scope')<small class="field__message">{{ $message }}</small>@enderror
            <small class="fhint">Defina margens diferentes por coleção</small>
          </div>
        </div>

        <div class="row row--wrap" style="margin-top:var(--space-4)">
          <button class="btn btn--secondary btn--sm" type="submit" name="action" value="{{ $acaoRecalcular }}">
            <x-velaro.icon name="refresh" /> Recalcular preços</button>
          <button class="btn btn--primary btn--sm" type="submit" name="action" value="{{ $acaoSalvar }}">
            <x-velaro.icon name="check" /> Salvar configurações</button>
        </div>
      </form>
    </div>

    @include('portal.precos.partials.filtros')

    {{-- ─────────────── TABELA ─────────────── --}}
    <div class="card">
      <div class="tabs">
        @foreach($abas as $valor => $rotulo)
          <a class="tab @if($filtros['aba'] === $valor) is-on @endif"
             href="{{ request()->fullUrlWithQuery(['aba' => $valor, 'page' => null]) }}">{{ $rotulo }}</a>
        @endforeach
      </div>

      @include('portal.precos.partials.tabela')
    </div>

    <p class="notice notice--gold"><x-velaro.icon name="info" /><span>O preço B2C é definido por você — por
      multiplicador, percentual, edição manual ou promoção. O <strong>custo Velaro nunca é exposto ao
      consumidor</strong>: ele aparece só aqui, no seu portal.</span></p>
  </div>

  <div class="stack">

    {{-- ─────────────── RESUMO DE MARGENS ─────────────── --}}
    <div class="card">
      <div class="card__head"><h2 class="title">Resumo de margens</h2></div>
      @php($total = max(1, $resumo['total']))
      @php($volta = 376.99)
      @php($fatiaIdeal = $resumo['ideal'] / $total * $volta)
      @php($fatiaBaixa = $resumo['baixa'] / $total * $volta)
      @php($fatiaCritica = $resumo['critica'] / $total * $volta)
      <div class="donutbox">
        <svg class="donut" viewBox="0 0 160 160" role="img"
             aria-label="Distribuição dos {{ $resumo['total'] }} produtos por faixa de margem">
          <circle cx="80" cy="80" r="60" fill="none" stroke="var(--border)" stroke-width="18" />
          <circle cx="80" cy="80" r="60" fill="none" stroke="var(--color-success-500)" stroke-width="18"
                  stroke-dasharray="{{ $fatiaIdeal }} {{ $volta }}" transform="rotate(-90 80 80)" />
          <circle cx="80" cy="80" r="60" fill="none" stroke="var(--color-warning-500)" stroke-width="18"
                  stroke-dasharray="{{ $fatiaBaixa }} {{ $volta }}"
                  stroke-dashoffset="{{ -$fatiaIdeal }}" transform="rotate(-90 80 80)" />
          <circle cx="80" cy="80" r="60" fill="none" stroke="var(--color-error-500)" stroke-width="18"
                  stroke-dasharray="{{ $fatiaCritica }} {{ $volta }}"
                  stroke-dashoffset="{{ -($fatiaIdeal + $fatiaBaixa) }}" transform="rotate(-90 80 80)" />
        </svg>
        <div class="donutbox__mid">
          <strong>{{ $margemMediaFmt }}</strong><small>Margem média</small>
        </div>
      </div>
      <ul class="legend">
        <li><i style="background:var(--color-success-500)"></i>Margem ideal (≥ {{ $margemMinimaFmt }})
          <b>{{ $resumo['ideal'] }} produtos</b></li>
        <li><i style="background:var(--color-warning-500)"></i>Margem baixa ({{ $margemCriticaFmt }} – {{ $margemMinimaFmt }})
          <b>{{ $resumo['baixa'] }} produtos</b></li>
        <li><i style="background:var(--color-error-500)"></i>Margem crítica (&lt; {{ $margemCriticaFmt }})
          <b>{{ $resumo['critica'] }} produtos</b></li>
      </ul>
    </div>

    {{-- ─────────────── CONFIGURAÇÃO RÁPIDA ─────────────── --}}
    <div class="card">
      <div class="card__head"><h2 class="title">Configuração rápida</h2></div>
      <form method="POST" action="{{ route('portal.precos.update') }}">
        @csrf
        @method('PUT')
        @include('portal.precos.partials.ocultos', ['ocultos' => [
          'pricing_model', 'multiplier', 'margin_global', 'rounding', 'rule_scope',
          'apply_to_all', 'allow_manual_override', 'allow_promotional_prices',
        ], 'valores' => $valores])

        @foreach(['margin_min' => 'Margem mínima desejada', 'margin_ideal' => 'Margem ideal', 'margin_max' => 'Margem máxima'] as $campo => $rotulo)
          <div class="field" @error($campo) data-state="error" @enderror>
            <label for="preco-{{ $campo }}">{{ $rotulo }}<i class="req">*</i></label>
            <span class="input-shell input-shell--suffix">
              <input class="input" type="number" id="preco-{{ $campo }}" name="{{ $campo }}" required
                     min="0" max="99.99" step="0.01" value="{{ old($campo, $valores[$campo]) }}">
              <span class="input-shell__suffix">%</span>
            </span>
            @error($campo)<small class="field__message">{{ $message }}</small>@enderror
          </div>
        @endforeach

        <button class="btn btn--gold" type="submit" name="action" value="{{ $acaoAplicar }}"
                style="width:100%;margin-top:var(--space-3)">
          <x-velaro.icon name="check" /> Aplicar para todos os produtos</button>
      </form>
    </div>

    <div class="card">
      <div class="card__head"><h2 class="title">Dicas para melhores margens</h2></div>
      <ul class="lst">
        <li>Margens entre 40% e 60% são ideais para o mercado.</li>
        <li>Considere o valor percebido e seu público-alvo.</li>
        <li>Revise seus preços periodicamente.</li>
      </ul>
      <a class="link-gold" href="{{ route('portal.ajuda') }}">Saiba mais sobre precificação →</a>
    </div>
  </div>
</div>

</x-velaro.layouts.portal>
