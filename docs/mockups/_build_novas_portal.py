# -*- coding: utf-8 -*-
"""Etapa 2 (fechamento) · telas do Portal do Lojista que faltavam.

39 Detalhe do pedido      — /portal/pedidos/{public_number}   (doc 2-5)
40 Notas fiscais emitidas — expansao do card resumido da 32   (doc 2-4)
41 Pagamento do lote      — o passo depois do drawer da 32    (doc 2-4)
42 Chamado de suporte     — /portal/suporte/{code}            (doc 2-8)
43 Central de ajuda       — absorve os 5 stubs da 36 e da 35

Regra do modulo: a relacao financeira e VELARO -> LOJISTA. Quem paga a Velaro e o
lojista; o consumidor final paga no caixa da loja e nao tem login (doc 2-10 §3).
"""
import importlib.util as il, random
s = il.spec_from_file_location("u", "_ui.py"); u = il.module_from_spec(s); s.loader.exec_module(u)
g = globals(); g.update({k: getattr(u, k) for k in dir(u) if not k.startswith("__")})
W = lambda f, c: (open(f, "w", encoding="utf-8").write(religar(c, f)), print("  ✓", f))
P = lambda a, b, t="Portal do Lojista": page(
      "Velaro · " + a.split("|")[0].strip(),
      portal_shell(a.split("|")[1].strip(), b, t))

# ─────────────────────── QR ilustrativo (deterministico, sem lib) ───────────────────────
def qrcode(seed=7, n=25, cell=8):
    r = random.Random(seed)
    size = n * cell
    fin = [(0, 0), (n - 7, 0), (0, n - 7)]
    dentro = lambda cx, cy: any(fx - 1 <= cx <= fx + 7 and fy - 1 <= cy <= fy + 7 for fx, fy in fin)
    p = [f'<rect width="{size}" height="{size}" fill="#ffffff"/>']
    for cy in range(n):
        for cx in range(n):
            if dentro(cx, cy) or r.random() > .46:
                continue
            p.append(f'<rect x="{cx*cell}" y="{cy*cell}" width="{cell}" height="{cell}" fill="#0c1817"/>')
    for fx, fy in fin:
        p.append(f'<rect x="{fx*cell}" y="{fy*cell}" width="{7*cell}" height="{7*cell}" fill="#0c1817"/>')
        p.append(f'<rect x="{(fx+1)*cell}" y="{(fy+1)*cell}" width="{5*cell}" height="{5*cell}" fill="#ffffff"/>')
        p.append(f'<rect x="{(fx+2)*cell}" y="{(fy+2)*cell}" width="{3*cell}" height="{3*cell}" fill="#0c1817"/>')
    return (f'<svg viewBox="0 0 {size} {size}" xmlns="http://www.w3.org/2000/svg" role="img" '
            f'aria-label="QR Code Pix do lote 24/2026 (ilustrativo)">{"".join(p)}</svg>')

def codebox(rotulo, codigo, acao="Copiar"):
    return (f'<div class="codebox"><span>{e(rotulo)}</span><code>{e(codigo)}</code>'
            f'<a class="link-gold" href="#">{e(acao)}</a></div>')

def datarows(itens):
    """itens: (icone|None, titulo, descricao, valor_html)"""
    out = ""
    for i, t, d, v in itens:
        ico = ic(i) if i else ""
        out += (f'<div class="datarow"><span class="datarow__k">{ico}<span>'
                f'<strong style="display:block;color:var(--ink)">{e(t)}</strong>'
                f'<small>{e(d)}</small></span></span><span class="datarow__v">{v}</span></div>')
    return out

# ══════════════════════════════════════════════════════════════════════════════
# 39 · DETALHE DO PEDIDO — /portal/pedidos/{public_number}   (doc 2-5)
# ══════════════════════════════════════════════════════════════════════════════
ITENS = [
 ("conforto","Aliança Clássica 6mm","ALC-6MM-18K","Ouro 18k · Anatômica / Polido","18","1","R$ 229,00","R$ 229,00"),
 ("trabalhada","Aliança Trabalhada 6mm","ALT-6MM-18K","Ouro 18k · Gravação / Fosca","18","1","R$ 221,00","R$ 221,00"),
]
itens_tab = tabela(
  [("Produto",""),("SKU",""),("Especificações",""),("Qtd","cell-num"),
   ("Preço unitário (custo Velaro)","cell-num"),("Total","cell-num")],
  [[f'<div class="row" style="gap:10px">{ringimg(v, 900+i)}'
    f'<span><strong style="color:var(--ink)">{e(n)}</strong><br><small class="muted">Par de alianças</small></span></div>',
    f'<code>{e(sku)}</code>',
    f'{e(esp)}<br><small class="muted">Aro: {e(aro)}</small>',
    f'<span class="num">{e(q)}</span>', f'<span class="num">{e(vu)}</span>',
    f'<span class="cell-strong num">{e(tt)}</span>']
   for i, (v, n, sku, esp, aro, q, vu, tt) in enumerate(ITENS)],
  foot='<div class="spread"><span>Subtotal dos itens</span><span class="num">R$ 450,00</span></div>'
       '<div class="spread"><span style="color:var(--color-success-700)">Gravação interna (1 unidade)</span>'
       '<span class="num">R$ 35,00</span></div>'
       '<div class="spread"><span>Frete</span><span class="num">R$ 0,00</span></div>'
       '<div class="spread"><span>Descontos</span><span class="num">R$ 0,00</span></div>'
       '<div class="spread" style="padding-top:10px"><strong>Total do pedido (custo Velaro)</strong>'
       '<span class="money money--action">R$ 485,00</span></div>')

gravacao = ('<div class="engravebox"><div class="row" style="gap:8px">' + ic("check")
  + '<strong>Gravação interna solicitada</strong></div>'
  + "".join(linha_dado(k, e(v)) for k, v in [
      ("Solicitada","Sim"),("Texto","Maria + João"),("Data gravada","15/06/2018"),
      ("Limite","até 20 caracteres"),("Aplicada em","1 unidade (Aliança Trabalhada 6mm)"),
      ("Custo adicional","R$ 35,00")]) + '</div>')

