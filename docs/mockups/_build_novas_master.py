# -*- coding: utf-8 -*-
"""Etapa 3 (complemento) · telas novas do Painel Interno Velaro (Master).

Fecha os destinos que as telas 52-60 apontavam para "#" e as rotas que os
escopos preveem mas nao tinham mockup:

  52a · Nova movimentacao de estoque        (doc 3-4 · 5 stubs da 52)
  52b · Movimentacoes do item               (doc 3-4 · "Ver todas" / "Ver reservas")
  53a · Novo recebimento (baixa de lote)    (doc 3-5 · passo 1 do fluxo obrigatorio)
  53b · Nota fiscal do lote                 (doc 3-5 · "Ver nota fiscal")
  61  · Novo pedido interno                 (doc 3-6 · "+ Novo pedido")
  62  · Novo produto                        (doc 3-7 · "+ Novo produto")
  63  · Nova promocao                       (doc 3-8 · "+ Nova promocao")
  64  · Desempenho da promocao              (doc 3-8 · "Relatorio de desempenho")
  65  · Vendas por periodo                  (doc 3-9)
  66  · Ranking de revendedores             (doc 3-9 · "Ver ranking completo")
  67  · Produtos mais vendidos              (doc 3-9 · "Ver todos os produtos")
  68  · Relatorios agendados                (doc 3-9 · report_schedules)
  69  · Todos os relatorios                 (doc 3-9 · regra 1)
  70  · Suporte, fila de chamados           (doc 3-12 · GET /backend/suporte)
"""
import importlib.util as il
s = il.spec_from_file_location("u", "_ui.py"); u = il.module_from_spec(s); s.loader.exec_module(u)
g = globals(); g.update({k: getattr(u, k) for k in dir(u) if not k.startswith("__")})
W = lambda f, c: (open(f, "w", encoding="utf-8").write(religar(c, f)), print("  ✓", f))
# Em "Titulo | XX-tela.html" o arquivo depois do "|" NAO e o arquivo gerado: e o
# item do menu que fica aceso. Estas telas sao filhas de um modulo, entao acendem
# sempre a tela-mae (52, 53, 54, 55, 56, 57 ou 60).
M = lambda a, b: page("Velaro · " + a.split("|")[0].strip(), master_shell(a.split("|")[1].strip(), b))
volta = lambda href, txt: f'<a class="link-gold" href="{href}">← {e(txt)}</a>'

def ministats(itens, cls="g4"):
    return (f'<div class="grid {cls}">' + "".join(
      f'<div class="ministat"><strong>{v}</strong><small>{e(t)}</small></div>' for v, t in itens) + '</div>')

def grafico(uid, atual, anterior):
    """Linha do periodo atual (area) + periodo anterior tracejado."""
    return ('<svg viewBox="0 0 600 180" class="chart" preserveAspectRatio="none">'
      f'<defs><linearGradient id="grad{uid}" x1="0" y1="0" x2="0" y2="1">'
      '<stop offset="0%" stop-color="var(--action)" stop-opacity=".22"/>'
      '<stop offset="100%" stop-color="var(--action)" stop-opacity="0"/></linearGradient></defs>'
      f'<path d="{atual}" fill="none" stroke="var(--action)" stroke-width="3" stroke-linejoin="round"/>'
      f'<path d="{atual} L600,180 L0,180 Z" fill="url(#grad{uid})"/>'
      f'<path d="{anterior}" fill="none" stroke="var(--color-gray-400)" stroke-width="2" stroke-dasharray="6 5"/></svg>')

LINHA_A = ("M0,152 L40,138 L80,124 L120,130 L160,98 L200,106 L240,72 L280,46 L320,66 "
           "L360,54 L400,76 L440,58 L480,68 L520,48 L560,60 L600,44")
LINHA_B = ("M0,166 L40,160 L80,152 L120,156 L160,136 L200,142 L240,126 L280,112 L320,130 "
           "L360,120 L400,134 L440,122 L480,128 L520,114 L560,122 L600,118")

def barras(itens, tom=""):
    return f'<div class="barlist {tom}">' + "".join(
      f'<div class="barrow"><div class="barrow__top"><span>{e(t)}</span><b>{e(v)}</b></div>'
      f'<span class="barrow__track"><span class="barrow__fill" style="width:{p}%"></span></span></div>'
      for t, v, p in itens) + '</div>'

def legenda_grafico():
    return ('<div class="chartlegend"><span><i style="background:var(--action)"></i>Período atual</span>'
            '<span><i class="dash"></i>Período anterior</span></div>')

def eixo(marcas):
    return '<div class="chartaxis">' + "".join(f'<span>{e(m)}</span>' for m in marcas) + '</div>'

def bloco(titulo, html):
    return f'<div><span class="eyebrow">{e(titulo)}</span>{html}</div>'

# ══════════════════════════ 52a · NOVA MOVIMENTAÇÃO DE ESTOQUE ══════════════════════════
# Destino único dos cinco stubs da 52: "+ Nova movimentação", "Ajustar estoque",
# "Registrar entrada", "Solicitar produção" e "Gerar pedido →". O tipo escolhido
# no topo troca os campos exibidos; no mockup estático fica o tipo "Entrada".
tipos = tabela([("Tipo de movimentação",""),("Efeito no saldo",""),("Campos obrigatórios",""),("Permissão","")],
  [[f'<div class="row" style="gap:8px">{ic(i)}<strong style="color:var(--ink)">{t}</strong></div>', ef, cp, f'<code>{pm}</code>']
   for i, t, ef, cp, pm in [
     ("arrow-up","Entrada","Soma no estoque atual","Aro, quantidade, documento de origem e motivo","velaro.stock.adjust"),
     ("arrow-down","Saída","Subtrai do estoque atual","Aro, quantidade, motivo e documento","velaro.stock.adjust"),
     ("edit","Ajuste","Define o novo saldo — grava <code>before</code> e <code>after</code>","Aro, quantidade e motivo","velaro.stock.adjust"),
     ("gear","Produção","Abre ordem de produção e alimenta o sob encomenda","Aro, quantidade e prazo previsto","velaro.stock.request_production"),
     ("bag","Reserva","Soma em reservado e reduz o disponível","Pedido vinculado, aro e quantidade","velaro.stock.adjust")]])

formulario = form([
  campo("Tipo de movimentação","Entrada — reabastecimento",True,"select",hint="Grava em stock_movements.type"),
  campo("Produto / SKU","ALC-4MM-OU · Aliança Clássica 4mm",True,"select"),
  campo("Tamanho (aro)","18",True,"select",hint="Cada aro é um SKU próprio em product_variants"),
  campo("Quantidade","30",True,hint="Unidades inteiras"),
  campo("Local de armazenamento","Matriz - Cofre A1",True,"select"),
  campo("Data e hora","07/06/2026 10:45",True),
  campo("Documento de origem","OP-2026-0148",hint="Ordem de produção, NF de entrada ou número do pedido"),
  campo("Motivo","Ordem de produção concluída",True,"select",hint="Grava em stock_movements.reason"),
  campo("Responsável","Velaro Alianças · Admin",hint="Preenchido pelo usuário logado (actor_id)"),
  campo("Observação","Lote conferido na entrada. Divergência de 0 peça.",tipo="textarea",largura=2),
], 2)

body = f'''
{volta("52-master-estoque.html","Voltar para o estoque")}
{head("Nova movimentação de estoque",
      "Entrada, saída, ajuste, produção e reserva de um SKU. O tipo escolhido define os campos obrigatórios.",
      btn("Cancelar","secondary","x","52-master-estoque.html") + btn("Registrar movimentação","primary","check"))}
<div class="split">
  <div class="stack">
    {card(None, tabs(["Entrada","Saída","Ajuste","Produção","Reserva"],"Entrada")
      + '<div style="margin-top:var(--space-5)">' + formulario + '</div>'
      + bloco("Impacto no saldo do aro 18", ministats([("28","Antes"),("+30","Movimento"),("58","Depois"),("54","Disponível")]))
      + '<div class="row row--wrap" style="margin-top:var(--space-5)">'
      + btn("Cancelar","secondary","x","52-master-estoque.html",sm=False)
      + btn("Salvar e lançar outra","secondary","plus",sm=False)
      + btn("Registrar movimentação","primary","check",sm=False) + '</div>')}
    {card("O que cada tipo de movimentação exige", tipos)}
    {notice("Ajuste de estoque é ação sensível: o movimento grava <code>before</code> e <code>after</code> e gera registro em <code>audit_logs</code> com o responsável (doc 3-4, regra 3).")}
  </div>
  <div class="stack">
    {card("Item selecionado",
      f'<div class="prod__img" style="height:140px">{rings.svg("classica", 900)}</div>'
      + "".join(linha_dado(k, e(v), i) for i, k, v in [
          ("doc","SKU","ALC-4MM-OU"),("diamond","Coleção","Clássica"),
          ("tag","Material","Ouro 18k Amarelo"),("sparkle","Acabamento","Polido"),
          ("pin","Local","Matriz - Cofre A1")])
      + btn("Ver movimentações do item","secondary","list","52b-master-estoque-historico.html",sm=False),
      acao=chip("Em estoque","ok"))}
    {card("Saldo atual do SKU",
      ministats([("142","Estoque atual"),("18","Reservado"),("124","Disponível"),("20","Mínimo")], "g2")
      + '<small class="fhint">Saldo somado de todos os aros (10 a 33).</small>')}
    {card("Reposição sugerida",
      '<p class="lede" style="font-size:var(--text-sm)">O item está com reposição <strong>sugerida</strong>: '
      '20 unidades para voltar ao nível de cobertura de 30 dias.</p>'
      + linha_dado("Quantidade sugerida", '<span class="num">20 unidades</span>', "box")
      + linha_dado("Prazo de produção", "7 dias úteis", "clock")
      + btn("Usar sugestão no tipo Produção","secondary","gear",sm=False))}
    {notice("Entrada e saída exigem <code>velaro.stock.adjust</code>; abrir ordem de produção exige <code>velaro.stock.request_production</code> (doc 3-4, seção 2).","info")}
  </div>
</div>'''
W("52a-master-estoque-movimentacao.html", M("Nova movimentação de estoque | 52-master-estoque.html", body))

# ══════════════════════════ 52b · MOVIMENTAÇÕES DO ITEM ══════════════════════════
# O drawer da 52 mostra só as 4 últimas. Aqui está o extrato completo do SKU,
# com a aba de reservas em aberto ("Ver reservas →").
MOV = [("07/06/2026 10:45","Entrada","arrow-up","ok","+30","28","58","18","OP-2026-0148","Sistema","Ordem de produção concluída"),
       ("06/06/2026 16:22","Reserva","bag","info","−6","64","58","18","#PED-2026-05841","Sistema","Reserva de pedido"),
       ("05/06/2026 09:18","Entrada","arrow-up","ok","+20","44","64","20","OP-2026-0141","Sistema","Ordem de produção concluída"),
       ("03/06/2026 14:37","Ajuste","edit","warn","−2","46","44","20","—","Admin","Inventário — divergência de contagem"),
       ("02/06/2026 11:05","Saída","arrow-down","danger","−4","50","46","22","Remessa SEM-2405","Expedição","Expedição da remessa semanal"),
       ("30/05/2026 17:40","Reserva","bag","info","−3","53","50","16","#PED-2026-05812","Sistema","Reserva de pedido"),
       ("28/05/2026 08:52","Produção","gear","violet","+40","13","53","18","OP-2026-0136","Produção","Ordem de produção concluída"),
       ("26/05/2026 15:11","Saída","arrow-down","danger","−9","22","13","24","Remessa SEM-2404","Expedição","Expedição da remessa semanal"),
       ("22/05/2026 10:02","Ajuste","edit","warn","+1","21","22","12","—","Admin","Peça devolvida em perfeito estado"),
       ("20/05/2026 09:30","Entrada","arrow-up","ok","+15","6","21","14","OP-2026-0128","Sistema","Ordem de produção concluída")]
rows = [[f'<span class="num">{d}</span>',
         f'<div class="row" style="gap:8px">{ic(i)}{chip(t, tom, flat=True)}</div>',
         f'<span class="num">{aro}</span>',
         f'<span class="cell-strong num">{q}</span>',
         f'<span class="num">{a}</span>', f'<span class="num">{p}</span>',
         (f'<code>{o}</code>' if o != "—" else '<span class="muted">—</span>'), resp, f'<small>{mt}</small>']
        for d, t, i, tom, q, a, p, aro, o, resp, mt in MOV]

