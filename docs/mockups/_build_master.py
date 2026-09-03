# -*- coding: utf-8 -*-
"""Etapa 3 · Painel Interno Velaro (Master)."""
import importlib.util as il
s = il.spec_from_file_location("u", "_ui.py"); u = il.module_from_spec(s); s.loader.exec_module(u)
g = globals(); g.update({k: getattr(u, k) for k in dir(u) if not k.startswith("__")})
W = lambda f, c: (open(f, "w", encoding="utf-8").write(religar(c, f)), print("  ✓", f))
M = lambda a, b: page("Velaro · " + a.split("|")[0].strip(), master_shell(a.split("|")[1].strip(), b))

# ══════════════════════════ 3.2 CLIENTES ══════════════════════════
CLI = [("MS","Maria Silva Oliveira","CPF: 123.456.789-00","João Ferreira","Joias & Cia","Pessoa Física","brand",
        "(11) 98765-4321","maria.silva@email.com","São Paulo / SP","22/05/2026","#1258"),
       ("JS","João Santos","CPF: 987.654.321-00","Amanda Vieira","Romance Joias","Pessoa Física","brand",
        "(19) 98123-4567","joao.santos@email.com","Campinas / SP","21/05/2026","#1256"),
       ("CA","Carla Lima","CPF: 321.654.987-00","Felipe Costa","Aliança &amp; Cia","Pessoa Física","brand",
        "(21) 97654-3210","carla.lima@email.com","Rio de Janeiro / RJ","20/05/2026","#1254"),
       ("AB","Aliança &amp; Cia Ltda","CNPJ: 12.345.678/0001-90","João Ferreira","Joias &amp; Cia","Pessoa Jurídica","info",
        "(41) 99988-7766","contato@aliancaecia.com.br","Curitiba / PR","19/05/2026","#1253"),
       ("RF","Romance Joias","CNPJ: 23.456.789/0001-10","Amanda Vieira","Romance Joias","Pessoa Jurídica","info",
        "(31) 99887-6655","vendas@romancejoias.com.br","Belo Horizonte / MG","18/05/2026","#1252"),
       ("FV","Fernando Vieira","CPF: 111.222.333-44","Felipe Costa","Aliança &amp; Cia","Pessoa Física","brand",
        "(11) 91234-5678","fernando.vieira@email.com","São Bernardo / SP","15/05/2026","#1249"),
       ("DL","D'Luxe Joalheria","CNPJ: 34.567.890/0001-20","Juliana Marques","D'Luxe Joalheria","Pessoa Jurídica","info",
        "(48) 99123-4455","comercial@dluxejoias.com.br","Florianópolis / SC","14/05/2026","#1247")]
rows = [[f'<div class="row" style="gap:10px"><span class="avatar avatar--sm" style="background:var(--color-gold-100);color:var(--color-gold-800)">{a}</span>'
         f'<span><strong style="color:var(--ink)">{n}</strong><br><small class="muted">{doc}</small></span></div>',
         f'{rn}<br><small class="muted">{rl}</small>', chip(tp, ttom, flat=True),
         f'{tel}<br><small class="muted">{ml}</small>', cid,
         f'{d}<br><small class="muted">{p}</small>', '<span class="muted">⋯</span>']
        for a, n, doc, rn, rl, tp, ttom, tel, ml, cid, d, p in CLI]

det = drawer("Maria Silva Oliveira", "".join([
  tabs(["Resumo","Pedidos","Dados cadastrais"], "Resumo"),
  '<div><span class="eyebrow">Informações principais</span>'
  + "".join(linha_dado(k, e(v), i) for i, k, v in [
      ("users","Tipo de cliente","Pessoa Física"),("mail","E-mail","maria.silva@email.com"),
      ("phone","Telefone","(11) 98765-4321"),("calendar","Data de cadastro","10/01/2026"),
      ("pin","Cidade/UF","São Paulo / SP")]) + '</div>',
  '<div><span class="eyebrow">Revendedor responsável</span>'
  '<div class="pickitem" style="margin-top:8px"><div class="pickitem__top">'
  '<div class="row" style="gap:10px"><span class="avatar avatar--sm" style="background:var(--color-gold-100);color:var(--color-gold-800)">JF</span>'
  '<span><strong>João Ferreira</strong><br><small>Joias &amp; Cia</small></span></div></div>'
  '<small>(11) 94567-8901 · joao.ferreira@joiascia.com.br</small>'
  + btn("Ver revendedor","secondary","store","58-master-revendedores.html") + '</div></div>',
  '<div><span class="eyebrow">Resumo de compras</span><div class="grid g3" style="margin-top:8px">'
  + "".join(f'<div class="ministat"><strong>{v}</strong><small>{e(t)}</small></div>'
      for v, t in [("12","Total de pedidos"),("R$ 12.450,00","Total gasto"),("22/05/2026","Último pedido #1258")])
  + '</div></div>',
  campo("Observações","Cliente prefere contato via WhatsApp.",tipo="textarea"),
]), sub="CPF: 123.456.789-00", chip_html=chip("Ativo","ok"),
  acoes=btn("Ver pedidos","secondary","bag","54-master-pedidos.html",sm=False)
        + btn("Ver revendedor","secondary","store","58-master-revendedores.html",sm=False)
        + btn("Editar cadastro","primary","edit",sm=False))

body = f'''
{head("Clientes","Gerencie seus clientes, acompanhe pedidos e histórico de compras.")}
{kpis([("users","Total de clientes","1.248",up("15% vs mês anterior"),"gold"),
       ("check","Clientes ativos","842",up("12% vs mês anterior"),"ok"),
       ("user-plus","Novos clientes (mês)","64",up("20% vs mês anterior"),"violet"),
       ("x","Clientes inativos","406",down("8% vs mês anterior"),"danger")])}
<div class="split split--wide">
  <div class="stack">
    {filtros("Buscar cliente por nome, e-mail ou telefone…",
      [("Status","Todos"),("Cidade/UF","Todas"),("Tipo de cliente","Todos")],
      acoes=btn("Mais filtros","secondary","filter"))}
    {card(None, tabela([("Cliente",""),("Revendedor responsável",""),("Tipo",""),("Contato",""),
        ("Cidade/UF",""),("Último pedido",""),("Ações","cell-num")], rows,
      foot=pag("Mostrando 1 a 7 de 1.248 clientes","1 2 3 … 125", '<span class="select-fake">10 por página</span>')))}
    {notice("Todo cliente final aparece <strong>sempre com o revendedor responsável identificado</strong>. Não há cadastro manual de cliente pelo Master como fluxo comercial padrão (Anexo I §5.2).")}
  </div>
  {det}
</div>'''
W("50-master-clientes.html", M("Clientes | 50-master-clientes.html", body))

# ══════════════════════════ 3.3 CONFIGURAÇÕES ══════════════════════════
secoes = "".join(
  f'<a class="seclink{" is-on" if on else ""}" href="#">{ic(i)}<span><strong>{e(t)}</strong><small>{e(d)}</small></span></a>'
  for i, t, d, on in [
    ("store","Perfil da empresa","Dados da sua loja e informações gerais",True),
    ("users","Usuários e permissões","Gerencie acessos e níveis de permissão",False),
    ("bell","Notificações","Preferências de alertas e comunicações",False),
    ("link","Integrações","Conexões com sistemas externos",False),
    ("lock","Segurança","Senha, 2FA e sessões ativas",False),
    ("coin","Financeiro","Configurações financeiras e fiscais",False),
    ("tag","Personalização","Aparência e identidade visual",False),
    ("download","Backup e dados","Exportar e gerenciar dados",False)])

pagamentos = tabela([("Forma de pagamento",""),("Status","")],
  [["PIX", chip("Ativo","ok")], ["Boleto Bancário", chip("Ativo","ok")], ["Cartão de Crédito", chip("Inativo","danger")]])