entrega = card("Entrega e retirada",
  "".join(linha_dado(k, v, i) for i, k, v in [
    ("truck","Modo de entrega", chip("Retirada na loja","neutral",flat=True)),
    ("store","Loja de destino", "Tomazelli Alianças"),
    ("pin","Endereço", "Rua das Alianças, 123 - Centro<br><small class='muted'>São Paulo / SP - 01000-000</small>"),
    ("calendar","Chegada prevista na loja", "23/05/2026"),
    ("box","Transportadora", '<span class="muted">Definida na expedição</span>'),
    ("search","Código de rastreio", '<span class="muted">—</span>')])
  + notice('Quando o pedido chegar à loja, a plataforma dispara WhatsApp e e-mail ao cliente '
           '<strong>em nome da Tomazelli Alianças</strong>. '
           '<a class="link-gold" href="38-portal-retirada.html">Ver painel de retirada →</a>', "info"))

pagamento = card("Pagamento",
  "".join(linha_dado(k, v, i) for i, k, v in [
    ("coin","Status do pagamento", chip("Pendente","warn")),
    ("box","Lote", "24/2026<br><small class='muted'>15/05/2026 a 21/05/2026</small>"),
    ("calendar","Prazo máximo para pagamento", '<span style="color:var(--color-error-700)">28/05/2026 às 18h</span>'),
    ("card","Forma de pagamento", '<span class="muted">A escolher no pagamento do lote</span>'),
    ("tag","Valor deste pedido no lote", '<span class="cell-strong num">R$ 485,00</span>')])
  + '<div class="row row--wrap" style="margin-top:var(--space-4)">'
  + btn("Pagar lote à Velaro","gold","lock","41-portal-pagamento.html",sm=False)
  + btn("Ver financeiro","secondary","coin","32-portal-financeiro.html",sm=False) + '</div>')

nota = card("Nota fiscal",
  "".join(linha_dado(k, v, i) for i, k, v in [
    ("doc","Situação", chip("Aguardando emissão","neutral")),
    ("factory","Emitente", "Velaro Alianças<br><small class='muted'>Venda B2B ao lojista</small>"),
    ("store","Destinatário", "Tomazelli Alianças<br><small class='muted'>CNPJ 12.345.678/0001-90</small>"),
    ("list","Série", "1"),
    ("calendar","Competência", "Maio/2026"),
    ("coin","Valor a faturar", '<span class="cell-strong num">R$ 485,00</span>')])
  + notice('A NF-e é emitida <strong>após a quitação do lote 24/2026</strong>. '
           '<a class="link-gold" href="40-portal-notas.html">Ver notas fiscais emitidas →</a>')
  + notice('A Velaro emite a NF da venda B2B ao lojista. O documento fiscal da venda ao '
           '<strong>consumidor final é responsabilidade da sua loja</strong>.', "info"))

historico = card("Histórico de atualizações", datarows([
  ("clock","Aguardando pagamento do lote","O pedido entra em produção assim que o lote 24/2026 for quitado.","16/05 10:33"),
  ("bag","Pedido registrado","Pedido criado no Portal por Tomazelli Alianças.","16/05 10:32"),
  ("edit","Gravação interna adicionada","Texto “Maria + João” aplicado a 1 unidade.","16/05 10:31"),
  ("user","Cliente vinculado","Maria Silva selecionada no cadastro de clientes.","16/05 10:29"),
]))

body = f'''
{head("Pedido #12548","Detalhe completo do pedido — linha do tempo, itens, entrega, pagamento e nota fiscal.",
  btn("← Voltar para pedidos","secondary",href="33-portal-pedidos.html",sm=False)
  + btn("Imprimir","secondary","print",sm=False)
  + btn("Abrir chamado sobre este pedido","secondary","support","42-portal-chamado.html",sm=False)
  + btn("Faturamento / Pagamento","gold","coin","41-portal-pagamento.html",sm=False))}
{card(None,
  '<div class="spread"><div class="row row--wrap" style="gap:10px">'
  + chip("Pedido registrado","neutral") + chip("Pagamento pendente","warn")
  + '<span class="muted" style="font-size:var(--text-sm)">Criado em 16/05/2026 às 10:32 · atualizado há 12 minutos</span>'
  + '</div></div>'
  + '<div class="identbar" style="margin-top:var(--space-4)">'
  + "".join(f'<div class="identcell"><span><small>{e(k)}</small><strong>{v}</strong></span></div>'
      for k, v in [("Cliente final","Maria Silva<br><small class='muted'>CPF 123.456.789-00</small>"),
                   ("Itens","2 unidades"),
                   ("Total (custo Velaro)","R$ 485,00"),
                   ("Lote","24/2026"),
                   ("Entrega prevista","23/05/2026")])
  + '</div>')}
<div class="split split--wide">
  <div class="stack">
    {card("Linha do tempo do pedido", timeline([
      ("done","Pedido registrado","Criado no Portal por Tomazelli Alianças","16/05/2026 10:32"),
      ("now","Aguardando pagamento do lote","Lote 24/2026 · vence em 28/05/2026 às 18h","Em aberto"),
      ("todo","Em produção","Liberada após a confirmação do pagamento do lote","—"),
      ("todo","Em transporte","Envio da fábrica para a loja do revendedor","—"),
      ("todo","Entregue","Pronto para retirada e retirado pelo cliente na loja","—")]),
      head_extra=chip("Pedido registrado","neutral"))}
    {card("Itens do pedido (2)", itens_tab + gravacao)}
    <div class="split" style="--gcols:minmax(0,1fr) minmax(0,1fr)">
      {entrega}
      {pagamento}
    </div>
    {nota}
    {historico}
    {notice("<strong>Status do pedido</strong> e <strong>status do pagamento</strong> são campos independentes (Anexo I §6). O pedido só entra em produção após a quitação do lote.")}
  </div>
  <div class="stack">
    {card("Resumo do pedido (custo Velaro)",
      linha_dado("Subtotal dos itens", '<span class="num">R$ 450,00</span>')
      + linha_dado("Gravação interna (1 unidade)", '<span class="num" style="color:var(--color-success-700)">R$ 35,00</span>')
      + linha_dado("Frete", '<span class="num">R$ 0,00</span>')
      + linha_dado("Descontos", '<span class="num">R$ 0,00</span>')
      + '<div class="spread" style="padding-top:10px"><strong>Total do pedido</strong>'
        '<span class="money money--action">R$ 485,00</span></div>'
      + '<small class="fhint">Valor devido pela sua loja à Velaro. O preço cobrado do consumidor final é definido por você em Preços e margens.</small>')}
    {card("Cliente final",
      '<div class="row" style="gap:10px;margin-bottom:var(--space-3)">'
      '<span class="avatar" style="background:var(--color-gold-100);color:var(--color-gold-800)">MS</span>'
      '<span><strong style="color:var(--ink)">Maria Silva</strong><br>'
      '<small class="muted">Cliente desde 15/05/2026</small></span></div>'
      + "".join(linha_dado(k, e(v), i) for i, k, v in [
          ("doc","CPF","123.456.789-00"),("phone","Telefone","(11) 98765-4321"),
          ("mail","E-mail","maria.silva@email.com"),("pin","Cidade/UF","São Paulo / SP")])
      + btn("Ver ficha do cliente","secondary","user","31-portal-clientes.html",sm=False))}
    {card("Ações do pedido",
      btn("Pagar lote à Velaro","primary","lock","41-portal-pagamento.html",sm=False)
      + btn("Baixar espelho do pedido","secondary","download",sm=False)
      + btn("Abrir chamado sobre este pedido","secondary","support","42-portal-chamado.html",sm=False)
      + btn("Duplicar pedido","secondary","refresh","30-portal-catalogo.html",sm=False))}
    {notice("Rota <code>/portal/pedidos/12548</code>. O pedido é sempre acessado pelo <strong>número público</strong> — o id interno nunca é exposto (Anexo I §4.5).","info")}
  </div>
</div>'''
W("39-portal-pedido.html", P("Detalhe do pedido | 33-portal-pedidos.html", body, "Portal do Lojista · Pedidos"))

