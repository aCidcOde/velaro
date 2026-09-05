{{--
/*
[Modulo: resources/views/site]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 1.8 do site publico: canais diretos, formulario de contato e o aceite LGPD do lead.
*/
--}}
<x-velaro.layouts.site title="Fale conosco">

<x-slot:hero>
<section class="hero"><div class="hero__inner">
  <div>
    <span class="badge-b2b"><x-velaro.icon name="users" /> Atendimento a lojistas</span>
    <h1>FALE CONOSCO</h1>
    <p class="hero-sub">Uma conversa direta com quem fabrica a aliança.</p>
    <p class="lede">Dúvida sobre coleção, prazo de produção, condição comercial ou uma solicitação de cadastro
      em andamento: escreva para o time comercial da Velaro e receba retorno em até 1 dia útil.</p>
    <p class="hero__note"><x-velaro.icon name="info" /><span>A Velaro é fábrica e vende
      <strong>somente para lojistas com CNPJ</strong>. Este canal é atendimento — quem quer revender
      precisa do pré-cadastro.</span></p>
  </div>
  <div class="hero__art"><div style="width:250px"><x-velaro.ring variant="bicolor" style="width:100%;display:block" /></div></div>
</div></section>
</x-slot:hero>

<section class="band-light"><div class="band__inner">

  @if(session('status'))
    <p class="notice notice--ok" style="margin-bottom:var(--space-5)" role="status">
      <x-velaro.icon name="check" /><span>{{ session('status') }}</span></p>
  @endif

  {{-- Canais diretos: os mesmos valores do rodapé, lidos de settings com is_public. --}}
  <div class="identbar">
    <div class="identcell"><x-velaro.icon name="phone" style="color:var(--color-gold-600)" />
      <span><small>Telefone comercial</small><strong>{{ $canais['telefone'] }}</strong></span></div>
    <div class="identcell"><x-velaro.icon name="whats" style="color:var(--color-gold-600)" />
      <span><small>WhatsApp</small><strong>{{ $canais['whatsapp'] }}</strong></span></div>
    <div class="identcell"><x-velaro.icon name="mail" style="color:var(--color-gold-600)" />
      <span><small>E-mail comercial</small><strong>{{ $canais['email'] }}</strong></span></div>
    <div class="identcell"><x-velaro.icon name="clock" style="color:var(--color-gold-600)" />
      <span><small>Horário de atendimento</small><strong>{{ $canais['horario'] }}</strong></span></div>
  </div>

  <div class="split" style="--gcols:minmax(0,1fr) 400px;margin-top:var(--space-4)">

    <div class="card">
      <div class="card__head"><h2 class="title"><x-velaro.icon name="mail" /> Envie sua mensagem</h2></div>

      <form method="POST" action="{{ route('site.contato.store') }}">
        @csrf
        {{-- Página de partida do lead: alimenta a triagem, não o visitante. --}}
        <input type="hidden" name="origin" value="{{ old('origin', $origem) }}">

        <h3 class="fsec">Sua mensagem</h3>
        <div class="fgrid fgrid--2">

          <div class="field" @error('name') data-state="error" @enderror>
            <label for="lead-name">Nome<i class="req">*</i></label>
            <input class="input" type="text" id="lead-name" name="name" value="{{ old('name') }}"
              placeholder="Como podemos chamar você?" maxlength="255" autocomplete="name" required>
            @error('name')<small class="field__message">{{ $message }}</small>@enderror
          </div>

          <div class="field" @error('email') data-state="error" @enderror>
            <label for="lead-email">E-mail<i class="req">*</i></label>
            <input class="input" type="email" id="lead-email" name="email" value="{{ old('email') }}"
              placeholder="seuemail@exemplo.com.br" maxlength="255" autocomplete="email" required>
            @error('email')<small class="field__message">{{ $message }}</small>@enderror
          </div>

          <div class="field" @error('phone') data-state="error" @enderror>
            <label for="lead-phone">Telefone / WhatsApp<i class="req">*</i></label>
            <input class="input" type="tel" id="lead-phone" name="phone" value="{{ old('phone') }}"
              placeholder="(00) 00000-0000" inputmode="tel" maxlength="30" autocomplete="tel" required>
            @error('phone')<small class="field__message">{{ $message }}</small>@enderror
            <small class="fhint">O retorno pode sair pelo mesmo número, por WhatsApp.</small>
          </div>

          <div class="field" @error('company') data-state="error" @enderror>
            <label for="lead-company">Empresa</label>
            <input class="input" type="text" id="lead-company" name="company" value="{{ old('company') }}"
              placeholder="Nome fantasia da sua loja" maxlength="255" autocomplete="organization">
            @error('company')<small class="field__message">{{ $message }}</small>@enderror
            <small class="fhint">Opcional — ajuda a direcionar o atendimento.</small>
          </div>

          @php($assuntoAtual = old('subject', $assuntoSelecionado))
          <div class="field field--full" @error('subject') data-state="error" @enderror>
            <label for="lead-subject">Assunto<i class="req">*</i></label>
            <select class="select" id="lead-subject" name="subject" required>
              <option value="" disabled @selected($assuntoAtual === '')>Selecione o assunto</option>
              @foreach($assuntos as $chave => $rotulo)
                <option value="{{ $chave }}" @selected($assuntoAtual === $chave)>{{ $rotulo }}</option>
              @endforeach
            </select>
            @error('subject')<small class="field__message">{{ $message }}</small>@enderror
          </div>

          <div class="field field--full" @error('message') data-state="error" @enderror>
            <label for="lead-message">Mensagem<i class="req">*</i></label>
            <textarea class="textarea" id="lead-message" name="message" maxlength="1000" required
              placeholder="Conte o que você precisa. Quanto mais contexto, mais direta é a resposta.">{{ old('message') }}</textarea>
            @error('message')<small class="field__message">{{ $message }}</small>@enderror
            <small class="fhint">Até 1.000 caracteres.</small>
          </div>

        </div>

        <h3 class="fsec">Consentimento</h3>
        <div class="stacklist">
          <label class="checkline" style="align-items:flex-start" for="lead-consent">
            <input type="checkbox" id="lead-consent" name="consent" value="1" @checked(old('consent')) required
              style="width:16px;height:16px;flex:none;margin:2px 0 0;accent-color:var(--action)">
            <span>Li e concordo com a <a href="{{ route('site.privacidade') }}" class="link-gold">Política de Privacidade</a>
              e autorizo a Velaro a usar os dados acima para responder a este contato.<i class="req">*</i></span>
          </label>
        </div>
        @error('consent')
          <p class="notice notice--danger" style="margin-top:var(--space-3)">
            <x-velaro.icon name="info" /><span>{{ $message }}</span></p>
        @enderror
        <p class="fhint" style="margin-top:var(--space-3)"><x-velaro.icon name="shield" />
          O aceite é obrigatório para enviar e fica registrado com data, hora, IP e a versão do texto vigente —
          a mesma prova exigida no cadastro de lojista.</p>

        <button class="btn btn--primary" type="submit" style="width:100%;margin-top:var(--space-6)">
          Enviar mensagem ›</button>
      </form>

      <p class="muted" style="text-align:center;margin:var(--space-3) 0 0;font-size:var(--text-xs)">
        <x-velaro.icon name="info" /> Registramos de qual página do site você veio, para direcionar o atendimento.</p>
    </div>

    <div class="stack">

      {{-- Lead não é pré-cadastro: quem quer revender vai para a tela 1.4. --}}
      <div class="card panel-dark">
        <h3 class="title" style="color:var(--color-gold-300)">Quer revender a Velaro?</h3>
        <p style="margin:var(--space-3) 0 0;font-size:var(--text-sm);line-height:22px;color:rgba(255,255,255,.72)">
          Este formulário <strong>não substitui o pré-cadastro</strong>. Para receber preço de fábrica e acesso
          ao Portal do Lojista, envie o cadastro completo: CNPJ, CNAE compatível e os três documentos da empresa.</p>
        <ul class="cklist cklist--dark" style="margin-top:var(--space-4)">
          @foreach(['Não cria cadastro de revendedor', 'Não libera preço nem condição comercial', 'Não dá acesso ao Portal do Lojista', 'Não dispensa o envio dos documentos'] as $negativa)
            <li><x-velaro.icon name="x" />{{ $negativa }}</li>
          @endforeach
        </ul>
        <div style="margin-top:var(--space-5)">
          <a class="btn btn--gold" href="{{ route('site.cadastro') }}"><x-velaro.icon name="user-plus" /> Quero ser revendedor</a>
        </div>
      </div>

      <div class="card panel-dark">
        <h3 class="title" style="color:var(--color-gold-300)">Como funciona o atendimento</h3>
        <ol class="howto">
          @foreach([['Mensagem recebida', 'O contato entra na fila de atendimento com a página de origem registrada.'], ['Triagem pelo assunto', 'A equipe assume o contato; a partir daí ele tem responsável e data de retorno.'], ['Resposta em até 1 dia útil', 'Respondemos por e-mail ou WhatsApp, no canal que você preferir.']] as $i => [$passo, $detalhe])
            <li><span class="num">{{ $i + 1 }}</span><div><strong>{{ $passo }}</strong><p>{{ $detalhe }}</p></div></li>
          @endforeach
        </ol>
      </div>

      <div class="card">
        <h3 class="title">Já é lojista Velaro?</h3>
        <p class="lede" style="margin-top:var(--space-2);font-size:var(--text-sm)">
          Pedido, financeiro e produção se resolvem mais rápido pelo chamado de suporte dentro do Portal,
          que já chega com o histórico da sua loja.</p>
        <div class="row row--wrap" style="margin-top:var(--space-4)">
          <a class="btn btn--primary btn--sm" href="{{ route('login') }}"><x-velaro.icon name="user" /> Entrar no Portal</a>
          {{-- 1.6 exige o protocolo na URL e o site não tem tela de busca por protocolo:
               até ela existir, o botão traz o visitante para o formulário com o assunto
               "Acompanhar solicitação de cadastro" já escolhido. --}}
          <a class="btn btn--secondary btn--sm" href="{{ route('site.contato', ['assunto' => 'acompanhar-cadastro', 'origem' => 'contato']).'#lead-subject' }}"><x-velaro.icon name="search" /> Acompanhar solicitação</a>
        </div>
      </div>

    </div>
  </div>

  <p class="notice notice--gold"><x-velaro.icon name="info" /><span><strong>Contato não é chamado.</strong>
    Quem ainda não é revendedor não abre chamado de suporte: a mensagem vira um lead na fila comercial,
    com responsável e data de atendimento registrados.</span></p>

</div></section>

</x-velaro.layouts.site>