body = f'''
{head("Configurações","Gerencie as preferências e parâmetros da sua conta e da plataforma.")}
<div class="split3" style="--gcols:300px minmax(0,1fr)">
  <div class="stack">{secoes}</div>
  <div class="stack">
    {card("Perfil da empresa",
      '<div class="split" style="--gcols:minmax(0,1fr) 220px;gap:var(--space-5)">'
      + '<div class="fgrid fgrid--2">'
      + "".join(campo(k, v) for k, v in [
          ("Nome fantasia","Velaro Alianças"),("Razão social","Velaro Alianças Ltda"),
          ("CNPJ","12.345.678/0001-90"),("Inscrição estadual","123.456.789.112"),
          ("E-mail comercial","contato@velaroaliancas.com.br"),("Telefone","(11) 98765-4321")])
      + campo("Endereço","Rua das Alianças, 100 – Centro · São Paulo / SP – 01000-000",largura=2)
      + '</div>'
      + f'<div class="logobox">{logo(40)}<strong style="margin-top:8px">VELARO</strong><small>ALIANÇAS</small>'
        f'<span class="logobox__up">{ic("edit")} Alterar logo</span></div></div>',
      acao=btn("Editar informações","secondary","edit"))}

    <div class="split" style="--gcols:minmax(0,1.35fr) minmax(0,1fr)">
      {card("Configurações gerais",
        '<div class="fgrid fgrid--1" style="max-width:320px">'
        + campo("Moeda padrão","Real (R$)",tipo="select")
        + campo("Fuso horário","(GMT-03:00) Brasília",tipo="select")
        + campo("Idioma","Português (Brasil)",tipo="select") + '</div>'
        + '<div style="margin-top:var(--space-5)">'
        + toggle("Exibir estoque negativo","Permitir visualização de produtos com estoque abaixo de zero", False)
        + toggle("Permitir pedidos sem estoque","Permitir que revendedores façam pedidos mesmo sem estoque disponível", False)
        + toggle("Aprovação automática de pedidos","Pedidos serão aprovados automaticamente após o pagamento")
        + toggle("Notificações por e-mail","Receber notificações importantes por e-mail") + '</div>')}
      {card("Configurações de pedidos",
        campo("Prazo padrão de produção","7 dias úteis",tipo="select")
        + campo("Validade do orçamento","15 dias",tipo="select")
        + campo("Cancelamento automático","Cancelar pedidos não pagos após 7 dias",tipo="select")
        + campo("Numeração dos pedidos","Sequencial por ano",tipo="select")
        + campo("Data de corte do lote","Toda segunda-feira",tipo="select",hint="Parâmetro de lote/remessa — Anexo I §6")
        + campo("Vencimento do lote","7 dias após o corte",tipo="select"))}
    </div>

    <div class="split3" style="--gcols:repeat(3,minmax(0,1fr))">
      {card("Informações fiscais",
        campo("Regime tributário","Simples Nacional",tipo="select")
        + campo("Série da nota fiscal","1",tipo="select")
        + btn("Configurar notas fiscais","secondary","doc",sm=False))}
      {card("Formas de pagamento B2B", pagamentos + btn("Gerenciar formas de pagamento","secondary","gear",sm=False))}
      {card("Outras configurações",
        "".join(f'<a class="seclink" href="#">{ic(i)}<span><strong>{e(t)}</strong><small>{e(d)}</small></span></a>'
          for i, t, d in [("download","Exportar dados","Faça o download dos dados da sua conta."),
                          ("refresh","Limpar cache","Melhore a performance do sistema."),
                          ("trash","Excluir conta","Atenção: esta ação não pode ser desfeita.")]))}
    </div>
    {notice("Toda escrita nesta tela gera registro em <code>audit_logs</code>. Credencial de integração fica cifrada em repouso e nunca é reexibida após salvar.")}
  </div>
</div>'''
W("51-master-config.html", M("Configurações | 51-master-config.html", body))

# ══════════════════════════ 3.4 ESTOQUE ══════════════════════════
EST = [("ALC-4MM-OU","Aliança Clássica 4mm","Clássica","Ouro 18k Amarelo","10 - 33","142","18","20","Sugerida","Em estoque","ok"),
       ("ALT-4MM-OU","Aliança Tradicional 4mm","Tradicional","Ouro 18k Amarelo","10 - 33","96","12","20","Sugerida","Em estoque","ok"),
       ("ALD-6MM-OU","Aliança Diamantada 6mm","Diamantada","Ouro 18k Amarelo","12 - 33","18","6","20","Prioritária","Baixo estoque","warn"),
       ("ALF-6MM-OU","Aliança Fosca 6mm","Fosca","Ouro 18k Amarelo","12 - 33","0","10","10","Prioritária","Reservado","info"),
       ("ALS-CLASS-PT","Anel Solitário Classic","Solitários","Ouro 18k Branco","10 - 24","34","4","10","Sugerida","Em estoque","ok"),
       ("APZ-VINT-OU","Aliança Vintage 5mm","Vintage","Ouro 18k Amarelo","12 - 30","7","1","10","Prioritária","Baixo estoque","warn"),
       ("ALR-ROSE-4MM","Aliança Rose 4mm","Rose","Ouro 18k Rosé","10 - 26","19","2","10","Sugerida","Em estoque","ok"),
       ("ALC-8MM-OU","Aliança Clássica 8mm","Clássica","Ouro 18k Amarelo","14 - 33","12","8","15","Sugerida","Em estoque","ok")]
rows = [[f'<code>{sku}</code>', f'<strong style="color:var(--ink)">{n}</strong>', col, mat,
         f'<span class="num">{tam}</span>', f'<span class="cell-strong num">{at}</span>',
         f'<span class="num">{rs}</span>', f'<span class="num">{mn}</span>',
         (chip(rp,"danger",flat=True) if rp=="Prioritária" else chip(rp,"neutral",flat=True)),
         chip(st, tom), '<span class="muted">⋯</span>']
        for sku, n, col, mat, tam, at, rs, mn, rp, st, tom in EST]

portam = tabela([("Tamanho",""),("Estoque atual","cell-num"),("Reservado","cell-num"),("Disponível","cell-num"),("Mínimo","cell-num")],
  [[f'<span class="num">{a}</span>'] + [f'<span class="num">{x}</span>' for x in r]
   for a, r in [("10 - 14",["28","4","24","5"]),("15 - 19",["36","5","31","5"]),
                ("20 - 24",["34","5","29","5"]),("25 - 29",["26","3","23","5"]),("30 - 33",["18","1","17","5"])]])

movs = "".join(
  f'<div class="datarow"><span class="datarow__k">{ic(i)}<span><strong style="display:block;color:var(--ink)">{e(t)}</strong>'
  f'<small>{e(o)}</small></span></span><span class="datarow__v"><span class="{c}">{e(q)}</span><br>'
  f'<small class="muted">{e(d)}</small></span></div>'
  for i, t, q, c, d, o in [
    ("arrow-up","Entrada","+30 unidades","kpi__delta--up","07/05/2026 10:45","Sistema"),
    ("bag","Reserva","−6 unidades","kpi__delta--down","06/05/2026 16:22","Pedido #5841"),
    ("arrow-up","Entrada","+20 unidades","kpi__delta--up","05/05/2026 09:18","Sistema"),
    ("edit","Ajuste manual","−2 unidades","kpi__delta--down","03/05/2026 14:37","Admin")])

det = drawer("Aliança Clássica 4mm", "".join([
  f'<div class="prod__img" style="height:150px">{rings.svg("classica", 700)}</div>',
  "".join(linha_dado(k, e(v), i) for i, k, v in [
    ("doc","SKU","ALC-4MM-OU"),("diamond","Coleção","Clássica"),("tag","Material","Ouro 18k Amarelo"),
    ("sparkle","Acabamento","Polido"),("pin","Local de armazenamento","Matriz - Cofre A1")]),
  f'<div><span class="eyebrow">Estoque por tamanho</span>{portam}</div>',
  '<div class="row row--wrap">' + btn("Ajustar estoque","secondary","edit") + btn("Registrar entrada","secondary","arrow-up")
  + btn("Solicitar produção","primary","gear") + '</div>',
  '<div class="grid g2">'
  + '<div class="ministat"><strong>18</strong><small>unidades reservadas</small>'
    '<a class="link-gold" href="#">Ver reservas →</a></div>'
  + '<div class="ministat"><strong>20</strong><small>unidades sugeridas</small>'
    '<a class="link-gold" href="#">Gerar pedido →</a></div></div>',
  f'<div><span class="eyebrow">Últimas movimentações</span>{movs}'
  '<a class="link-gold" href="#">Ver todas →</a></div>',
  '<div><span class="eyebrow">Ajuste manual rápido</span>'
  '<div class="row" style="gap:8px;margin-top:8px"><div class="qtybox"><span>−</span><b class="num">0</b><span>+</span></div>'
  '<span class="select-fake">unidades</span>' + btn("Aplicar ajuste","primary","check") + '</div>'
  '<small class="fhint">Ajuste de estoque é ação sensível: gera registro em <code>audit_logs</code> com valor anterior e posterior.</small></div>',
]), chip_html=chip("Em estoque","ok"))

body = f'''
{head("Estoque","Acompanhe disponibilidade dos produtos, saldos por tamanho, reservas e necessidade de reposição.")}
{kpis([("box","Itens em estoque","2.587",up("8,4% vs. mês anterior"),"gold"),
       ("info","Baixo estoque","87",up("12,1% vs. mês anterior"),"warn"),
       ("bag","Reservados","214",flat("0,0% vs. mês anterior"),"info"),
       ("clock","Sob encomenda","63",up("5,0% vs. mês anterior"),"violet"),
       ("cart","Reposições pendentes","23",up("4,5% vs. mês anterior"),"danger")], "g5")}
<div class="split split--wide">
  <div class="stack">
    {filtros("Buscar por SKU, produto ou coleção…",
      [("Categoria","Todas"),("Status","Todos"),("Local","Todos")],
      acoes=btn("Filtros","secondary","filter") + btn("Exportar","secondary","download") + btn("+ Nova movimentação","primary"))}
    {card(None, tabela([("SKU",""),("Produto",""),("Coleção",""),("Material",""),("Tamanhos",""),
        ("Estoque atual","cell-num"),("Reservado","cell-num"),("Estoque mínimo","cell-num"),
        ("Reposição",""),("Status",""),("Ações","cell-num")], rows, check=True,
      foot=pag("Mostrando 1 a 8 de 58 itens","1 2 3 … 8", '<span class="select-fake">10 por página</span>')))}
    {notice("O controle de estoque físico principal pertence à Velaro. O Portal do Parceiro Premium <strong>apenas consulta</strong> disponibilidade (Anexo I §6).")}
  </div>
  {det}
</div>'''
W("52-master-estoque.html", M("Estoque | 52-master-estoque.html", body))