# ══════════════════════════════════════════════════════════════════════════════
# 40 · NOTAS FISCAIS EMITIDAS   (doc 2-4 — tabela `invoices`)
# ══════════════════════════════════════════════════════════════════════════════
NF = [
 ("000.024.156","1","20/05/2026","Maio/2026","R$ 18.110,00","23/2026","#5126","Autorizada","ok"),
 ("000.024.087","1","13/05/2026","Maio/2026","R$ 24.650,00","22/2026","#5119","Autorizada","ok"),
 ("000.023.945","1","06/05/2026","Maio/2026","R$ 21.340,00","21/2026","#5104","Autorizada","ok"),
 ("000.023.902","1","29/04/2026","Abril/2026","R$ 19.480,00","20/2026","#5088","Autorizada","ok"),
 ("000.023.815","1","22/04/2026","Abril/2026","R$ 12.740,00","19/2026","#5061","Autorizada","ok"),
 ("000.023.788","1","15/04/2026","Abril/2026","R$ 3.980,00","18/2026","#5042","Cancelada","danger"),
]
rows = [[f'<strong style="color:var(--ink)">NF-e {e(n)}</strong>',
         f'<span class="num">{e(se)}</span>',
         e(d), e(comp),
         f'<span class="cell-strong num">{e(v)}</span>',
         f'<span class="num">{e(lote)}</span>',
         f'<a class="link-gold" href="39-portal-pedido.html">{e(ped)}</a>',
         chip(st, tom),
         (f'<span class="row" style="gap:10px;justify-content:flex-end">'
          f'<a class="link-gold" href="#">Baixar NF</a><a class="link-gold" href="#">Baixar XML</a></span>'
          if st == "Autorizada" else
          '<span class="row" style="gap:10px;justify-content:flex-end">'
          '<a class="link-gold" href="#">Ver motivo</a></span>')]
        for n, se, d, comp, v, lote, ped, st, tom in NF]

comp_resumo = datarows([
  ("calendar","Maio/2026","3 notas · lotes 21 a 23", '<span class="cell-strong num">R$ 64.100,00</span>'),
  ("calendar","Abril/2026","3 notas · lotes 18 a 20", '<span class="cell-strong num">R$ 36.200,00</span>'),
  ("calendar","Março/2026","4 notas · lotes 14 a 17", '<span class="cell-strong num">R$ 41.870,00</span>'),
])

alerta_nota = ('Divergência em alguma nota? <a class="link-gold" href="42-portal-chamado.html">Abra um chamado</a> '
               'na categoria Financeiro que o time da Velaro corrige a emissão.')

body = f'''
{head("Notas fiscais emitidas","Todas as NF-e que a Velaro emitiu contra a Tomazelli Alianças — a venda B2B fábrica → lojista.",
  btn("← Voltar para o financeiro","secondary",href="32-portal-financeiro.html",sm=False)
  + btn("Exportar planilha","secondary","download",sm=False)
  + btn("Baixar XMLs do período","gold","download",sm=False))}
{kpis([("doc","Notas emitidas","24","Este mês","gold"),
       ("coin","Valor total faturado","R$ 96.320,00","Este mês","ok"),
       ("calendar","Última emissão","20/05/2026","NF-e 000.024.156","info"),
       ("x","Notas canceladas","1","Últimos 90 dias","danger")])}
<div class="split split--wide">
  <div class="stack">
    {filtros("Buscar por número da NF-e, pedido ou lote…",
      [("Período","Últimos 90 dias"),("Competência","Todas"),("Status","Todas"),("Série","Todas")],
      acoes=btn("Limpar filtros","secondary","x") + btn("Filtros avançados","secondary","filter"))}
    {card(None, tabs(["Todas as notas","Autorizadas","Canceladas"],"Todas as notas")
      + tabela([("Número NF-e",""),("Série","cell-num"),("Emissão",""),("Competência",""),
                ("Valor total","cell-num"),("Lote",""),("Pedido vinculado",""),("Status",""),("Ações","cell-num")],
        rows, check=True,
        foot=pag("Mostrando 1 a 6 de 24 notas fiscais","1 2 3 … 4", '<span class="select-fake">6 por página</span>')))}
    {card("Download em lote",
      '<p class="lede" style="font-size:var(--text-sm)">Selecione as notas na tabela acima ou baixe todo o período filtrado de uma vez.</p>'
      + '<div class="row row--wrap">' + btn("Baixar PDFs (ZIP)","secondary","download",sm=False)
      + btn("Baixar XMLs (ZIP)","secondary","download",sm=False)
      + btn("Enviar para o meu contador","secondary","mail",sm=False) + '</div>')}
    {notice("A Velaro emite a NF da venda B2B ao lojista. O lojista é responsável por emitir o documento fiscal da venda ao seu consumidor final.")}
  </div>
  <div class="stack">
    {card("Resumo por competência", comp_resumo)}
    {card("Como funciona a nota fiscal", checklist([
      ("ok","Lote fechado no domingo","semanal"),
      ("ok","Pagamento à Velaro confirmado","até a data limite"),
      ("ok","NF-e emitida contra o CNPJ da sua loja","D+1"),
      ("wait","PDF e XML disponíveis nesta tela","automático"),
      ("wait","Liberação dos pedidos para produção","após a NF")]))}
    {card("Dados do destinatário",
      "".join(linha_dado(k, e(v), i) for i, k, v in [
        ("store","Razão social","Tomazelli Alianças Ltda"),
        ("doc","CNPJ","12.345.678/0001-90"),
        ("list","Inscrição estadual","123.456.789.112"),
        ("pin","Endereço fiscal","Rua das Alianças, 123 - Centro, São Paulo / SP")])
      + btn("Atualizar dados fiscais","secondary","edit","34-portal-loja.html",sm=False))}
    {notice(alerta_nota,"info")}
  </div>
</div>'''
W("40-portal-notas.html", P("Notas fiscais emitidas | 32-portal-financeiro.html", body, "Portal do Lojista · Financeiro"))

