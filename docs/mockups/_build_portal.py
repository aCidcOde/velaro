# -*- coding: utf-8 -*-
"""Etapa 2 · Portal do Lojista. Campos fiéis ao protótipo + o que o Anexo I exige."""
import importlib.util as il
s = il.spec_from_file_location("u", "_ui.py"); u = il.module_from_spec(s); s.loader.exec_module(u)
g = globals(); g.update({k: getattr(u, k) for k in dir(u) if not k.startswith("__")})
W = lambda f, c: (open(f, "w").write(c), print("  ✓", f))
P = lambda a, b: page("Velaro · " + a.split("|")[0].strip(), portal_shell(a.split("|")[1].strip(), b))

# ══════════════════════════ 2.2 CATÁLOGO REVENDEDOR ══════════════════════════
CAT = [
 ("ALC4-4MM","Aliança Clássica 4mm","R$ 15,00","ok","Em estoque","classica",True),
 ("ALTD-4MM","Aliança Diamantada 4mm","R$ 17,90","ok","Em estoque","diamantada",False),
 ("ALTA-6MM","Aliança Trabalhada 6mm","R$ 22,90","info","Sob encomenda","trabalhada",False),
 ("ALTA-4MM","Aliança Tradicional","R$ 13,00","info","Sob encomenda","classica",False),
 ("ALT-4MM","Aliança Fosca 6mm","R$ 26,00","ok","Em estoque","fosca",False),
 ("ALCON-6MM","Aliança Conforto 6mm","R$ 21,00","ok","Em estoque","conforto",False),
 ("ALIN-6MM","Aliança Anatômica 6mm","R$ 31,90","ok","Em estoque","classica",False),
 ("ALF-5MM","Aliança Fina 5mm","R$ 14,90","ok","Em estoque","fosca",False),
 ("ALBH-6MM","Aliança Brilhante 6mm","R$ 29,00","info","Sob encomenda","cravejada",False),
 ("ALDZ-6MM","Aliança Dupla Zircônia 6mm","R$ 27,00","ok","Em estoque","diamantada",False),
]
cards = "".join(
  prodcard(v, 400+i, "SKU: " + sku, nome, "", preco,
    chip_html=f'<div class="row" style="gap:5px">{chip(disp, tom, flat=True)}{chip("NOVO","brand",flat=True) if novo else ""}</div>',
    acoes=btn("Ver detalhes","secondary") + btn("+ Adicionar","primary"))
  for i, (sku, nome, preco, tom, disp, v, novo) in enumerate(CAT))

detalhe = drawer("Aliança Clássica 4mm",
  "".join([
    f'<div class="prod__img" style="height:170px">{rings.svg("classica", 480)}</div>',
    '<div class="row" style="gap:6px">' + "".join(
      f'<span class="thumb" style="width:52px;height:52px">{rings.thumb(v, 490+i)}</span>'
      for i, v in enumerate(["classica","fosca","conforto","cravejada"])) + '<span class="thumb" style="width:52px;height:52px;display:grid;place-items:center;color:var(--ink-muted)">›</span></div>',
    '<div><span class="eyebrow">Custo para o lojista</span>'
    '<div class="money money--action" style="margin-top:4px">R$ 15,00</div>'
    '<small class="muted" style="font-size:var(--text-xs)">Preço interno. Não exibir a clientes.</small></div>',
    "".join(linha_dado(k, e(v), i) for i, k, v in [
      ("diamond","Material","Ouro 18k"),("tag","Largura","4mm"),("sparkle","Acabamento","Polido"),
      ("clock","Prazo de entrega","Até 2 dias úteis"),("box","Disponibilidade","Em estoque")]),
    '<div class="field"><label>Quantidade (unid.)</label>'
    '<div class="qtybox"><span>−</span><b class="num">1</b><span>+</span></div></div>',
  ]),
  sub="Ref. ALC4-4MM", chip_html=chip("Em estoque","ok"),
  acoes=btn("Adicionar ao pedido","gold","cart","33-portal-pedidos.html",sm=False))

body = f'''
{head("Catálogo Revendedor","Catálogo com custo exclusivo para lojistas, disponibilidade e ferramentas para criação de pedidos.")}
{kpis([("box","Total de produtos","1.248","Ver catálogo →","gold"),
       ("check","Em estoque","892","Produtos disponíveis","ok"),
       ("clock","Sob encomenda","356","Produtos sob pedido","info"),
       ("tag","Coleções ativas","18","Ver coleções →","violet")])}
<div class="split split--wide">
  <div class="stack">
    {filtros("Buscar produto, código ou referência…",
      [("Coleção","Todas"),("Material","Todos"),("Acabamento","Todos"),("Largura","Todas"),("Disponibilidade","Todas")],
      acoes=btn("Limpar filtros","secondary","x") + '<span class="select-fake">Ordenar por: Lançamento</span>' + btn("Exportar catálogo","secondary","download"))}
    <div class="prods">{cards}</div>
    {notice("Os preços exibidos são <strong>exclusivos para revendedores</strong> e não devem ser compartilhados com clientes finais.")}
  </div>
  {detalhe}
</div>'''
W("30-portal-catalogo.html", P("Catálogo Revendedor | 30-portal-catalogo.html", body))

# ══════════════════════════ 2.3 CLIENTES / CRM ══════════════════════════
CLI = [("MS","Maria Silva","São Paulo / SP","123.456.789-00","(11) 98765-4321","maria.silva@email.com","15/05/2026","#5128","Ativo","ok"),
       ("JS","João Souza","Santos / SP","987.654.321-00","(13) 99123-4567","joao.souza@email.com","10/05/2026","#5021","Ativo","ok"),
       ("AC","Ana Costa","Campinas / SP","456.789.123-00","(19) 98234-5678","ana.costa@email.com","02/05/2026","#5021","Ativo","ok"),
       ("CP","Carlos Pereira","São José do Campo / SP","321.654.987-00","(12) 98876-5432","carlos.pereira@email.com","28/04/2026","#4890","Inativo","danger")]