# ══════════════════════════ 3.5 FINANCEIRO ══════════════════════════
LOT = [("SEM-2405","Tomazelli Alianças","Mai/2026","#8765, #8766, #8767","R$ 15.680,00","28/05/2026","27/05/2026","Pago","ok","NF enviada","ok","Liberado","ok"),
       ("SEM-2404","Aliança Ouro Fino","Mai/2026","#8745, #8746","R$ 9.450,00","25/05/2026","25/05/2026","Pago","ok","NF enviada","ok","Liberado","ok"),
       ("SEM-2403","Joias &amp; Cia","Mai/2026","#8721, #8722, #8723","R$ 12.320,00","20/05/2026","19/05/2026","Pago","ok","NF enviada","ok","Liberado","ok"),
       ("SEM-2402","Brilho Eterno","Mai/2026","#8701, #8702","R$ 8.900,00","18/05/2026","-","Aguardando baixa","warn","-","neutral","Pendente","warn"),
       ("SEM-2401","Reis Alianças","Mai/2026","#8687, #8688, #8689","R$ 14.780,00","15/05/2026","-","Aguardando baixa","warn","-","neutral","Pendente","warn"),
       ("ABR-2404","Tomazelli Alianças","Abr/2026","#8650, #8651","R$ 10.450,00","30/04/2026","29/04/2026","Pago","ok","NF enviada","ok","Liberado","ok"),
       ("ABR-2403","Aliança Tradição","Abr/2026","#8631, #8632","R$ 7.220,00","28/04/2026","26/04/2026","Pago","ok","NF enviada","ok","Liberado","ok"),
       ("ABR-2401","Joalheria Prime","Abr/2026","#8589, #8590","R$ 9.350,00","22/04/2026","-","Em aberto","danger","-","neutral","Pendente","warn")]
rows = [[f'<strong style="color:var(--ink)">{l}</strong>', r, p, f'<small>{pv}</small>',
         f'<span class="cell-strong num">{v}</span>', f'<span class="num">{dv}</span>',
         (f'<span class="num">{dp}</span>' if dp != "-" else '<span class="muted">—</span>'),
         chip(sf, sftom),
         (chip(nf, nftom, flat=True) if nf != "-" else '<span class="muted">—</span>'),
         chip(le, letom, flat=True), '<span class="muted">⋯</span>']
        for l, r, p, pv, v, dv, dp, sf, sftom, nf, nftom, le, letom in LOT]

fluxo = timeline([
  ("done","1. Recebimento identificado","Pagamento do lote confirmado","27/05/2026 10:35"),
  ("done","2. Baixa financeira realizada","Baixa registrada com sucesso","27/05/2026 10:42"),
  ("done","3. Nota fiscal emitida e enviada","NF emitida e enviada ao revendedor","27/05/2026 11:05"),
  ("done","4. Pedidos aprovados","Aprovados para produção/expedição","27/05/2026 11:20"),
  ("done","5. Liberação para entrega","Liberado para próxima remessa semanal","27/05/2026 11:35")])

pedlote = tabela([("Pedido",""),("Cliente final",""),("Valor do pedido","cell-num"),("Status","")],
  [[f'<strong style="color:var(--ink)">{p}</strong>', c, f'<span class="cell-strong num">{v}</span>', chip("Aprovado","ok",flat=True)]
   for p, c, v in [("#8765","Maria Silva Oliveira","R$ 5.280,00"),("#8766","João Santos","R$ 5.150,00"),
                   ("#8767","Carla Lima","R$ 5.250,00")]])

det = drawer("Lote #SEM-2405", "".join([
  f'<div><span class="eyebrow">Fluxo financeiro e operacional</span>{fluxo}</div>',
  '<div><span class="eyebrow">Dados do revendedor</span>'
  + linha_dado("Tomazelli Alianças","(47) 99916-1234","store")
  + linha_dado("Responsável: Lucas Tomazelli","tomazelli@aliancas.com.br","user") + '</div>',
  '<div><span class="eyebrow">Resumo do lote</span><div class="grid g2" style="margin-top:8px">'
  + "".join(f'<div class="ministat"><strong style="font-size:19px">{v}</strong><small>{e(t)}</small></div>'
      for v, t in [("SEM-2405","Lote"),("Mai/2026","Período"),("3 pedidos","Pedidos vinculados"),("R$ 15.680,00","Valor total")])
  + '</div></div>',
  f'<div><span class="eyebrow">Pedidos do lote</span>{pedlote}</div>',
  '<div><span class="eyebrow">Nota fiscal</span>'
  + linha_dado("NF nº 000.024.587", chip("NF enviada","ok",flat=True), "doc")
  + linha_dado("Data de emissão","27/05/2026","calendar") + '</div>',
  notice("<strong>Liberação logística.</strong> Liberado para envio na próxima remessa semanal. Previsão de envio: <strong>31/05/2026</strong>."),
]), chip_html=chip("Pago e liberado","ok"),
  acoes=btn("Ver pedidos","secondary","bag","54-master-pedidos.html",sm=False)
        + btn("Ver nota fiscal","secondary","doc",sm=False)
        + btn("✓ Confirmar liberação","primary","truck",sm=False))

body = f'''
{head("Financeiro","Acompanhe recebimentos, baixas, notas fiscais e liberações de remessas.")}
{kpis([("coin","Recebimentos do período","R$ 128.560,00",up("18,6% vs. mês anterior"),"ok"),
       ("clock","Lotes aguardando baixa","12",up("9,1% vs. mês anterior"),"warn"),
       ("doc","Notas fiscais emitidas","48",up("22,4% vs. mês anterior"),"info"),
       ("truck","Remessas liberadas","31",up("14,3% vs. mês anterior"),"violet"),
       ("card","Pagamentos pendentes","R$ 32.450,00",down("8,7% vs. mês anterior"),"danger")], "g5")}
<div class="split split--wide">
  <div class="stack">
    {filtros("Buscar por lote, revendedor, pedido…",
      [("Período","Todos"),("Status","Todos"),("Situação","Todos")],
      acoes=btn("Filtros","secondary","filter") + btn("Exportar","secondary","download") + btn("+ Novo recebimento","primary"))}
    {card(None, tabela([("Lote",""),("Revendedor",""),("Período",""),("Pedidos vinculados",""),
        ("Valor do lote","cell-num"),("Data de vencimento",""),("Data de pagamento",""),
        ("Status financeiro",""),("Nota fiscal",""),("Liberação de entrega",""),("Ações","cell-num")], rows, check=True,
      foot=pag("Mostrando 1 a 8 de 28 lotes","1 2 3 … 4", '<span class="select-fake">10 por página</span>')))}
    {notice("Fluxo obrigatório: <strong>recebimento identificado → baixa financeira → NF emitida → pedidos aprovados → liberação para a remessa</strong>. Nenhuma remessa sai sem quitação confirmada do lote (Anexo I §5.5).")}
  </div>
  {det}
</div>'''
W("53-master-financeiro.html", M("Financeiro | 53-master-financeiro.html", body))

# ══════════════════════════ 3.6 PEDIDOS ══════════════════════════
LST = [("#PED-2026-05678","Em transporte","info","15/05/2026","Maria Silva Oliveira","João Ferreira Joias & Cia","R$ 3.240,00","3 itens",True),
       ("#PED-2026-05677","Produção em andamento","violet","14/05/2026","Amanda Vieira","Romance Joias","R$ 2.180,00","2 itens",False),
       ("#PED-2026-05676","Aguardando pagamento","warn","14/05/2026","Carla Lima","Aliança & Cia","R$ 1.760,00","2 itens",False),
       ("#PED-2026-05675","Pronto para retirada","ok","13/05/2026","Fernando Vieira","Aliança & Cia","R$ 5.480,00","4 itens",False),
       ("#PED-2026-05674","Concluído","ok","12/05/2026","Juliana Marques","D'Luxe Joalheria","R$ 2.950,00","2 itens",False)]
lista = "".join(
  f'<a class="pickitem{" is-on" if on else ""}" href="#"><div class="pickitem__top">'
  f'<strong>{e(p)}</strong>{chip(st, tom, flat=True)}</div>'
  f'<small>{e(d)}</small><strong style="font-size:var(--text-body)">{e(c)}</strong>'
  f'<small>Revendedor: {e(r)}</small>'
  f'<div class="pickitem__top" style="margin-top:6px"><span class="cell-strong num">{v}</span>'
  f'<small>{e(n)}</small></div></a>'
  for p, st, tom, d, c, r, v, n, on in LST)

