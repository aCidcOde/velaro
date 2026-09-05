{{--
/*
[Modulo: resources/views/portal/suporte]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 2.8 do Portal: acesso rapido, fila de chamados do lojista e os canais de atendimento.
*/
--}}
<x-velaro.layouts.portal title="Suporte" titulo="Suporte">

<div class="page-head"><div>
  <h1 class="display-md">Suporte</h1>
  <p class="lede">Estamos aqui para ajudar você a vender mais e ter a melhor experiência.</p>
</div></div>

@if(session('status'))
  <p class="notice notice--ok" style="margin-bottom:var(--space-4)" role="status">
    <x-velaro.icon name="check" /><span>{{ session('status') }}</span></p>
@endif

<div class="split split--wide">
  <div class="stack">

    {{-- ─────────────── ACESSO RÁPIDO ─────────────── --}}
    <div class="card">
      <div class="card__head"><h2 class="title">Acesso rápido</h2></div>
      <div class="quickgrid">
        <a class="quickcard" href="{{ route('portal.suporte.create') }}">
          <x-velaro.icon name="support" />
          <span><strong>Abrir chamado</strong><small>Fale com nossa equipe</small></span><b>›</b></a>
        <a class="quickcard" href="{{ route('portal.ajuda') }}">
          <x-velaro.icon name="info" />
          <span><strong>Perguntas frequentes</strong><small>Tire suas dúvidas</small></span><b>›</b></a>
        <a class="quickcard" href="{{ route('portal.ajuda') }}">
          <x-velaro.icon name="book" />
          <span><strong>Guias e manuais</strong><small>Aprenda a usar a plataforma</small></span><b>›</b></a>
        <a class="quickcard" href="{{ route('portal.ajuda') }}">
          <x-velaro.icon name="eye" />
          <span><strong>Vídeos tutoriais</strong><small>Assista e aprenda</small></span><b>›</b></a>
        <a class="quickcard" href="https://wa.me/{{ preg_replace('/\D+/', '', $canais['whatsapp']) }}"
           target="_blank" rel="noopener">
          <x-velaro.icon name="whats" />
          <span><strong>WhatsApp</strong><small>Atendimento rápido</small></span><b>›</b></a>
      </div>
    </div>

    {{-- ─────────────── MEUS CHAMADOS ─────────────── --}}
    <div class="card">
      <div class="card__head"><h2 class="title">Meus chamados</h2></div>

      <form class="filters" method="GET" action="{{ route('portal.suporte.index') }}" role="search">
        <span class="input-shell" style="flex:1;min-width:240px">
          <x-velaro.icon name="search" class="ic input-shell__icon" />
          <input class="input input--compact" type="search" name="q" value="{{ $filtros['q'] }}"
                 placeholder="Buscar por número, assunto ou mensagem…"
                 aria-label="Buscar por número, assunto ou mensagem">
        </span>

        <label class="fbox"><span>Status</span>
          <select class="select select--compact" name="status">
            <option value="">Todos</option>
            @foreach($statusDisponiveis as $valor => $texto)
              <option value="{{ $valor }}" @selected($filtros['status'] === $valor)>{{ $texto['rotulo'] }}</option>
            @endforeach
          </select>
        </label>

        <label class="fbox"><span>Categoria</span>
          <select class="select select--compact" name="categoria">
            <option value="">Todas</option>
            @foreach($categorias as $categoria)
              <option value="{{ $categoria }}" @selected($filtros['categoria'] === $categoria)>{{ $categoria }}</option>
            @endforeach
          </select>
        </label>

        <label class="fbox"><span>Período</span>
          <select class="select select--compact" name="periodo">
            @foreach($periodos as $valor => $rotulo)
              <option value="{{ $valor }}" @selected($filtros['periodo'] === (string) $valor)>{{ $rotulo }}</option>
            @endforeach
          </select>
        </label>

        <div class="row row--wrap push">
          <button class="btn btn--secondary btn--sm" type="submit"><x-velaro.icon name="filter" /> Filtrar</button>
          <a class="btn btn--secondary btn--sm" href="{{ route('portal.suporte.index') }}">Limpar</a>
        </div>
      </form>

      <div class="table-scroll"><table class="table">
        <thead><tr>
          <th>Nº do chamado</th>
          <th>Assunto</th>
          <th>Categoria</th>
          <th>Status</th>
          <th>Prioridade</th>
          <th>Última atualização</th>
          <th class="cell-num">Ações</th>
        </tr></thead>
        <tbody>
        @forelse($chamados as $chamado)
          @php($status = $statusDisponiveis[$chamado->status] ?? ['rotulo' => $chamado->status, 'chip' => 'chip--neutral'])
          @php($prioridade = $prioridades[$chamado->priority] ?? ['rotulo' => $chamado->priority, 'chip' => 'chip--neutral'])
          <tr>
            <td><strong style="color:var(--ink)">{{ $chamado->code }}</strong></td>
            <td>
              <strong style="color:var(--ink)">{{ $chamado->subject }}</strong>
              @if($chamado->order)
                <br><small class="muted">Pedido {{ $chamado->order->public_number }}</small>
              @elseif($chamado->customer)
                <br><small class="muted">Cliente {{ $chamado->customer->name }}</small>
              @endif
            </td>
            <td>{{ $chamado->category }}</td>
            <td><span class="chip {{ $status['chip'] }}">{{ $status['rotulo'] }}</span></td>
            <td><span class="chip {{ $prioridade['chip'] }}">{{ $prioridade['rotulo'] }}</span></td>
            <td>{{ $chamado->updated_at?->format('d/m/Y H:i') }}</td>
            <td class="cell-num">
              <a class="link-gold" href="{{ route('portal.suporte.show', $chamado) }}">Abrir</a>
            </td>
          </tr>
        @empty
          <tr><td colspan="7"><p class="muted" style="margin:0">Nenhum chamado no período e nos filtros
            selecionados.</p></td></tr>
        @endforelse
        </tbody>
      </table></div>

      <div class="tfoot">
        <div class="pagination">
          <span class="muted">
            @if($chamados->total() > 0)
              Exibindo {{ $chamados->firstItem() }} a {{ $chamados->lastItem() }} de {{ $chamados->total() }} chamados
            @else
              Nenhum chamado nesta busca
            @endif
          </span>
          <span class="pnums">
            @if($chamados->onFirstPage())
              <span class="pnum" aria-hidden="true">‹</span>
            @else
              <a class="pnum" href="{{ $chamados->previousPageUrl() }}" rel="prev" aria-label="Página anterior">‹</a>
            @endif
            @foreach($chamados->getUrlRange(max(1, $chamados->currentPage() - 2), min($chamados->lastPage(), $chamados->currentPage() + 2)) as $numero => $url)
              <a class="pnum @if($numero === $chamados->currentPage()) is-on @endif" href="{{ $url }}"
                 @if($numero === $chamados->currentPage()) aria-current="page" @endif>{{ $numero }}</a>
            @endforeach
            @if($chamados->hasMorePages())
              <a class="pnum" href="{{ $chamados->nextPageUrl() }}" rel="next" aria-label="Próxima página">›</a>
            @else
              <span class="pnum" aria-hidden="true">›</span>
            @endif
          </span>
          <span class="muted">{{ $porPagina }} por página</span>
        </div>
      </div>
    </div>

    <p class="notice notice--gold"><x-velaro.icon name="info" /><span>O atendimento ocorre entre
      <strong>a Velaro e o revendedor</strong>. O cliente final aparece apenas como pessoa vinculada ao pedido
      e não participa da conversa.</span></p>
  </div>

  <div class="stack">
    <div class="card">
      <div class="card__head"><h2 class="title">Status do suporte</h2></div>
      <div class="grid g2">
        <div class="ministat"><strong>{{ $numeros['total'] }}</strong><small>Total de chamados</small></div>
        <div class="ministat"><strong>{{ $numeros['em_atendimento'] }}</strong><small>Em atendimento</small></div>
        <div class="ministat"><strong>{{ $numeros['aguardando'] }}</strong><small>Aguardando retorno</small></div>
        <div class="ministat"><strong>{{ $numeros['respondidos'] }}</strong><small>Respondidos</small></div>
      </div>
    </div>

    <div class="card">
      <div class="card__head"><h2 class="title">Horário de atendimento</h2></div>
      <div class="datarow">
        <span class="datarow__k"><x-velaro.icon name="clock" /> Segunda a sexta-feira</span>
        <span class="datarow__v">08h às 18h</span>
      </div>
      <small class="fhint">Horário de Brasília · exceto feriados</small>
    </div>

    @include('portal.suporte.partials.canais')

    <div class="card">
      <div class="card__head"><h2 class="title">Central de ajuda completa</h2></div>
      <p class="lede" style="font-size:var(--text-sm)">Acesse tutoriais, guias e respostas para as dúvidas mais
        comuns.</p>
      <a class="btn btn--secondary" href="{{ route('portal.ajuda') }}">Acessar central de ajuda →</a>
    </div>
  </div>
</div>

</x-velaro.layouts.portal>