rows = [[f'<div class="row" style="gap:10px"><span class="avatar" style="background:var(--color-gold-100);color:var(--color-gold-800)">{a}</span>'
         f'<span><strong style="color:var(--ink)">{n}</strong><br><small class="muted">{cid}</small></span></div>',
         f'<span class="num">{cpf}</span>',
         f'<span class="row" style="gap:6px">{tel} {ic("whats", style="color:#25D366;width:15px;height:15px")}</span>',
         mail, f'{d}<br><small class="muted">Pedido {ped}</small>', chip(st, tom), '<span class="muted">⋯</span>']
        for a, n, cid, cpf, tel, mail, d, ped, st, tom in CLI]

novo = drawer("Novo cliente", "".join([
  form([campo("Nome completo","Maria Silva",True),
        campo("CPF","123.456.789-00",True),
        campo("Telefone/WhatsApp","(11) 98765-4321",True),
        campo("E-mail","maria.silva@email.com",True),
        campo("Data de nascimento","12/03/1990"),
        campo("Data de casamento / namoro","15/06/2018",hint="Usado só com consentimento de marketing")], 1),
  '<div class="consentbox"><strong>Usar para campanhas de marketing</strong>'
  '<span class="checkline"><span class="cbox is-on">✓</span>Receber campanhas em datas especiais</span>'
  '<small>Registrável e revogável — exigência de LGPD do Anexo I §4.3.</small></div>',
  form([campo("Origem do contato","Indicação",False,"select"),
        campo("Cidade/UF","São Paulo / SP",True),
        campo("Endereço","Rua das Flores, 120 - Centro",True),
        campo("Observações","Cliente interessada em alianças clássicas, retratada na loja.",False,"textarea")], 1),
]), sub="Preencha os dados do cliente para cadastrá-lo e começar a criar pedidos.",
  acoes=btn("Salvar cliente","primary","check",sm=False) + btn("Salvar e criar pedido","secondary","cart","33-portal-pedidos.html",sm=False)
        + notice("<strong>Próximo passo:</strong> com o cliente salvo, você poderá criar um pedido de alianças com mais agilidade."))

body = f'''
{head("Clientes","Gerencie os clientes da sua loja e acompanhe pedidos e relacionamento.",
      btn("+ Novo cliente","primary",sm=False))}
{kpis([("users","Clientes cadastrados","486","Ver detalhes →","gold"),
       ("check","Clientes ativos","352","Ver detalhes →","ok"),
       ("bag","Pedidos em aberto","28","Ver detalhes →","info"),
       ("calendar","Último cadastro","Hoje, 10:32","Ver detalhes →","violet")])}
<div class="split split--wide">
  <div class="stack">
    {filtros("Buscar por nome, CPF, e-mail ou telefone…",
      [("Status","Todos"),("Cidade/UF","Todas"),("Período do cadastro","Todas")],
      acoes=btn("Limpar filtros","secondary","x"))}
    {card(None, tabela([("Cliente",""),("CPF",""),("Telefone",""),("E-mail",""),("Último pedido",""),("Status",""),("Ações","cell-num")], rows,
      foot=pag("Mostrando 1 a 4 de 486 clientes","1 2 3 … 122")))}
  </div>
  {novo}
</div>'''
W("31-portal-clientes.html", P("Clientes | 31-portal-clientes.html", body))

# ══════════════════════════ 2.4 FINANCEIRO ══════════════════════════
PED = [("#5128","65012","MS","Maria Silva","15/05/2026","10:32","R$ 5.980,00","warn","Aguardando pagamento","—"),
       ("#5127","65078","JS","João Souza","15/05/2026","14:15","R$ 3.450,00","warn","Aguardando compensação","—"),
       ("#5126","65068","AC","Ana Costa","20/05/2026","09:21","R$ 7.860,00","ok","Pago","Baixar NF"),
       ("#5125","65021","CP","Carlos Pereira","20/05/2026","16:42","R$ 4.300,00","ok","Pago","Baixar NF"),
       ("#5124","64980","LF","Larissa Fernandes","21/05/2026","11:08","R$ 6.750,00","warn","Aguardando pagamento","—"),
       ("#5123","64962","RB","Rafael Barbosa","21/05/2026","15:33","R$ 5.210,00","warn","Aguardando compensação","—")]
rows = [[f'<strong style="color:var(--ink)">{p}</strong><br><small class="muted">Pedido: {ref}</small>',
         f'<div class="row" style="gap:8px"><span class="avatar avatar--sm" style="background:var(--color-gold-100);color:var(--color-gold-800)">{a}</span>{n}</div>',
         f'{d}<br><small class="muted">{h}</small>', f'<span class="cell-strong num">{v}</span>', '24/2026',
         '28/05/2026<br><small class="muted">às 18h</small>', chip(st, tom),
         (f'<a class="link-gold" href="#">{nf}</a>' if nf != "—" else '<span class="muted">—</span>'),
         '<span class="muted">⋯</span>']
        for p, ref, a, n, d, h, v, tom, st, nf in PED]

nfs = tabela([("Número NF-e",""),("Data de emissão",""),("Competência",""),("Valor total","cell-num"),("Status",""),("Ações","")],
  [[f'<strong style="color:var(--ink)">NF-e {n}</strong>', d, "Maio/2026", f'<span class="cell-strong num">{v}</span>',
    chip("Autorizada","ok"), btn("Consultar","secondary","search")]
   for n, d, v in [("000.024.156","20/05/2026","R$ 18.110,00"),
                   ("000.024.087","13/05/2026","R$ 24.650,00"),
                   ("000.023.945","06/05/2026","R$ 21.340,00")]],
  foot='<a class="link-gold" href="#">Ver todas as notas fiscais emitidas →</a>')