itens = tabela([("Produto",""),("Código",""),("Especificações",""),("Qtd","cell-num"),("Valor unit.","cell-num"),("Total","cell-num")],
  [[f'<div class="row" style="gap:10px">{ringimg(v, 720+i)}<span><strong style="color:var(--ink)">{n}</strong>'
    f'<br><small class="muted">{m}</small></span></div>', f'<code>{cod}</code>',
    f'Aro: {aro}<br><small class="muted">Gravação: {gr}</small>',
    f'<span class="num">{q}</span>', f'<span class="num">{vu}</span>', f'<span class="cell-strong num">{tt}</span>']
   for i, (v, n, m, cod, aro, gr, q, vu, tt) in enumerate([
     ("classica","Aliança Classic 4mm","Ouro 18K","ALC-4MM-18K","18","M ❤ S","1","R$ 1.120,00","R$ 1.120,00"),
     ("classica","Aliança Classic 4mm","Ouro 18K","ALC-4MM-18K","18","M ❤ S","1","R$ 1.120,00","R$ 1.120,00"),
     ("fosca","Aliança Sole 6mm","Ouro 18K","ALS-6MM-18K","20","Para sempre","1","R$ 1.000,00","R$ 1.000,00")])],
  foot='<div class="spread"><strong>Subtotal</strong><span class="num">R$ 3.240,00</span></div>'
       '<div class="spread"><strong>Total</strong><span class="money money--action">R$ 3.240,00</span></div>')

status = timeline([
  ("done","Pedido realizado",None,"15/05 10:32"),
  ("done","Pagamento confirmado",None,"15/05 10:45"),
  ("done","Produção em andamento",None,"16/05 09:10"),
  ("done","Produção finalizada",None,"21/05 14:25"),
  ("now","Em transporte para a loja",None,"22/05 08:30"),
  ("todo","Pronto para retirada na loja","Aguardando chegada à loja","—"),
  ("todo","Retirado pelo cliente","Aguardando confirmação","—")])

hist = "".join(
  f'<div class="datarow"><span class="datarow__k">{ic(i)}<span>'
  f'<strong style="display:block;color:var(--ink)">{e(t)}</strong><small>{e(d)}</small></span></span>'
  f'<span class="datarow__v"><small class="muted">{e(q)}</small></span></div>'
  for i, t, d, q in [
    ("truck","Pedido em transporte","Pedido saiu da fábrica e está a caminho da loja do revendedor.","22/05 08:30"),
    ("check","Produção finalizada","Todos os itens deste pedido foram finalizados com sucesso.","21/05 14:25"),
    ("gear","Produção em andamento","Produção iniciada pela fábrica.","16/05 09:10"),
    ("coin","Pagamento confirmado","Pagamento do pedido confirmado via PIX.","15/05 10:45"),
    ("bag","Pedido realizado","Pedido criado pelo revendedor.","15/05 10:32")])

body = f'''
{head("Pedidos","Acompanhe, gerencie e visualize todos os pedidos realizados.",
      btn("Exportar","secondary","download") + btn("+ Novo pedido","primary"))}
{kpis([("list","Todos","1.248","","gold"),("coin","Aguardando pagamento","24","","warn"),
       ("gear","Em produção","58","","violet"),("truck","Em transporte","16","","info"),
       ("check","Concluídos","1.150","","ok")], "g5")}
<div class="split3">
  <div class="stack">
    {filtros("Buscar pedido, cliente ou código…", [("Status","Todos"),("Período","Últimos 30 dias")])}
    <div class="stacklist">{lista}</div>
    {pag("Mostrando 1 a 5 de 1.248 pedidos","1 2 3 … 125")}
  </div>
  <div class="stack">
    {card(None,
      '<div class="spread"><div><a class="link-gold" href="#">← Voltar para pedidos</a>'
      '<h2 class="display-sm" style="margin-top:6px">Pedido #PED-2026-05678</h2>'
      '<p class="lede" style="font-size:var(--text-sm)">Data do pedido: 15/05/2026 às 10:32</p></div>'
      + chip("Em transporte","info") + btn("⋮ Mais ações","secondary") + '</div>'
      + '<div class="identbar" style="margin-top:var(--space-4)">'
      + "".join(f'<div class="identcell"><span><small>{e(k)}</small><strong>{e(v)}</strong></span></div>'
          for k, v in [("Cliente","Maria Silva Oliveira · CPF 123.456.789-00"),
                       ("Revendedor","João Ferreira Joias & Cia · RV-0156"),
                       ("Total do pedido","R$ 3.240,00"),("Forma de pagamento","PIX"),("Lote","L-2026-0312")])
      + '</div>')}
    {card("Itens do pedido (3)", itens)}
    <div class="split" style="--gcols:1fr 1fr">
      {card("Endereço de entrega (loja do revendedor)",
        '<p class="lede" style="font-size:var(--text-sm)">João Ferreira Joias &amp; Cia<br>'
        'Rua das Joias, 145 - Centro<br>São Paulo / SP - 01000-000</p>')}
      {card("Observações", campo("","Cliente solicitou gravação interna.",tipo="textarea"))}
    </div>
    {card("Histórico de atualizações", hist)}
  </div>
  <div class="stack">
    {card("Status do pedido", status)}
    {notice("Quando o pedido chegar na loja, o revendedor e o cliente serão notificados automaticamente que o pedido está pronto para retirada.")}
    {card("Confirmação de retirada",
      '<p class="lede" style="font-size:var(--text-sm)">Confirme abaixo quando o pedido for retirado pelo cliente na loja.</p>'
      + btn("Confirmar retirada do lote inteiro","secondary","box",sm=False)
      + btn("Confirmar retirada por pedido","primary","check",sm=False))}
    {card("Notificações enviadas",
      "".join(f'<div class="datarow"><span class="datarow__k">{ic(i)}<span>'
              f'<strong style="display:block;color:var(--ink)">{e(t)}</strong><small>{e(d)}</small></span></span>'
              f'<span class="datarow__v">{chip("Enviado","ok",flat=True)}</span></div>'
        for i, t, d in [("store","Revendedor (João Ferreira Joias & Cia)","Enviado em 22/05/2026 às 08:30"),
                        ("user","Cliente (Maria Silva Oliveira)","Enviado em 22/05/2026 às 08:30")]))}
  </div>
</div>'''
W("54-master-pedidos.html", M("Pedidos | 54-master-pedidos.html", body))

# ══════════════════════════ 3.7 PRODUTOS ══════════════════════════
PRD = [("classica","Aliança Classic 4mm","ALC-4MM-18K","Ouro 18K","R$ 1.120,00",True),
       ("conforto","Aliança Classic 6mm","ALC-6MM-18K","Ouro 18K","R$ 1.240,00",False),
       ("fosca","Aliança Sole 6mm","ALS-6MM-18K","Ouro 18K","R$ 1.000,00",False),
       ("fosca","Aliança Sole 8mm","ALS-8MM-18K","Ouro 18K","R$ 1.180,00",False),
       ("diamond","Aliança Diamante 3pts","ALD-3PTS-18K","Ouro 18K","R$ 1.350,00",False),
       ("cravejada","Aliança Diamante 5pts","ALD-5PTS-18K","Ouro 18K","R$ 1.650,00",False),
       ("trabalhada","Aliança Trabalhada 6mm","ALT-6MM-18K","Ouro 18K","R$ 1.090,00",False),
       ("trabalhada","Aliança Trabalhada 8mm","ALT-8MM-18K","Ouro 18K","R$ 1.270,00",False)]
lista = "".join(
  f'<a class="pickitem{" is-on" if on else ""}" href="#"><div class="row" style="gap:10px">{ringimg(v, 740+i)}'
  f'<span style="flex:1;min-width:0"><strong>{e(n)}</strong><br><small>{e(cod)}</small></span>'
  f'<span style="text-align:right"><small>{e(m)}</small><br><span class="cell-strong num">{p}</span></span>'
  f'{chip("Ativo","ok",flat=True)}</div></a>'
  for i, (v, n, cod, m, p, on) in enumerate(PRD))