RES = [("#PED-2026-05841","Tomazelli Alianças","Maria Silva Oliveira","18","2","06/06/2026 16:22","14/06/2026","Aguardando pagamento","warn"),
       ("#PED-2026-05812","Aliança &amp; Cia","Fernando Vieira","16","3","30/05/2026 17:40","10/06/2026","Aprovado","ok"),
       ("#PED-2026-05798","Romance Joias","Amanda Vieira","20","4","28/05/2026 11:15","10/06/2026","Em produção","violet"),
       ("#PED-2026-05776","D'Luxe Joalheria","Juliana Marques","22","5","25/05/2026 09:48","07/06/2026","Em produção","violet"),
       ("#PED-2026-05754","Brilho Eterno","Marina Carvalho","18","4","21/05/2026 14:02","07/06/2026","Aprovado","ok")]
reservas = tabela([("Pedido",""),("Revendedor",""),("Cliente final",""),("Aro","cell-num"),
                   ("Qtd reservada","cell-num"),("Reservado em",""),("Previsão de expedição",""),("Status do pedido","")],
  [[f'<a class="link-gold" href="54-master-pedidos.html">{p}</a>', rv, cf,
    f'<span class="num">{aro}</span>', f'<span class="cell-strong num">{q}</span>',
    f'<span class="num">{qd}</span>', f'<span class="num">{pv}</span>', chip(st, tom, flat=True)]
   for p, rv, cf, aro, q, qd, pv, st, tom in RES],
  foot='<div class="spread"><strong>Total reservado</strong><span class="num">18 unidades</span></div>')

ident = '<div class="identbar">' + "".join(
  f'<div class="identcell"><span><small>{e(k)}</small><strong>{v}</strong></span></div>'
  for k, v in [("SKU","ALC-4MM-OU"),("Produto","Aliança Clássica 4mm"),
               ("Estoque atual","142 unidades"),("Reservado","18 unidades"),
               ("Disponível","124 unidades"),("Estoque mínimo","20 unidades")]) + '</div>'

body = f'''
{volta("52-master-estoque.html","Voltar para o estoque")}
{head("Movimentações do item","Extrato completo do SKU ALC-4MM-OU — Aliança Clássica 4mm.",
      btn("Exportar","secondary","download") + btn("+ Nova movimentação","primary","plus","52a-master-estoque-movimentacao.html"))}
{card(None, ident)}
{kpis([("arrow-up","Entradas no período","105",up("22 unidades vs. mês anterior"),"ok"),
       ("arrow-down","Saídas no período","13",down("4 unidades vs. mês anterior"),"danger"),
       ("edit","Ajustes manuais","3",flat("mesmo volume do mês anterior"),"warn"),
       ("bag","Reservas em aberto","18",up("6 unidades vs. mês anterior"),"info")])}
<div class="split split--wide">
  <div class="stack">
    {card(None, tabs(["Movimentações","Reservas"],"Movimentações"))}
    {filtros("Buscar por documento, pedido ou responsável…",
      [("Tipo","Todos"),("Aro","Todos"),("Período","Últimos 30 dias"),("Origem","Todas")],
      acoes=btn("Filtros","secondary","filter") + btn("Exportar","secondary","download"))}
    {card(None, '<div class="densetable">' + tabela([("Data e hora","cell-nowrap"),("Tipo",""),("Aro","cell-num"),
        ("Quantidade","cell-num"),("Antes","cell-num"),("Depois","cell-num"),("Origem / documento",""),
        ("Responsável",""),("Motivo","")], rows,
      foot=pag("Mostrando 1 a 10 de 148 movimentações","1 2 3 … 15", '<span class="select-fake">10 por página</span>')) + '</div>')}
    {card("Reservas em aberto (18 unidades)", f'<div class="densetable">{reservas}</div>',
      acao='<span class="select-fake">Aba “Reservas”</span>')}
    {notice("Toda linha do extrato guarda <code>before</code> e <code>after</code>. É essa dupla que permite auditar um ajuste manual sem depender do saldo atual (doc 3-4, regra 3).")}
  </div>
  <div class="stack">
    {card("Item",
      f'<div class="prod__img" style="height:140px">{rings.svg("classica", 901)}</div>'
      + "".join(linha_dado(k, e(v), i) for i, k, v in [
          ("doc","SKU","ALC-4MM-OU"),("diamond","Coleção","Clássica"),
          ("tag","Material","Ouro 18k Amarelo"),("pin","Local","Matriz - Cofre A1"),
          ("box","Tamanhos","10 a 33")]),
      acao=chip("Em estoque","ok"))}
    {card("Saldo do período",
      "".join(linha_dado(k, v) for k, v in [
        ("Saldo em 01/05/2026",'<span class="num">50 unidades</span>'),
        ("Entradas",'<span class="num" style="color:var(--color-success-700)">+105</span>'),
        ("Saídas",'<span class="num" style="color:var(--color-error-700)">−13</span>'),
        ("Ajustes",'<span class="num">−1</span>'),
        ("Saldo em 07/06/2026",'<span class="num">142 unidades</span>')]))}
    {card("Ações do item",
      btn("Registrar entrada","secondary","arrow-up","52a-master-estoque-movimentacao.html",sm=False)
      + btn("Ajustar estoque","secondary","edit","52a-master-estoque-movimentacao.html",sm=False)
      + btn("Solicitar produção","secondary","gear","52a-master-estoque-movimentacao.html",sm=False)
      + btn("Gerar pedido de reposição","primary","cart","52a-master-estoque-movimentacao.html",sm=False))}
    {notice("O controle de estoque físico é da Velaro. O Portal do Parceiro Premium <strong>apenas consulta</strong> disponibilidade (Anexo I §6).","info")}
  </div>
</div>'''
W("52b-master-estoque-historico.html", M("Movimentações do item | 52-master-estoque.html", body))

# ══════════════════════════ 53a · NOVO RECEBIMENTO (BAIXA DE LOTE) ══════════════════════════
# Passo 1 do fluxo obrigatório do doc 3-5: identificar o recebimento e vincular
# os pedidos do lote. Destino do botão "+ Novo recebimento" da 53.
fluxo = stepper([
  ("Recebimento identificado","now","Você está aqui"),
  ("Baixa financeira","todo","Após confirmar o valor"),
  ("Nota fiscal emitida","locked","Depende da baixa"),
  ("Pedidos aprovados","locked","Depende da NF"),
  ("Liberação da remessa","locked","Depende da aprovação")])

formas = "".join(
  f'<div class="payopt{" is-on" if on else ""}"><span class="radio{" is-on" if on else ""}"></span>{ic(i)}'
  f'<strong>{e(t)}</strong><small>{e(d)}</small></div>'
  for i, t, d, on in [
    ("coin","Pix","Identificação automática pelo E2E do comprovante.",True),
    ("doc","Boleto bancário","Baixa pelo retorno CNAB do banco.",False),
    ("card","Transferência / TED","Exige conciliação manual do extrato.",False)])

PEDL = [("#PED-2026-05841","Maria Silva Oliveira","28/05/2026","R$ 5.280,00","Aguardando pagamento","warn",True),
        ("#PED-2026-05846","João Santos","29/05/2026","R$ 5.150,00","Aguardando pagamento","warn",True),
        ("#PED-2026-05847","Carla Lima","29/05/2026","R$ 5.250,00","Aguardando pagamento","warn",True),
        ("#PED-2026-05852","Fernando Vieira","30/05/2026","R$ 2.980,00","Aguardando pagamento","warn",False)]
pedidos = tabela([("Pedido",""),("Cliente final",""),("Data",""),("Valor do pedido","cell-num"),("Status financeiro","")],
  [[f'<a class="link-gold" href="54-master-pedidos.html">{p}</a>', c, f'<span class="num">{d}</span>',
    f'<span class="cell-strong num">{v}</span>',
    chip(st, tom, flat=True) + (' ' + chip("no lote","brand",flat=True) if sel else '')]
   for p, c, d, v, st, tom, sel in PEDL], check=True,
  foot='<div class="spread"><strong>3 pedidos selecionados</strong><span class="money money--action">R$ 15.680,00</span></div>')

confere = ("".join(linha_dado(k, v, i) for i, k, v in [
    ("bag","Soma dos pedidos selecionados",'<span class="num">R$ 15.680,00</span>'),
    ("coin","Valor recebido",'<span class="num">R$ 15.680,00</span>'),
    ("check","Diferença",'<span class="num" style="color:var(--color-success-700)">R$ 0,00</span>')])
  + f'<div class="airesult">{ic("check")}<span><small>Conferência</small>{chip("Valor confere com o lote","ok")}</span></div>')

body = f'''
{volta("53-master-financeiro.html","Voltar para o financeiro")}
{head("Novo recebimento","Passo 1 de 5 — identifique o pagamento recebido e vincule os pedidos que compõem o lote.",
      btn("Cancelar","secondary","x","53-master-financeiro.html") + btn("Salvar rascunho","secondary","doc")
      + btn("Confirmar baixa financeira","primary","check"))}
{card(None, fluxo)}
<div class="split">
  <div class="stack">
    {card("Identificação do recebimento", form([
        campo("Revendedor","Tomazelli Alianças · RV-0876",True,"select"),
        campo("Lote","SEM-2406 · Mai/2026",True,"select",hint="Ou crie um lote novo com a data de corte da semana"),
        campo("Valor recebido","R$ 15.680,00",True),
        campo("Data do pagamento","03/06/2026",True,hint="Grava em payments.paid_at"),
        campo("Identificador externo","E2E1234567890202606031042",hint="E2E do Pix, nosso número do boleto ou ID da TED"),
        campo("Conta de recebimento","Banco do Brasil · Ag. 1234-5 / CC 98765-4",True,"select"),
      ], 2))}
    {card("Forma de recebimento", f'<div class="stack">{formas}</div>')}
    {card("Pedidos do lote", pedidos,
      acao=btn("Buscar pedidos","secondary","search"))}
    {card("Conferência do valor", confere)}
    {card("Comprovante",
      f'<div class="upload"><span class="upload__ic">{ic("upload")}</span>'
      '<strong>Anexar comprovante do pagamento</strong>'
      '<small>PDF, PNG ou JPG · máx. 5MB · grava em payments.receipt_path</small></div>'
      + campo("Observação da baixa","Pagamento identificado no extrato do dia 03/06 às 10:42.",tipo="textarea"))}
    {notice("Fluxo obrigatório: <strong>recebimento identificado → baixa financeira → NF emitida/enviada → pedidos aprovados → liberação para a remessa</strong>. Nenhuma remessa sai sem quitação confirmada do lote (Anexo I §5.5).")}
  </div>
  <div class="stack">
    {card("Resumo do lote",
      ministats([("SEM-2406","Lote"),("Mai/2026","Período"),("3","Pedidos"),("R$ 15.680,00","Valor")], "g2")
      + "".join(linha_dado(k, v) for k, v in [
          ("Revendedor","Tomazelli Alianças"),("Responsável","Lucas Tomazelli"),
          ("Data de corte",'<span class="num">01/06/2026</span>'),
          ("Vencimento",'<span class="num">08/06/2026</span>'),
          ("Status atual", chip("Aguardando baixa","warn",flat=True))]))}
    {card("O que acontece ao confirmar", checklist([
      ("ok","Baixa registrada em payments","1"),
      ("wait","NF de venda B2B emitida","2"),
      ("wait","Pedidos do lote aprovados para produção","3"),
      ("wait","Lote liberado para a remessa semanal","4")])
      + '<small class="fhint">Cada passo pede a permissão correspondente: '
        '<code>velaro.finance.reconcile</code>, <code>velaro.finance.issue_invoice</code> e '
        '<code>velaro.finance.release_shipment</code>.</small>')}
    {card("Ações",
      btn("Confirmar baixa financeira","primary","check",sm=False)
      + btn("Salvar rascunho","secondary","doc",sm=False)
      + btn("Cancelar","secondary","x","53-master-financeiro.html",sm=False))}
    {notice("Baixa financeira e liberação logística são ações sensíveis: ambas geram registro em <code>audit_logs</code> com valor, ator e data (doc 3-5, regra 3).","info")}
  </div>
</div>'''
W("53a-master-financeiro-recebimento.html", M("Novo recebimento | 53-master-financeiro.html", body))