metodos = "".join(
  f'<label class="payopt{" is-on" if on else ""}"><span class="radio{" is-on" if on else ""}"></span>'
  f'{ic(i)}<strong>{e(t)}</strong><small>{e(d)}</small></label>'
  for i, t, d, on in [("coin","PIX","Aprovação imediata",True),
                      ("doc","Boleto bancário","Compensação em até 1 dia útil",False),
                      ("card","Transferência bancária","Compensação em até 1 dia útil",False)])

pagar = drawer("Pagamento à Velaro", "".join([
  '<div class="pickitem is-on"><span class="eyebrow">Lote selecionado</span>'
  '<div class="pickitem__top"><strong>Lote semanal 24/2026</strong></div>'
  '<small>15/05/2026 a 21/05/2026</small></div>',
  linha_dado("Data limite para pagamento", '<span style="color:var(--color-error-700)">28/05/2026 às 18h</span>', "calendar"),
  '<div class="pickitem"><div class="pickitem__top"><strong>Pedidos no lote</strong>'
  '<span class="cell-strong num">R$ 48.750,00</span></div><small>12 pedidos</small>'
  '<a class="link-gold" href="#">Ver detalhes dos pedidos ⌄</a></div>',
  '<div><span class="eyebrow">Resumo do pagamento</span>'
  + linha_dado("Subtotal (custos Velaro)", '<span class="num">R$ 48.750,00</span>')
  + linha_dado("Descontos", '<span class="num">− R$ 0,00</span>')
  + '<div class="spread" style="padding-top:10px"><strong>Total a pagar</strong>'
    '<span class="money money--action">R$ 48.750,00</span></div></div>',
  notice("A produção dos pedidos deste lote será liberada <strong>após a confirmação do pagamento</strong>."),
  f'<div><span class="eyebrow">Método de pagamento</span><div class="stack" style="margin-top:8px">{metodos}</div></div>',
]), sub="Pagar lote semanal",
  acoes=btn("Realizar pagamento à Velaro","primary","lock",sm=False)
        + notice("Após a confirmação, o lote será liberado para produção e você receberá a confirmação por e-mail.","info"))

body = f'''
{head("Financeiro","Acompanhe os pedidos feitos pela sua loja, controle lotes e pagamentos à Velaro, e consulte notas fiscais emitidas.")}
{notice("<strong>Lote atual vence em 28/05/2026 às 18h.</strong> Evite atrasos e mantenha seus pedidos em produção.","danger")}
{kpis([("coin","Total em aberto","R$ 48.750,00","Ver detalhes →","danger"),
       ("bag","Pedidos no lote atual","12 pedidos","R$ 48.750,00","gold"),
       ("calendar","Próximo vencimento","28/05/2026","às 18h","warn"),
       ("doc","Notas fiscais emitidas","24","Este mês","info"),
       ("check","Pagamentos confirmados","R$ 96.320,00","Este mês","ok")], "g5")}
<div class="split split--wide">
  <div class="stack">
    {card(None, tabs(["Pedidos do lote atual","Todos os pedidos","Lotes anteriores"],"Pedidos do lote atual")
      + tabela([("Pedido",""),("Cliente final",""),("Data do pedido",""),("Valor custo Velaro","cell-num"),("Lote",""),
                ("Prazo máximo para pagamento",""),("Status do pagamento",""),("NF-e",""),("Ações","")], rows,
        foot=pag("Mostrando 1 a 6 de 12 pedidos do lote 24/2026","1 2")))}
    {card("Notas fiscais emitidas", nfs)}
    {notice("A Velaro emite a NF da venda B2B ao lojista. O lojista é responsável por emitir o documento fiscal da venda ao seu consumidor final.")}
  </div>
  {pagar}
</div>'''
W("32-portal-financeiro.html", P("Financeiro | 32-portal-financeiro.html", body))

# ══════════════════════════ 2.5 PEDIDOS ══════════════════════════
OP = {"Pedido registrado":"neutral","Em produção":"violet","Em transporte":"info","Entregue":"ok","Cancelado":"danger"}
FP = {"Pendente":"warn","Pago":"ok","Aguardando compensação":"warn","Vencido":"danger"}
LIN = [("#12548","Maria Silva","16/05/2026","10:32",2,"R$ 450,00","Pedido registrado","Pendente","23/05/2026"),
       ("#12547","João Santos","15/05/2026","14:18",1,"R$ 129,00","Em produção","Pago","22/05/2026"),
       ("#12546","Ana Paula Costa","15/05/2026","09:45",3,"R$ 798,00","Em transporte","Pago","21/05/2026"),
       ("#12545","Carlos Oliveira","14/05/2026","16:20",2,"R$ 380,00","Entregue","Pago","16/05/2026"),
       ("#12544","Juliana Lima","13/05/2026","11:05",1,"R$ 210,00","Em produção","Aguardando compensação","15/05/2026"),
       ("#12543","Rafael Ferreira","12/05/2026","13:50",3,"R$ 198,00","Cancelado","Vencido","—"),
       ("#12542","Fernanda Souza","11/05/2026","17:22",2,"R$ 630,00","Em produção","Pago","19/05/2026"),
       ("#12541","Lucas Almeida","10/05/2026","10:16",1,"R$ 199,00","Pedido registrado","Pendente","17/05/2026")]
rows = [[f'<strong style="color:var(--ink)">{p}</strong>', c, f'{d}<br><small class="muted">{h}</small>',
         f'<span class="num">{n}</span>', f'<span class="cell-strong num">{v}</span>',
         chip(so, OP[so]), chip(sp, FP[sp]), prev, '<span class="muted">⋯</span>']
        for p, c, d, h, n, v, so, sp, prev in LIN]

itens = "".join(
  f'<div class="orderitem">{ringimg(v, 520+i)}<div><strong>{e(n)}</strong>'
  f'<small>{e(sp)}</small><small>Aro: {aro}</small></div>'
  f'<div style="text-align:right"><small class="muted">Qtd: {q}</small><br><span class="cell-strong num">{val}</span></div></div>'
  for i, (v, n, sp, aro, q, val) in enumerate([
    ("conforto","Aliança Clássica 6mm","Ouro 18k - Anat. / Polido","18",1,"R$ 229,00"),
    ("trabalhada","Aliança Trabalhada 6mm","Ouro 18k - Gravação / Fosca","18",1,"R$ 221,00")]))