# ══════════════════════════════════════════════════════════════════════════════
# 41 · PAGAMENTO DO LOTE À VELARO   (doc 2-4 — `payments`)
# ══════════════════════════════════════════════════════════════════════════════
LOTE = [("#5128","MS","Maria Silva","15/05/2026","R$ 5.980,00","Aguardando pagamento","warn"),
        ("#5127","JS","João Souza","15/05/2026","R$ 3.450,00","Aguardando pagamento","warn"),
        ("#5124","LF","Larissa Fernandes","21/05/2026","R$ 6.750,00","Aguardando pagamento","warn"),
        ("#5123","RB","Rafael Barbosa","21/05/2026","R$ 5.210,00","Aguardando pagamento","warn"),
        ("#12548","MS","Maria Silva","16/05/2026","R$ 485,00","Aguardando pagamento","warn"),
        ("#12544","JL","Juliana Lima","13/05/2026","R$ 210,00","Aguardando pagamento","warn")]
rows = [[f'<a class="link-gold" href="39-portal-pedido.html"><strong>{e(p)}</strong></a>',
         f'<div class="row" style="gap:8px"><span class="avatar avatar--sm" style="background:var(--color-gold-100);color:var(--color-gold-800)">{e(a)}</span>{e(n)}</div>',
         e(d), f'<span class="cell-strong num">{e(v)}</span>', chip(st, tom)]
        for p, a, n, d, v, st, tom in LOTE]

metodos = "".join(
  f'<label class="payopt{" is-on" if on else ""}"><span class="radio{" is-on" if on else ""}"></span>'
  f'{ic(i)}<strong>{e(t)}</strong><small>{e(d)}</small></label>'
  for i, t, d, on in [("coin","PIX","Aprovação imediata",True),
                      ("doc","Boleto bancário","Compensação em até 1 dia útil",False),
                      ("card","Transferência bancária","Compensação em até 1 dia útil",False)])

pix = card("Pix — aprovação imediata",
  '<div class="paydoc">'
  + f'<div class="qrbox">{qrcode(11)}<small>Aponte a câmera do app do seu banco.<br>QR válido até 28/05/2026 às 18h.</small></div>'
  + '<div class="paydoc__side">'
  + codebox("Pix copia e cola",
      "00020126580014BR.GOV.BCB.PIX0136a1b2c3d4-5e6f-7890-abcd-ef1234567890"
      "5204000053039865406487505802BR5919VELARO ALIANCAS LTD6009SAO PAULO"
      "62070503***6304A1B2")
  + "".join(linha_dado(k, v) for k, v in [
      ("Beneficiário","Velaro Alianças Indústria e Comércio Ltda"),
      ("Identificador","LOTE-24-2026-TOM0001"),
      ("Valor", '<span class="cell-strong num">R$ 48.750,00</span>')])
  + btn("Baixar QR Code","secondary","download",sm=False)
  + '</div></div>'
  + notice("A baixa do Pix é automática. Assim que o banco confirmar, o lote muda para <strong>Pago</strong> e a produção é liberada.","ok"),
  head_extra=chip("Selecionado","ok"))

boleto = card("Boleto bancário",
  codebox("Linha digitável", "34191.79001 01043.510047 91020.150008 8 96820000487500")
  + "".join(linha_dado(k, v, i) for i, k, v in [
      ("calendar","Vencimento", '<span style="color:var(--color-error-700)">28/05/2026</span>'),
      ("factory","Beneficiário", "Velaro Alianças Indústria e Comércio Ltda"),
      ("store","Pagador", "Tomazelli Alianças Ltda · CNPJ 12.345.678/0001-90"),
      ("coin","Valor do documento", '<span class="cell-strong num">R$ 48.750,00</span>'),
      ("clock","Compensação", "Até 1 dia útil após o pagamento")])
  + '<div class="row row--wrap" style="margin-top:var(--space-4)">'
  + btn("Baixar boleto (PDF)","secondary","download",sm=False)
  + btn("Enviar boleto por e-mail","secondary","mail",sm=False) + '</div>'
  + notice("Boleto pago após o vencimento não libera o lote automaticamente — o pedido volta para a fila do lote seguinte."))

banco = card("Transferência bancária",
  "".join(linha_dado(k, e(v), i) for i, k, v in [
    ("factory","Favorecido","Velaro Alianças Indústria e Comércio Ltda"),
    ("card","Banco","341 · Itaú Unibanco"),
    ("list","Agência","1234"),
    ("list","Conta corrente","56789-0"),
    ("info","Identificação obrigatória","Lote 24/2026 · cód. revendedor 00876")])
  + notice("Na transferência, a baixa <strong>não é automática</strong>: envie o comprovante abaixo para acelerar a conferência.","info"))