# ══════════════════════════ 53b · NOTA FISCAL DO LOTE ══════════════════════════
# Destino do botão "Ver nota fiscal" do drawer da 53.
ident = '<div class="identbar">' + "".join(
  f'<div class="identcell"><span><small>{e(k)}</small><strong>{v}</strong></span></div>'
  for k, v in [("Nota fiscal","nº 000.024.587"),("Série","1"),("Emissão","27/05/2026 às 11:05"),
               ("Lote","SEM-2405"),("Valor total","R$ 15.680,00")]) + '</div>'

itens_nf = tabela([("Código",""),("Descrição",""),("NCM","cell-num"),("CFOP","cell-num"),
                   ("Aro","cell-num"),("Qtd","cell-num"),("Valor unit.","cell-num"),("Total","cell-num")],
  [[f'<code>{cod}</code>', f'<strong style="color:var(--ink)">{d}</strong>',
    f'<span class="num">{n}</span>', f'<span class="num">{cf}</span>', f'<span class="num">{aro}</span>',
    f'<span class="num">{q}</span>', f'<span class="num">{vu}</span>', f'<span class="cell-strong num">{t}</span>']
   for cod, d, n, cf, aro, q, vu, t in [
     ("ALC-4MM-18K","Aliança Clássica 4mm · Ouro 18K","7113.19.00","5102","18","4","R$ 1.120,00","R$ 4.480,00"),
     ("ALC-6MM-18K","Aliança Clássica 6mm · Ouro 18K","7113.19.00","5102","20","2","R$ 1.240,00","R$ 2.480,00"),
     ("ALS-6MM-18K","Aliança Sole 6mm · Ouro 18K","7113.19.00","5102","22","3","R$ 1.000,00","R$ 3.000,00"),
     ("ALD-3PTS-18K","Aliança Diamante 3pts · Ouro 18K","7113.19.00","5102","16","2","R$ 1.350,00","R$ 2.700,00"),
     ("ALT-6MM-18K","Aliança Trabalhada 6mm · Ouro 18K","7113.19.00","5102","18","3","R$ 1.006,66","R$ 3.020,00")]],
  foot='<div class="spread"><strong>Total dos produtos (14 peças)</strong><span class="num">R$ 15.680,00</span></div>')

cobertos = tabela([("Pedido",""),("Cliente final",""),("Valor","cell-num"),("Status","")],
  [[f'<a class="link-gold" href="54-master-pedidos.html">{p}</a>', c,
    f'<span class="cell-strong num">{v}</span>', chip("Aprovado","ok",flat=True)]
   for p, c, v in [("#PED-2026-05765","Maria Silva Oliveira","R$ 5.280,00"),
                   ("#PED-2026-05766","João Santos","R$ 5.150,00"),
                   ("#PED-2026-05767","Carla Lima","R$ 5.250,00")]])

body = f'''
{volta("53-master-financeiro.html","Voltar para o financeiro")}
{head("Nota fiscal do lote SEM-2405","NF de venda B2B emitida pela Velaro para o revendedor.",
      btn("Baixar PDF","secondary","download") + btn("Baixar XML","secondary","doc")
      + btn("Imprimir DANFE","secondary","print") + btn("Reenviar ao revendedor","primary","mail"))}
{card(None, ident)}
<div class="split split--wide">
  <div class="stack">
    {card("Chave de acesso",
      '<p class="lede" style="font-size:var(--text-sm)"><code>3526 0612 3456 7800 0190 5500 1000 0245 8710 2458 7103</code></p>'
      + '<div class="row row--wrap" style="gap:6px">'
      + chip("Autorizada","ok") + chip("NF enviada ao revendedor","ok",flat=True)
      + chip("Protocolo 135260001245871","neutral",flat=True) + '</div>')}
    {card("Destinatário",
      "".join(linha_dado(k, e(v), i) for i, k, v in [
        ("store","Razão social","Tomazelli Alianças Ltda."),
        ("doc","CNPJ","12.345.678/0001-90"),
        ("doc","Inscrição estadual","123.456.789.112"),
        ("pin","Endereço","Av. Alberto Andaló, 1234 – Centro – São José do Rio Preto / SP – 15090-070"),
        ("mail","E-mail de envio","contato@tomazellialiancas.com.br"),
        ("user","Responsável","Lucas Tomazelli")]))}
    {card("Itens da nota (5 SKUs · 14 peças)", f'<div class="densetable">{itens_nf}</div>')}
    {card("Totais e impostos",
      "".join(linha_dado(k, v) for k, v in [
        ("Total dos produtos",'<span class="num">R$ 15.680,00</span>'),
        ("Base de cálculo do ICMS",'<span class="num">R$ 15.680,00</span>'),
        ("Valor do ICMS",'<span class="num">R$ 0,00</span>'),
        ("Valor do IPI",'<span class="num">R$ 0,00</span>'),
        ("PIS",'<span class="num">R$ 0,00</span>'),
        ("COFINS",'<span class="num">R$ 0,00</span>'),
        ("Frete",'<span class="num">R$ 0,00</span>'),
        ("Desconto",'<span class="num">R$ 0,00</span>'),
        ("Valor total da nota",'<span class="money money--action">R$ 15.680,00</span>')])
      + '<small class="fhint">Regime tributário: Simples Nacional — impostos recolhidos no documento único de arrecadação. Parâmetro em Configurações → Informações fiscais.</small>')}
    {card("Pedidos cobertos por esta nota", cobertos,
      acao=btn("Ver pedidos","secondary","bag","54-master-pedidos.html"))}
    {notice("A Velaro emite a NF da venda B2B ao lojista; <strong>o lojista emite a nota do consumidor final</strong>. A plataforma não emite documento em nome do consumidor (doc 3-5, regra 2).")}
  </div>
  <div class="stack">
    {card("Situação da nota", timeline([
      ("done","NF autorizada pela SEFAZ","Protocolo 135260001245871","27/05/2026 11:05"),
      ("done","PDF e XML gerados","invoices.pdf_path / xml_path","27/05/2026 11:06"),
      ("done","Enviada por e-mail ao revendedor","contato@tomazellialiancas.com.br","27/05/2026 11:07"),
      ("done","Baixada pelo revendedor","Portal do Lojista → Financeiro","27/05/2026 14:22")]))}
    {card("Arquivos",
      "".join(f'<div class="docfile">{ic("doc")}<span><strong>{e(n)}</strong><small>{e(d)}</small></span>'
              f'<b class="docfile__ok">↓</b></div>'
        for n, d in [("NFe-000024587.pdf","DANFE · 148 KB"),("NFe-000024587.xml","XML autorizado · 22 KB")])
      + btn("Baixar os dois arquivos","secondary","download",sm=False))}
    {card("Emitente",
      "".join(linha_dado(k, e(v)) for k, v in [
        ("Razão social","Velaro Alianças Ltda"),("CNPJ","12.345.678/0001-90"),
        ("Inscrição estadual","123.456.789.112"),("Regime","Simples Nacional"),
        ("Série","1"),("Provedor","Emissor próprio")]))}
    {card("Ações",
      btn("Reenviar ao revendedor","primary","mail",sm=False)
      + btn("Ver lote no financeiro","secondary","coin","53-master-financeiro.html",sm=False)
      + btn("Solicitar cancelamento da NF","danger","x",sm=False))}
    {notice("Cancelamento de nota é ação sensível e depende de <code>velaro.finance.issue_invoice</code>; o pedido de cancelamento fica registrado em <code>audit_logs</code>.","info")}
  </div>
</div>'''
W("53b-master-financeiro-nota.html", M("Nota fiscal do lote | 53-master-financeiro.html", body))

# ══════════════════════════ 61 · NOVO PEDIDO INTERNO ══════════════════════════
# Destino do botão "+ Novo pedido" da 54. Pedido registrado pela Velaro em nome
# de um revendedor (telefone/WhatsApp) — o revendedor continua sendo o
# responsável comercial e o consumidor final não tem login (modelo B2B).
passos = stepper([
  ("Revendedor","done","Tomazelli Alianças"),
  ("Itens do pedido","now","3 itens · R$ 3.240,00"),
  ("Condição comercial","todo","Pagamento e lote"),
  ("Entrega","todo","Remessa semanal"),
  ("Revisão","todo","Confirmar e criar")])

itens_novo = tabela([("Produto",""),("Código",""),("Aro","cell-num"),("Gravação",""),
                     ("Qtd","cell-num"),("Valor unit.","cell-num"),("Total","cell-num"),("","cell-num")],
  [[f'<div class="row" style="gap:10px">{ringimg(v, 910+i)}<span><strong style="color:var(--ink)">{n}</strong>'
    f'<br><small class="muted">{m}</small></span></div>', f'<code>{cod}</code>',
    f'<span class="num">{aro}</span>', f'<small>{gr}</small>',
    f'<span class="num">{q}</span>', f'<span class="num">{vu}</span>',
    f'<span class="cell-strong num">{t}</span>', f'<span class="muted">{ic("trash")}</span>']
   for i, (v, n, m, cod, aro, gr, q, vu, t) in enumerate([
     ("classica","Aliança Classic 4mm","Ouro 18K","ALC-4MM-18K","18","M ❤ S","1","R$ 1.120,00","R$ 1.120,00"),
     ("classica","Aliança Classic 4mm","Ouro 18K","ALC-4MM-18K","22","M ❤ S","1","R$ 1.120,00","R$ 1.120,00"),
     ("fosca","Aliança Sole 6mm","Ouro 18K","ALS-6MM-18K","20","Para sempre","1","R$ 1.000,00","R$ 1.000,00")])],
  foot='<div class="spread"><strong>Subtotal (3 itens)</strong><span class="num">R$ 3.240,00</span></div>')

adicionar = (form([
    campo("Produto / SKU","Buscar por nome, código ou referência…",tipo="select"),
    campo("Aro","Selecione o tamanho",tipo="select"),
    campo("Quantidade","1"),
    campo("Gravação interna","Até 20 caracteres",hint="Só para produtos com gravação habilitada"),
  ], 2)
  + '<div class="row row--wrap" style="margin-top:var(--space-4)">'
  + btn("Ver catálogo","secondary","book","55-master-produtos.html")
  + btn("Adicionar item","primary","plus") + '</div>')