body = f'''
{head("Produtos","Gerencie seu catálogo de produtos.",
      btn("Exportar","secondary","download") + btn("+ Novo produto","primary"))}
{card(None, tabs(["Lista de produtos","Categorias","Coleções","Materiais","Acabamentos","Gravações"],"Lista de produtos"))}
<div class="split3">
  <div class="stack">
    {filtros("Buscar produto por nome, código ou referência…", [("Categoria","Todas as categorias")])}
    <div class="stacklist">{lista}</div>
    {pag("Mostrando 1 a 8 de 248 produtos","1 2 3 … 25", '<span class="select-fake">10 por página</span>')}
  </div>
  <div class="stack">
    {card(None,
      '<div class="spread"><div><span class="eyebrow">Editando produto</span>'
      '<h2 class="display-sm" style="margin-top:4px">Aliança Classic 4mm</h2></div>'
      + chip("Ativo","ok") + btn("Ver produto","secondary","eye") + '</div>'
      + tabs(["Informações gerais","Preço e disponibilidade","Especificações","Gravação","Imagens"],"Informações gerais")
      + '<div style="margin-top:var(--space-5)">' + form([
          campo("Nome do produto","Aliança Classic 4mm",True),
          campo("Código / Referência","ALC-4MM-18K",True),
          campo("Categoria","Alianças Tradicionais",True,"select"),
          campo("Coleção","Classic",False,"select"),
          campo("Material","Ouro 18K",True,"select"),
          campo("Acabamento","Polido",True,"select"),
          campo("Largura","4 mm",False,"select"),
          campo("Formato","Reta",False,"select"),
          campo("Aro / tamanhos disponíveis","10 a 33",False,hint="Cada aro vira um SKU em product_variants"),
          campo("Preço B2B (custo para o lojista)","R$ 1.120,00",True),
        ], 2)
      + campo("Descrição","Aliança tradicional de 4mm em ouro 18K, acabamento polido. Conforto e durabilidade para o dia a dia.",tipo="textarea")
      + '</div>'
      + toggle("Produto ativo","Produtos inativos não aparecem para os revendedores")
      + toggle("Permite gravação interna","Habilita o campo de gravação no carrinho da vitrine")
      + '<div class="row row--wrap" style="margin-top:var(--space-5)">'
      + btn("Cancelar","secondary",sm=False) + btn("Salvar alterações","primary","check",sm=False) + '</div>')}
  </div>
  <div class="stack">
    {card("Imagem do produto",
      f'<div class="prod__img" style="height:170px">{rings.svg("classica", 760)}</div>'
      + '<div class="row" style="gap:6px;margin-top:8px">'
      + "".join(f'<span class="thumb" style="width:52px;height:52px">{rings.thumb(v, 770+i)}</span>'
          for i, v in enumerate(["classica","conforto","fosca","trabalhada"]))
      + '</div>' + btn("Gerenciar imagens","secondary","edit",sm=False))}
    {card("Resumo do produto",
      "".join(linha_dado(k, v) for k, v in [
        ("Material","Ouro 18K"),("Largura","4 mm"),("Acabamento","Polido"),
        ("Status", chip("Ativo","ok",flat=True)),("Preço base",'<span class="num">R$ 1.120,00</span>'),
        ("Estoque disponível",'<span class="num">128 unidades</span>'),("Revendedores ativos",'<span class="num">42</span>')]))}
    {card("Ações rápidas",
      "".join(f'<a class="seclink" href="#">{ic(i)}<span><strong>{e(t)}</strong></span></a>'
        for i, t in [("doc","Duplicar produto"),("clock","Histórico de alterações"),("trash","Inativar produto")]))}
    {notice("Mudança de preço <strong>não afeta pedido já criado</strong> — <code>unit_price</code> é snapshot imutável.")}
  </div>
</div>'''
W("55-master-produtos.html", M("Produtos | 55-master-produtos.html", body))

# ══════════════════════════ 3.8 PROMOÇÕES ══════════════════════════
PRM = [("Desconto Progressivo - Alianças","Ativa","ok","01/05/2026 até 31/05/2026","Descontos progressivos em alianças selecionadas.","Desconto progressivo","PROMO-2026-05","cravejada",True),
       ("Dia dos Namorados","Agendada","warn","01/06/2026 até 12/06/2026","Coleção especial com preços exclusivos.","Preço especial","PROMO-2026-06","rose",False),
       ("Frete Grátis","Encerrada","neutral","10/04/2026 até 20/04/2026","Frete grátis para pedidos acima de R$ 1.000,00.","Frete grátis","PROMO-2026-04","classica",False),
       ("Brilho que Encanta","Encerrada","neutral","15/03/2026 até 31/03/2026","20% OFF em alianças com diamantes.","Desconto fixo","PROMO-2026-03","diamond",False),
       ("Coleção Eternidade","Rascunho","info","Previsto: 01/07/2026 até 31/07/2026","Lançamento da nova coleção Eternidade.","Lançamento","PROMO-2026-07","trabalhada",False)]
lista = "".join(
  f'<a class="pickitem{" is-on" if on else ""}" href="#"><div class="row" style="gap:10px;align-items:flex-start">'
  f'{ringimg(v, 780+i)}<span style="flex:1;min-width:0"><div class="pickitem__top"><strong>{e(n)}</strong>'
  f'{chip(st, tom, flat=True)}</div><small>{e(per)}</small><br><small>{e(d)}</small>'
  f'<div class="pickitem__top" style="margin-top:6px"><small>Tipo: {e(tp)}</small><small>Cód. {e(cod)}</small></div>'
  f'</span></div></a>'
  for i, (n, st, tom, per, d, tp, cod, v, on) in enumerate(PRM))

body = f'''
{head("Promoções","Crie e gerencie campanhas promocionais para seus revendedores.",
      btn("Visualizar em loja","secondary","eye","03-vitrine-pdv.html") + btn("+ Nova promoção","primary"))}
<div class="split3">
  <div class="stack">
    {filtros("Buscar promoção…", [("Campanhas","Todas as campanhas")])}
    <div class="stacklist">{lista}</div>
    {pag("Mostrando 1 a 5 de 12 promoções","1 2 3", '<span class="select-fake">10 por página</span>')}
  </div>
  <div class="stack">
    {card(None,
      '<div class="spread"><div><span class="eyebrow">Editando promoção</span>'
      '<h2 class="display-sm" style="margin-top:4px">Desconto Progressivo - Alianças</h2></div>'
      + chip("Ativa","ok") + btn("⏸ Pausar campanha","secondary") + btn("Duplicar promoção","secondary","doc") + '</div>'
      + tabs(["Informações básicas","Produtos e regras","Público-alvo","Canais","Condições","Aparência"],"Informações básicas")
      + '<div style="margin-top:var(--space-5)">' + form([
          campo("Nome da promoção","Desconto Progressivo - Alianças",True),
          campo("Período da promoção","01/05/2026  até  31/05/2026",True),
          campo("Código da promoção","PROMO-2026-05",True),
          campo("Status","Ativa",True,"select",hint="Promoção está ativa e visível para os revendedores."),
          campo("Tipo de promoção","Desconto progressivo",True,"select",hint="Descontos aplicados conforme o valor total do pedido."),
          campo("Prioridade de exibição","Alta",False,"select",hint="Define a ordem de destaque da promoção na loja."),
        ], 2)
      + '<div class="split" style="--gcols:minmax(0,1fr) 280px;gap:var(--space-5);margin-top:var(--space-4)">'
      + campo("Descrição","Aproveite descontos progressivos em alianças selecionadas. Quanto maior o pedido, maior o desconto!",tipo="textarea",hint="Caracteres: 89/500")
      + '<div>' + toggle("Exibir selo na loja","Mostrar selo de destaque na vitrine da loja") + '</div></div></div>'
      + notice("Esta promoção está ativa e visível para todos os revendedores elegíveis.")
      + '<div class="row row--wrap" style="margin-top:var(--space-5)">'
      + btn("Cancelar","secondary",sm=False) + btn("Salvar alterações","primary","check",sm=False) + '</div>')}
  </div>
  <div class="stack">
    {card("Prévia da promoção",
      '<div class="promoprev"><strong>DESCONTO PROGRESSIVO</strong><span>EM ALIANÇAS SELECIONADAS</span>'
      '<em>COMPRE MAIS, ECONOMIZE MAIS!</em><div class="promoprev__tiers">'
      + "".join(f'<span><b>{p}</b><small>acima de {v}</small></span>'
          for p, v in [("5%","R$ 1.000"),("10%","R$ 2.000"),("15%","R$ 3.000")])
      + '</div></div>'
      + '<small class="fhint">Esta é uma prévia de como a promoção será exibida na loja. A aparência pode variar em diferentes dispositivos.</small>'
      + btn("Ver na loja ↗","secondary","eye","03-vitrine-pdv.html",sm=False))}
    {card("Resumo da promoção",
      "".join(linha_dado(k, v) for k, v in [
        ("Tipo","Desconto progressivo"),("Período","01/05/2026 até 31/05/2026"),
        ("Status", chip("Ativa","ok",flat=True)),("Canais","Loja online, WhatsApp, E-mail"),
        ("Público-alvo","Todos os revendedores ativos"),("Orçamento estimado",'<span class="num">R$ 0,00</span>'),
        ("Criado em","25/04/2026 às 14:30"),("Última atualização","18/05/2026 às 09:15")]))}
    {card("Ações rápidas",
      "".join(f'<a class="seclink" href="#">{ic(i)}<span><strong>{e(t)}</strong></span></a>'
        for i, t in [("clock","Histórico de alterações"),("chart","Relatório de desempenho"),("trash","Excluir promoção")]))}
  </div>
</div>'''
W("56-master-promocoes.html", M("Promoções | 56-master-promocoes.html", body))

# ══════════════════════════ 3.9 RELATÓRIOS ══════════════════════════
spark = ('<svg viewBox="0 0 600 180" class="chart" preserveAspectRatio="none">'
  '<defs><linearGradient id="ga" x1="0" y1="0" x2="0" y2="1">'
  '<stop offset="0%" stop-color="var(--action)" stop-opacity=".22"/>'
  '<stop offset="100%" stop-color="var(--action)" stop-opacity="0"/></linearGradient></defs>'
  '<path d="M0,150 L40,142 L80,120 L120,128 L160,96 L200,104 L240,70 L280,42 L320,64 L360,52 L400,74 L440,58 L480,66 L520,50 L560,58 L600,52"'
  ' fill="none" stroke="var(--action)" stroke-width="3" stroke-linejoin="round"/>'
  '<path d="M0,150 L40,142 L80,120 L120,128 L160,96 L200,104 L240,70 L280,42 L320,64 L360,52 L400,74 L440,58 L480,66 L520,50 L560,58 L600,52 L600,180 L0,180 Z" fill="url(#ga)"/>'
  '<path d="M0,168 L40,164 L80,150 L120,156 L160,138 L200,144 L240,124 L280,110 L320,128 L360,118 L400,132 L440,120 L480,126 L520,112 L560,120 L600,116"'
  ' fill="none" stroke="var(--color-gray-400)" stroke-width="2" stroke-dasharray="6 5"/></svg>')

