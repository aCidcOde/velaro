{{--
[Modulo: resources/views/backend/velaro/revendedores]
@Author: André Gomes ( @acidcode )
@since 2026-09-06
Tela 3.10 — base de revendedores: KPIs, filtros, tabela e a gaveta do cadastro manual.
--}}
<x-velaro.layouts.master title="Revendedores">

  <div class="page-head">
    <div>
      <h1 class="display-md">Revendedores</h1>
      <p class="lede">Gerencie os revendedores ativos e realize cadastros manuais com verificação de CNAEs.</p>
    </div>
    @if($podeCadastrar)
      <div class="row row--wrap">
        {{-- O formulário é um <details>: o design system não carrega Alpine, e o
             drawer do mockup precisa abrir sem JS. --}}
        <a class="btn btn--primary" href="#cadastro-manual"><x-velaro.icon name="plus" /> Novo revendedor</a>
      </div>
    @endif
  </div>

  @if(session('status'))
    <p class="notice notice--ok"><x-velaro.icon name="check" /><span>{{ session('status') }}</span></p>
  @endif

  <section class="grid g4" aria-label="Indicadores dos revendedores">
    @foreach($kpis as $kpi)
      <div class="card card--compact">
        <div class="kpi">
          <span class="kpi__icon kpi__icon--{{ $kpi['tom'] }}"><x-velaro.icon :name="$kpi['icone']" /></span>
          <div>
            <div class="kpi__label">{{ $kpi['rotulo'] }}</div>
            <div class="kpi__value">{{ $kpi['valor'] }}</div>
            @if($kpi['filtro'] !== [])
              <a class="kpi__delta" href="{{ route('backend.revendedores.index', $kpi['filtro']) }}">Ver na lista →</a>
            @endif
          </div>
        </div>
      </div>
    @endforeach
  </section>

  <form class="filters" method="GET" action="{{ route('backend.revendedores.index') }}">
    <label class="fsearch">
      <x-velaro.icon name="search" />
      <input class="input input--bare" type="search" name="busca" value="{{ $filtros['busca'] }}"
             placeholder="Buscar revendedor…" aria-label="Buscar revendedor">
    </label>
    <label class="fbox">
      <span>Status</span>
      <select class="select select--compact" name="status">
        <option value="">Todos os status</option>
        @foreach([
          \App\Models\Reseller::STATUS_APPROVED => 'Ativo',
          \App\Models\Reseller::STATUS_PENDING => 'Pendente',
          \App\Models\Reseller::STATUS_AWAITING_INFO => 'Aguardando informações',
          \App\Models\Reseller::STATUS_REJECTED => 'Reprovado',
          \App\Models\Reseller::STATUS_INACTIVE => 'Inativo',
        ] as $valor => $rotulo)
          <option value="{{ $valor }}" @selected($filtros['status'] === $valor)>{{ $rotulo }}</option>
        @endforeach
      </select>
    </label>
    <div class="row row--wrap push">
      <button class="btn btn--secondary btn--sm" type="submit"><x-velaro.icon name="filter" /> Filtrar</button>
    </div>
  </form>

  <div class="card">
    <div class="table-scroll">
      <table class="table">
        <thead>
          <tr>
            <th>Revendedor</th><th>Cidade / UF</th><th>Responsável</th><th>Status</th>
            <th>Tipo de cadastro</th><th>CNAE verificado</th><th>Data</th><th class="cell-num">Ações</th>
          </tr>
        </thead>
        <tbody>
          @forelse($revendedores as $revendedor)
            @php($verificacao = $revendedor->verifications->first())
            <tr>
              <td>
                <strong style="color:var(--ink)">{{ $revendedor->trade_name }}</strong>
                <small class="muted">{{ $revendedor->code ?? $revendedor->protocol }}</small>
              </td>
              <td>{{ $revendedor->city }} / {{ $revendedor->state }}</td>
              <td>{{ $revendedor->contact_name }}</td>
              <td>
                @if($revendedor->status === \App\Models\Reseller::STATUS_APPROVED)
                  <span class="chip chip--ok chip--flat">Ativo</span>
                @elseif($revendedor->status === \App\Models\Reseller::STATUS_REJECTED)
                  <span class="chip chip--danger chip--flat">Reprovado</span>
                @elseif($revendedor->status === \App\Models\Reseller::STATUS_INACTIVE)
                  <span class="chip chip--neutral chip--flat">Inativo</span>
                @else
                  <span class="chip chip--warn chip--flat">Pendente</span>
                @endif
              </td>
              <td>
                @if($revendedor->registration_type === \App\Models\Reseller::REGISTRATION_TYPE_MANUAL)
                  <span class="chip chip--brand chip--flat">Manual</span>
                @else
                  <span class="chip chip--neutral chip--flat">Automático</span>
                @endif
              </td>
              <td>
                @if($verificacao === null)
                  <span class="chip chip--neutral chip--flat">Em verificação</span>
                @elseif($verificacao->cnaes_compativeis)
                  <span class="chip chip--ok chip--flat">Compatível</span>
                @else
                  <span class="chip chip--danger chip--flat">Incompatível</span>
                @endif
              </td>
              <td><span class="num">{{ $revendedor->created_at?->format('d/m/Y') }}</span></td>
              <td class="cell-num">
                <a class="btn btn--secondary btn--sm" href="{{ route('backend.revendedores.show', $revendedor) }}">
                  <x-velaro.icon name="eye" /> Ver
                </a>
              </td>
            </tr>
          @empty
            <tr><td colspan="8"><p class="lede" style="margin:0">Nenhum revendedor com esses filtros.</p></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($revendedores->hasPages())
      <div class="pag">{{ $revendedores->links() }}</div>
    @endif
  </div>

  @if($podeCadastrar)
    <details class="card" id="cadastro-manual">
      <summary class="card__head"><h2 class="title">Cadastro manual de revendedor</h2></summary>

      @if($errors->any())
        <p class="notice notice--danger">
          <x-velaro.icon name="x" />
          <span>Confira os campos destacados abaixo.</span>
        </p>
      @endif

      <form method="POST" action="{{ route('backend.revendedores.store') }}" class="stack">
        @csrf
        <div class="fgrid fgrid--2">
          @foreach([
            'trade_name' => ['Nome fantasia', true],
            'legal_name' => ['Razão social', true],
            'cnpj' => ['CNPJ', true],
            'contact_name' => ['Responsável', true],
            'email' => ['E-mail', true],
            'phone' => ['Telefone', true],
            'whatsapp' => ['WhatsApp', false],
            'state_registration' => ['Inscrição estadual', false],
            'postal_code' => ['CEP', true],
            'street' => ['Endereço', true],
            'street_number' => ['Número', true],
            'address_complement' => ['Complemento', false],
            'district' => ['Bairro', true],
            'city' => ['Cidade', true],
            'state' => ['UF', true],
          ] as $campo => [$rotulo, $obrigatorio])
            <div class="field" @if($errors->has($campo)) data-state="error" @endif>
              <label for="{{ $campo }}">{{ $rotulo }}@if($obrigatorio)<i class="req">*</i>@endif</label>
              <input class="input" id="{{ $campo }}" name="{{ $campo }}" value="{{ old($campo) }}"
                     @if($campo === 'email') type="email" @else type="text" @endif
                     @if($campo === 'state') maxlength="2" @endif>
              @error($campo)<small class="field__message">{{ $message }}</small>@enderror
            </div>
          @endforeach
          <div class="field field--full">
            <label for="internal_notes">Observações (internas)</label>
            <textarea class="textarea" id="internal_notes" name="internal_notes" rows="3" maxlength="500">{{ old('internal_notes') }}</textarea>
          </div>
        </div>
        <div class="row row--wrap">
          <button class="btn btn--primary" type="submit"><x-velaro.icon name="check" /> Salvar cadastro</button>
        </div>
        <p class="notice notice--info">
          <x-velaro.icon name="info" />
          <span>O cadastro nasce pendente. A aprovação é a ação seguinte, na ficha do revendedor, e fica registrada com justificativa.</span>
        </p>
      </form>
    </details>
  @endif

</x-velaro.layouts.master>