body = f'''
{volta("54-master-pedidos.html","Voltar para pedidos")}
{head("Novo pedido interno",
      "Pedido registrado pela Velaro em nome de um revendedor — atendimento por telefone ou WhatsApp.",
      btn("Cancelar","secondary","x","54-master-pedidos.html") + btn("Salvar rascunho","secondary","doc")
      + btn("Criar pedido","primary","check"))}
{card(None, passos)}
<div class="split">
  <div class="stack">
    {card("Revendedor",
      '<div class="pickitem is-on"><div class="pickitem__top">'
      '<div class="row" style="gap:10px"><span class="avatar avatar--sm">TA</span>'
      '<span><strong>Tomazelli Alianças</strong><br><small>RV-0876 · São José do Rio Preto / SP</small></span></div>'
      + chip("Ativo","ok",flat=True) + '</div>'
      '<small>Responsável: Lucas Tomazelli · (17) 99123-4567 · contato@tomazellialiancas.com.br</small></div>'
      + '<div style="margin-top:var(--space-4)">' + form([
          campo("Revendedor","Tomazelli Alianças · RV-0876",True,"select"),
          campo("Canal de origem","WhatsApp",True,"select",hint="Telefone, WhatsApp ou e-mail"),
          campo("Atendente responsável","Velaro Alianças · Admin",True,"select"),
          campo("Pedido de referência do revendedor","PC-2026-114",hint="Número do pedido de compra do lojista, se houver"),
        ], 2) + '</div>',
      acao=btn("Trocar revendedor","secondary","store","58-master-revendedores.html"))}
    {card("Cliente final (opcional)", form([
        campo("Nome do cliente final","Maria Silva Oliveira"),
        campo("CPF","123.456.789-00"),
        campo("Telefone","(11) 98765-4321"),
        campo("E-mail","maria.silva@email.com"),
      ], 2)
      + notice("O consumidor final <strong>não tem login e não paga a Velaro</strong>: ele aparece apenas como pessoa vinculada ao pedido, e a cobrança é sempre Velaro → lojista (Anexo I §5.2).","info"))}
    {card("Itens do pedido", f'<div class="densetable">{itens_novo}</div>', acao='<span class="select-fake">Tabela: B2B Parceiro Premium</span>')}
    {card("Adicionar item", adicionar)}
    {card("Condição comercial", form([
        campo("Tabela de preço","B2B Parceiro Premium",True,"select",hint="Define o unit_price gravado no item"),
        campo("Promoção aplicada","PROMO-2026-05 · Desconto progressivo",False,"select",hint="Faixa de 10% acima de R$ 2.000"),
        campo("Forma de pagamento","Pix",True,"select"),
        campo("Lote de faturamento","SEM-2406 · corte em 01/06/2026",True,"select"),
        campo("Prazo de produção","7 dias úteis",True,"select"),
        campo("Vencimento","08/06/2026",True),
      ], 2))}
    {card("Entrega", form([
        campo("Modo de entrega","Remessa semanal para a loja do revendedor",True,"select"),
        campo("Previsão de envio","14/06/2026",True),
        campo("Endereço de entrega","Av. Alberto Andaló, 1234 – Centro – São José do Rio Preto / SP – 15090-070",True,largura=2),
        campo("Observações do pedido","Cliente solicitou gravação interna nas duas peças.",tipo="textarea",largura=2),
      ], 2))}
  </div>
  <div class="stack">
    {card("Resumo do pedido",
      "".join(linha_dado(k, v) for k, v in [
        ("Subtotal",'<span class="num">R$ 3.240,00</span>'),
        ("Desconto da promoção",'<span class="num" style="color:var(--color-success-700)">− R$ 324,00</span>'),
        ("Frete",'<span class="num">Incluso na remessa</span>'),
        ("Total do pedido",'<span class="money money--action">R$ 2.916,00</span>')])
      + ministats([("3","Itens"),("14/06","Previsão")], "g2"))}
    {card("Situação inicial",
      "".join(linha_dado(k, v) for k, v in [
        ("Status operacional", chip("Registrado","info",flat=True)),
        ("Status financeiro", chip("Aguardando pagamento","warn",flat=True)),
        ("Lote","SEM-2406"),("Criado por","Velaro Alianças · Admin"),
        ("Origem","Pedido interno (WhatsApp)")])
      + '<small class="fhint">Status operacional e status financeiro são independentes (doc 3-6, regra 2).</small>')}
    {card("Ações",
      btn("Criar pedido","primary","check",sm=False)
      + btn("Salvar rascunho","secondary","doc",sm=False)
      + btn("Cancelar","secondary","x","54-master-pedidos.html",sm=False))}
    {notice("Mudança de preço <strong>não afeta pedido já criado</strong>: o <code>unit_price</code> é gravado como snapshot no item (doc 3-7, regra 3).")}
    {notice("Pedido criado pelo painel interno registra o ator em <code>audit_logs</code> e aparece no histórico do pedido como “criado pela Velaro em nome do revendedor”.","info")}
  </div>
</div>'''
W("61-master-pedido-novo.html", M("Novo pedido interno | 54-master-pedidos.html", body))

# ══════════════════════════ 62 · NOVO PRODUTO ══════════════════════════
# Mesma diagramação do "Editando produto" da 55, em estado vazio e sem a coluna
# direita de resumo (não há o que resumir antes de salvar).
PRD = [("classica","Aliança Classic 4mm","ALC-4MM-18K","Ouro 18K","R$ 1.120,00"),
       ("conforto","Aliança Classic 6mm","ALC-6MM-18K","Ouro 18K","R$ 1.240,00"),
       ("fosca","Aliança Sole 6mm","ALS-6MM-18K","Ouro 18K","R$ 1.000,00"),
       ("diamond","Aliança Diamante 3pts","ALD-3PTS-18K","Ouro 18K","R$ 1.350,00"),
       ("cravejada","Aliança Diamante 5pts","ALD-5PTS-18K","Ouro 18K","R$ 1.650,00"),
       ("trabalhada","Aliança Trabalhada 6mm","ALT-6MM-18K","Ouro 18K","R$ 1.090,00")]
lista = ('<a class="pickitem is-on" href="#"><div class="pickitem__top">'
         '<strong>Novo produto</strong>' + chip("Rascunho","info",flat=True) + '</div>'
         '<small>Ainda não salvo — nenhum SKU gerado.</small></a>'
  + "".join(
    f'<a class="pickitem" href="55-master-produtos.html"><div class="row" style="gap:10px">{ringimg(v, 920+i)}'
    f'<span style="flex:1;min-width:0"><strong>{e(n)}</strong><br><small>{e(cod)}</small></span>'
    f'<span style="text-align:right"><small>{e(m)}</small><br><span class="cell-strong num">{p}</span></span>'
    f'{chip("Ativo","ok",flat=True)}</div></a>'
    for i, (v, n, cod, m, p) in enumerate(PRD)))

editor = (
  '<div class="spread"><div><span class="eyebrow">Novo produto</span>'
  '<h2 class="display-sm" style="margin-top:4px">Produto sem nome</h2>'
  '<p class="lede" style="font-size:var(--text-sm)">Preencha as informações gerais para gerar o SKU e liberar as demais abas.</p></div>'
  + chip("Rascunho","info") + btn("Cancelar","secondary","x","55-master-produtos.html") + '</div>'
  + tabs(["Informações gerais","Preço e disponibilidade","Especificações","Gravação","Imagens"],"Informações gerais")
  + '<div class="emptyform" style="margin-top:var(--space-5)">' + form([
      campo("Nome do produto","Ex.: Aliança Classic 4mm",True),
      campo("Código / Referência","Ex.: ALC-4MM-18K",True,hint="Gerado automaticamente se ficar em branco"),
      campo("Categoria","Selecione a categoria",True,"select"),
      campo("Coleção","Selecione a coleção",False,"select"),
      campo("Material","Selecione o material",True,"select"),
      campo("Acabamento","Selecione o acabamento",True,"select"),
      campo("Largura","Selecione a largura",False,"select"),
      campo("Formato","Selecione o formato",False,"select"),
      campo("Aro / tamanhos disponíveis","Ex.: 10 a 33",False,hint="Cada aro vira um SKU em product_variants"),
      campo("Preço B2B (custo para o lojista)","R$ 0,00",True,hint="Preço interno — nunca exibido ao consumidor final"),
    ], 2)
  + campo("Descrição","Descreva o produto como ele deve aparecer no catálogo do revendedor.",tipo="textarea")
  + '</div>'
  + toggle("Produto ativo","Produtos inativos não aparecem para os revendedores", False)
  + toggle("Permite gravação interna","Habilita o campo de gravação no carrinho da vitrine", False)
  + '<div class="row row--wrap" style="margin-top:var(--space-5)">'
  + btn("Cancelar","secondary","x","55-master-produtos.html",sm=False)
  + btn("Salvar rascunho","secondary","doc",sm=False)
  + btn("Salvar produto","primary","check",sm=False) + '</div>')

imagens = ('<div class="fgrid fgrid--4">' + "".join(
    f'<div class="upload"><span class="upload__ic">{ic("upload")}</span>'
    f'<strong>{e(t)}</strong><small>{e(d)}</small></div>'
    for t, d in [("Imagem principal","JPG ou PNG · 1000×1000"),("Imagem 2","Opcional"),
                 ("Imagem 3","Opcional"),("Imagem 4","Opcional")]) + '</div>'
  + '<small class="fhint">A primeira imagem enviada vira a <code>is_primary</code> em <code>product_images</code>; a ordem pode ser trocada depois.</small>')

body = f'''
{volta("55-master-produtos.html","Voltar para produtos")}
{head("Novo produto","Cadastro de um item do catálogo mestre. O produto nasce inativo e só aparece para os revendedores depois de ativado.",
      btn("Importar planilha","secondary","upload") + btn("Salvar produto","primary","check"))}
{card(None, tabs(["Lista de produtos","Categorias","Coleções","Materiais","Acabamentos","Gravações"],"Lista de produtos"))}
<div class="split" style="--gcols:330px minmax(0,1fr)">
  <div class="stack">
    {filtros("Buscar produto por nome, código ou referência…", [("Categoria","Todas as categorias")])}
    <div class="stacklist">{lista}</div>
    {pag("Mostrando 1 a 6 de 248 produtos","1 2 3 … 25", '<span class="select-fake">10 por página</span>')}
  </div>
  <div class="stack">
    {card(None, editor)}
    {card("Imagens do produto", imagens)}
    {notice("Enquanto o produto estiver inativo ele <strong>não aparece para os revendedores</strong> nem na vitrine do lojista (doc 3-7, regra 2).")}
    {notice("As abas <strong>Preço e disponibilidade</strong>, <strong>Especificações</strong>, <strong>Gravação</strong> e <strong>Imagens</strong> só ficam editáveis depois do primeiro salvamento, quando os SKUs de cada aro são gerados.","info")}
  </div>
</div>'''
W("62-master-produto-novo.html", M("Novo produto | 55-master-produtos.html", body))

# ══════════════════════════ 63 · NOVA PROMOÇÃO ══════════════════════════
# Editor em estado vazio, status Rascunho, com as abas do doc 3-8 seção 5.
tiers = tabela([("Faixa",""),("Valor mínimo do pedido","cell-num"),("Desconto","cell-num"),("","cell-num")],
  [['<span class="muted">Nenhuma faixa cadastrada — a promoção precisa de pelo menos uma.</span>',
    '<span class="muted">—</span>', '<span class="muted">—</span>', '']],
  foot='<div class="row row--wrap">' + btn("+ Adicionar faixa","secondary","plus")
       + '<small class="fhint">Ex.: 5% acima de R$ 1.000 · 10% acima de R$ 2.000 · 15% acima de R$ 3.000. '
         'Grava em <code>promotion_rules</code>.</small></div>')

publico = form([
    campo("Público-alvo","Todos os revendedores ativos",True,"select",hint="Grava em promotion_audiences"),
    campo("Plano do revendedor","Todos os planos",False,"select"),
    campo("Região","Todas as regiões",False,"select"),
    campo("Canais","Loja online, WhatsApp, E-mail",False,"select"),
    campo("Pedido mínimo","R$ 0,00",False),
    campo("Limite de uso por revendedor","Sem limite",False,"select"),
  ], 2)

revisao = checklist([
  ("wait","Informações básicas preenchidas",""),
  ("wait","Pelo menos uma faixa ou produto vinculado",""),
  ("wait","Público-alvo definido",""),
  ("wait","Período sem conflito com outra campanha ativa",""),
  ("wait","Prévia conferida antes de publicar","")])