body = f'''
{head("Pagamento do lote à Velaro","Lote semanal 24/2026 · período de 15/05/2026 a 21/05/2026 · 12 pedidos.",
  btn("← Voltar para o financeiro","secondary",href="32-portal-financeiro.html",sm=False)
  + btn("Baixar demonstrativo do lote","secondary","download",sm=False))}
{notice("<strong>Este lote vence em 28/05/2026 às 18h.</strong> Depois do vencimento os pedidos saem da fila de produção e voltam para o lote seguinte.","danger")}
{card(None, stepper([
  ("Lote fechado","done","21/05/2026"),
  ("Conferência dos pedidos","done","12 pedidos · R$ 48.750,00"),
  ("Forma de pagamento","now","Você está aqui"),
  ("Compensação","todo","Até 1 dia útil"),
  ("Liberação para produção","todo","Automática após a baixa")]))}
<div class="split split--wide">
  <div class="stack">
    {card("① Confira o lote",
      '<div class="identbar">'
      + "".join(f'<div class="identcell"><span><small>{e(k)}</small><strong>{v}</strong></span></div>'
          for k, v in [("Lote","24/2026"),("Período","15/05 a 21/05/2026"),
                       ("Pedidos","12"),("Data limite","28/05/2026 às 18h"),
                       ("Total a pagar","R$ 48.750,00")])
      + '</div>'
      + '<div style="margin-top:var(--space-4)">'
      + linha_dado("Subtotal (custos Velaro)", '<span class="num">R$ 48.750,00</span>')
      + linha_dado("Descontos", '<span class="num">− R$ 0,00</span>')
      + linha_dado("Acréscimos por atraso", '<span class="num">R$ 0,00</span>')
      + '<div class="spread" style="padding-top:10px"><strong>Total a pagar à Velaro</strong>'
        '<span class="money money--action">R$ 48.750,00</span></div></div>')}
    {card("② Pedidos incluídos no lote (12)",
      tabela([("Pedido",""),("Cliente final",""),("Data do pedido",""),
              ("Valor (custo Velaro)","cell-num"),("Status do pagamento","")], rows,
        foot=pag("Mostrando 1 a 6 de 12 pedidos do lote 24/2026","1 2"))
      + notice("Todos os pedidos do lote são quitados juntos. Não há pagamento avulso por pedido — a fatura é do lote (Anexo I §6)."))}
    {card("③ Escolha a forma de pagamento",
      f'<div class="stack" style="margin-top:4px">{metodos}</div>')}
    {pix}
    {boleto}
    {banco}
  </div>
  <div class="stack">
    {card("Total a pagar",
      '<div class="pickitem is-on"><span class="eyebrow">Lote selecionado</span>'
      '<div class="pickitem__top"><strong>Lote semanal 24/2026</strong></div>'
      '<small>15/05/2026 a 21/05/2026 · 12 pedidos</small></div>'
      + linha_dado("Forma escolhida", chip("PIX","ok",flat=True))
      + linha_dado("Data limite", '<span style="color:var(--color-error-700)">28/05/2026 às 18h</span>')
      + '<div class="spread" style="padding-top:10px"><strong>Total</strong>'
        '<span class="money money--action">R$ 48.750,00</span></div>'
      + btn("Confirmar e gerar cobrança","primary","lock",sm=False)
      + btn("Já paguei — enviar comprovante","secondary","upload",sm=False)
      + notice("Após a confirmação, o lote será liberado para produção e você receberá a confirmação por e-mail.","info"))}
    {card("Enviar comprovante",
      f'<div class="upload"><span class="upload__ic">{ic("upload")}</span>'
      '<strong>Arraste o comprovante ou clique para enviar</strong>'
      '<small>PDF, PNG ou JPG · até 5 MB</small></div>'
      + '<div class="docfile" style="margin-top:var(--space-3)">' + ic("doc")
      + '<span><strong>comprovante_pix_lote_23.pdf</strong><small>14/05/2026 · 240 KB</small></span>'
        '<b class="docfile__ok">↓</b></div>')}
    {card("Precisa de ajuda com o pagamento?",
      '<p class="lede" style="font-size:var(--text-sm)">Divergência de valor, boleto vencido ou dúvida sobre o lote? Fale com o financeiro da Velaro.</p>'
      + btn("Abrir chamado · Financeiro","secondary","support","42-portal-chamado.html",sm=False)
      + btn("Ver artigos sobre pagamento","secondary","book","43-portal-ajuda.html",sm=False))}
    {notice("A cobrança é <strong>Velaro → lojista</strong>. A plataforma não processa pagamento do consumidor final: o cliente paga no caixa da sua loja (Anexo I §4.10).")}
  </div>
</div>'''
W("41-portal-pagamento.html", P("Pagamento do lote à Velaro | 32-portal-financeiro.html", body, "Portal do Lojista · Financeiro"))

# ══════════════════════════════════════════════════════════════════════════════
# 42 · CHAMADO DE SUPORTE — /portal/suporte/{code}   (doc 2-8)
# ══════════════════════════════════════════════════════════════════════════════
conversa = "".join(
  f'<div class="msg{" msg--agent" if papel=="Velaro" else ""}">'
  f'<span class="avatar avatar--sm">{e(av)}</span>'
  f'<div class="msg__body"><div class="msg__head"><strong>{e(aut)}</strong>'
  f'{chip(papel, "ok" if papel=="Velaro" else "brand", flat=True)}'
  f'<span class="msg__when">{e(q)}</span></div><p>{txt}</p>{anexo}</div></div>'
  for av, aut, papel, q, txt, anexo in [
    ("JF","João Ferreira · Tomazelli Alianças","Minha loja","16/05/2026 às 10:32",
     "Olá, bom dia!<br>Gostaria de saber o prazo para entrega do pedido #12458, da cliente Maria Silva.<br>"
     "Ela pretende retirar as alianças antes do casamento, no dia 30/05.<br>"
     "Conseguimos garantir a chegada na loja até lá?", ""),
    ("EV","Equipe Velaro Suporte","Velaro","16/05/2026 às 11:04",
     "Olá, João! Bom dia.<br>O pedido #12458 está no <b>lote 24/2026</b>, que vence em 28/05/2026 às 18h.<br>"
     "A produção só é liberada depois da quitação do lote. A partir daí o prazo é de "
     "<b>5 dias úteis de produção + 2 dias úteis de transporte</b> até a sua loja.<br>"
     "Antecipando o pagamento do lote ainda esta semana, a chegada na loja fica prevista para <b>26/05</b>.", ""),
    ("JF","João Ferreira · Tomazelli Alianças","Minha loja","16/05/2026 às 11:18",
     "Perfeito, vamos antecipar o pagamento hoje mesmo.<br>Segue o espelho do pedido conferido com a cliente.",
     '<div class="docfile" style="margin-top:10px">'
     '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">'
     '<path d="M6 3h9l4 4v14H6z"/><path d="M15 3v4h4M9 12h6M9 16h6"/></svg>'
     '<span><strong>espelho_pedido_12458.pdf</strong><small>16/05/2026 às 11:18 · 180 KB</small></span>'
     '<b class="docfile__ok">↓</b></div>'),
    ("EV","Equipe Velaro Suporte","Velaro","16/05/2026 às 11:26",
     "Recebido, obrigado!<br>Assim que o pagamento do lote for compensado o pedido entra automaticamente na fila "
     "de produção e você acompanha a mudança de status no Portal.<br>"
     "Deixo o chamado em atendimento até a confirmação. Qualquer coisa, é só responder por aqui.", ""),
  ])