det = drawer("Pedido #12548", "".join([
  "".join(linha_dado(k, e(v)) for k, v in [
    ("Cliente","Maria Silva"),("Data do pedido","16/05/2026 10:32"),("Entrega prevista","23/05/2026")]),
  f'<div class="spread">{linha_dado("Status do pedido", chip("Pedido registrado","neutral"))}</div>',
  f'<div class="spread">{linha_dado("Status do pagamento", chip("Pendente","warn"))}</div>',
  '<div class="engravebox"><div class="row" style="gap:8px">' + ic("check") + '<strong>Gravação interna</strong></div>'
  + "".join(linha_dado(k, e(v)) for k, v in [
      ("Solicitada","Sim"),("Texto","Maria + João"),("Limite","até 20 caracteres"),("Custo adicional","R$ 35,00")]) + '</div>',
  f'<div><span class="eyebrow">Itens do pedido (2)</span><div class="stack" style="margin-top:8px">{itens}</div></div>',
  '<div><span class="eyebrow">Resumo do pedido (custo Velaro)</span>'
  + linha_dado("Subtotal dos itens", '<span class="num">R$ 450,00</span>')
  + linha_dado('<span style="color:var(--color-success-700)">Gravação interna (1 unidade)</span>', '<span class="num">R$ 35,00</span>')
  + linha_dado("Frete", '<span class="num">R$ 0,00</span>')
  + linha_dado("Descontos", '<span class="num">R$ 0,00</span>')
  + '<div class="spread" style="padding-top:10px"><strong>Total do pedido (custo Velaro)</strong>'
    '<span class="money money--action">R$ 485,00</span></div></div>',
]), chip_html=chip("Pedido registrado","neutral"),
  acoes=btn("Ver detalhes","secondary",sm=False) + btn("Faturamento / Pagamento","gold","coin","32-portal-financeiro.html",sm=False))

body = f'''
{head("Pedidos","Acompanhe e gerencie todos os pedidos da sua loja.")}
{kpis([("list","Todos os pedidos","248","Ver todos →","gold"),
       ("coin","Aguardando pagamento","18","Ver pedidos →","warn"),
       ("gear","Em produção","36","Ver pedidos →","violet"),
       ("truck","Em transporte","24","Ver pedidos →","info"),
       ("check","Entregues","168","Ver pedidos →","ok"),
       ("x","Cancelados","2","Ver pedidos →","danger")], "g5")}
<div class="split split--wide">
  <div class="stack">
    {filtros("Buscar por número do pedido, cliente ou produto…",
      [("Período","Últimos 90 dias"),("Status do pedido","Todos"),("Status do pagamento","Todos")],
      acoes=btn("Filtros avançados","secondary","filter"))}
    {card(None, tabela([("Pedido",""),("Cliente",""),("Data",""),("Itens","cell-num"),("Valor (custo Velaro)","cell-num"),
        ("Status do pedido",""),("Status do pagamento",""),("Entrega prevista",""),("Ações","")], rows,
      foot=pag("Exibindo 1 a 8 de 248 pedidos","1 2 3 … 31", '<span class="select-fake">8 por página</span>')))}
    {notice("<strong>Status do pedido</strong> e <strong>status do pagamento</strong> são campos independentes (Anexo I §6). O pedido só entra em produção após a quitação do lote.")}
  </div>
  {det}
</div>'''
W("33-portal-pedidos.html", P("Pedidos | 33-portal-pedidos.html", body))

# ══════════════════════════ 2.6 PERSONALIZAÇÃO DA LOJA ══════════════════════════
cores = "".join(
  f'<div class="field"><label>{e(l)}</label><span class="colorbox"><i style="background:{c}"></i>{c}</span></div>'
  for l, c in [("Primária","#800020"),("Secundária","#B8860B"),("Fundo","#FFFFFF"),("Texto","#1A1A1A")])

exemplo = tabela([("Custo Revendedor","cell-num"),("Multiplicador","cell-num"),("Preço Cliente Final (exibido)","cell-num")],
  [[f'<span class="num">{a}</span>', f'<span class="num">{b}</span>', f'<span class="cell-strong num">{c}</span>']
   for a, b, c in [("R$ 500,00","× 3,6","R$ 1.800,00"),("R$ 1.000,00","× 3,6","R$ 3.600,00"),("R$ 2.000,00","× 3,6","R$ 7.200,00")]])

destaques = "".join(
  f'<div class="prevprod">{ringimg(v, 540+i, "thumb")}<strong>{e(n)}</strong><small>Ouro 18k</small>'
  f'<span class="prevprod__price num">{p}</span>{chip(t, "neutral", flat=True)}</div>'
  for i, (v, n, p, t) in enumerate([
    ("classica","Aliança Clássica","R$ 1.800,00","Retirada na loja"),
    ("fosca","Aliança Tradicional","R$ 1.950,00","Retirada na loja"),
    ("conforto","Aliança Anatômica","R$ 2.250,00","Retirada na loja"),
    ("trabalhada","Aliança com Friso","R$ 1.850,00","Pedido realizado na loja"),
    ("diamantada","Aliança Diamantada","R$ 2.450,00","Retirada na loja"),
    ("fosca","Aliança Fosca","R$ 1.900,00","Retirada na loja"),
    ("trabalhada","Aliança Trabalhada","R$ 2.350,00","Pedido realizado na loja"),
    ("tricolor","Aliança Dupla","R$ 2.650,00","Retirada na loja")]))