body = f'''
{volta("56-master-promocoes.html","Voltar para promoções")}
{head("Nova promoção","Campanha B2B da Velaro para os revendedores. Nasce como rascunho e só vale depois de agendada ou ativada.",
      btn("Cancelar","secondary","x","56-master-promocoes.html") + btn("Salvar rascunho","secondary","doc")
      + btn("Agendar campanha","primary","calendar"))}
<div class="split">
  <div class="stack">
    {card(None,
      '<div class="spread"><div><span class="eyebrow">Nova promoção</span>'
      '<h2 class="display-sm" style="margin-top:4px">Campanha sem nome</h2></div>'
      + chip("Rascunho","info") + btn("Duplicar de outra campanha","secondary","doc","56-master-promocoes.html") + '</div>'
      + tabs(["Informações básicas","Produtos e regras","Público-alvo","Canais","Condições","Aparência"],"Informações básicas")
      + '<div class="emptyform" style="margin-top:var(--space-5)">' + form([
          campo("Nome da promoção","Ex.: Desconto Progressivo - Alianças",True),
          campo("Período da promoção","dd/mm/aaaa  até  dd/mm/aaaa",True),
          campo("Código da promoção","PROMO-2026-08",True,hint="Sugerido pelo sistema, editável"),
          campo("Status","Rascunho",True,"select",hint="Rascunho não fica visível para nenhum revendedor."),
          campo("Tipo de promoção","Selecione o tipo",True,"select",
                hint="Desconto progressivo · Preço especial · Frete grátis · Desconto fixo · Lançamento"),
          campo("Prioridade de exibição","Média",False,"select",hint="Define a ordem de destaque da promoção na loja."),
        ], 2)
      + '<div class="split" style="--gcols:minmax(0,1fr) 280px;gap:var(--space-5);margin-top:var(--space-4)">'
      + campo("Descrição","Descreva a campanha como o revendedor vai lê-la.",tipo="textarea",hint="Caracteres: 0/500")
      + '<div>' + toggle("Exibir selo na loja","Mostrar selo de destaque na vitrine da loja", False) + '</div></div></div>'
      + notice("Enquanto o status for <strong>Rascunho</strong>, a campanha não aparece para nenhum revendedor e não entra no cálculo de preço.","info")
      + '<div class="row row--wrap" style="margin-top:var(--space-5)">'
      + btn("Cancelar","secondary","x","56-master-promocoes.html",sm=False)
      + btn("Salvar rascunho","secondary","doc",sm=False)
      + btn("Agendar campanha","primary","calendar",sm=False) + '</div>')}
    {card("Produtos e regras", tiers
      + '<div style="margin-top:var(--space-5)"><span class="eyebrow">Produtos incluídos</span>'
      f'<div class="upload" style="margin-top:8px"><span class="upload__ic">{ic("tag")}</span>'
      '<strong>Nenhum produto selecionado</strong>'
      '<small>Escolha produtos, coleções ou categorias inteiras — grava em promotion_products.</small></div>'
      + '<div class="row row--wrap" style="margin-top:var(--space-3)">'
      + btn("Selecionar produtos","secondary","tag","55-master-produtos.html")
      + btn("Selecionar coleção inteira","secondary","diamond") + '</div></div>')}
    {card("Público-alvo e canais", publico)}
  </div>
  <div class="stack">
    {card("Prévia da promoção",
      '<div class="promoprev"><strong>NOME DA CAMPANHA</strong><span>SUBTÍTULO DA CAMPANHA</span>'
      '<em>A PRÉVIA APARECE AO PREENCHER</em><div class="promoprev__tiers">'
      + "".join('<span><b>—</b><small>faixa não definida</small></span>' for _ in range(3))
      + '</div></div>'
      + '<small class="fhint">Esta é uma prévia de como a promoção será exibida na loja. A aparência pode variar em diferentes dispositivos.</small>'
      + btn("Ver na loja ↗","secondary","eye","03-vitrine-pdv.html",sm=False))}
    {card("Resumo da promoção",
      "".join(linha_dado(k, v) for k, v in [
        ("Tipo",'<span class="muted">Não definido</span>'),
        ("Período",'<span class="muted">Não definido</span>'),
        ("Status", chip("Rascunho","info",flat=True)),
        ("Canais",'<span class="muted">Nenhum</span>'),
        ("Público-alvo","Todos os revendedores ativos"),
        ("Orçamento estimado",'<span class="num">R$ 0,00</span>'),
        ("Criado por","Velaro Alianças · Admin"),
        ("Criado em","07/06/2026 às 09:12")]))}
    {card("Revisão antes de publicar", revisao
      + '<small class="fhint">A campanha só pode ser agendada ou ativada com todos os itens marcados.</small>')}
    {notice("Promoção B2B (Velaro → lojista) <strong>não se confunde</strong> com a promoção que o revendedor cria na própria vitrine (doc 3-8, regra 3).")}
    {card("Ações rápidas",
      "".join(f'<a class="seclink" href="{h}">{ic(i)}<span><strong>{e(t)}</strong></span></a>'
        for i, t, h in [("doc","Duplicar campanha existente","56-master-promocoes.html"),
                        ("chart","Ver desempenho de campanhas","64-master-promocao-desempenho.html"),
                        ("promo","Voltar para a lista de promoções","56-master-promocoes.html")]))}
  </div>
</div>'''
W("63-master-promocao-nova.html", M("Nova promoção | 56-master-promocoes.html", body))

# ══════════════════════════ 64 · DESEMPENHO DA PROMOÇÃO ══════════════════════════
# Destino de "Relatório de desempenho" nas ações rápidas da 56.
prodpromo = tabela([("Produto",""),("SKU",""),("Pedidos","cell-num"),("Peças","cell-num"),
                    ("Faturamento","cell-num"),("Desconto concedido","cell-num")],
  [[f'<div class="row" style="gap:10px">{ringimg(v, 930+i)}<span><strong style="color:var(--ink)">{n}</strong>'
    f'<br><small class="muted">Ouro 18K</small></span></div>', f'<code>{sku}</code>',
    f'<span class="num">{p}</span>', f'<span class="num">{q}</span>',
    f'<span class="cell-strong num">{f}</span>', f'<span class="num">{d}</span>']
   for i, (v, n, sku, p, q, f, d) in enumerate([
     ("classica","Aliança Classic 4mm","ALC-4MM-18K","38","72","R$ 40.320,00","R$ 3.628,80"),
     ("conforto","Aliança Classic 6mm","ALC-6MM-18K","21","44","R$ 27.280,00","R$ 2.455,20"),
     ("fosca","Aliança Sole 6mm","ALS-6MM-18K","14","26","R$ 13.000,00","R$ 1.040,00"),
     ("trabalhada","Aliança Trabalhada 6mm","ALT-6MM-18K","9","18","R$ 9.810,00","R$ 686,70"),
     ("diamond","Aliança Diamante 3pts","ALD-3PTS-18K","4","6","R$ 8.100,00","R$ 309,30")])],
  foot='<a class="link-gold" href="67-master-relatorio-produtos.html">Ver todos os produtos →</a>')

faixas = tabela([("Faixa acionada","cell-nowrap"),("Desconto","cell-num"),
                 ("Pedidos","cell-num"),("Faturamento","cell-num")],
  [[f'<strong style="color:var(--ink)">{t}</strong>', f'<span class="num">{d}</span>',
    f'<span class="num">{p}</span>', f'<span class="cell-strong num">{f}</span>']
   for t, d, p, pc, f in [("Acima de R$ 1.000","5%","31","36,0%","R$ 21.480,00"),
                          ("Acima de R$ 2.000","10%","42","48,9%","R$ 48.960,00"),
                          ("Acima de R$ 3.000","15%","13","15,1%","R$ 21.900,00")]])

toprev = tabela([("Revendedor",""),("Pedidos","cell-num"),("Faturamento","cell-num")],
  [[n, f'<span class="num">{p}</span>', f'<span class="cell-strong num">{f}</span>']
   for n, p, f in [("Tomazelli Alianças","18","R$ 21.640,00"),
                   ("João Ferreira Joias &amp; Cia","15","R$ 17.980,00"),
                   ("Aliança &amp; Cia","12","R$ 13.420,00"),
                   ("Romance Joias","9","R$ 9.760,00"),
                   ("D'Luxe Joalheria","7","R$ 7.310,00")]],
  foot='<a class="link-gold" href="66-master-relatorio-revendedores.html">Ver ranking completo →</a>')

body = f'''
{volta("56-master-promocoes.html","Voltar para promoções")}
{head("Desempenho da promoção",
      "Desconto Progressivo - Alianças · PROMO-2026-05 · 01/05/2026 até 31/05/2026",
      btn("Ver promoção","secondary","promo","56-master-promocoes.html")
      + btn("Agendar este relatório","secondary","calendar","68-master-relatorios-agendados.html")
      + btn("Exportar","primary","download"))}
{filtros("Período: 01/05/2026 até 31/05/2026",
  [("Comparar com","Período anterior"),("Revendedor","Todos"),("Produto","Todos")],
  acoes=chip("Ativa","ok") + btn("Limpar filtros","secondary","x"))}
{kpis([("bag","Pedidos com a promoção","86",up("34,4% vs. período anterior"),"gold"),
       ("coin","Faturamento gerado","R$ 92.340,00",up("28,1% vs. período anterior"),"ok"),
       ("tag","Desconto concedido","R$ 8.120,00",up("31,0% vs. período anterior"),"warn"),
       ("chart","Ticket médio","R$ 1.073,72",up("6,4% vs. período anterior"),"violet"),
       ("store","Revendedores que usaram","34",up("21,4% vs. período anterior"),"info")], "g5")}
<div class="split split--wide">
  <div class="stack">
    {card("Evolução no período",
      legenda_grafico() + grafico("promo", LINHA_A, LINHA_B)
      + eixo(["01/05","05/05","10/05","15/05","20/05","25/05","31/05"]),
      acao='<span class="select-fake">Diário</span>')}
    <div class="split" style="--gcols:minmax(0,1fr) minmax(0,1fr)">
      {card("Faixas de desconto acionadas", faixas)}
      {card("Participação no faturamento do mês",
        barras([("Pedidos com a promoção","R$ 92.340,00 · 37,6%",38),
                ("Pedidos sem promoção","R$ 153.440,00 · 62,4%",62)])
        + '<div style="margin-top:var(--space-4)">'
        + barras([("Desconto sobre o faturamento gerado","8,8%",9),
                  ("Meta de desconto da campanha","10,0%",10)], "barlist--gold") + '</div>')}
    </div>
    {card("Ranking de produtos na promoção", f'<div class="densetable">{prodpromo}</div>')}
    {notice("O desconto concedido é calculado sobre o <code>unit_price</code> gravado no item do pedido — recalcular a campanha depois <strong>não muda pedido já criado</strong>.")}
  </div>
  <div class="stack">
    {card("Resumo da campanha",
      "".join(linha_dado(k, v) for k, v in [
        ("Código","PROMO-2026-05"),("Tipo","Desconto progressivo"),
        ("Período","01/05/2026 até 31/05/2026"),
        ("Status", chip("Ativa","ok",flat=True)),
        ("Canais","Loja online, WhatsApp, E-mail"),
        ("Público-alvo","Todos os revendedores ativos"),
        ("Produtos vinculados",'<span class="num">18 SKUs</span>'),
        ("Orçamento estimado",'<span class="num">R$ 0,00</span>')])
      + btn("Editar promoção","secondary","edit","56-master-promocoes.html",sm=False))}
    {card("Top revendedores na campanha", toprev)}
    {card("Indicadores de adesão",
      "".join(linha_dado(k, v) for k, v in [
        ("Revendedores elegíveis",'<span class="num">248</span>'),
        ("Revendedores que usaram",'<span class="num">34</span>'),
        ("Taxa de adesão",'<span class="num">13,7%</span>'),
        ("Peças vendidas na campanha",'<span class="num">166</span>'),
        ("Pedidos por revendedor",'<span class="num">2,5</span>')]))}
    {card("Ações",
      btn("Exportar desempenho","secondary","download",sm=False)
      + btn("Duplicar campanha","secondary","doc","63-master-promocao-nova.html",sm=False)
      + btn("Pausar campanha","danger","x",sm=False))}
  </div>
</div>'''
W("64-master-promocao-desempenho.html", M("Desempenho da promoção | 56-master-promocoes.html", body))

# ══════════════════════════ 65 · VENDAS POR PERÍODO ══════════════════════════
# Primeiro atalho de "Relatórios rápidos" da 57.
SEM = [("01/05 a 07/05","54","132","R$ 52.480,00","R$ 1.310,00","R$ 51.170,00","R$ 971,85"),
       ("08/05 a 14/05","61","148","R$ 60.120,00","R$ 1.520,00","R$ 58.600,00","R$ 985,57"),
       ("15/05 a 21/05","58","144","R$ 57.640,00","R$ 1.480,00","R$ 56.160,00","R$ 993,79"),
       ("22/05 a 28/05","49","124","R$ 48.960,00","R$ 1.220,00","R$ 47.740,00","R$ 999,18"),
       ("29/05 a 31/05","26","64","R$ 26.580,00","R$ 720,00","R$ 25.860,00","R$ 1.022,31")]
vendas = tabela([("Período",""),("Pedidos","cell-num"),("Peças","cell-num"),("Faturamento bruto","cell-num"),
                 ("Descontos","cell-num"),("Faturamento líquido","cell-num"),("Ticket médio","cell-num")],
  [[f'<strong style="color:var(--ink)">{p}</strong>'] + [f'<span class="num">{x}</span>' for x in [pe, pc, fb, ds]]
   + [f'<span class="cell-strong num">{fl}</span>', f'<span class="num">{tm}</span>']
   for p, pe, pc, fb, ds, fl, tm in SEM],
  foot='<div class="spread"><strong>Total do período</strong>'
       '<span class="num">248 pedidos · 612 peças · R$ 245.780,00 bruto · R$ 239.530,00 líquido</span></div>')