abertura = card("Abrir novo chamado",
  '<p class="lede" style="font-size:var(--text-sm)">Descreva o que aconteceu. Chamados vinculados a um pedido são respondidos mais rápido, porque o time já enxerga o lote, a produção e a nota fiscal.</p>'
  + form([campo("Assunto","Ex.: aliança recebida com aro errado",True,largura=2),
          campo("Categoria","Pedidos",True,"select",hint="Pedidos · Financeiro · Vitrine / Loja · Personalização da loja"),
          campo("Pedido relacionado","#12548 · Maria Silva",False,"select",hint="Opcional — vincula o chamado ao pedido"),
          campo("Prioridade","Média",False,"select"),
          campo("Cliente final vinculado","Maria Silva",False,"select",hint="Aparece só como pessoa vinculada; não participa da conversa"),
         ], 2)
  + campo("Descrição","Conte o que aconteceu, com número do pedido, aro, acabamento e o que você espera como solução.",True,"textarea")
  + f'<div class="upload" style="margin-top:var(--space-3)"><span class="upload__ic">{ic("upload")}</span>'
    '<strong>Anexar fotos ou documentos</strong><small>PNG, JPG ou PDF · até 5 MB por arquivo</small></div>'
  + '<div class="row row--wrap" style="margin-top:var(--space-4)">'
  + btn("Abrir chamado","primary","support",sm=False)
  + btn("Salvar rascunho","secondary",sm=False)
  + btn("Falar no WhatsApp","secondary","whats",sm=False) + '</div>'
  + notice("O atendimento ocorre entre <strong>a Velaro e a sua loja</strong>. O cliente final aparece apenas como pessoa vinculada ao pedido e não participa da conversa (Anexo I §5.12)."))

body = f'''
{head("Chamado de suporte","Abra um chamado novo ou acompanhe o atendimento em andamento com a equipe Velaro.",
  btn("← Voltar para o suporte","secondary",href="36-portal-suporte.html",sm=False)
  + btn("Central de ajuda","secondary","book","43-portal-ajuda.html",sm=False))}
{abertura}
{card(None,
  '<div class="spread"><div>'
  '<span class="eyebrow">Chamado em andamento</span>'
  '<div class="row row--wrap" style="gap:10px;margin-top:6px"><h2 class="display-sm">#45821</h2>'
  + chip("Em atendimento","info") + chip("Prioridade: Média","warn") + '</div>'
  '<p class="lede" style="margin-top:6px"><strong style="color:var(--ink)">Dúvida sobre prazos de entrega</strong></p>'
  '<small class="muted">Aberto em 16/05/2026 às 10:32 · última atualização 16/05/2026 às 11:26</small></div>'
  + btn("Imprimir","secondary","print",sm=False) + btn("Encerrar chamado","secondary","check",sm=False) + '</div>'
  + '<div class="identbar" style="margin-top:var(--space-4)">'
  + "".join(f'<div class="identcell"><span><small>{e(k)}</small><strong>{v}</strong></span></div>'
      for k, v in [("Minha loja","Tomazelli Alianças<br><small class='muted'>Cód. 00876</small>"),
                   ("Contato","João Ferreira<br><small class='muted'>(11) 98888-2020</small>"),
                   ("Categoria","Pedidos"),
                   ("Pedido relacionado","#12458<br><a class='link-gold' href='39-portal-pedido.html'>Ver pedido ↗</a>"),
                   ("Cliente final","Maria Silva<br><small class='muted'>Vinculada ao pedido</small>")])
  + '</div>')}
<div class="split split--wide">
  <div class="stack">
    {card("Conversa", f'<div class="thread">{conversa}</div>'
      + '<div class="replybox">'
      + campo("Sua resposta","Digite sua mensagem para a equipe Velaro…",tipo="textarea")
      + f'<div class="spread" style="margin-top:var(--space-3)">'
        f'<span class="row" style="gap:10px;color:var(--ink-muted)">{ic("upload")} <small class="muted">Anexar arquivo</small></span>'
      + btn("Enviar mensagem","primary","mail",sm=False) + '</div></div>')}
    {notice("Você vê a conversa completa do atendimento. <strong>Observações internas</strong> da equipe Velaro existem no chamado, mas nunca são exibidas ao revendedor (doc 2-8 §3.3).","info")}
  </div>
  <div class="stack">
    {card("Detalhes do chamado",
      "".join(linha_dado(k, v) for k, v in [
        ("Protocolo", '<span class="num">#45821</span>'),
        ("Status", chip("Em atendimento","info",flat=True)),
        ("Prioridade", chip("Média","warn",flat=True)),
        ("Categoria","Pedidos"),
        ("Assunto","Dúvida sobre prazos de entrega"),
        ("Pedido relacionado", '<a class="link-gold" href="39-portal-pedido.html">#12458</a>'),
        ("Cliente final","Maria Silva"),
        ("Canal de abertura","Portal do Lojista"),
        ("Aberto em","16/05/2026 às 10:32"),
        ("Última atualização","16/05/2026 às 11:26"),
        ("Responsável na Velaro","Equipe Velaro Suporte")]))}
    {card("Histórico de status", timeline([
      ("now","Em atendimento","Equipe Velaro Suporte","Desde 16/05 11:26"),
      ("done","Respondido","Equipe Velaro Suporte","16/05 11:04"),
      ("done","Aberto","Portal do Lojista · João Ferreira","16/05 10:32")]))}
    {card("Anexos",
      "".join(f'<div class="docfile">{ic("doc")}<span><strong>{e(n)}</strong><small>{e(d)}</small></span>'
              f'<b class="docfile__ok">↓</b></div>'
        for n, d in [("espelho_pedido_12458.pdf","16/05/2026 às 11:18 · 180 KB")])
      + btn("Adicionar anexo","secondary","upload",sm=False))}
    {card("Canais de atendimento",
      "".join(f'<div class="datarow"><span class="datarow__k">{ic(i)}<span>'
              f'<strong style="display:block;color:var(--ink)">{e(t)}</strong><small>{e(d)}</small></span></span>'
              f'<span class="datarow__v">{chip(s, stom, flat=True)}</span></div>'
        for i, t, d, s, stom in [
          ("support","Chat online","Disponível na plataforma","Online","ok"),
          ("whats","WhatsApp","(11) 99999-9999","Online","ok"),
          ("mail","E-mail","suporte@velaroaliancas.com.br","24h","neutral"),
          ("phone","Telefone","(11) 3000-0000","08h às 18h","neutral")])
      + '<small class="fhint">Segunda a sexta, das 08h às 18h (horário de Brasília) · exceto feriados</small>')}
    {notice("Rota <code>/portal/suporte/45821</code>. Todo chamado é escopado por <strong>reseller_id</strong>: você só enxerga os chamados da sua loja (doc 2-8 §2).","info")}
  </div>
</div>'''
W("42-portal-chamado.html", P("Chamado de suporte | 36-portal-suporte.html", body, "Portal do Lojista · Suporte"))

