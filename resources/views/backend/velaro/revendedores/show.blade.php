{{--
[Modulo: resources/views/backend/velaro/revendedores]
@Author: André Gomes ( @acidcode )
@since 2026-09-06
Tela 3.10 — ficha do revendedor: dados, CNAEs, documentos, aceites, loja e historico.
--}}
<x-velaro.layouts.master :title="$reseller->trade_name">

  <div class="page-head">
    <div>
      <a class="link-gold" href="{{ route('backend.revendedores.index') }}">← Voltar para revendedores</a>
      <h1 class="display-md">{{ $reseller->trade_name }}</h1>
      <p class="lede">
        {{ $reseller->legal_name }} · {{ $reseller->code ?? $reseller->protocol }}
        @if($reseller->registration_type === \App\Models\Reseller::REGISTRATION_TYPE_MANUAL)
          · <span class="chip chip--brand chip--flat">Cadastro manual</span>
        @endif
      </p>
    </div>
    <div class="row row--wrap">
      @if($reseller->status === \App\Models\Reseller::STATUS_APPROVED)
        <span class="chip chip--ok">Ativo</span>
      @elseif($reseller->status === \App\Models\Reseller::STATUS_REJECTED)
        <span class="chip chip--danger">Reprovado</span>
      @elseif($reseller->status === \App\Models\Reseller::STATUS_INACTIVE)
        <span class="chip chip--neutral">Inativo</span>
      @else
        <span class="chip chip--warn">Pendente de aprovação</span>
      @endif
    </div>
  </div>

  @if(session('status'))
    <p class="notice notice--ok"><x-velaro.icon name="check" /><span>{{ session('status') }}</span></p>
  @endif

  <div class="split split--wide">
    <div class="stack">

      <div class="card">
        <div class="card__head"><h2 class="title">Dados da empresa</h2></div>
        <div class="datarow"><span class="datarow__k">Razão social</span><span class="datarow__v">{{ $reseller->legal_name }}</span></div>
        <div class="datarow"><span class="datarow__k">CNPJ</span><span class="datarow__v num">{{ $reseller->cnpj }}</span></div>
        <div class="datarow"><span class="datarow__k">Inscrição estadual</span><span class="datarow__v num">{{ $reseller->state_registration ?: '—' }}</span></div>
        <div class="datarow"><span class="datarow__k">Responsável</span><span class="datarow__v">{{ $reseller->contact_name }}</span></div>
        <div class="datarow"><span class="datarow__k">E-mail</span><span class="datarow__v">{{ $reseller->email }}</span></div>
        <div class="datarow"><span class="datarow__k">Telefone / WhatsApp</span><span class="datarow__v num">{{ $reseller->phone }} · {{ $reseller->whatsapp ?: '—' }}</span></div>
        <div class="datarow">
          <span class="datarow__k">Endereço</span>
          <span class="datarow__v">
            {{ $reseller->street }}, {{ $reseller->street_number }}@if($reseller->address_complement) — {{ $reseller->address_complement }}@endif<br>
            {{ $reseller->district }} · {{ $reseller->city }} / {{ $reseller->state }} · {{ $reseller->postal_code }}
          </span>
        </div>
      </div>

      <div class="card">
        <div class="card__head"><h2 class="title">CNAEs</h2></div>
        @forelse($reseller->cnaes as $cnae)
          <div class="datarow">
            <span class="datarow__k num">{{ $cnae->code }}</span>
            <span class="datarow__v">
              {{ $cnae->description }}
              @if($cnae->compatible === null)
                <span class="chip chip--neutral chip--flat">Em verificação</span>
              @elseif($cnae->compatible)
                <span class="chip chip--ok chip--flat">Compatível</span>
              @else
                <span class="chip chip--danger chip--flat">Incompatível</span>
              @endif
            </span>
          </div>
        @empty
          <p class="lede" style="margin:0">Nenhum CNAE registrado.</p>
        @endforelse
      </div>

      <div class="card">
        <div class="card__head"><h2 class="title">Documentos</h2></div>
        @forelse($reseller->documents as $documento)
          <div class="datarow">
            <span class="datarow__k"><x-velaro.icon name="doc" /> {{ $documento->original_name }}</span>
            <span class="datarow__v num">{{ number_format($documento->size_bytes / 1024, 0, ',', '.') }} KB</span>
          </div>
        @empty
          <p class="lede" style="margin:0">Nenhum documento anexado.</p>
        @endforelse
      </div>

      <div class="card">
        <div class="card__head"><h2 class="title">Histórico</h2></div>
        <ul class="timeline">
          @forelse($reseller->statusEvents as $evento)
            <li class="tl tl--done">
              <span class="tl__body">
                <strong>{{ $evento->to_status }}</strong>
                <small>{{ $evento->created_at?->format('d/m/Y H:i') }} · {{ $evento->actor?->name ?? 'Lojista' }}</small>
                @if($evento->note)<p class="lede" style="margin:4px 0 0">{{ $evento->note }}</p>@endif
              </span>
            </li>
          @empty
            <li class="tl"><span class="tl__body"><small>Sem eventos registrados.</small></span></li>
          @endforelse
        </ul>
      </div>

    </div>

    <div class="stack">

      <div class="card">
        <div class="card__head"><h2 class="title">Verificação de CNAEs</h2></div>
        @if($verificacao === null)
          <p class="lede" style="margin:0">Este cadastro ainda não passou pela verificação.</p>
        @else
          <ul class="cklist">
            @foreach([
              'CNPJ válido' => $verificacao->cnpj_valido,
              'Empresa ativa' => $verificacao->empresa_ativa,
              'CNAEs compatíveis' => $verificacao->cnaes_compativeis,
            ] as $rotulo => $atende)
              <li><x-velaro.icon :name="$atende ? 'check' : 'x'" /> {{ $rotulo }}</li>
            @endforeach
          </ul>
          <div class="datarow">
            <span class="datarow__k">Resultado</span>
            <span class="datarow__v">
              @if($verificacao->cnaes_compativeis)
                <span class="chip chip--ok">Compatível / Pré-aprovado</span>
              @else
                <span class="chip chip--danger">Incompatível</span>
              @endif
            </span>
          </div>
        @endif
      </div>

      <div class="card">
        <div class="card__head"><h2 class="title">Aceites do lojista</h2></div>
        @forelse($reseller->consents as $aceite)
          <div class="datarow">
            <span class="datarow__k">{{ $aceite->type }}</span>
            <span class="datarow__v">
              <span class="num">{{ $aceite->document_version }}</span>
              @if($aceite->revoked_at)
                <span class="chip chip--danger chip--flat">Revogado</span>
              @elseif($aceite->granted)
                <span class="chip chip--ok chip--flat">{{ $aceite->granted_at?->format('d/m/Y') }}</span>
              @else
                <span class="chip chip--neutral chip--flat">Não aceito</span>
              @endif
            </span>
          </div>
        @empty
          <p class="lede" style="margin:0">Nenhum aceite registrado.</p>
        @endforelse
      </div>

      @if($reseller->store)
        <div class="card">
          <div class="card__head"><h2 class="title">Vitrine</h2></div>
          <div class="datarow"><span class="datarow__k">Loja</span><span class="datarow__v">{{ $reseller->store->name }}</span></div>
          <div class="datarow">
            <span class="datarow__k">Endereço</span>
            <span class="datarow__v"><a class="link-gold" href="{{ route('vitrine.index', $reseller->store) }}">/loja/{{ $reseller->store->slug }}</a></span>
          </div>
        </div>
      @endif

      @if($reseller->internal_notes)
        <div class="card">
          <div class="card__head"><h2 class="title">Observações internas</h2></div>
          <p class="lede" style="margin:0">{{ $reseller->internal_notes }}</p>
        </div>
      @endif

      @if($podeImpersonar)
        <div class="card">
          <div class="card__head"><h2 class="title">Ver como revendedor</h2></div>
          {{-- A permissão existe, mas a sessão de impersonate ainda não: ela cruza o
               LoginResponse e o middleware do Portal, que são de outro território.
               A tela diz o estado real em vez de oferecer um botão inerte. --}}
          <p class="lede" style="margin:0">
            Ação prevista no escopo (§2 e §7), com registro de início e fim em <code>audit_logs</code>.
            Ainda não habilitada — depende da sessão de impersonate no Portal.
          </p>
        </div>
      @endif

    </div>
  </div>

</x-velaro.layouts.master>