body = f'''
{head("Personalização da loja","Configure a identidade visual, regras de preços e como sua vitrine será exibida para o cliente final. Todas as alterações são refletidas na vitrine do cliente.")}
<div class="split split--wide">
  <div class="stack">
    {card("① Identidade da loja",
      '<div class="split" style="grid-template-columns:210px minmax(0,1fr);gap:var(--space-5)">'
      + f'<div class="logobox"><span class="logobox__mark">T</span><strong>TOMAZELLI</strong><small>ALIANÇAS</small>'
        f'<span class="logobox__up">{ic("upload")} Enviar nova logo</span><small>PNG ou JPG · Máx. 2MB</small></div>'
      + '<div>' + form([
          campo("Nome da loja","Tomazelli Alianças",True),
          campo("Slogan","Símbolo de amor. Promessa para a vida toda.",True),
          campo("Telefone","(11) 98888-2020",True),
          campo("WhatsApp","(11) 98888-2020",True),
          campo("E-mail","contato@tomazellialiancas.com.br",True),
          campo("Domínio / URL da loja","https:// tomazellialiancas.com.br",True),
          campo("Endereço","Rua das Alianças, 123 - Centro, São Paulo - SP",True,largura=2),
        ], 2) + '</div></div>'
      + '<h3 class="fsec">Banner principal</h3>'
        '<div class="bannerbox"><strong>SÍMBOLO DE AMOR.</strong><span>PROMESSA PARA A VIDA TODA.</span>'
        f'<span class="bannerbox__edit">{ic("edit")}</span></div>'
        '<small class="fhint">1920×600px recomendado</small>'
      + f'<h3 class="fsec">Cores da marca</h3><div class="fgrid fgrid--4">{cores}</div>'
      + toggle("Exibir apenas a marca Tomazelli Alianças para o cliente final","Sua vitrine será exibida somente com a marca da sua loja")
      + toggle("Ocultar marca do fornecedor","Remover qualquer menção à Velaro Alianças"))}

    {card("② Regra de preços",
      '<div class="split" style="grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:var(--space-5)">'
      + '<div><span class="eyebrow">Modelo de precificação</span><div class="stack" style="margin-top:8px">'
        '<label class="payopt is-on"><span class="radio is-on"></span>' + ic("tag") + '<strong>Multiplicador</strong>'
        '<small>Aplicar um fator multiplicador</small></label>'
        '<label class="payopt"><span class="radio"></span>' + ic("chart") + '<strong>Percentual</strong>'
        '<small>Aplicar um percentual de margem</small></label></div>'
        '<div class="field" style="margin-top:var(--space-4)"><label>Fator de multiplicação</label>'
        '<div class="qtybox"><span>−</span><b class="num">3,6x</b><span>+</span></div></div></div>'
      + '<div>' + toggle("Aplicar a todos os produtos do catálogo")
        + toggle("Permitir edição manual por produto")
        + toggle("Permitir preços promocionais") + '</div></div>'
      + '<h3 class="fsec">Exemplo de cálculo com multiplicador 3,6x</h3>' + exemplo
      + notice("O pagamento do cliente final é realizado <strong>diretamente na loja</strong>. A vitrine não processa pagamento online."))}

    <div class="row row--wrap">
      {btn("Salvar configurações","secondary","check",sm=False)}
      {btn("Publicar vitrine","gold","globe","37-portal-vitrine.html",sm=False)}
      {btn("Pré-visualizar loja","secondary","eye","03-vitrine-pdv.html",sm=False)}
    </div>
  </div>

  <div class="drawer">
    <header class="drawer__head"><div><h2 class="title">Pré-visualização da loja</h2>
      <p class="drawer__sub">Assim o cliente verá sua vitrine, com a identidade da sua loja.</p></div>
      <span class="row" style="gap:4px;color:var(--ink-muted)">{ic("eye")}</span></header>
    <div class="drawer__body">
      <div class="storeprev">
        <div class="storeprev__bar"><strong>TOMAZELLI</strong><span>{ic("search")} {ic("cart")}</span></div>
        <div class="storeprev__tabs"><b>Todos os produtos</b><span>Alianças</span><span>Solitários</span><span>Acessórios</span></div>
        <div class="storeprev__banner"><strong>SÍMBOLO DE AMOR.</strong><span>PROMESSA PARA A VIDA TODA.</span>
          <em>Conheça nossas alianças</em></div>
        <div class="storeprev__grid"><span class="eyebrow" style="grid-column:1/-1">Destaques</span>{destaques}</div>
      </div>
      {notice("Esta prévia é pintada pelos campos acima. A vitrine <strong>não exibe marca Velaro</strong> em nenhum ponto.")}
    </div>
  </div>
</div>'''
W("34-portal-loja.html", P("Personalização da loja | 34-portal-loja.html", body))

# ══════════════════════════ 2.7 PREÇOS E MARGENS ══════════════════════════
PR = [("classica","Aliança Clássica 4mm","ALC18-4MM","Clássica","R$ 100,00","50","100%","R$ 200,00","Margem ideal","ok"),
      ("diamantada","Aliança Diamantada 6mm","ADIA-6MM","Diamantada","R$ 120,00","55","122,2%","R$ 265,00","Margem ideal","ok"),
      ("fosca","Aliança Fosca 6mm","AFOS-6MM","Fosca","R$ 85,00","45","81,8%","R$ 165,00","Margem baixa","warn"),
      ("trabalhada","Aliança Trabalhada 6mm","ATR6-6MM","Trabalhada","R$ 110,00","48","92,3%","R$ 210,00","Margem ideal","ok"),
      ("conforto","Aliança Conforto 4mm","ACONF-4MM","Conforto","R$ 95,00","50","100%","R$ 190,00","Margem ideal","ok")]
rows = [[f'<div class="row" style="gap:10px">{ringimg(v, 560+i)}<span><strong style="color:var(--ink)">{n}</strong>'
         f'<br><small class="muted">Ref. {r}</small></span></div>', col,
         f'<span class="num">{c}</span>',
         f'<span class="inline-edit num">{m} %</span>', f'<span class="num">{mk}</span>',
         f'<span class="inline-edit inline-edit--on num">{ps}</span>', chip(st, tom),
         f'<span class="row" style="gap:4px;justify-content:flex-end">{ic("edit", style="color:var(--ink-muted)")}<span class="muted">⋯</span></span>']
        for i, (v, n, r, col, c, m, mk, ps, st, tom) in enumerate(PR)]

