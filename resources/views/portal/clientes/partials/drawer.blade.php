{{--
[Modulo: resources/views/portal/clientes/partials]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Gaveta "Novo cliente" do prototipo: o inventario de campos do cadastro, ainda sem rota de gravacao contratada.
--}}
{{--
  As 19 rotas do portal estão fechadas e não há `POST /portal/clientes`: o
  cadastro do consumidor final é escopo declarado da tela 2.3, mas sem verbo de
  escrita no contrato de rotas deste lote. Os campos ficam aqui, na forma e na
  ordem do protótipo (a régua de aceite da seção 5), com os controles
  desabilitados e a pendência dita em voz alta — some-los seria perder o
  inventário; fingir que gravam seria pior.
--}}
<aside class="drawer" id="novo-cliente" aria-labelledby="novo-cliente-titulo">
  <header class="drawer__head">
    <div>
      <h2 class="title" id="novo-cliente-titulo">Novo cliente</h2>
      <p class="drawer__sub">Preencha os dados do cliente para cadastrá-lo e começar a criar pedidos.</p>
    </div>
  </header>

  <div class="drawer__body">
    <div class="fgrid fgrid--1">
      <div class="field">
        <label for="cliente-nome">Nome completo<i class="req">*</i></label>
        <input class="input" id="cliente-nome" type="text" name="name" placeholder="Nome e sobrenome" disabled>
      </div>
      <div class="field">
        <label for="cliente-documento">CPF<i class="req">*</i></label>
        <input class="input" id="cliente-documento" type="text" name="document" placeholder="000.000.000-00" inputmode="numeric" disabled>
      </div>
      <div class="field">
        <label for="cliente-telefone">Telefone/WhatsApp<i class="req">*</i></label>
        <span class="input-shell input-shell--suffix">
          <input class="input" id="cliente-telefone" type="tel" name="phone" placeholder="(00) 00000-0000" disabled>
          <x-velaro.icon name="whats" class="ic input-shell__suffix" style="color:#25D366" />
        </span>
      </div>
      <div class="field">
        <label for="cliente-email">E-mail<i class="req">*</i></label>
        <input class="input" id="cliente-email" type="email" name="email" placeholder="nome@email.com" disabled>
      </div>
      <div class="field">
        <label for="cliente-nascimento">Data de nascimento</label>
        <input class="input" id="cliente-nascimento" type="date" name="birth_date" disabled>
      </div>
      <div class="field">
        <label for="cliente-casamento">Data de casamento / namoro</label>
        <input class="input" id="cliente-casamento" type="date" name="wedding_date" disabled>
        <small class="fhint">Usado só com consentimento de marketing.</small>
      </div>
    </div>

    {{-- Regra 1 da tela (LGPD): a data de casamento/namoro só alimenta campanha
         com consentimento de marketing válido — e o consentimento é registrável
         E revogável, por isso mora em `customer_consents`, com histórico, e não
         num booleano no cliente. --}}
    <div class="consentbox">
      <strong>Usar para campanhas de marketing</strong>
      <span class="checkline">
        <span class="cbox" aria-hidden="true"></span>
        Receber campanhas em datas especiais
      </span>
      <small>Registrável e revogável — exigência de LGPD do Anexo I §4.3.</small>
    </div>

    <div class="fgrid fgrid--1">
      <div class="field">
        <label for="cliente-origem">Origem do contato</label>
        <select class="select" id="cliente-origem" name="contact_source" disabled>
          @foreach($origens as $chave => $rotulo)
            <option value="{{ $chave }}">{{ $rotulo }}</option>
          @endforeach
        </select>
      </div>
      <div class="field">
        <label for="cliente-cidade">Cidade/UF<i class="req">*</i></label>
        <div class="row" style="gap:8px">
          <input class="input" id="cliente-cidade" type="text" name="city" placeholder="Cidade" style="flex:1" disabled>
          <select class="select" name="state" aria-label="UF" style="max-width:110px" disabled>
            @foreach($ufs as $sigla => $nome)
              <option value="{{ $sigla }}">{{ $sigla }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="field">
        <label for="cliente-endereco">Endereço<i class="req">*</i></label>
        <input class="input" id="cliente-endereco" type="text" name="address" placeholder="Rua, número e bairro" disabled>
      </div>
      <div class="field">
        <label for="cliente-observacoes">Observações</label>
        <textarea class="textarea" id="cliente-observacoes" name="notes" rows="3" placeholder="Preferências, contexto da visita, o que combinar no atendimento." disabled></textarea>
      </div>
    </div>
  </div>

  <div class="drawer__foot">
    <p class="notice notice--gold">
      <x-velaro.icon name="info" />
      <span><strong>Cadastro pendente de rota.</strong> O formulário está no escopo da tela, mas a gravação
        (<code>POST /portal/clientes</code>) ainda não faz parte das rotas contratadas do portal.</span>
    </p>
    <p class="notice notice--info">
      <x-velaro.icon name="info" />
      <span><strong>Próximo passo:</strong> com o cliente salvo, você poderá criar um pedido de alianças com mais agilidade.</span>
    </p>
  </div>
</aside>
