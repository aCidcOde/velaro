{{--
[Modulo: resources/views/portal/pedidos/partials]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 2.11 — bloco de retirada do pedido: log de notificacoes, previa da mensagem e confirmacao da retirada.
--}}
{{--
  A tela 2.11 é um ESTADO do detalhe do pedido, não uma rota à parte: o bloco
  aparece quando o pedido chega à loja (`ready_for_pickup`) e continua depois de
  retirado, quando deixa de ser painel de disparo e vira comprovante.
--}}
<section class="split split--wide" aria-label="Pedido pronto para retirada">
  <div class="stack">

    <div class="card">
      <div class="card__head">
        <h2 class="title">Notificações enviadas</h2>
        <span class="chip chip--ok">{{ $retirada['retirado'] ? 'Retirado' : 'Pronto para retirada' }}</span>
      </div>

      @if($retirada['chegadaEm'])
        <p class="lede" style="font-size:var(--text-sm)">
          Chegada na loja confirmada em {{ $retirada['chegadaEm'] }} — a comunicação ao cliente sai a partir daí.
        </p>
      @endif

      @if($retirada['notificacoes'] === [])
        <p class="notice notice--info">
          <x-velaro.icon name="info" />
          <span><strong>Nenhum envio registrado ainda.</strong> O disparo automático na chegada grava aqui o
            canal, o destinatário e a hora de cada mensagem.</span>
        </p>
      @else
        @foreach($retirada['notificacoes'] as $envio)
          <div class="datarow">
            <span class="datarow__k">
              <x-velaro.icon :name="$envio['icone']" />
              <span>
                <strong style="display:block;color:var(--ink)">{{ $envio['destinatarioTipo'] }} · {{ $envio['canal'] }}</strong>
                <small>{{ $envio['destinatario'] ?? '—' }}@if($envio['enviadoEm']) · enviado em {{ $envio['enviadoEm'] }}@endif</small>
                @if($envio['erro'])<small style="color:var(--color-error-700)">{{ $envio['erro'] }}</small>@endif
              </span>
            </span>
            <span class="datarow__v"><span class="chip {{ $envio['chip'] }} chip--flat">{{ $envio['situacao'] }}</span></span>
          </div>
        @endforeach
      @endif

      <p class="notice notice--gold">
        <x-velaro.icon name="info" />
        <span><strong>Reenvio pendente de rota.</strong> O disparo e o reenvio da notificação são operação de
          escrita e ainda não têm verbo nas rotas contratadas do portal — o histórico acima já lê
          <code>notification_logs</code>.</span>
      </p>
    </div>

    <div class="card">
      <div class="card__head"><h2 class="title">Confirmação de retirada</h2></div>

      @if($retirada['retirado'])
        <p class="notice notice--ok">
          <x-velaro.icon name="check" />
          <span><strong>Retirada confirmada.</strong> O pedido saiu da loja com o cliente.</span>
        </p>
        <div class="datarow">
          <span class="datarow__k"><x-velaro.icon name="user" /> Retirado por</span>
          <span class="datarow__v">{{ $retirada['confirmacao']['retiradoPor'] ?? '—' }}
            @if($retirada['confirmacao']['peloProprioCliente'])<small class="muted">· o próprio cliente</small>@endif
          </span>
        </div>
        <div class="datarow">
          <span class="datarow__k"><x-velaro.icon name="card" /> Documento</span>
          <span class="datarow__v">{{ $retirada['confirmacao']['documento'] ?? '—' }}</span>
        </div>
        <div class="datarow">
          <span class="datarow__k"><x-velaro.icon name="clock" /> Data e hora da retirada</span>
          <span class="datarow__v">{{ $retirada['confirmacao']['em'] ?? '—' }}</span>
        </div>
      @else
        <p class="lede" style="font-size:var(--text-sm)">Confirme abaixo quando o pedido for retirado pelo cliente na loja.</p>
        <div class="fgrid fgrid--3">
          <div class="field">
            <label for="retirada-nome">Retirado por</label>
            <input class="input" id="retirada-nome" type="text" name="picked_up_by_name" placeholder="Nome de quem retirou" disabled>
          </div>
          <div class="field">
            <label for="retirada-documento">Documento</label>
            <input class="input" id="retirada-documento" type="text" name="picked_up_by_document" placeholder="CPF ou RG" disabled>
          </div>
          <div class="field">
            <label for="retirada-em">Data e hora da retirada</label>
            <input class="input" id="retirada-em" type="datetime-local" name="picked_up_at" disabled>
          </div>
        </div>
        <p class="notice notice--gold">
          <x-velaro.icon name="info" />
          <span><strong>Confirmação pendente de rota.</strong> Os campos são os de <code>orders</code> que
            registram a retirada; o verbo de escrita ainda não faz parte das rotas contratadas do portal.</span>
        </p>
      @endif
    </div>
  </div>

  <div class="stack">
    {{-- Regra 1 da 2.11: a mensagem sai EM NOME DO REVENDEDOR. A marca Velaro não
         aparece para o consumidor final — quem assina é a loja. --}}
    <div class="card">
      <div class="card__head"><h2 class="title">Como o cliente recebe</h2></div>
      <div class="phone">
        <div class="phone__screen">
          <div class="phone__time">{{ $retirada['previa']['relogio']['hora'] }}<small>{{ $retirada['previa']['relogio']['data'] }}</small></div>
          <div class="notif">
            <div class="notif__head">
              <span class="notif__app">WhatsApp</span>
              <span>{{ $retirada['confirmacao']['em'] ? 'enviado' : 'prévia' }}</span>
            </div>
            <strong>{{ $retirada['previa']['remetente'] }}</strong>
            @foreach($retirada['previa']['whatsapp'] as $linha)
              <p @class(['notif__meta' => $linha['tom'] === 'meta', 'notif__ok' => $linha['tom'] === 'ok'])>{{ $linha['texto'] }}</p>
            @endforeach
          </div>

          <div class="notif" style="margin-top:var(--space-3)">
            <div class="notif__head"><span class="notif__app">E-mail</span><span>prévia</span></div>
            <strong>{{ $retirada['previa']['remetente'] }}</strong>
            <p><b>{{ $retirada['previa']['email']['assunto'] }}</b></p>
            <p>{{ $retirada['previa']['email']['corpo'] }}</p>
            <p class="notif__meta">{{ $retirada['previa']['email']['assinatura'] }}</p>
          </div>
        </div>
      </div>

      @foreach($retirada['previa']['canais'] as $canal)
        <div class="datarow">
          <span class="datarow__k">{{ $canal['rotulo'] }}</span>
          <span class="datarow__v">{{ $canal['destino'] ?? '—' }}</span>
        </div>
      @endforeach
    </div>

    <p class="notice notice--gold">
      <x-velaro.icon name="store" />
      <span>A mensagem sai <strong>em nome do revendedor</strong>. A marca Velaro não aparece para o
        consumidor final (Anexo I §4.12).</span>
    </p>

    <p class="notice notice--info">
      <x-velaro.icon name="info" />
      <span>Comunicação transacional. Não depende de consentimento de marketing e é registrada
        separadamente das campanhas promocionais (Anexo I §6).</span>
    </p>
  </div>
</section>