donut = ('<div class="donutbox"><svg viewBox="0 0 120 120" class="donut">'
  '<circle cx="60" cy="60" r="46" fill="none" stroke="var(--color-success-500)" stroke-width="18" stroke-dasharray="134 155" transform="rotate(-90 60 60)"/>'
  '<circle cx="60" cy="60" r="46" fill="none" stroke="var(--color-warning-500)" stroke-width="18" stroke-dasharray="68 221" transform="rotate(77 60 60)"/>'
  '<circle cx="60" cy="60" r="18" fill="none" stroke="var(--color-info-500)" stroke-width="0"/>'
  '<circle cx="60" cy="60" r="46" fill="none" stroke="var(--color-info-500)" stroke-width="18" stroke-dasharray="19 270" transform="rotate(161 60 60)"/>'
  '<circle cx="60" cy="60" r="46" fill="none" stroke="var(--color-violet-700)" stroke-width="18" stroke-dasharray="28 261" transform="rotate(184 60 60)"/>'
  '<circle cx="60" cy="60" r="46" fill="none" stroke="var(--color-error-500)" stroke-width="18" stroke-dasharray="14 275" transform="rotate(218 60 60)"/>'
  '<circle cx="60" cy="60" r="46" fill="none" stroke="var(--color-gray-300)" stroke-width="18" stroke-dasharray="27 262" transform="rotate(235 60 60)"/>'
  '</svg><div class="donutbox__mid"><strong>248</strong><small>pedidos</small></div></div>'
  '<ul class="legend">' + "".join(
    f'<li><i style="background:{c}"></i>{t}<b>{v}</b></li>'
    for c, t, v in [("var(--color-success-500)","Concluídos","115 (46,4%)"),
                    ("var(--color-warning-500)","Em produção","58 (23,4%)"),
                    ("var(--color-info-500)","Em transporte","16 (6,5%)"),
                    ("var(--color-violet-700)","Aguardando pagamento","24 (9,7%)"),
                    ("var(--color-error-500)","Cancelados","12 (4,8%)"),
                    ("var(--color-gray-300)","Outros","23 (9,3%)")]) + '</ul>')

toprev = tabela([("Posição","cell-num"),("Revendedor",""),("Faturamento","cell-num"),("Pedidos","cell-num"),("Ticket médio","cell-num")],
  [[f'<span class="num">{i}</span>', n, f'<span class="cell-strong num">{f}</span>',
    f'<span class="num">{p}</span>', f'<span class="num">{t}</span>']
   for i, n, f, p, t in [(1,"João Ferreira Joias & Cia","R$ 78.450,00","76","R$ 1.032,24"),
                         (2,"Aliança & Cia","R$ 54.230,00","53","R$ 1.023,21"),
                         (3,"Romance Joias","R$ 36.780,00","39","R$ 943,08"),
                         (4,"Carla Lima Joias","R$ 28.560,00","29","R$ 984,83"),
                         (5,"D'Luxe Joalheria","R$ 21.760,00","23","R$ 945,22")]],
  foot='<a class="link-gold" href="#">Ver ranking completo →</a>')

topprod = tabela([("Produto",""),("Quantidade","cell-num"),("Faturamento","cell-num")],
  [[f'<div class="row" style="gap:10px">{ringimg(v, 800+i)}<span><strong style="color:var(--ink)">{n}</strong>'
    f'<br><small class="muted">Ouro 18K</small></span></div>', f'<span class="num">{q}</span>',
    f'<span class="cell-strong num">{f}</span>']
   for i, (v, n, q, f) in enumerate([("classica","Aliança Classic 4mm","156","R$ 175.560,00"),
                                     ("conforto","Aliança Classic 6mm","132","R$ 165.000,00"),
                                     ("fosca","Aliança Sole 6mm","98","R$ 98.000,00"),
                                     ("fosca","Aliança Sole 8mm","76","R$ 80.120,00"),
                                     ("diamond","Aliança Diamante 3pts","42","R$ 63.250,00")])],
  foot='<a class="link-gold" href="#">Ver todos os produtos →</a>')

body = f'''
{head("Relatórios","Acompanhe o desempenho da sua operação com dados atualizados.",
      btn("Agendar relatórios","secondary","calendar") + btn("Exportar","primary","download"))}
{filtros("Período: 01/05/2026 até 31/05/2026",
  [("Comparar com","Período anterior"),("Revendedor","Todos"),("Categoria","Todas")],
  acoes=btn("Limpar filtros","secondary","x"))}
{kpis([("coin","Faturamento bruto","R$ 245.780,00",up("18,6% vs período anterior"),"ok"),
       ("bag","Pedidos realizados","248",up("12,4% vs período anterior"),"gold"),
       ("box","Itens vendidos","612",up("15,2% vs período anterior"),"violet"),
       ("chart","Ticket médio","R$ 990,24",up("5,7% vs período anterior"),"warn"),
       ("user-plus","Novos clientes","34",up("21,4% vs período anterior"),"info")], "g5")}
<div class="split split--wide">
  <div class="stack">
    <div class="split" style="--gcols:minmax(0,1.3fr) minmax(0,1fr)">
      {card("Faturamento ao longo do tempo",
        '<div class="chartlegend"><span><i style="background:var(--action)"></i>Período atual</span>'
        '<span><i class="dash"></i>Período anterior</span></div>' + spark
        + '<div class="chartaxis"><span>01/05</span><span>05/05</span><span>10/05</span><span>15/05</span>'
          '<span>20/05</span><span>25/05</span><span>31/05</span></div>',
        acao='<span class="select-fake">Diário</span>')}
      {card("Pedidos por status", donut, acao='<span class="select-fake">Diário</span>')}
    </div>
    <div class="split" style="--gcols:1fr 1fr">
      {card("Top revendedores por faturamento", toprev)}
      {card("Top produtos por quantidade", topprod)}
    </div>
  </div>
  <div class="stack">
    {card("Resumo financeiro",
      "".join(linha_dado(k, v) for k, v in [
        ("Recebimentos confirmados",'<span class="num">R$ 238.150,00</span>'),
        ("A receber",'<span class="num">R$ 32.480,00</span>'),
        ("Inadimplência",'<span class="num" style="color:var(--color-error-700)">R$ 7.380,00</span>'),
        ("Taxa de inadimplência",'<span class="num">2,9%</span>'),
        ("Descontos concedidos",'<span class="num">R$ 6.250,00</span>')])
      + '<a class="link-gold" href="#">Ver relatório financeiro completo →</a>')}
    {card("Relatórios rápidos",
      "".join(f'<a class="seclink" href="#">{ic(i)}<span><strong>{e(t)}</strong><small>{e(d)}</small></span></a>'
        for i, t, d in [("chart","Vendas por período","Análise de vendas e faturamento."),
                        ("bag","Pedidos por status","Acompanhe os pedidos por situação."),
                        ("box","Estoque atual","Resumo do estoque por produto."),
                        ("coin","Financeiro","Recebimentos, pagamentos e inadimplência."),
                        ("tag","Top produtos","Produtos mais vendidos no período.")])
      + '<a class="link-gold" href="#">Ver todos os relatórios →</a>')}
    {card("Relatórios agendados",
      "".join(f'<div class="datarow"><span class="datarow__k">{ic("calendar")}<span>'
              f'<strong style="display:block;color:var(--ink)">{e(t)}</strong><small>{e(d)}</small></span></span>'
              f'<span class="datarow__v">{chip("Ativo","ok",flat=True)}</span></div>'
        for t, d in [("Relatório semanal de vendas","Toda segunda-feira às 08:00"),
                     ("Relatório de estoque","Todo dia 1º às 09:00"),
                     ("Relatório financeiro mensal","Todo dia 5 às 10:00")])
      + '<a class="link-gold" href="#">Gerenciar agendamentos →</a>')}
  </div>
</div>'''
W("57-master-relatorios.html", M("Relatórios | 57-master-relatorios.html", body))

# ══════════════════════════ 3.10 REVENDEDORES ══════════════════════════
REV = [("TA","Tomazelli Alianças","São José do Rio Preto / SP","André Tomazelli","Ativo","ok","Automático","Compatível","ok","31/05/2026"),
       ("AC","Aliança &amp; Cia","Joinville / SC","Carla Moreira","Ativo","ok","Automático","Compatível","ok","30/05/2026"),
       ("RJ","Romance Joias","Caxias do Sul / RS","Juliana Rigon","Pendente","warn","Manual","Em verificação","warn","30/05/2026"),
       ("DJ","D'Luxe Joalheria","Belo Horizonte / MG","Diego Lopes","Ativo","ok","Manual","Compatível","ok","29/05/2026"),
       ("BE","Brilho Eterno","Curitiba / PR","Marina Carvalho","Ativo","ok","Automático","Compatível","ok","29/05/2026")]
