{{--
/*
[Modulo: resources/views/portal/loja]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 2.6 do Portal: identidade da vitrine, regra de preco e a previa pintada pelos proprios campos.
*/
--}}
<x-velaro.layouts.portal title="Personalização da loja" titulo="Personalização da loja">

<div class="page-head"><div>
  <h1 class="display-md">Personalização da loja</h1>
  <p class="lede">Configure a identidade visual, regras de preços e como sua vitrine será exibida para o
    cliente final. Todas as alterações são refletidas na vitrine do cliente.</p>
</div></div>

@if(session('status'))
  <p class="notice notice--ok" style="margin-bottom:var(--space-4)" role="status">
    <x-velaro.icon name="check" /><span>{{ session('status') }}</span></p>
@endif

@if($errors->any())
  <p class="notice notice--danger" style="margin-bottom:var(--space-4)" role="alert">
    <x-velaro.icon name="info" /><span>Revise os campos destacados para salvar a vitrine.</span></p>
@endif

<div class="split split--wide">

  {{-- O formulário é o próprio item da grade: blocos ① e ② e o rodapé de botões
       vão num PUT só, porque a rota é uma só (PUT /portal/loja). --}}
  <form class="stack" method="POST" action="{{ route('portal.loja.update') }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    {{-- ─────────────── ① IDENTIDADE DA LOJA ─────────────── --}}
    <div class="card">
      <div class="card__head"><h2 class="title">① Identidade da loja</h2></div>

      <div class="split" style="--gcols:210px minmax(0,1fr);gap:var(--space-5)">
        <div class="logobox">
          @if($loja->logo_path)
            <img src="{{ asset('storage/'.$loja->logo_path) }}" alt="Logo de {{ $loja->name }}"
                 style="max-width:100%;height:auto">
          @else
            <span class="logobox__mark">{{ mb_strtoupper(mb_substr($loja->name ?? 'L', 0, 1)) }}</span>
          @endif
          <strong>{{ mb_strtoupper($loja->name ?? 'Sua loja') }}</strong>
          <small>ALIANÇAS</small>
          <label class="logobox__up" for="loja-logo"><x-velaro.icon name="upload" /> Enviar nova logo</label>
          <input class="input" type="file" id="loja-logo" name="logo" accept="image/png,image/jpeg"
                 style="max-width:100%;font-size:var(--text-xs)">
          <small>PNG ou JPG · Máx. 2MB</small>
          @error('logo')<small class="field__message">{{ $message }}</small>@enderror
        </div>

        <div>
          {{-- `align-items:start` porque o campo de domínio tem uma linha de
               ajuda a mais: sem isso o `.field` vizinho estica e o input de
               e-mail sai com o dobro da altura dos outros. --}}
          <div class="fgrid fgrid--2" style="align-items:start">
            <div class="field" @error('name') data-state="error" @enderror>
              <label for="loja-nome">Nome da loja<i class="req">*</i></label>
              <input class="input" type="text" id="loja-nome" name="name" maxlength="255" required
                     value="{{ old('name', $loja->name) }}" placeholder="Tomazelli Alianças">
              @error('name')<small class="field__message">{{ $message }}</small>@enderror
            </div>

            <div class="field" @error('slogan') data-state="error" @enderror>
              <label for="loja-slogan">Slogan</label>
              <input class="input" type="text" id="loja-slogan" name="slogan" maxlength="255"
                     value="{{ old('slogan', $loja->slogan) }}"
                     placeholder="Símbolo de amor. Promessa para a vida toda.">
              @error('slogan')<small class="field__message">{{ $message }}</small>@enderror
            </div>

            <div class="field" @error('phone') data-state="error" @enderror>
              <label for="loja-telefone">Telefone</label>
              <input class="input" type="tel" id="loja-telefone" name="phone" maxlength="30" inputmode="tel"
                     value="{{ old('phone', $loja->phone) }}" placeholder="(11) 98888-2020">
              @error('phone')<small class="field__message">{{ $message }}</small>@enderror
            </div>

            <div class="field" @error('whatsapp') data-state="error" @enderror>
              <label for="loja-whatsapp">WhatsApp</label>
              <input class="input" type="tel" id="loja-whatsapp" name="whatsapp" maxlength="30" inputmode="tel"
                     value="{{ old('whatsapp', $loja->whatsapp) }}" placeholder="(11) 98888-2020">
              @error('whatsapp')<small class="field__message">{{ $message }}</small>@enderror
            </div>

            <div class="field" @error('email') data-state="error" @enderror>
              <label for="loja-email">E-mail</label>
              <input class="input" type="email" id="loja-email" name="email" maxlength="255"
                     value="{{ old('email', $loja->email) }}" placeholder="contato@minhaloja.com.br">
              @error('email')<small class="field__message">{{ $message }}</small>@enderror
            </div>

            <div class="field" @error('domain') data-state="error" @enderror>
              <label for="loja-dominio">Domínio / URL da loja</label>
              <span class="input-shell input-shell--prefix-text">
                <span class="input-shell__prefix">https://</span>
                <input class="input" type="text" id="loja-dominio" name="domain" maxlength="255"
                       value="{{ old('domain', $loja->domain) }}" placeholder="minhaloja.com.br"
                       style="padding-left:74px">
              </span>
              @error('domain')<small class="field__message">{{ $message }}</small>@enderror
              <small class="fhint">Opcional. Sem domínio próprio, a loja atende pelo endereço Velaro abaixo.</small>
            </div>

            {{-- `slug` é UNIQUE e é a URL pública da vitrine (/loja/{slug}): sem
                 campo aqui o lojista nunca conseguiria escolher o próprio endereço. --}}
            <div class="field" @error('slug') data-state="error" @enderror>
              <label for="loja-slug">Endereço da loja na Velaro<i class="req">*</i></label>
              <span class="input-shell input-shell--prefix-text">
                <span class="input-shell__prefix">/loja/</span>
                <input class="input" type="text" id="loja-slug" name="slug" maxlength="255" required
                       value="{{ old('slug', $loja->slug) }}" placeholder="minha-loja" style="padding-left:56px">
              </span>
              @error('slug')<small class="field__message">{{ $message }}</small>@enderror
            </div>

            <div class="field field--full" @error('address') data-state="error" @enderror>
              <label for="loja-endereco">Endereço</label>
              <input class="input" type="text" id="loja-endereco" name="address" maxlength="255"
                     value="{{ old('address', $loja->address) }}"
                     placeholder="Rua das Alianças, 123 - Centro, São Paulo - SP">
              @error('address')<small class="field__message">{{ $message }}</small>@enderror
            </div>
          </div>
        </div>
      </div>

      <h3 class="fsec">Banner principal</h3>
      <div class="bannerbox">
        @if($loja->banner_path)
          <img src="{{ asset('storage/'.$loja->banner_path) }}" alt="Banner de {{ $loja->name }}"
               style="width:100%;height:auto;border-radius:var(--radius-sm)">
        @else
          <strong>{{ mb_strtoupper($loja->name ?? 'SUA LOJA') }}</strong>
          <span>{{ $loja->slogan ?? 'Símbolo de amor. Promessa para a vida toda.' }}</span>
        @endif
      </div>
      <div class="field" style="margin-top:var(--space-3)">
        <label for="loja-banner">Enviar novo banner</label>
        <input class="input" type="file" id="loja-banner" name="banner" accept="image/png,image/jpeg">
        <small class="fhint">1920×600px recomendado</small>
        @error('banner')<small class="field__message">{{ $message }}</small>@enderror
      </div>

      <h3 class="fsec">Cores da marca</h3>
      <div class="fgrid fgrid--4" style="align-items:start">
        @foreach($cores as $campo => $rotulo)
          <div class="field" @error($campo) data-state="error" @enderror>
            <label for="loja-{{ $campo }}">{{ $rotulo }}</label>
            <span class="colorbox">
              <input type="color" id="loja-{{ $campo }}" name="{{ $campo }}"
                     value="{{ old($campo, $loja->{$campo}) }}"
                     style="width:22px;height:22px;padding:0;border:0;background:none"
                     aria-label="Cor {{ mb_strtolower($rotulo) }}">
              <span class="num">{{ old($campo, $loja->{$campo}) }}</span>
            </span>
            @error($campo)<small class="field__message">{{ $message }}</small>@enderror
          </div>
        @endforeach
      </div>

      {{-- O `.switch` do design system é um span decorativo: no protótipo ele só
           ilustra o estado. Aqui o controle precisa existir de verdade, então a
           linha usa o `.checkline` com um checkbox — o mesmo padrão já aprovado
           nas telas do site (cadastro e contato). --}}
      @foreach($toggles as $campo => $texto)
        <div class="toggleline">
          <label class="checkline" style="align-items:flex-start" for="loja-{{ $campo }}">
            <input type="checkbox" id="loja-{{ $campo }}" name="{{ $campo }}" value="1"
                   @checked(old($campo, $loja->{$campo}))
                   style="flex:none;margin-top:3px;width:16px;height:16px;accent-color:var(--action)">
            <span><strong>{{ $texto['titulo'] }}</strong><small>{{ $texto['ajuda'] }}</small></span>
          </label>
        </div>
      @endforeach
    </div>

    {{-- ─────────────── ② REGRA DE PREÇOS ─────────────── --}}
    <div class="card">
      <div class="card__head"><h2 class="title">② Regra de preços</h2></div>

      <div class="split" style="--gcols:minmax(0,1fr) minmax(0,1fr);gap:var(--space-5)">
        <div>
          <span class="eyebrow">Modelo de precificação</span>
          <div class="stack" style="margin-top:8px">
            @foreach($modelos as $valor => $rotulo)
              @php($ligado = old('pricing_model', $configuracao->pricing_model) === $valor)
              <label class="payopt @if($ligado) is-on @endif" for="preco-modelo-{{ $valor }}">
                <input type="radio" id="preco-modelo-{{ $valor }}" name="pricing_model" value="{{ $valor }}"
                       @checked($ligado) style="width:16px;height:16px;accent-color:var(--action)">
                <x-velaro.icon name="{{ $valor === $multiplicadorModelo ? 'diamond' : 'chart' }}" />
                <strong>{{ $rotulo }}</strong>
                <small>{{ $valor === $multiplicadorModelo ? 'Aplicar um fator multiplicador' : 'Aplicar um percentual de margem' }}</small>
              </label>
            @endforeach
          </div>

          <div class="field" style="margin-top:var(--space-4)" @error('multiplier') data-state="error" @enderror>
            <label for="preco-multiplicador">Fator de multiplicação<i class="req">*</i></label>
            <input class="input" type="number" id="preco-multiplicador" name="multiplier" required
                   min="1" max="99.99" step="0.1"
                   value="{{ old('multiplier', number_format((float) $configuracao->multiplier, 2, '.', '')) }}">
            @error('multiplier')<small class="field__message">{{ $message }}</small>@enderror
            <small class="fhint">Hoje: {{ $fator }}</small>
          </div>
        </div>

        <div>
          @foreach($togglesDePreco as $campo => $texto)
            <div class="toggleline">
              <label class="checkline" style="align-items:flex-start" for="preco-{{ $campo }}">
                <input type="checkbox" id="preco-{{ $campo }}" name="{{ $campo }}" value="1"
                       @checked(old($campo, $configuracao->{$campo}))
                       style="flex:none;margin-top:3px;width:16px;height:16px;accent-color:var(--action)">
                <span><strong>{{ $texto }}</strong></span>
              </label>
            </div>
          @endforeach
          <p class="lede" style="font-size:var(--text-sm);margin-top:var(--space-3)">
            As margens por coleção e por produto ficam em
            <a class="link-gold" href="{{ route('portal.precos.edit') }}">Preços e margens</a>.</p>
        </div>
      </div>

      <h3 class="fsec">Exemplo de cálculo com multiplicador {{ $fator }}</h3>
      <div class="table-scroll"><table class="table">
        <thead><tr>
          <th class="cell-num">Custo Revendedor</th>
          <th class="cell-num">Multiplicador</th>
          <th class="cell-num">Preço Cliente Final (exibido)</th>
        </tr></thead>
        <tbody>
        @foreach($exemplos as $exemplo)
          <tr>
            <td class="cell-num"><span class="num">{{ $exemplo['custo'] }}</span></td>
            <td class="cell-num"><span class="num">{{ $exemplo['fator'] }}</span></td>
            <td class="cell-num"><span class="cell-strong num">{{ $exemplo['preco'] }}</span></td>
          </tr>
        @endforeach
        </tbody>
      </table></div>

      <p class="notice notice--gold"><x-velaro.icon name="info" /><span>O pagamento do cliente final é realizado
        <strong>diretamente na loja</strong>. A vitrine não processa pagamento online.</span></p>
    </div>

    <div class="row row--wrap">
      <button class="btn btn--secondary" type="submit" name="action" value="{{ $acaoSalvar }}">
        <x-velaro.icon name="check" /> Salvar configurações</button>
      <button class="btn btn--gold" type="submit" name="action" value="{{ $acaoPublicar }}">
        <x-velaro.icon name="globe" /> Publicar vitrine</button>
      <a class="btn btn--secondary" href="{{ route('portal.vitrine') }}">
        <x-velaro.icon name="eye" /> Pré-visualizar loja</a>
    </div>
  </form>

  {{-- ─────────────── PRÉ-VISUALIZAÇÃO ─────────────── --}}
  @include('portal.loja.partials.previa')

</div>

</x-velaro.layouts.portal>