# ══════════════════════════════════════════════════════════════════════════════
# 43 · CENTRAL DE AJUDA — absorve os 5 stubs (36 ×4 e 35 ×1)
# ══════════════════════════════════════════════════════════════════════════════
CATS = [("book","Primeiros passos","8 artigos","#primeiros-passos"),
        ("bag","Catálogo e pedidos","14 artigos","#pedidos"),
        ("coin","Financeiro e pagamentos","11 artigos","#financeiro"),
        ("tag","Preços e margens","9 artigos","#precificacao"),
        ("store","Vitrine e personalização","12 artigos","#vitrine"),
        ("doc","Notas fiscais","6 artigos","#notas")]
categorias = "".join(
  f'<a class="quickcard" href="{h}">{ic(i)}<span><strong>{e(t)}</strong><small>{e(d)}</small></span><b>›</b></a>'
  for i, t, d, h in CATS)

def artlist(itens, ativo=None):
    return '<div class="artlist">' + "".join(
      f'<a class="artitem{" is-on" if t==ativo else ""}" href="#precificacao">{ic("doc")}'
      f'<span><strong>{e(t)}</strong></span><small>{e(m)}</small></a>' for t, m in itens) + '</div>'

exemplo_calc = tabela(
  [("Custo Velaro","cell-num"),("Multiplicador","cell-num"),("Preço ao cliente final","cell-num"),("Margem","cell-num")],
  [[f'<span class="num">{a}</span>', f'<span class="num">{b}</span>',
    f'<span class="cell-strong num">{c}</span>', f'<span class="num">{d}</span>']
   for a, b, c, d in [("R$ 500,00","× 3,6","R$ 1.800,00","72,2%"),
                      ("R$ 1.000,00","× 3,6","R$ 3.600,00","72,2%"),
                      ("R$ 1.120,00","× 2,0","R$ 2.240,00","50,0%"),
                      ("R$ 2.000,00","× 1,8","R$ 3.600,00","44,4%")]])

artigo = f'''
<div class="row row--wrap" style="gap:8px;margin-bottom:var(--space-3)">
  {chip("Preços e margens","brand",flat=True)}
  <small class="muted">6 min de leitura</small>
  <small class="muted">· Atualizado em 12/05/2026</small>
</div>
<h2 class="display-sm">Como definir a margem e o preço de venda da sua loja</h2>
<div class="artbody" style="margin-top:var(--space-4)">
  <p>O preço que o seu cliente paga é <strong>definido por você</strong>. A Velaro cobra da sua loja
  apenas o custo B2B do pedido; o valor da etiqueta, o parcelamento e a promoção são decisões da
  Tomazelli Alianças. Este artigo mostra como a plataforma calcula o preço sugerido e como escolher
  o número que faz sentido para a sua vitrine.</p>

  <h3>1. Os três números da tela Preços e margens</h3>
  <ul>
    <li><strong>Custo Velaro</strong> — o que a sua loja paga pela peça. Nunca aparece para o consumidor.</li>
    <li><strong>Margem (%)</strong> — quanto do preço final sobra para a loja depois do custo.</li>
    <li><strong>Markup</strong> — quanto você acrescenta sobre o custo. Margem de 50% equivale a markup de 100%.</li>
  </ul>

  <h3>2. Escolha o modelo: multiplicador ou percentual</h3>
  <p>O <strong>multiplicador</strong> é o modelo mais usado em joalheria: você define um fator único
  (3,6x, por exemplo) e a plataforma aplica a todo o catálogo. O <strong>percentual</strong> é mais
  fino: define uma margem alvo e a plataforma calcula o preço para chegar nela, produto a produto.</p>

  <h3>3. Simulação com os produtos do seu catálogo</h3>
  {exemplo_calc}

  <h3>4. Faixas saudáveis</h3>
  <ul>
    <li><strong>Margem ideal</strong> — 40% ou mais. É a faixa que sustenta vitrine física, vendedor e reposição.</li>
    <li><strong>Margem baixa</strong> — entre 20% e 39%. Aceitável em campanha e em peça de giro rápido.</li>
    <li><strong>Margem crítica</strong> — abaixo de 20%. A plataforma sinaliza em vermelho na listagem.</li>
  </ul>

  <h3>5. Arredondamento e percepção de preço</h3>
  <p>A plataforma arredonda o preço calculado conforme a regra escolhida (para cima em 0,99, para a
  dezena, ou nenhum). Arredondar para cima raramente derruba conversão e devolve alguns pontos de
  margem ao longo do mês.</p>

  <h3>6. O que a plataforma nunca faz</h3>
  <p>Não processa o pagamento do consumidor final, não sugere preço mínimo obrigatório e não expõe
  o custo Velaro na vitrine. O pagamento do cliente acontece <strong>no caixa da sua loja</strong>.</p>
</div>
<div class="spread" style="margin-top:var(--space-5);padding-top:var(--space-4);border-top:1px solid var(--border)">
  <span class="row row--wrap" style="gap:10px"><small class="muted">Este artigo foi útil?</small>
    {btn("Sim","secondary","check")}{btn("Não","secondary","x")}</span>
  {btn("Ir para Preços e margens","gold","tag","35-portal-precos.html",sm=False)}
</div>'''