rows = [[f'<div class="row" style="gap:10px"><span class="avatar avatar--sm">{a}</span><strong style="color:var(--ink)">{n}</strong></div>',
         cid, resp, chip(st, stom), tc, chip(cn, cntom, flat=True), f'<span class="num">{d}</span>',
         f'<span class="row" style="gap:6px;justify-content:flex-end">{ic("eye", style="color:var(--ink-muted)")}<span class="muted">⋮</span></span>']
        for a, n, cid, resp, st, stom, tc, cn, cntom, d in REV]

cnaes = "".join(
  f'<li class="ck--ok">{ic("check")}<span><strong style="display:block;color:var(--ink)">{e(c)}</strong>'
  f'<small style="color:var(--ink-muted)">{e(d)}</small></span></li>'
  for c, d in [("4783-1/01","Comércio varejista de joias"),
               ("4783-1/02","Comércio varejista de relógios"),
               ("4789-0/01","Comércio varejista de souvenires, bijuterias e artesanatos")])

docs = "".join(
  f'<div class="docfile">{ic("doc")}<span><strong>{e(t)}</strong><small>{e(f)} · {e(s)}</small></span>'
  f'<b class="docfile__ok">{ic("check")}</b></div>'
  for t, f, s in [("Contrato social","Contrato_Social.pdf","212 KB"),
                  ("Documento do sócio","RG_Andre_Tomazelli.pdf","189 KB"),
                  ("Cartão CNPJ","Cartao_CNPJ.pdf","94 KB")])

novo = drawer("Cadastro manual de revendedor", "".join([
  form([campo("Nome fantasia","Tomazelli Alianças",True),
        campo("Razão social","Tomazelli Alianças Ltda.",True),
        campo("CNPJ","12.345.678/0001-90",True),
        campo("Responsável","André Tomazelli",True),
        campo("CPF do responsável","000.000.000-00",True),
        campo("E-mail","contato@tomazellialiancas.com.br",True),
        campo("Telefone / WhatsApp","(17) 99123-4567",True),
        campo("CEP","15090-070",True),
        campo("Endereço","Av. Alberto Andaló",True),
        campo("Número","1234",True),
        campo("Complemento","Sala 02"),
        campo("Bairro","Centro",True),
        campo("Cidade","São José do Rio Preto",True),
        campo("UF","SP",True,"select")], 2),
  f'<div class="split" style="--gcols:1fr 1fr;gap:var(--space-4)">'
  f'<div><span class="eyebrow">CNAEs informados</span><ul class="cklist" style="margin-top:8px">{cnaes}</ul></div>'
  f'<div><span class="eyebrow">Verificação por IA</span>'
  + checklist([("ok","CNPJ válido",""),("ok","Empresa ativa",""),("ok","CNAEs compatíveis","")])
  + f'<div class="airesult">{ic("sparkle")}<span><small>Resultado</small>'
    f'{chip("Compatível / Pré-aprovado","ok")}</span></div></div></div>',
  f'<div><span class="eyebrow">Documentos anexados</span><div class="grid g3" style="margin-top:8px">{docs}</div></div>',
  campo("Observações (internas)","Revendedor com histórico positivo. Cliente parceiro desde 2023.",tipo="textarea",hint="62/500"),
]), acoes=btn("Verificar CNAEs com IA","secondary","brain",sm=False)
        + btn("Salvar cadastro","secondary","check",sm=False)
        + btn("✓ Aprovar revendedor","primary","check",sm=False)
        + notice("Ao aprovar, o revendedor será ativado e poderá realizar pedidos imediatamente. A ação gera registro em <code>audit_logs</code>."))

body = f'''
{head("Revendedores","Gerencie os revendedores ativos e realize cadastros manuais com verificação de CNAEs por IA.",
      btn("+ Novo revendedor","primary",sm=False))}
{kpis([("store","Revendedores ativos","248",up("12,4% vs mês anterior"),"ok"),
       ("clock","Pendentes de aprovação","18",up("5,3% vs mês anterior"),"warn"),
       ("doc","Cadastros manuais no mês","26",up("44,8% vs mês anterior"),"violet"),
       ("brain","CNAEs verificados por IA","312",up("21,7% vs mês anterior"),"info")])}
<div class="split split--wide">
  <div class="stack">
    {filtros("Buscar revendedor…", [("Status","Todos os status")], acoes=btn("Filtros","secondary","filter"))}
    {card(None, tabela([("Revendedor",""),("Cidade / UF",""),("Responsável",""),("Status",""),
        ("Tipo de cadastro",""),("CNAE verificado",""),("Data",""),("Ações","cell-num")], rows,
      foot=pag("Mostrando 1 a 5 de 248 revendedores","1 2 3 4 5 … 50", '<span class="select-fake">5 por página</span>')))}
    {notice("O cadastro manual executa verificação por IA e permite <strong>aprovar na própria tela</strong>, sem passar pela fila de pré-cadastro (Anexo I §5.10).")}
    {notice('A função <strong>“Ver como revendedor”</strong> exige permissão própria e registra início e fim da sessão em <code>audit_logs</code> (Anexo I §2 e §7).',"info")}
  </div>
  {novo}
</div>'''
W("58-master-revendedores.html", M("Revendedores | 58-master-revendedores.html", body))

# ══════════════════════════ 3.11 SOLICITAÇÕES PRÉ-CADASTRO ══════════════════════════
SOL = [("Tomazelli Alianças","André Tomazelli","São José do Rio Preto / SP","31/05/2026","Compatível","ok","Aguardando decisão","warn",True),
       ("Aliança &amp; Cia","Carla Moreira","Joinville / SC","30/05/2026","Compatível","ok","Em análise","violet",False),
       ("Romance Joias","Juliana Rigon","Caxias do Sul / RS","29/05/2026","Incompatível","danger","Pendente","warn",False),
       ("D'Luxe Joalheria","Diego Lopes","Belo Horizonte / MG","29/05/2026","Compatível","ok","Solicitação enviada","info",False),
       ("Joias do Vale","Marcos Oliveira","Vale do Paraíba / SP","28/05/2026","Compatível","ok","Em análise","violet",False),
       ("Brilho &amp; Cia","Patrícia Mendes","Curitiba / PR","27/05/2026","Incompatível","danger","Pendente","warn",False),
       ("Essência Joias","Fernanda Costa","Fortaleza / CE","26/05/2026","Compatível","ok","Solicitação enviada","info",False),
       ("Única Alianças","Rafael Lima","Campinas / SP","25/05/2026","Compatível","ok","Aguardando decisão","warn",False)]
rows = [[f'<strong style="color:var(--ink)">{n}</strong>', r, c, f'<span class="num">{d}</span>',
         chip(ia, iatom, flat=True), chip(st, sttom, flat=True), '<span class="muted">⋯</span>']
        for n, r, c, d, ia, iatom, st, sttom, on in SOL]

cnaes = "".join(f'<li>{ic("check")}<span><strong style="display:block;color:var(--ink)">{c}</strong>'
                f'<small style="color:var(--ink-muted)">{d}</small></span></li>'
  for c, d in [("4783-1/01","Comércio varejista de artigos de joalheria"),
               ("4783-1/02","Comércio varejista de relógios"),
               ("4789-0/01","Comércio varejista de suvenires, bijuterias e artesanatos")])
docs = "".join(
  f'<div class="docfile">{ic("doc")}<span><strong>{e(t)}</strong><small>PDF · {e(s)}</small></span>'
  f'<b class="docfile__ok">{ic("check")}</b></div>'
  for t, s in [("Contrato social.pdf","245 KB"),("Documento do sócio.pdf","198 KB"),("Cartão CNPJ.pdf","142 KB")])

det = drawer("Detalhes da solicitação", "".join([
  '<div class="fgrid fgrid--2">' + "".join(campo(k, v) for k, v in [
    ("Nome fantasia","Tomazelli Alianças"),("Razão social","Tomazelli Alianças Ltda."),
    ("CNPJ","12.345.678/0001-90"),("Responsável","André Tomazelli"),
    ("E-mail","contato@tomazellialiancas.com.br"),("Telefone / WhatsApp","(17) 99123-4567"),
    ("CEP","15090-070")]) + campo("Endereço","Av. Alberto Andaló, 1234 – Centro – São José do Rio Preto / SP",largura=2) + '</div>',
  f'<div><span class="eyebrow">CNAEs informados</span><ul class="cklist" style="margin-top:8px">{cnaes}</ul></div>',
  '<div class="split" style="--gcols:1fr 1fr;gap:var(--space-4)">'
  + '<div><span class="eyebrow">Validação por IA</span>'
  + checklist([("ok","CNPJ válido",""),("ok","Empresa ativa",""),("ok","CNAEs compatíveis",""),("ok","Documentação enviada","")])
  + f'<div class="airesult">{ic("sparkle")}<span><small>Resultado</small>{chip("Compatível / Pré-aprovado","ok")}</span></div></div>'
  + f'<div><span class="eyebrow">Documentos anexados</span><div class="stack" style="margin-top:8px">{docs}</div></div></div>',
  campo("Observações internas","Histórico positivo. Solicitação recebida via site público. Perfil compatível com a operação.",tipo="textarea"),
]), acoes='<span class="eyebrow">Ações da solicitação</span>'
        + btn("✓ Aprovar cadastro","primary","check",sm=False)
        + btn("ⓘ Solicitar informações adicionais","secondary","info",sm=False)
        + btn("✗ Reprovar cadastro","danger","x",sm=False)
        + notice("Ao aprovar, o revendedor poderá acessar a plataforma e realizar pedidos. A decisão fica registrada com justificativa em <code>audit_logs</code>."))