body = f'''
{volta("57-master-relatorios.html","Voltar para relatórios")}
{head("Vendas por período","Faturamento B2B da Velaro para os revendedores, comparado ao período anterior.",
      btn("Agendar este relatório","secondary","calendar","68-master-relatorios-agendados.html")
      + btn("Exportar CSV","secondary","download") + btn("Exportar PDF","primary","doc"))}
{filtros("Período: 01/05/2026 até 31/05/2026",
  [("Comparar com","Período anterior"),("Revendedor","Todos"),("Categoria","Todas"),("Agrupar por","Semana")],
  acoes=btn("Limpar filtros","secondary","x"))}
{kpis([("coin","Faturamento bruto","R$ 245.780,00",up("18,6% vs. período anterior"),"ok"),
       ("tag","Descontos concedidos","R$ 6.250,00",up("9,2% vs. período anterior"),"warn"),
       ("chart","Faturamento líquido","R$ 239.530,00",up("18,8% vs. período anterior"),"gold"),
       ("bag","Pedidos realizados","248",up("12,4% vs. período anterior"),"violet"),
       ("box","Ticket médio","R$ 990,24",up("5,7% vs. período anterior"),"info")], "g5")}
<div class="split split--wide">
  <div class="stack">
    {card("Faturamento ao longo do tempo",
      legenda_grafico() + grafico("vendas", LINHA_A, LINHA_B)
      + eixo(["01/05","05/05","10/05","15/05","20/05","25/05","31/05"]),
      acao='<span class="select-fake">Diário</span>')}
    {card("Vendas detalhadas", f'<div class="densetable">{vendas}</div>', acao='<span class="select-fake">Agrupado por semana</span>')}
    {notice("Exportação pesada sempre roda como <strong>job assíncrono</strong>: o arquivo é gerado em segundo plano e registrado em <code>report_exports</code> (doc 3-9, regra 3).")}
  </div>
  <div class="stack">
    {card("Faturamento por categoria",
      barras([("Alianças Tradicionais","R$ 128.420,00 · 52,2%",52),
              ("Alianças com Diamante","R$ 61.320,00 · 24,9%",25),
              ("Alianças Trabalhadas","R$ 37.760,00 · 15,4%",15),
              ("Solitários e anéis","R$ 18.280,00 · 7,5%",8)]))}
    {card("Comparativo com o período anterior",
      "".join(linha_dado(k, v) for k, v in [
        ("Faturamento bruto",'<span class="num">R$ 207.240,00</span>'),
        ("Pedidos",'<span class="num">221</span>'),
        ("Ticket médio",'<span class="num">R$ 937,00</span>'),
        ("Variação do faturamento",'<span class="num" style="color:var(--color-success-700)">+18,6%</span>'),
        ("Variação do ticket",'<span class="num" style="color:var(--color-success-700)">+5,7%</span>')]))}
    {card("Exportações recentes",
      "".join(f'<div class="docfile">{ic("doc")}<span><strong>{e(n)}</strong><small>{e(d)}</small></span>'
              f'<b class="docfile__ok">↓</b></div>'
        for n, d in [("vendas-2026-05.csv","07/06/2026 às 09:04 · Admin · 68 KB"),
                     ("vendas-2026-04.pdf","02/05/2026 às 08:11 · Admin · 412 KB"),
                     ("vendas-2026-03.csv","02/04/2026 às 08:09 · Admin · 64 KB")])
      + '<small class="fhint">Histórico de <code>report_exports</code> — tipo, filtros, arquivo e quem gerou.</small>')}
    {card("Outros relatórios",
      "".join(f'<a class="seclink" href="{h}">{ic(i)}<span><strong>{e(t)}</strong><small>{e(d)}</small></span></a>'
        for i, t, d, h in [
          ("store","Ranking de revendedores","Quem mais comprou no período.","66-master-relatorio-revendedores.html"),
          ("tag","Produtos mais vendidos","Peças e faturamento por SKU.","67-master-relatorio-produtos.html"),
          ("list","Todos os relatórios","Índice completo dos tipos previstos.","69-master-relatorios-biblioteca.html")]))}
  </div>
</div>'''
W("65-master-relatorio-vendas.html", M("Vendas por período | 57-master-relatorios.html", body))

# ══════════════════════════ 66 · RANKING DE REVENDEDORES ══════════════════════════
# Destino do "Ver ranking completo →" da 57.
RANK = [(1,"João Ferreira Joias &amp; Cia","São Paulo / SP","76","188","R$ 78.450,00","R$ 1.032,24","31/05/2026","+22,4%","ok"),
        (2,"Aliança &amp; Cia","Joinville / SC","53","131","R$ 54.230,00","R$ 1.023,21","30/05/2026","+14,8%","ok"),
        (3,"Tomazelli Alianças","São José do Rio Preto / SP","41","104","R$ 42.180,00","R$ 1.028,78","31/05/2026","+31,2%","ok"),
        (4,"Romance Joias","Caxias do Sul / RS","39","92","R$ 36.780,00","R$ 943,08","29/05/2026","+6,1%","ok"),
        (5,"Carla Lima Joias","Rio de Janeiro / RJ","29","68","R$ 28.560,00","R$ 984,83","28/05/2026","−3,4%","danger"),
        (6,"D'Luxe Joalheria","Belo Horizonte / MG","23","54","R$ 21.760,00","R$ 945,22","27/05/2026","+9,7%","ok"),
        (7,"Brilho Eterno","Curitiba / PR","19","44","R$ 18.240,00","R$ 960,00","26/05/2026","+4,2%","ok"),
        (8,"Aliança Ouro Fino","Goiânia / GO","16","38","R$ 15.680,00","R$ 980,00","25/05/2026","−1,8%","danger"),
        (9,"Joalheria Prime","Fortaleza / CE","14","32","R$ 13.420,00","R$ 958,57","24/05/2026","+11,3%","ok"),
        (10,"Essência Joias","Recife / PE","12","28","R$ 11.480,00","R$ 956,67","22/05/2026","+2,6%","ok")]
rank = tabela([("Posição","cell-num"),("Revendedor",""),("Cidade / UF",""),("Pedidos","cell-num"),
               ("Peças","cell-num"),("Faturamento","cell-num"),("Ticket médio","cell-num"),
               ("Última compra",""),("Variação","cell-num")],
  [[f'<span class="num">{i}º</span>',
    f'<div class="row" style="gap:10px"><span class="avatar avatar--sm">{n.replace("&amp;","").split()[0][:2].upper()}</span>'
    f'<a class="link-gold" href="58-master-revendedores.html">{n}</a></div>', c,
    f'<span class="num">{p}</span>', f'<span class="num">{q}</span>',
    f'<span class="cell-strong num">{f}</span>', f'<span class="num">{t}</span>',
    f'<span class="num">{u}</span>',
    f'<span class="kpi__delta kpi__delta--{"up" if tom=="ok" else "down"}">{v}</span>']
   for i, n, c, p, q, f, t, u, v, tom in RANK],
  foot=pag("Mostrando 1 a 10 de 148 revendedores com compra no período","1 2 3 … 15",
           '<span class="select-fake">10 por página</span>'))

body = f'''
{volta("57-master-relatorios.html","Voltar para relatórios")}
{head("Ranking de revendedores","Quem mais comprou da Velaro no período, com ticket médio e variação.",
      btn("Agendar este relatório","secondary","calendar","68-master-relatorios-agendados.html")
      + btn("Exportar CSV","secondary","download") + btn("Exportar PDF","primary","doc"))}
{filtros("Buscar revendedor…",
  [("Período","01/05/2026 até 31/05/2026"),("Região / UF","Todas"),("Plano","Todos"),("Ordenar por","Faturamento")],
  acoes=btn("Limpar filtros","secondary","x"))}
{kpis([("store","Revendedores com compra","148",up("9,6% vs. período anterior"),"gold"),
       ("coin","Faturamento total","R$ 245.780,00",up("18,6% vs. período anterior"),"ok"),
       ("chart","Ticket médio geral","R$ 990,24",up("5,7% vs. período anterior"),"violet"),
       ("clock","Sem compra no período","100",down("6,5% vs. período anterior"),"warn")])}
<div class="split split--wide">
  <div class="stack">
    {card("Ranking completo", f'<div class="densetable">{rank}</div>')}
    {notice("O ranking considera apenas pedidos <strong>com pagamento confirmado</strong> no período. Pedidos aguardando baixa entram no relatório do mês em que o lote for quitado.")}
  </div>
  <div class="stack">
    {card("Concentração do faturamento",
      barras([("Top 3 revendedores","R$ 174.860,00 · 71,1%",71),
              ("4º ao 10º","R$ 44.680,00 · 18,2%",18),
              ("Demais 138","R$ 26.240,00 · 10,7%",11)])
      + '<small class="fhint">Concentração alta em poucos revendedores é risco comercial: acompanhar no plano de expansão.</small>')}
    {card("Maiores crescimentos",
      "".join(f'<div class="datarow"><span class="datarow__k">{ic("arrow-up")}<span>'
              f'<strong style="display:block;color:var(--ink)">{n}</strong><small>{d}</small></span></span>'
              f'<span class="datarow__v"><span class="kpi__delta kpi__delta--up">{v}</span></span></div>'
        for n, d, v in [("Tomazelli Alianças","São José do Rio Preto / SP","+31,2%"),
                        ("João Ferreira Joias &amp; Cia","São Paulo / SP","+22,4%"),
                        ("Aliança &amp; Cia","Joinville / SC","+14,8%")]))}
    {card("Sem compra no período",
      "".join(f'<div class="datarow"><span class="datarow__k">{ic("clock")}<span>'
              f'<strong style="display:block;color:var(--ink)">{n}</strong><small>Última compra: {d}</small></span></span>'
              f'<span class="datarow__v">{chip("Reativar","warn",flat=True)}</span></div>'
        for n, d in [("Joias do Vale","28/03/2026"),("Única Alianças","14/03/2026"),("Brilho &amp; Cia","02/03/2026")])
      + btn("Ver lista completa","secondary","store","58-master-revendedores.html",sm=False))}
    {card("Outros relatórios",
      "".join(f'<a class="seclink" href="{h}">{ic(i)}<span><strong>{e(t)}</strong><small>{e(d)}</small></span></a>'
        for i, t, d, h in [
          ("chart","Vendas por período","Faturamento e ticket médio.","65-master-relatorio-vendas.html"),
          ("tag","Produtos mais vendidos","Peças e faturamento por SKU.","67-master-relatorio-produtos.html"),
          ("list","Todos os relatórios","Índice completo dos tipos previstos.","69-master-relatorios-biblioteca.html")]))}
  </div>
</div>'''
W("66-master-relatorio-revendedores.html", M("Ranking de revendedores | 57-master-relatorios.html", body))

# ══════════════════════════ 67 · PRODUTOS MAIS VENDIDOS ══════════════════════════
# Destino do "Ver todos os produtos →" da 57.
TOP = [(1,"classica","Aliança Classic 4mm","ALC-4MM-18K","Alianças Tradicionais","156","R$ 175.560,00","28,6%","142","+18,2%","ok"),
       (2,"conforto","Aliança Classic 6mm","ALC-6MM-18K","Alianças Tradicionais","132","R$ 165.000,00","26,9%","96","+12,4%","ok"),
       (3,"fosca","Aliança Sole 6mm","ALS-6MM-18K","Alianças Foscas","98","R$ 98.000,00","16,0%","18","+9,8%","ok"),
       (4,"fosca","Aliança Sole 8mm","ALS-8MM-18K","Alianças Foscas","76","R$ 80.120,00","13,1%","0","−4,1%","danger"),
       (5,"diamond","Aliança Diamante 3pts","ALD-3PTS-18K","Alianças com Diamante","42","R$ 63.250,00","10,3%","34","+22,6%","ok"),
       (6,"cravejada","Aliança Diamante 5pts","ALD-5PTS-18K","Alianças com Diamante","28","R$ 46.200,00","7,5%","12","+6,3%","ok"),
       (7,"trabalhada","Aliança Trabalhada 6mm","ALT-6MM-18K","Alianças Trabalhadas","24","R$ 26.160,00","4,3%","7","−2,8%","danger"),
       (8,"trabalhada","Aliança Trabalhada 8mm","ALT-8MM-18K","Alianças Trabalhadas","19","R$ 24.130,00","3,9%","19","+3,5%","ok"),
       (9,"rose","Aliança Rose 4mm","ALR-ROSE-4MM","Alianças Rosé","17","R$ 18.020,00","2,9%","19","+14,0%","ok"),
       (10,"diamond","Anel Solitário Classic","ALS-CLASS-PT","Solitários e anéis","12","R$ 21.600,00","3,5%","34","+7,7%","ok")]