FAQ = [
 ("Quando o meu pedido entra em produção?",
  "Assim que o lote da semana é quitado. A produção leva 5 dias úteis, mais 2 dias úteis de transporte até a sua loja."),
 ("O consumidor final consegue comprar pelo site da Velaro?",
  "Não. O consumidor não tem login na plataforma e não paga a Velaro. Ele escolhe na sua vitrine e paga no caixa da sua loja."),
 ("Posso pagar um pedido isolado, fora do lote?",
  "Não. A cobrança é sempre por lote semanal. Um pedido feito depois do fechamento entra automaticamente no lote seguinte."),
 ("Quem emite a nota fiscal para o meu cliente?",
  "Você. A Velaro emite a NF-e da venda B2B contra o CNPJ da sua loja; a nota da venda ao consumidor final é responsabilidade da sua loja."),
 ("O cliente vê que a fábrica é a Velaro?",
  "Não. A vitrine é white label: sai com a marca da sua loja, e as mensagens de retirada são enviadas em nome dela."),
 ("Como corrijo o preço de um único produto?",
  "Em Preços e margens, ative “Permitir edição manual por produto” e edite o preço sugerido direto na linha da tabela."),
]
faq = "".join(f'<div class="faqitem"><strong>{e(p)}</strong><p>{e(r)}</p></div>' for p, r in FAQ)

GUIAS = [("Manual do Portal do Lojista (PDF)","Versão 2026.1 · 48 páginas · 6,2 MB"),
         ("Guia rápido: primeiro pedido em 10 minutos","PDF · 8 páginas · 1,1 MB"),
         ("Checklist de abertura da vitrine","PDF · 2 páginas · 320 KB"),
         ("Tabela de aros e equivalências","PDF · 1 página · 180 KB"),
         ("Modelo de etiqueta e espelho de pedido","ZIP · 2,4 MB")]
guias = "".join(
  f'<div class="docfile">{ic("doc")}<span><strong>{e(n)}</strong><small>{e(d)}</small></span>'
  f'<b class="docfile__ok">↓</b></div>' for n, d in GUIAS)

VIDEOS = [("Tour pelo Portal do Lojista","Primeiros passos","8:12"),
          ("Montando o primeiro pedido no catálogo","Catálogo e pedidos","5:40"),
          ("Fechando e pagando o lote semanal","Financeiro","6:55"),
          ("Publicando a sua vitrine white label","Vitrine e personalização","9:20")]
videos = "".join(
  f'<a class="videocard" href="#"><span class="videocard__thumb"><b>▶</b><em>{e(dur)}</em></span>'
  f'<strong>{e(t)}</strong><small>{e(cat)} · {e(dur)}</small></a>'
  for t, cat, dur in VIDEOS)

body = f'''
{head("Central de ajuda","Tutoriais, guias e respostas para as dúvidas mais comuns do Portal do Lojista.",
  btn("← Voltar para o suporte","secondary",href="36-portal-suporte.html",sm=False)
  + btn("Abrir chamado","gold","support","42-portal-chamado.html",sm=False))}
{card(None,
  f'<div class="helpsearch">{ic("search")}<span>Buscar artigo, guia ou vídeo — ex.: “margem”, “lote”, “nota fiscal”…</span></div>'
  + '<div class="row row--wrap" style="margin-top:var(--space-3);gap:6px">'
    '<small class="muted">Buscas mais comuns:</small>'
  + "".join(chip(t,"neutral",flat=True) for t in
      ["prazo de produção","pagar o lote","margem ideal","baixar XML","aro errado","publicar vitrine"])
  + '</div>')}
{card("Categorias", f'<div class="quickgrid">{categorias}</div>')}
<div class="split" style="--gcols:minmax(0,1fr) 320px">
  <div class="stack">
    <section id="precificacao">{card(None, artigo)}</section>
    <section id="faq">{card("Perguntas frequentes", faq,
      acao='<a class="link-gold" href="42-portal-chamado.html">Não achei minha dúvida →</a>')}</section>
    <section id="guias">{card("Guias e manuais", guias
      + '<small class="fhint">Materiais atualizados a cada revisão do catálogo. Baixe sempre a versão mais recente.</small>')}</section>
    <section id="videos">{card("Vídeos tutoriais", f'<div class="videogrid">{videos}</div>'
      + '<div class="row row--wrap" style="margin-top:var(--space-4)">'
      + btn("Ver todos os vídeos","secondary","eye") + '</div>')}</section>
  </div>
  <div class="stack">
    {card("Nesta categoria · Preços e margens", artlist([
      ("Como definir a margem e o preço de venda da sua loja","6 min"),
      ("Multiplicador ou percentual: qual usar","4 min"),
      ("Arredondamento de preços na vitrine","3 min"),
      ("Preço promocional sem quebrar a margem","5 min"),
      ("Por que o custo Velaro nunca aparece ao cliente","2 min"),
      ("Recalculando preços após reajuste da fábrica","4 min")],
      ativo="Como definir a margem e o preço de venda da sua loja"))}
    {card("Mais lidos", artlist([
      ("Como funciona o lote semanal de pagamento","5 min"),
      ("Prazo de produção e de transporte","3 min"),
      ("Publicar a vitrine white label","7 min"),
      ("Baixar PDF e XML das notas fiscais","2 min"),
      ("Solicitar troca por aro errado","4 min")]))}
    {card("Atalhos do Portal",
      "".join(f'<a class="seclink" href="{h}">{ic(i)}<span><strong>{e(t)}</strong><small>{e(d)}</small></span></a>'
        for i, t, d, h in [
          ("tag","Preços e margens","Ajuste margem, markup e arredondamento","35-portal-precos.html"),
          ("coin","Financeiro","Lotes, pagamentos e vencimentos","32-portal-financeiro.html"),
          ("doc","Notas fiscais","PDF e XML das NF-e emitidas","40-portal-notas.html"),
          ("store","Personalização da loja","Identidade, banner e regra de preço","34-portal-loja.html")]))}
    {card("Não encontrou o que precisava?",
      '<p class="lede" style="font-size:var(--text-sm)">Abra um chamado e o time da Velaro responde em horário comercial, com o seu pedido e o seu lote já à vista.</p>'
      + btn("Abrir chamado","primary","support","42-portal-chamado.html",sm=False)
      + btn("Falar no WhatsApp","secondary","whats",sm=False)
      + linha_dado("Atendimento","Segunda a sexta, 08h às 18h","clock"))}
    {notice("A central de ajuda é do <strong>Portal do Lojista</strong>. Conteúdo para o consumidor final não existe aqui: ele não tem login na plataforma.","info")}
  </div>
</div>'''
W("43-portal-ajuda.html", P("Central de ajuda | 36-portal-suporte.html", body, "Portal do Lojista · Suporte"))
