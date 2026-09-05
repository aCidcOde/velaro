{{--
[Modulo: resources/views/backend/velaro/pre-cadastros]
@Author: André Gomes ( @acidcode )
@since 2026-09-06
Tela 3.11 — detalhe da solicitacao: dados, CNAEs, triagem, documentos e as tres decisoes humanas.
--}}
<x-velaro.layouts.master :title="'Solicitação '.$reseller->protocol">

  <div class="page-head">
    <div>
      <a class="link-gold" href="{{ route('backend.pre-cadastros.index') }}">← Voltar para as solicitações</a>
      <h1 class="display-md">{{ $reseller->trade_name }}</h1>
      <p class="lede">Protocolo {{ $reseller->protocol }} · recebida em {{ $reseller->created_at?->format('d/m/Y \à\s H:i') }}</p>
    </div>
    <div class="row row--wrap">
      @if($reseller->status === \App\Models\Reseller::STATUS_AWAITING_INFO)
        <span class="chip chip--info">Aguardando informações do lojista</span>
      @elseif($reseller->status === \App\Models\Reseller::STATUS_APPROVED)
        <span class="chip chip--ok">Aprovado</span>
      @elseif($reseller->status === \App\Models\Reseller::STATUS_REJECTED)
        <span class="chip chip--danger">Reprovado</span>
      @else
        <span class="chip chip--warn">Aguardando decisão</span>
      @endif
    </div>
  </div>

  @if(session('status'))
    <p class="notice notice--ok"><x-velaro.icon name="check" /><span>{{ session('status') }}</span></p>
  @endif

  <div class="split split--wide">
    <div class="stack">

      <div class="card">
        <div class="card__head"><h2 class="title">Detalhes da solicitação</h2></div>
        <div class="datarow"><span class="datarow__k">Nome fantasia</span><span class="datarow__v">{{ $reseller->trade_name }}</span></div>
        <div class="datarow"><span class="datarow__k">Razão social</span><span class="datarow__v">{{ $reseller->legal_name }}</span></div>
        <div class="datarow"><span class="datarow__k">CNPJ</span><span class="datarow__v num">{{ $reseller->cnpj }}</span></div>
        <div class="datarow"><span class="datarow__k">Inscrição estadual</span><span class="datarow__v num">{{ $reseller->state_registration ?: '—' }}</span></div>
        <div class="datarow"><span class="datarow__k">Responsável</span><span class="datarow__v">{{ $reseller->contact_name }}</span></div>
        <div class="datarow"><span class="datarow__k">CPF do responsável</span><span class="datarow__v num">{{ $reseller->contact_cpf ?: '—' }}</span></div>
        <div class="datarow"><span class="datarow__k">E-mail</span><span class="datarow__v">{{ $reseller->email }}</span></div>
        <div class="datarow"><span class="datarow__k">Telefone / WhatsApp</span><span class="datarow__v num">{{ $reseller->phone }} · {{ $reseller->whatsapp ?: '—' }}</span></div>
        <div class="datarow"><span class="datarow__k">CEP</span><span class="datarow__v num">{{ $reseller->postal_code }}</span></div>
        <div class="datarow">
          <span class="datarow__k">Endereço</span>
          <span class="datarow__v">
            {{ $reseller->street }}, {{ $reseller->street_number }}@if($reseller->address_complement) — {{ $reseller->address_complement }}@endif<br>
            {{ $reseller->district }} · {{ $reseller->city }} / {{ $reseller->state }}
          </span>
        </div>
      </div>

      <div class="card">
        <div class="card__head"><h2 class="title">CNAEs informados</h2></div>
        @forelse($reseller->cnaes as $cnae)
          <div class="datarow">
            <span class="datarow__k num">{{ $cnae->code }}@if($cnae->is_primary) <span class="chip chip--brand chip--flat">Principal</span>@endif</span>
            <span class="datarow__v">
              {{ $cnae->description }}
              @if($cnae->compatible)
                <span class="chip chip--ok chip--flat">Compatível</span>
              @else
                <span class="chip chip--danger chip--flat">Incompatível</span>
              @endif
            </span>
          </div>
        @empty
          <p class="lede" style="margin:0">Nenhum CNAE informado na solicitação.</p>
        @endforelse
      </div>

      <div class="card">
        <div class="card__head"><h2 class="title">Documentos anexados</h2></div>
        @forelse($reseller->documents as $documento)
          <div class="datarow">
            <span class="datarow__k"><x-velaro.icon name="doc" /> {{ $documento->original_name }}</span>
            <span class="datarow__v">
              <span class="num">{{ number_format($documento->size_bytes / 1024, 0, ',', '.') }} KB</span>
              <span class="chip chip--ok chip--flat"><x-velaro.icon name="check" /> Enviado</span>
            </span>
          </div>
        @empty
          <p class="lede" style="margin:0">Nenhum documento anexado.</p>
        @endforelse
      </div>

      <div class="card">
        <div class="card__head"><h2 class="title">Histórico da solicitação</h2></div>
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
        <div class="card__head"><h2 class="title">Validação por IA</h2></div>
        @if($verificacao === null)
          {{-- Sem triagem não se inventa resultado: a decisão continua humana e a
               ausência precisa aparecer, não virar um "incompatível" falso. --}}
          <p class="lede" style="margin:0">Esta solicitação ainda não passou pela triagem automática.</p>
        @else
          <ul class="cklist">
            @foreach([
              'CNPJ válido' => $verificacao->cnpj_valido,
              'Empresa ativa' => $verificacao->empresa_ativa,
              'CNAEs compatíveis' => $verificacao->cnaes_compativeis,
              'Documentação enviada' => $verificacao->documentacao_enviada,
            ] as $rotulo => $atende)
              <li>
                <x-velaro.icon :name="$atende ? 'check' : 'x'" />
                {{ $rotulo }}
              </li>
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
          @if($verificacao->score !== null)
            <div class="datarow"><span class="datarow__k">Score</span><span class="datarow__v num">{{ $verificacao->score }}</span></div>
          @endif
        @endif
        <p class="notice notice--info">
          <x-velaro.icon name="info" />
          <span>A triagem é pré-aprovação. <strong>A decisão final é humana</strong> e fica registrada com justificativa (Anexo I §3.7).</span>
        </p>
      </div>

      @if($reseller->internal_notes)
        <div class="card">
          <div class="card__head"><h2 class="title">Observações internas</h2></div>
          <p class="lede" style="margin:0">{{ $reseller->internal_notes }}</p>
        </div>
      @endif

      @if($podeDecidir)
        <div class="card">
          <div class="card__head"><h2 class="title">Ações da solicitação</h2></div>

          @error('justificativa')
            <p class="notice notice--danger"><x-velaro.icon name="x" /><span>{{ $message }}</span></p>
          @enderror

          {{-- Uma justificativa por decisão, e ela é obrigatória: é o que o §7 exige
               em ação sensível e o que o lojista lê na tela 1.6. Cada ação é um
               <form> próprio para o POST levar só a sua justificativa. --}}
          @if($podeAprovar)
            <form method="POST" action="{{ route('backend.pre-cadastros.aprovar', $reseller) }}" class="stack">
              @csrf
              <div class="field">
                <label for="justificativa-aprovar">Justificativa da aprovação<i class="req">*</i></label>
                <textarea class="textarea" id="justificativa-aprovar" name="justificativa" rows="3"
                          placeholder="Registre o que sustentou a aprovação.">{{ old('justificativa') }}</textarea>
              </div>
              <button class="btn btn--primary" type="submit"><x-velaro.icon name="check" /> Aprovar cadastro</button>
            </form>
            <p class="notice notice--gold">
              <x-velaro.icon name="info" />
              <span>Ao aprovar, o revendedor poderá acessar a plataforma e realizar pedidos.</span>
            </p>
          @endif

          @if($podePedirInfo)
            <form method="POST" action="{{ route('backend.pre-cadastros.solicitar-informacoes', $reseller) }}" class="stack">
              @csrf
              <div class="field">
                <label for="justificativa-info">O que falta<i class="req">*</i></label>
                <textarea class="textarea" id="justificativa-info" name="justificativa" rows="3"
                          placeholder="Diga ao lojista qual documento ou informação precisa reenviar."></textarea>
              </div>
              <button class="btn btn--secondary" type="submit"><x-velaro.icon name="info" /> Solicitar informações adicionais</button>
            </form>
          @endif

          @if($podeReprovar)
            <form method="POST" action="{{ route('backend.pre-cadastros.reprovar', $reseller) }}" class="stack">
              @csrf
              <div class="field">
                <label for="justificativa-reprovar">Motivo da reprovação<i class="req">*</i></label>
                <textarea class="textarea" id="justificativa-reprovar" name="justificativa" rows="3"
                          placeholder="Registre o motivo da recusa."></textarea>
              </div>
              <button class="btn btn--danger" type="submit"><x-velaro.icon name="x" /> Reprovar cadastro</button>
            </form>
          @endif
        </div>
      @else
        <div class="card">
          <div class="card__head"><h2 class="title">Ações da solicitação</h2></div>
          <p class="lede" style="margin:0">
            Esta solicitação já foi decidida e saiu da fila. O cadastro é acompanhado em
            <a class="link-gold" href="{{ route('backend.revendedores.show', $reseller) }}">Revendedores</a>.
          </p>
        </div>
      @endif

    </div>
  </div>

</x-velaro.layouts.master>