top = tabela([("Posição","cell-num"),("Produto",""),("SKU",""),("Categoria",""),("Peças","cell-num"),
              ("Faturamento","cell-num"),("% do faturamento","cell-num"),("Estoque atual","cell-num"),("Variação","cell-num")],
  [[f'<span class="num">{i}º</span>',
    f'<div class="row" style="gap:10px">{ringimg(v, 940+i)}<span>'
    f'<a class="link-gold" href="55-master-produtos.html">{n}</a><br><small class="muted">Ouro 18K</small></span></div>',
    f'<code>{sku}</code>', cat, f'<span class="num">{q}</span>',
    f'<span class="cell-strong num">{f}</span>', f'<span class="num">{pc}</span>',
    (f'<a class="link-gold num" href="52-master-estoque.html">{est}</a>' if est != "0"
     else f'<span class="num" style="color:var(--color-error-700)">0</span>'),
    f'<span class="kpi__delta kpi__delta--{"up" if tom=="ok" else "down"}">{va}</span>']
   for i, v, n, sku, cat, q, f, pc, est, va, tom in TOP],
  foot=pag("Mostrando 1 a 10 de 86 SKUs vendidos no período","1 2 3 … 9",
           '<span class="select-fake">10 por página</span>'))

body = f'''
{volta("57-master-relatorios.html","Voltar para relatórios")}
{head("Produtos mais vendidos","Peças e faturamento por SKU no período, com o saldo de estoque de cada item.",
      btn("Agendar este relatório","secondary","calendar","68-master-relatorios-agendados.html")
      + btn("Exportar CSV","secondary","download") + btn("Exportar PDF","primary","doc"))}
{filtros("Buscar produto, SKU ou coleção…",
  [("Período","01/05/2026 até 31/05/2026"),("Categoria","Todas"),("Coleção","Todas"),
   ("Material","Todos"),("Ordenar por","Peças vendidas")],
  acoes=btn("Limpar filtros","secondary","x"))}
{kpis([("box","Peças vendidas","612",up("15,2% vs. período anterior"),"gold"),
       ("tag","SKUs com venda","86",up("7,5% vs. período anterior"),"violet"),
       ("coin","Faturamento dos produtos","R$ 245.780,00",up("18,6% vs. período anterior"),"ok"),
       ("info","SKUs sem venda no período","162",down("4,1% vs. período anterior"),"warn")])}
<div class="split split--wide">
  <div class="stack">
    {card("Ranking de produtos", f'<div class="densetable">{top}</div>')}
    {notice("A coluna de estoque vem do módulo de Estoque e é o saldo <strong>de agora</strong>, não o do período — SKU com venda alta e saldo zero é gatilho de reposição prioritária.")}
  </div>
  <div class="stack">
    {card("Peças por categoria",
      barras([("Alianças Tradicionais","288 peças · 47,1%",47),
              ("Alianças Foscas","174 peças · 28,4%",28),
              ("Alianças com Diamante","70 peças · 11,4%",11),
              ("Alianças Trabalhadas","43 peças · 7,0%",7),
              ("Outros","37 peças · 6,1%",6)]))}
    {card("Peças por material",
      barras([("Ouro 18k Amarelo","438 peças · 71,6%",72),
              ("Ouro 18k Branco","112 peças · 18,3%",18),
              ("Ouro 18k Rosé","62 peças · 10,1%",10)], "barlist--gold"))}
    {card("Precisam de reposição",
      "".join(f'<div class="datarow"><span class="datarow__k">{ic("box")}<span>'
              f'<strong style="display:block;color:var(--ink)">{n}</strong><small>{sku} · {d}</small></span></span>'
              f'<span class="datarow__v">{chip(st, tom, flat=True)}</span></div>'
        for n, sku, d, st, tom in [
          ("Aliança Sole 8mm","ALS-8MM-18K","76 peças vendidas · saldo 0","Reservado","info"),
          ("Aliança Diamantada 6mm","ALD-6MM-OU","38 peças vendidas · saldo 18","Baixo estoque","warn"),
          ("Aliança Vintage 5mm","APZ-VINT-OU","21 peças vendidas · saldo 7","Baixo estoque","warn")])
      + btn("Abrir estoque","secondary","box","52-master-estoque.html",sm=False))}
    {card("Outros relatórios",
      "".join(f'<a class="seclink" href="{h}">{ic(i)}<span><strong>{e(t)}</strong><small>{e(d)}</small></span></a>'
        for i, t, d, h in [
          ("chart","Vendas por período","Faturamento e ticket médio.","65-master-relatorio-vendas.html"),
          ("store","Ranking de revendedores","Quem mais comprou no período.","66-master-relatorio-revendedores.html"),
          ("list","Todos os relatórios","Índice completo dos tipos previstos.","69-master-relatorios-biblioteca.html")]))}
  </div>
</div>'''
W("67-master-relatorio-produtos.html", M("Produtos mais vendidos | 57-master-relatorios.html", body))

# ══════════════════════════ 68 · RELATÓRIOS AGENDADOS ══════════════════════════
# Destino do botão "Agendar relatórios" e do "Gerenciar agendamentos →" da 57.
# Espelha report_schedules: name, type, cron, recipients, format, is_active, last_run_at.
AGD = [("Relatório semanal de vendas","Vendas por período","Toda segunda-feira às 08:00","0 8 * * 1","PDF",
        "3 destinatários","01/06/2026 08:00","08/06/2026 08:00","Ativo","ok"),
       ("Relatório de estoque","Estoque atual","Todo dia 1º às 09:00","0 9 1 * *","CSV",
        "2 destinatários","01/06/2026 09:00","01/07/2026 09:00","Ativo","ok"),
       ("Relatório financeiro mensal","Financeiro","Todo dia 5 às 10:00","0 10 5 * *","PDF",
        "4 destinatários","05/06/2026 10:00","05/07/2026 10:00","Ativo","ok"),
       ("Ranking de revendedores","Revendedores","Todo dia 1º às 07:30","30 7 1 * *","XLSX",
        "2 destinatários","01/06/2026 07:30","01/07/2026 07:30","Ativo","ok"),
       ("Produtos mais vendidos","Produtos","Toda sexta-feira às 18:00","0 18 * * 5","CSV",
        "1 destinatário","05/06/2026 18:00","12/06/2026 18:00","Ativo","ok"),
       ("Inadimplência","Inadimplência","Toda quarta-feira às 09:00","0 9 * * 3","PDF",
        "3 destinatários","03/06/2026 09:00","10/06/2026 09:00","Pausado","warn"),
       ("Pedidos por status","Pedidos por status","Diário às 07:00","0 7 * * *","CSV",
        "2 destinatários","07/06/2026 07:00","08/06/2026 07:00","Ativo","ok"),
       ("Clientes novos no mês","Clientes","Todo dia 1º às 08:30","30 8 1 * *","CSV",
        "1 destinatário","01/06/2026 08:30","01/07/2026 08:30","Falhou","danger")]
agendados = tabela([("Nome",""),("Tipo de relatório",""),("Frequência",""),("Formato",""),
                    ("Destinatários",""),("Último envio",""),("Próximo envio",""),("Status",""),("Ações","cell-num")],
  [[f'<strong style="color:var(--ink)">{n}</strong>', t,
    f'{fr}<br><small class="muted"><code>{cr}</code></small>', chip(fm,"neutral",flat=True),
    ds, f'<span class="num">{ul}</span>', f'<span class="num">{px}</span>',
    chip(st, tom, flat=True), '<span class="muted">⋯</span>']
   for n, t, fr, cr, fm, ds, ul, px, st, tom in AGD], check=True,
  foot=pag("Mostrando 1 a 8 de 8 agendamentos","1", '<span class="select-fake">10 por página</span>'))

novo = drawer("Novo agendamento", "".join([
  form([campo("Nome do agendamento","Relatório semanal de vendas",True),
        campo("Tipo de relatório","Vendas por período",True,"select",
              hint="Um dos tipos previstos na biblioteca de relatórios"),
        campo("Frequência","Semanal",True,"select"),
        campo("Dia da semana","Segunda-feira",True,"select"),
        campo("Horário","08:00",True),
        campo("Formato","PDF",True,"select",hint="PDF, CSV ou XLSX"),
        campo("Período coberto","Últimos 7 dias",True,"select"),
        campo("Fuso horário","(GMT-03:00) Brasília",True,"select")], 2),
  campo("Destinatários","diretoria@velaro.com.br, comercial@velaro.com.br, financeiro@velaro.com.br",True,
        hint="Grava em report_schedules.recipients — separados por vírgula"),
  campo("Filtros salvos","Todos os revendedores · Todas as categorias",tipo="select",
        hint="Os filtros são guardados como JSON junto do agendamento"),
  toggle("Agendamento ativo","Desligue para pausar sem apagar o histórico de envios"),
  toggle("Avisar quando a geração falhar","Envia alerta ao responsável se o job não concluir"),
  bloco("Expressão gerada", '<p class="lede" style="font-size:var(--text-sm)"><code>0 8 * * 1</code> — '
        'toda segunda-feira às 08:00, horário de Brasília.</p>'),
]), sub="report_schedules", chip_html=chip("Rascunho","info"),
  acoes=btn("Enviar agora (teste)","secondary","mail",sm=False)
        + btn("Cancelar","secondary","x","57-master-relatorios.html",sm=False)
        + btn("Salvar agendamento","primary","check",sm=False)
        + notice("A geração roda como <strong>job assíncrono</strong> e nunca no controller: o arquivo fica em <code>report_exports</code> e o envio registra <code>last_run_at</code> (doc 3-9, regra 3)."))

body = f'''
{volta("57-master-relatorios.html","Voltar para relatórios")}
{head("Relatórios agendados","Envios automáticos por e-mail, com frequência, formato e destinatários.",
      btn("Ver histórico de envios","secondary","clock") + btn("+ Novo agendamento","primary","plus"))}
{kpis([("calendar","Agendamentos ativos","7",up("2 novos no mês"),"gold"),
       ("mail","Envios no mês","34",up("13,3% vs. mês anterior"),"ok"),
       ("clock","Próximo envio","08/06 07:00","Pedidos por status","info"),
       ("x","Falhas nos últimos 30 dias","1",down("2 falhas a menos"),"danger")])}
<div class="split split--wide">
  <div class="stack">
    {filtros("Buscar agendamento por nome, tipo ou destinatário…",
      [("Tipo","Todos"),("Frequência","Todas"),("Formato","Todos"),("Status","Todos")],
      acoes=btn("Filtros","secondary","filter") + btn("Exportar","secondary","download"))}
    {card(None, f'<div class="densetable">{agendados}</div>')}
    {card("Últimos envios",
      "".join(f'<div class="datarow"><span class="datarow__k">{ic(i)}<span>'
              f'<strong style="display:block;color:var(--ink)">{t}</strong><small>{d}</small></span></span>'
              f'<span class="datarow__v">{chip(st, tom, flat=True)}</span></div>'
        for i, t, d, st, tom in [
          ("mail","Pedidos por status","07/06/2026 às 07:00 · 2 destinatários · CSV · 42 KB","Enviado","ok"),
          ("mail","Produtos mais vendidos","05/06/2026 às 18:00 · 1 destinatário · CSV · 38 KB","Enviado","ok"),
          ("mail","Relatório financeiro mensal","05/06/2026 às 10:00 · 4 destinatários · PDF · 512 KB","Enviado","ok"),
          ("x","Clientes novos no mês","01/06/2026 às 08:30 · falha ao gerar o arquivo","Falhou","danger")]))}
    {notice("Pausar um agendamento não apaga o histórico: o registro fica em <code>report_schedules</code> com <code>is_active = false</code> e o histórico de <code>report_exports</code> permanece.")}
  </div>
  {novo}
</div>'''
W("68-master-relatorios-agendados.html", M("Relatórios agendados | 57-master-relatorios.html", body))