resumo = f'''
<div class="donutbox">
  <svg viewBox="0 0 120 120" class="donut">
    <circle cx="60" cy="60" r="48" fill="none" stroke="var(--color-gray-200)" stroke-width="16"/>
    <circle cx="60" cy="60" r="48" fill="none" stroke="var(--color-success-500)" stroke-width="16"
      stroke-dasharray="257 45" stroke-linecap="butt" transform="rotate(-90 60 60)"/>
    <circle cx="60" cy="60" r="48" fill="none" stroke="var(--color-warning-500)" stroke-width="16"
      stroke-dasharray="30 272" stroke-linecap="butt" transform="rotate(165 60 60)"/>
    <circle cx="60" cy="60" r="48" fill="none" stroke="var(--color-error-500)" stroke-width="16"
      stroke-dasharray="8 294" stroke-linecap="butt" transform="rotate(207 60 60)"/>
  </svg>
  <div class="donutbox__mid"><strong>48,7%</strong><small>Margem média</small></div>
</div>
<ul class="legend">
  <li><i style="background:var(--color-success-500)"></i>Margem ideal (≥ 40%)<b>86 produtos</b></li>
  <li><i style="background:var(--color-warning-500)"></i>Margem baixa (20% – 39%)<b>12 produtos</b></li>
  <li><i style="background:var(--color-error-500)"></i>Margem crítica (&lt; 20%)<b>2 produtos</b></li>
</ul>'''

body = f'''
{head("Preços e margens","Defina suas margens e visualize os preços sugeridos para sua loja.")}
{kpis([("chart","Margem média atual","48,7%","Sobre o custo","ok"),
       ("tag","Markup médio","95,2%","Sobre o custo","gold"),
       ("info","Produtos com margem abaixo do ideal","12","Ajuste recomendado","warn"),
       ("coin","Preço médio de venda","R$ 876,45","Por unidade","info"),
       ("clock","Atualizado em","15/05/2026 10:30","Última atualização","violet")], "g5")}
<div class="split split--wide">
  <div class="stack">
    {card(None, '<div class="fgrid fgrid--3">'
      + campo("Margem global padrão","50 %",hint="Aplicada quando não houver regra específica")
      + campo("Arredondamento de preços","Para cima (0,99)",tipo="select",hint="Como os preços serão exibidos na loja")
      + campo("Regra de preço","Por coleção",tipo="select",hint="Defina margens diferentes por coleção")
      + '</div><div class="row row--wrap" style="margin-top:var(--space-4)">'
      + btn("Recalcular preços","secondary","refresh") + btn("Salvar configurações","primary","check") + '</div>')}
    {filtros("Buscar por produto, código ou referência…",
      [("Coleção","Todas"),("Material","Todos"),("Acabamento","Todos")],
      acoes=btn("Mais filtros","secondary","filter") + btn("Exportar tabela","secondary","download"))}
    {card(None, tabs(["Todos os produtos","Por coleção","Regras de margem"],"Todos os produtos")
      + tabela([("Produto",""),("Coleção",""),("Custo Velaro","cell-num"),("Margem (%)","cell-num"),
                ("Markup (%)","cell-num"),("Preço sugerido","cell-num"),("Status",""),("Ações","cell-num")], rows,
        foot=pag("Exibindo 1 a 5 de 128 produtos","1 2 3 … 26", '<span class="select-fake">5 por página</span>')))}
    {notice("O preço B2C é definido pelo revendedor por multiplicador, percentual, edição manual ou promoção (Anexo I §6). O custo Velaro nunca é exposto ao consumidor.")}
  </div>
  <div class="stack">
    {card("Resumo de margens", resumo)}
    {card("Configuração rápida",
      campo("Margem mínima desejada","40 %") + campo("Margem ideal","50 %") + campo("Margem máxima","60 %")
      + btn("Aplicar para todos os produtos","gold","check",sm=False))}
    {card("Dicas para melhores margens",
      '<ul class="lst"><li>Margens entre 40% e 60% são ideais para o mercado.</li>'
      '<li>Considere o valor percebido e seu público-alvo.</li>'
      '<li>Revise seus preços periodicamente.</li></ul>'
      '<a class="link-gold" href="#">Saiba mais sobre precificação →</a>')}
  </div>
</div>'''
W("35-portal-precos.html", P("Preços e margens | 35-portal-precos.html", body))

# ══════════════════════════ 2.8 SUPORTE ══════════════════════════
CH = [("#45821","Dúvida sobre prazos de entrega","Gostaria de saber o prazo para entrega do pedido #12458…","Pedidos","Em atendimento","info","Média","warn","16/05/2026 10:32"),
      ("#45820","Alteração de endereço de cobrança","Preciso atualizar o endereço de cobrança da empresa.","Financeiro","Aguardando retorno","warn","Alta","danger","15/05/2026 14:18"),
      ("#45819","Erro ao aplicar cupom de desconto","O sistema não está aceitando o cupom criado na loja.","Vitrine / Loja","Em análise","violet","Média","warn","14/05/2026 09:45"),
      ("#45818","Dúvida sobre personalização","Como configuro o banner principal da minha loja?","Personalização da loja","Respondido","ok","Baixa","ok","13/05/2026 16:20"),
      ("#45817","Pedido com produto faltante","Recebemos o pedido #12410 mas veio faltando um item.","Pedidos","Aguardando retorno","warn","Alta","danger","12/05/2026 11:05")]
rows = [[f'<strong style="color:var(--ink)">{n}</strong>',
         f'<strong style="color:var(--ink)">{a}</strong><br><small class="muted">{d}</small>',
         cat, chip(st, stom), chip(pr, ptom, flat=False), q, '<span class="muted">⋯</span>']
        for n, a, d, cat, st, stom, pr, ptom, q in CH]