body = f'''
{head("Solicitações pré-cadastro","Acompanhe solicitações recebidas e valide novos revendedores.",
      btn("Exportar","secondary","download") + btn("Atualizar status","primary","refresh"))}
{kpis([("user-plus","Solicitações recebidas","32","","gold"),
       ("brain","Em análise por IA","9","","violet"),
       ("clock","Aguardando decisão","14","","warn"),
       ("check","Aprovadas no mês","18","","ok"),
       ("x","Reprovadas no mês","6","","danger")], "g5")}
<div class="split split--wide">
  <div class="stack">
    {filtros("Buscar empresa ou responsável…", [("Status","Todos"),("Período","Últimos 30 dias")],
      acoes=btn("Filtros","secondary","filter"))}
    {card(None, tabela([("Empresa",""),("Responsável",""),("Cidade/UF",""),("Data",""),
        ("Resultado IA",""),("Status",""),("Ações","cell-num")], rows,
      foot=pag("Mostrando 1 a 8 de 32 solicitações","1 2 3 4")))}
    {notice("A IA funciona como <strong>triagem / pré-aprovação</strong>. A <strong>decisão final permanece humana</strong> e mantém histórico e justificativa (Anexo I §3.7 e §3.8).")}
  </div>
  {det}
</div>'''
W("59-master-precadastro.html", M("Solicitações pré-cadastro | 59-master-precadastro.html", body))

# ══════════════════════════ 3.12 SUPORTE ══════════════════════════
conversa = "".join(
  f'<div class="msg{" msg--agent" if papel=="Atendente" else ""}">'
  f'<span class="avatar avatar--sm">{av}</span>'
  f'<div class="msg__body"><div class="msg__head"><strong>{e(aut)}</strong>{chip(papel, "brand" if papel=="Revendedor" else "ok", flat=True)}'
  f'<span class="msg__when">{e(q)}</span></div><p>{txt}</p>{anexo}</div></div>'
  for av, aut, papel, q, txt, anexo in [
    ("JF","João Ferreira - Tomazelli Alianças","Revendedor","18/05/2026 às 10:24",
     "Olá, boa tarde!<br>Recebemos o pedido PED-2026-0587 da cliente Maria Cliente.<br>"
     "A aliança chegou no tamanho errado (solicitado aro 18 e veio aro 20).<br>"
     "Gostaríamos de solicitar a troca para o tamanho correto, por gentileza.<br>Aguardo retorno com as orientações.", ""),
    ("EV","Equipe Velaro Suporte","Atendente","18/05/2026 às 10:31",
     "Olá, João! Tudo bem?<br>Lamentamos o ocorrido e vamos ajudar com a troca.<br>"
     "Para darmos andamento, por favor confirme:<br>• O tamanho correto: aro 18<br>"
     "• Se a aliança foi utilizada ou não<br>• Se mantém as mesmas características (modelo, cor do ouro, gravação)<br>"
     "Também precisamos que nos envie uma foto da aliança recebida.", ""),
    ("JF","João Ferreira - Tomazelli Alianças","Revendedor","18/05/2026 às 10:38",
     "Segue foto da aliança recebida.<br>Confirmo que o tamanho correto é aro 18 e não foi utilizada.<br>"
     "Mantém o modelo, cor do ouro e gravação.",
     '<div class="docfile" style="margin-top:10px"><svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">'
     '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="10" r="1.6"/><path d="M4 17l5-4 4 3 3-2 4 3"/></svg>'
     '<span><strong>foto_alianca_recebida.jpg</strong><small>1.2 MB</small></span>'
     '<b class="docfile__ok">↓</b></div>')])

vinculos = "".join(
  f'<div class="identcell"><span><small>{e(k)}</small><strong>{v}</strong></span></div>'
  for k, v in [("Revendedor","Tomazelli Alianças<br><small class='muted'>Código: TOM-0001</small>"),
               ("Contato","João Ferreira<br><small class='muted'>(11) 98765-4321</small>"),
               ("Pedido relacionado","PED-2026-0587<br><a class='link-gold' href='54-master-pedidos.html'>Ver pedido ↗</a>"),
               ("Cliente final","Maria Cliente<br><small class='muted'>(Cliente final)</small>"),
               ("Assunto","Troca de tamanho de aliança")])

body = f'''
{head("Suporte","Gerencie suas solicitações de suporte e acompanhe o atendimento.")}
<div class="split3" style="--gcols:minmax(0,1.5fr) 320px 300px">
  <div class="stack">
    {card(None,
      '<a class="link-gold" href="#">← Voltar para todas as solicitações</a>'
      + f'<div class="spread" style="margin-top:var(--space-3)"><div>'
        f'<div class="row" style="gap:10px"><h2 class="display-sm">#SUP-2026-0598</h2>{chip("Prioridade: Média","warn")}</div>'
        f'<p class="lede" style="margin-top:6px"><strong style="color:var(--ink)">Troca de tamanho de aliança - tamanho errado</strong></p>'
        f'<small class="muted">Criado em 18/05/2026 às 10:24 · Atualizado há 35 minutos</small></div>'
      + btn("Imprimir","secondary","print") + btn("Atualizar status ⌄","primary") + '</div>'
      + f'<div class="identbar" style="margin-top:var(--space-4)">{vinculos}</div>')}
    {card("Conversa", f'<div class="thread">{conversa}</div>'
      + '<div class="replybox">' + tabs(["Responder","Observação interna"],"Responder")
      + campo("","Digite sua resposta…",tipo="textarea")
      + f'<div class="spread" style="margin-top:var(--space-3)"><span class="row" style="gap:10px;color:var(--ink-muted)">{ic("upload")}{ic("sparkle")}</span>'
      + btn("Enviar resposta ⌄","primary","mail",sm=False) + '</div></div>')}
  </div>
  <div class="stack">
    {card("Detalhes da solicitação",
      "".join(linha_dado(k, v) for k, v in [
        ("Status", chip("Em atendimento","info",flat=True)),("Prioridade", chip("Média","warn",flat=True)),
        ("Categoria","Troca / Produto"),("Assunto","Troca de tamanho de aliança"),
        ("Revendedor","Tomazelli Alianças · TOM-0001"),("Contato","João Ferreira"),
        ("Cliente final","Maria Cliente"),("Pedido relacionado","PED-2026-0587"),
        ("Data de criação","18/05/2026 às 10:24"),("Última atualização","18/05/2026 às 10:59"),
        ("Produtos","Aliança Classic 4mm (Par) · Ouro 18K - Aro 18")])
      + '<div style="margin-top:var(--space-3)"><span class="eyebrow">Tags</span>'
      + '<div class="row row--wrap" style="gap:6px;margin-top:6px">'
      + "".join(chip(t,"neutral",flat=True) for t in ["Troca","Tamanho","Aliança","Ouro 18K"]) + '</div></div>'
      + '<div style="margin-top:var(--space-3)"><span class="eyebrow">Diagnóstico técnico</span>'
      + "".join(linha_dado(k, v) for k, v in [
          ("Ambiente","Produção"),("Canal de origem","Portal do Revendedor"),
          ("Navegador","Google Chrome 124.0.0.0"),("Sistema operacional","Windows 11"),
          ("IP de acesso","189.12.34.56")]) + '</div>')}
  </div>
  <div class="stack">
    {card("Ações rápidas",
      btn("✓ Resolver solicitação","ok-outline","check",sm=False)
      + btn("ⓘ Solicitar informações adicionais","secondary","info",sm=False)
      + btn("✗ Encerrar sem solução","danger","x",sm=False))}
    {card("Histórico de status", timeline([
      ("now","Em atendimento","Equipe Velaro Suporte","Desde 18/05 10:31"),
      ("done","Aguardando resposta do revendedor","Equipe Velaro Suporte","18/05 10:31"),
      ("done","Aberta","Portal do Revendedor","18/05 10:24")]))}
    {card("Anexos",
      "".join(f'<div class="docfile">{ic("doc")}<span><strong>{e(n)}</strong><small>{e(d)}</small></span>'
              f'<b class="docfile__ok">↓</b></div>'
        for n, d in [("foto_alianca_recebida.jpg","18/05/2026 às 10:38 · 1.2 MB"),
                     ("etiqueta_pedido.jpg","18/05/2026 às 10:24 · 0.8 MB")])
      + btn("Adicionar anexo","secondary","upload",sm=False))}
    {card("Atendimento",
      linha_dado("Responsável","Equipe Velaro Suporte")
      + btn("Transferir atendimento","secondary","users",sm=False))}
    {notice("A conversa é <strong>Velaro ↔ revendedor</strong>. O cliente final aparece apenas como pessoa vinculada ao pedido e não participa do atendimento (Anexo I §5.12).")}
  </div>
</div>'''
W("60-master-suporte.html", M("Suporte | 60-master-suporte.html", body))