# ══════════════════════════ 69 · TODOS OS RELATÓRIOS ══════════════════════════
# Destino do "Ver todos os relatórios →" da 57. Índice dos tipos previstos na
# regra 1 do doc 3-9 — cada card leva ao relatório correspondente.
def grupo(titulo, itens):
    cards = "".join(
      f'<a class="quickcard" href="{h}">{ic(i)}<span><strong>{e(t)}</strong><small>{e(d)}</small></span><b>›</b></a>'
      for i, t, d, h in itens)
    return bloco(titulo, f'<div class="quickgrid" style="margin-top:8px">{cards}</div>')

body = f'''
{volta("57-master-relatorios.html","Voltar para relatórios")}
{head("Todos os relatórios","Os nove tipos previstos no escopo, cada um com filtros de período, exportação e agendamento.",
      btn("Relatórios agendados","secondary","calendar","68-master-relatorios-agendados.html")
      + btn("Exportar","primary","download"))}
{filtros("Buscar relatório por nome ou assunto…", [("Domínio","Todos"),("Formato","Todos")],
  acoes=btn("Limpar filtros","secondary","x"))}
<div class="split split--wide">
  <div class="stack">
    {card("Biblioteca de relatórios",
      grupo("Comercial", [
        ("chart","Faturamento B2B","Vendas e ticket médio por período, com comparativo.","65-master-relatorio-vendas.html"),
        ("store","Revendedores","Ranking de compras, ticket médio e variação.","66-master-relatorio-revendedores.html"),
        ("tag","Produtos","Peças e faturamento por SKU, categoria e material.","67-master-relatorio-produtos.html"),
        ("users","Clientes","Clientes finais por revendedor, novos e recorrentes.","50-master-clientes.html")])
      + '<div style="margin-top:var(--space-5)"></div>'
      + grupo("Operacional", [
        ("bag","Pedidos por status","Distribuição dos pedidos por situação operacional.","57-master-relatorios.html"),
        ("box","Estoque","Saldo por SKU e aro, reservas e reposição.","52-master-estoque.html"),
        ("truck","Indicadores operacionais","Prazo de produção, remessas e retiradas.","57-master-relatorios.html")])
      + '<div style="margin-top:var(--space-5)"></div>'
      + grupo("Financeiro", [
        ("coin","Financeiro","Recebimentos, baixas, notas fiscais e liberações.","53-master-financeiro.html"),
        ("card","Inadimplência","Lotes vencidos, valor em aberto e prazo médio de atraso.","53-master-financeiro.html")]))}
    {card("Como cada relatório se comporta",
      tabela([("Etapa",""),("O que acontece",""),("Onde fica registrado","")],
        [[f'<strong style="color:var(--ink)">{t}</strong>', d, f'<code>{r}</code>']
         for t, d, r in [
           ("Consulta em tela","Filtros de período, revendedor e categoria aplicados na hora.","velaro.reports.view"),
           ("Exportação","Arquivo gerado por job assíncrono, nunca no controller.","report_exports"),
           ("Agendamento","Envio recorrente por e-mail, com frequência e formato.","report_schedules"),
           ("Permissão","Ver, exportar e agendar são permissões separadas.","velaro.reports.export / schedule")]]))}
    {notice("Exportação pesada sempre via job — nunca síncrona no controller (doc 3-9, regra 3).")}
  </div>
  <div class="stack">
    {card("Atalhos",
      "".join(f'<a class="seclink" href="{h}">{ic(i)}<span><strong>{e(t)}</strong><small>{e(d)}</small></span></a>'
        for i, t, d, h in [
          ("chart","Painel de relatórios","Visão consolidada com KPIs e gráficos.","57-master-relatorios.html"),
          ("calendar","Relatórios agendados","Gerencie envios automáticos.","68-master-relatorios-agendados.html"),
          ("promo","Desempenho de promoções","Resultado de uma campanha específica.","64-master-promocao-desempenho.html")]))}
    {card("Exportações recentes",
      "".join(f'<div class="docfile">{ic("doc")}<span><strong>{e(n)}</strong><small>{e(d)}</small></span>'
              f'<b class="docfile__ok">↓</b></div>'
        for n, d in [("vendas-2026-05.csv","07/06/2026 às 09:04 · 68 KB"),
                     ("revendedores-2026-05.xlsx","01/06/2026 às 07:30 · 124 KB"),
                     ("financeiro-2026-05.pdf","05/06/2026 às 10:00 · 512 KB")])
      + '<small class="fhint">Guardadas em <code>report_exports</code> com tipo, filtros e quem gerou.</small>')}
    {card("Permissões do módulo",
      "".join(linha_dado(k, f'<code>{v}</code>') for k, v in [
        ("Ver relatórios","velaro.reports.view"),
        ("Exportar","velaro.reports.export"),
        ("Agendar","velaro.reports.schedule")])
      + '<small class="fhint">Perfil Master exige <code>is_admin</code> mais o gate <code>access-backend</code>.</small>')}
  </div>
</div>'''
W("69-master-relatorios-biblioteca.html", M("Todos os relatórios | 57-master-relatorios.html", body))

# ══════════════════════════ 70 · SUPORTE — FILA DE CHAMADOS ══════════════════════════
# A rota GET /backend/suporte, que não tinha mockup: a 60 é só o DETALHE de um
# chamado. É também o destino do "← Voltar para todas as solicitações" da 60.
SUP = [("#SUP-2026-0598","Troca de tamanho de aliança - tamanho errado","Tomazelli Alianças","TOM-0001",
        "Troca / Produto","Média","warn","Em atendimento","info","Equipe Velaro Suporte","PED-2026-0587","Há 35 minutos"),
       ("#SUP-2026-0597","Pedido não chegou na data prevista","Aliança &amp; Cia","ALC-0042",
        "Entrega","Alta","danger","Aberta","warn","Não atribuído","PED-2026-05762","Há 1 hora"),
       ("#SUP-2026-0596","Divergência na nota fiscal do lote SEM-2404","Aliança Ouro Fino","AOF-0018",
        "Financeiro","Alta","danger","Em atendimento","info","Equipe Velaro Financeiro","—","Há 2 horas"),
       ("#SUP-2026-0595","Gravação saiu com letra errada","Romance Joias","RMJ-0087",
        "Troca / Produto","Média","warn","Aguardando revendedor","violet","Equipe Velaro Suporte","PED-2026-05741","Há 4 horas"),
       ("#SUP-2026-0594","Como alterar o preço de venda na vitrine","D'Luxe Joalheria","DLX-0031",
        "Dúvida / Plataforma","Baixa","neutral","Resolvida","ok","Equipe Velaro Suporte","—","Hoje às 09:12"),
       ("#SUP-2026-0593","Solicitação de segunda via de boleto","Brilho Eterno","BRE-0064",
        "Financeiro","Baixa","neutral","Resolvida","ok","Equipe Velaro Financeiro","—","Hoje às 08:40"),
       ("#SUP-2026-0592","Produto sumiu do catálogo do portal","Joalheria Prime","JLP-0102",
        "Dúvida / Plataforma","Média","warn","Em atendimento","info","Equipe Velaro Suporte","—","Ontem às 17:22"),
       ("#SUP-2026-0591","Cliente desistiu — cancelar pedido","Essência Joias","ESJ-0119",
        "Cancelamento","Alta","danger","Aguardando revendedor","violet","Equipe Velaro Suporte","PED-2026-05688","Ontem às 15:05")]
fila = tabela([("Chamado",""),("Revendedor",""),("Categoria",""),("Prioridade",""),("Status",""),
               ("Responsável",""),("Pedido relacionado",""),("Última atualização",""),("Ações","cell-num")],
  [[f'<a class="link-gold cell-nowrap" href="60-master-suporte.html"><strong>{cod}</strong></a>'
    f'<br><small class="muted">{assunto}</small>',
    f'{rv}<br><small class="muted">{cd}</small>', cat, chip(pr, prtom, flat=True), chip(st, sttom, flat=True),
    resp, (f'<a class="link-gold" href="54-master-pedidos.html">{ped}</a>' if ped != "—" else '<span class="muted">—</span>'),
    f'<small>{at}</small>',
    f'<a class="link-gold" href="60-master-suporte.html">Abrir →</a>']
   for cod, assunto, rv, cd, cat, pr, prtom, st, sttom, resp, ped, at in SUP], check=True,
  foot=pag("Mostrando 1 a 8 de 42 chamados","1 2 3 … 6", '<span class="select-fake">10 por página</span>'))

body = f'''
{head("Suporte","Fila de chamados abertos pelos revendedores. Abra um chamado para responder, transferir ou resolver.",
      btn("Exportar","secondary","download") + btn("Atualizar fila","secondary","refresh")
      + btn("+ Novo chamado interno","primary","plus"))}
{kpis([("support","Chamados abertos","18",up("12,5% vs. semana anterior"),"warn"),
       ("clock","Em atendimento","12",flat("mesmo volume da semana anterior"),"info"),
       ("check","Resolvidos hoje","9",up("28,6% vs. média diária"),"ok"),
       ("chart","Tempo médio de resposta","2h14",down("22 minutos vs. semana anterior"),"violet")])}
<div class="split split--wide">
  <div class="stack">
    {filtros("Buscar por código, revendedor, assunto ou pedido…",
      [("Status","Todos"),("Prioridade","Todas"),("Categoria","Todas"),("Responsável","Todos"),("Período","Últimos 30 dias")],
      acoes=btn("Filtros","secondary","filter") + btn("Exportar","secondary","download"))}
    {card(None, tabs(["Todos","Abertos","Em atendimento","Aguardando revendedor","Resolvidos"],"Todos"))}
    {card(None, f'<div class="densetable">{fila}</div>')}
    {notice("A conversa é <strong>Velaro ↔ revendedor</strong>. O cliente final aparece apenas como pessoa vinculada ao pedido e não participa do atendimento (Anexo I §5.12).")}
    {notice("Observação interna nunca é visível ao revendedor: ela fica em <code>support_messages.is_internal_note</code> e só aparece no painel interno (doc 3-12, regra 3).","info")}
  </div>
  <div class="stack">
    {card("Chamados por categoria",
      barras([("Troca / Produto","14 chamados · 33,3%",33),
              ("Entrega","10 chamados · 23,8%",24),
              ("Financeiro","8 chamados · 19,0%",19),
              ("Dúvida / Plataforma","6 chamados · 14,3%",14),
              ("Cancelamento","4 chamados · 9,6%",10)]))}
    {card("Atendimento",
      "".join(linha_dado(k, v) for k, v in [
        ("Não atribuídos",'<span class="num" style="color:var(--color-error-700)">3</span>'),
        ("Equipe Velaro Suporte",'<span class="num">21</span>'),
        ("Equipe Velaro Financeiro",'<span class="num">9</span>'),
        ("Prioridade alta em aberto",'<span class="num">5</span>'),
        ("Mais antigo em aberto","Há 2 dias")])
      + btn("Distribuir chamados","secondary","users",sm=False))}
    {card("Metas de atendimento", checklist([
      ("ok","Primeira resposta em até 4h","2h14"),
      ("ok","Resolução de dúvida em até 1 dia útil","0,6 dia"),
      ("wait","Resolução de troca em até 5 dias úteis","4,2 dias"),
      ("wait","Chamado sem resposta há mais de 24h","1 chamado")]))}
    {card("Chamados que precisam de você",
      "".join(f'<a class="seclink" href="60-master-suporte.html">{ic(i)}'
              f'<span><strong>{e(t)}</strong><small>{e(d)}</small></span></a>'
        for i, t, d in [
          ("bell","#SUP-2026-0597 · Entrega","Alta prioridade, ainda não atribuído."),
          ("clock","#SUP-2026-0591 · Cancelamento","Aguardando revendedor há 2 dias."),
          ("coin","#SUP-2026-0596 · Financeiro","Divergência de nota fiscal do lote SEM-2404.")]))}
  </div>
</div>'''
W("70-master-suporte-lista.html", M("Suporte — fila de chamados | 60-master-suporte.html", body))

print("  →", 14, "telas novas do Painel Interno geradas.")