atalhos = "".join(
  f'<a class="quickcard" href="#">{ic(i)}<span><strong>{e(t)}</strong><small>{e(d)}</small></span><b>›</b></a>'
  for i, t, d in [("support","Abrir chamado","Fale com nossa equipe"),
                  ("info","Perguntas frequentes","Tire suas dúvidas"),
                  ("book","Guias e manuais","Aprenda a usar a plataforma"),
                  ("eye","Vídeos tutoriais","Assista e aprenda"),
                  ("whats","WhatsApp","Atendimento rápido")])

canais = "".join(
  f'<div class="datarow"><span class="datarow__k">{ic(i)}<span><strong style="display:block;color:var(--ink)">{e(t)}</strong>'
  f'<small>{e(d)}</small></span></span><span class="datarow__v">{chip(s, stom, flat=True)}</span></div>'
  for i, t, d, s, stom in [
    ("support","Chat online","Disponível na plataforma","Online","ok"),
    ("whats","WhatsApp","(11) 99999-9999","Online","ok"),
    ("mail","E-mail","suporte@velaroaliancas.com.br","24h","neutral"),
    ("phone","Telefone","(11) 3000-0000","08h às 18h","neutral")])

body = f'''
{head("Suporte","Estamos aqui para ajudar você a vender mais e ter a melhor experiência.")}
<div class="split split--wide">
  <div class="stack">
    {card("Acesso rápido", f'<div class="quickgrid">{atalhos}</div>')}
    {card("Meus chamados",
      filtros("Buscar por número, assunto ou mensagem…",
        [("Status","Todos"),("Categoria","Todas"),("Período","Últimos 90 dias")],
        acoes=btn("Filtros","secondary","filter"))
      + tabela([("Nº do chamado",""),("Assunto",""),("Categoria",""),("Status",""),("Prioridade",""),
                ("Última atualização",""),("Ações","cell-num")], rows,
        foot=pag("Exibindo 1 a 5 de 23 chamados","1 2 3 … 5", '<span class="select-fake">5 por página</span>')))}
    {notice("O atendimento ocorre entre <strong>a Velaro e o revendedor</strong>. O cliente final aparece apenas como pessoa vinculada ao pedido e não participa da conversa (Anexo I §5.12).")}
  </div>
  <div class="stack">
    {card("Status do suporte", '<div class="grid g2">'
      + "".join(f'<div class="ministat"><strong>{v}</strong><small>{e(t)}</small></div>'
        for v, t in [("23","Total de chamados"),("5","Em atendimento"),("8","Aguardando retorno"),("10","Respondidos")])
      + '</div>')}
    {card("Horário de atendimento",
      linha_dado("Segunda a sexta-feira","08h às 18h","clock")
      + '<small class="fhint">Horário de Brasília · exceto feriados</small>')}
    {card("Canais de atendimento", canais)}
    {card("Central de ajuda completa",
      '<p class="lede" style="font-size:var(--text-sm)">Acesse tutoriais, guias e respostas para as dúvidas mais comuns.</p>'
      + btn("Acessar central de ajuda →","secondary",sm=False))}
  </div>
</div>'''
W("36-portal-suporte.html", P("Suporte | 36-portal-suporte.html", body))

# ══════════════════════════ 2.9 VITRINE PARA CLIENTES (gestão) ══════════════════════════
prev = "".join(
  f'<div class="prevprod">{ringimg(v, 580+i, "thumb")}<strong>{e(n)}</strong><small>Ouro 18k</small>'
  f'<span class="prevprod__price num">{p}</span>{chip("Parcela simulada na loja","neutral",flat=True)}</div>'
  for i, (v, n, p) in enumerate([
    ("classica","Aliança Ouro 18k Tradicional 4mm","R$ 1.890,00"),
    ("diamantada","Aliança Ouro 18k Diamantada 4mm","R$ 2.160,00"),
    ("bicolor","Par de Alianças Ouro 18k 4mm","R$ 5.490,00"),
    ("cravejada","Aliança Ouro 18k Filete de Pedra 4mm","R$ 2.490,00"),
    ("diamond","Solitário Ouro 18k com Diamante 20pts","R$ 2.890,00"),
    ("fosca","Aliança Ouro 18k Fosca 5mm","R$ 1.750,00")]))

conf = "".join([
  f'<div class="toggleline"><div><strong>Status da vitrine</strong></div>{chip("Ativa","ok")}</div>',
  toggle("Exibir apenas marca Tomazelli Alianças"),
  toggle("Mostrar preços ao cliente final"),
  toggle("Retirada somente na loja"),
  toggle("Pagamento realizado diretamente na loja"),
  '<div class="toggleline"><div><strong>Categorias visíveis</strong></div>'
  '<span class="muted" style="font-size:var(--text-sm)">Todas as categorias ›</span></div>',
  '<div class="toggleline"><div><strong>Destaque de produtos</strong></div>'
  '<span class="muted" style="font-size:var(--text-sm)">12 produtos selecionados ›</span></div>',
])
atalhos = "".join(
  f'<a class="quickcard" href="{h}">{ic(i)}<span><strong>{e(t)}</strong><small>{e(d)}</small></span><b>›</b></a>'
  for i, t, d, h in [("eye","Abrir em tablet","Visualizar em tablet","03-vitrine-pdv.html"),
                     ("link","Copiar link da vitrine","Compartilhar link de acesso","#"),
                     ("phone","Visualizar no celular","Como o cliente vê","03-vitrine-pdv.html"),
                     ("support","Iniciar atendimento","Falar com um atendente","36-portal-suporte.html")])

body = f'''
{head("Vitrine para clientes","Personalize e gerencie a vitrine da sua loja. É assim que seus clientes veem e escolhem as alianças e joias diretamente na loja.")}
{kpis([("box","Produtos publicados","268",up("18 novos esta semana"),"gold"),
       ("diamond","Coleções ativas","12","Ver coleções","violet"),
       ("bag","Pedidos iniciados na vitrine","37",up("9 esta semana"),"ok"),
       ("clock","Última atualização","24/05/2026 10:32","Atualizado há 2 horas","info")])}
<div class="split split--wide">
  <div class="stack">
    {card("Configurações da vitrine", conf
      + '<div class="row row--wrap" style="margin-top:var(--space-5)">'
      + btn("Salvar configurações","secondary","check") + btn("Abrir vitrine ↗","primary","globe","03-vitrine-pdv.html") + '</div>'
      + notice("A vitrine <strong>não processa pagamento online</strong>. O cliente escolhe os produtos e o pagamento é realizado diretamente na loja."))}
    {card("Acesso rápido", f'<div class="quickgrid">{atalhos}</div>')}
  </div>
  <div class="drawer">
    <header class="drawer__head"><div><h2 class="title">Pré-visualização da vitrine</h2></div>
      <span class="drawer__x">{ic("eye")}</span></header>
    <div class="drawer__body">
      <div class="storeprev">
        <div class="storeprev__bar"><strong>TOMAZELLI</strong><span>{ic("search")} {ic("cart")}</span></div>
        <div class="storeprev__tabs"><b>Todos os produtos</b><span>Alianças</span><span>Solitários</span><span>Acessórios</span></div>
        <div class="storeprev__banner"><strong>AMOR QUE SE ETERNIZA.</strong>
          <span>Alianças e joias que celebram os melhores momentos.</span><em>Conheça nossa coleção</em></div>
        <div class="storeprev__grid">{prev}</div>
      </div>
      {notice("<strong>Zero vazamento de marca.</strong> Nenhuma referência a Velaro ou SVD aparece para o consumidor final.")}
    </div>
  </div>
</div>'''
W("37-portal-vitrine.html", P("Vitrine para clientes | 37-portal-vitrine.html", body))

# ══════════════════════════ 2.11 PEDIDO PRONTO PARA RETIRADA ══════════════════════════
whats = '''<div class="phone"><div class="phone__screen">
  <div class="phone__time">10:30<small>Terça-feira, 20 de maio</small></div>
  <div class="notif">
    <div class="notif__head"><span class="notif__app">WhatsApp</span><span>agora</span></div>
    <strong>Tomazelli Alianças</strong>
    <p>Olá, Maria Silva! Seu pedido <b>#2412</b> já chegou à loja e está pronto para retirada.</p>
    <p class="notif__meta">📍 Endereço: Rua das Alianças, 123 - Centro</p>
    <p class="notif__meta">🕐 Horário: seg. a sex., das 9h às 18h.</p>
    <p class="notif__ok">✓ Estamos te esperando!</p>
  </div>
  <div class="notif">
    <div class="notif__head"><span class="notif__app">E-mail</span><span>5 min atrás</span></div>
    <strong>Tomazelli Alianças</strong>
    <p><b>Seu pedido está pronto para retirada</b></p>
    <p>Olá, Maria Silva. Informamos que o seu pedido #2412 já está disponível para retirada na loja Tomazelli Alianças.</p>
  </div>
</div></div>'''

body = f'''
{head("Pedido pronto para retirada","Quando o pedido chega à loja, a plataforma dispara a comunicação automática ao consumidor em nome do revendedor e informa você no Portal.")}
<div class="split split--wide">
  <div class="stack">
    {card("Pedido #2412 · Maria Silva",
      timeline([
        ("done","Pedido realizado",None,"15/05 10:32"),
        ("done","Pagamento confirmado","Lote 24/2026 quitado","15/05 10:45"),
        ("done","Produção em andamento",None,"16/05 09:10"),
        ("done","Produção finalizada",None,"21/05 14:25"),
        ("done","Em transporte para a loja",None,"22/05 08:30"),
        ("now","Pronto para retirada na loja","Chegada confirmada · notificações disparadas","23/05 08:15"),
        ("todo","Retirado pelo cliente","Aguardando confirmação","—")]),
      head_extra=chip("Pronto para retirada","ok"))}

    {card("Notificações enviadas",
      "".join(f'<div class="datarow"><span class="datarow__k">{ic(i)}<span>'
              f'<strong style="display:block;color:var(--ink)">{e(t)}</strong><small>{e(d)}</small></span></span>'
              f'<span class="datarow__v">{chip("Enviado","ok",flat=True)}</span></div>'
        for i, t, d in [
          ("whats","Cliente final · WhatsApp","Maria Silva · enviado em 23/05/2026 às 08:15"),
          ("mail","Cliente final · E-mail","maria.silva@email.com · enviado em 23/05/2026 às 08:15"),
          ("bell","Revendedor · Portal","Tomazelli Alianças · notificado em 23/05/2026 às 08:15")])
      + '<div class="row row--wrap" style="margin-top:var(--space-4)">'
      + btn("Reenviar notificação","secondary","refresh") + btn("Ver prévia da mensagem","secondary","eye") + '</div>')}

    {card("Confirmação de retirada",
      '<p class="lede" style="font-size:var(--text-sm)">Confirme abaixo quando o pedido for retirado pelo cliente na loja.</p>'
      + form([campo("Retirado por","Nome de quem retirou"),
              campo("Documento","CPF ou RG"),
              campo("Data e hora da retirada","23/05/2026 15:40")], 3)
      + '<div class="row row--wrap" style="margin-top:var(--space-4)">'
      + btn("Confirmar retirada por pedido","primary","check",sm=False)
      + btn("Confirmar retirada do lote inteiro","secondary","box",sm=False) + '</div>')}
  </div>
  <div class="stack">
    {card("Como o cliente recebe", whats)}
    {notice("A mensagem sai <strong>em nome do revendedor</strong>. A marca Velaro não aparece para o consumidor final (Anexo I §4.12).")}
    {notice("Comunicação transacional. Não depende de consentimento de marketing e é registrada separadamente das campanhas promocionais (Anexo I §6).","info")}
  </div>
</div>'''
W("38-portal-retirada.html", P("Pedido pronto para retirada | 38-portal-retirada.html", body))
