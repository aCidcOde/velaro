# -*- coding: utf-8 -*-
"""Etapa 3.3 · Subtelas de Configurações do Painel Interno (51a–51h).

A tela 51-master-config.html é um MENU: oito cartões de seção que não levavam
a lugar nenhum. Este gerador escreve as oito seções de destino.

Todas mantêm o MESMO trilho de seções à esquerda (com a entrada corrente
marcada como ativa) e o painel da seção à direita, num
`.split` com `--gcols:280px minmax(0,1fr)`.

Régua de escopo: docs/telas/3-3-master-config.md — a seção 5 transcreve o
protótipo aprovado e é vinculante.
"""
import importlib.util as il, random
s = il.spec_from_file_location("u", "_ui.py"); u = il.module_from_spec(s); s.loader.exec_module(u)
g = globals(); g.update({k: getattr(u, k) for k in dir(u) if not k.startswith("__")})
W = lambda f, c: (open(f, "w", encoding="utf-8").write(religar(c, f)), print("  ✓", f))
# O item ativo da navegação lateral continua sendo "Configurações": estas oito
# telas são filhas de 51-master-config.html.
M = lambda titulo, body: page("Velaro · " + titulo, master_shell("51-master-config.html", body))

# ═════════════════════════ TRILHO DE SEÇÕES (comum às 8 telas) ═════════════════════════
SECOES = [
  ("store",    "Perfil da empresa",     "Dados da sua loja e informações gerais", "51h-master-config-empresa.html"),
  ("users",    "Usuários e permissões", "Gerencie acessos e níveis de permissão", "51a-master-config-usuarios.html"),
  ("bell",     "Notificações",          "Preferências de alertas e comunicações", "51b-master-config-notificacoes.html"),
  ("link",     "Integrações",           "Conexões com sistemas externos",         "51c-master-config-integracoes.html"),
  ("lock",     "Segurança",             "Senha, 2FA e sessões ativas",            "51d-master-config-seguranca.html"),
  ("coin",     "Financeiro",            "Configurações financeiras e fiscais",    "51e-master-config-financeiro.html"),
  ("tag",      "Personalização",        "Aparência e identidade visual",          "51f-master-config-personalizacao.html"),
  ("download", "Backup e dados",        "Exportar e gerenciar dados",             "51g-master-config-backup.html"),
]

def rail(atual):
    links = "".join(
      f'<a class="seclink{" is-on" if href == atual else ""}" href="{href}">{ic(i)}'
      f'<span><strong>{e(t)}</strong><small>{e(d)}</small></span></a>'
      for i, t, d, href in SECOES)
    # O slug da permissao nao cabe ao lado do rotulo numa coluna de 280px:
    # empilha em vez de espremer (o .datarow e flex de extremidades).
    empilha = lambda i, k, v: (
      '<div class="datarow" style="flex-direction:column;align-items:flex-start;gap:4px">'
      f'<span class="datarow__k">{ic(i)} {e(k)}</span><code>{v}</code></div>')
    acesso = card("Acesso a esta área",
      linha_dado("Perfil exigido", "Master", "shield")
      + empilha("lock", "Permissão", "velaro.settings.manage")
      + empilha("check", "Gate", "access-backend"),
      cls="card--compact")
    return ('<div class="stack">'
            '<a class="link-gold" href="51-master-config.html">← Voltar para Configurações</a>'
            f'<div class="stacklist">{links}</div>{acesso}</div>')

def tela(arquivo, titulo, sub, painel, acoes=""):
    body = f'''
{head(titulo, sub, acoes)}
<div class="split" style="--gcols:280px minmax(0,1fr)">
  {rail(arquivo)}
  <div class="stack">{painel}</div>
</div>'''
    W(arquivo, M(f"{titulo} · Configurações", body))

AUDIT = ("Toda escrita nesta tela gera registro em <code>audit_logs</code> com autor, "
         "valor anterior e valor posterior (§3.1 do escopo 3.3).")

# ══════════════════════════ 51a · USUÁRIOS E PERMISSÕES ══════════════════════════
USR = [("AV","Ana Vasques","ana.vasques@velaroaliancas.com.br","Master","brand","Hoje às 09:12","Ativo","ok"),
       ("RM","Rafael Mendes","rafael.mendes@velaroaliancas.com.br","Financeiro","info","Hoje às 08:47","Ativo","ok"),
       ("CS","Camila Souza","camila.souza@velaroaliancas.com.br","Comercial","violet","Ontem às 17:30","Ativo","ok"),
       ("DP","Diego Prado","diego.prado@velaroaliancas.com.br","Expedição","neutral","Ontem às 16:05","Ativo","ok"),
       ("EV","Equipe Velaro Suporte","suporte@velaroaliancas.com.br","Suporte","ok","Hoje às 10:59","Ativo","ok"),
       ("LT","Letícia Tavares","leticia.tavares@velaroaliancas.com.br","Comercial","violet","Nunca acessou","Convite pendente","warn"),
       ("MB","Marcos Bueno","marcos.bueno@velaroaliancas.com.br","Financeiro","info","28/02/2026","Inativo","danger")]
rows = [[f'<div class="row" style="gap:10px"><span class="avatar avatar--sm" '
         f'style="background:var(--color-gold-100);color:var(--color-gold-800)">{ini}</span>'
         f'<span><strong style="color:var(--ink)">{e(n)}</strong><br><small class="muted">{e(ml)}</small></span></div>',
         chip(pp, ptom, flat=True), f'<span class="num">{e(ac)}</span>', chip(st, stom),
         f'<span class="row" style="gap:6px;justify-content:flex-end">{ic("eye", style="color:var(--ink-muted)")}'
         f'{ic("edit", style="color:var(--ink-muted)")}<span class="muted">⋮</span></span>']
        for ini, n, ml, pp, ptom, ac, st, stom in USR]

SIM = ic("check", style="color:var(--color-success-700)")
NAO = '<span class="muted">—</span>'
LER = chip("Leitura", "neutral", flat=True)
PERM = [
  ("Dashboard", "velaro.dashboard.view",        SIM, SIM, SIM, SIM, SIM),
  ("Clientes finais", "velaro.customers.update", SIM, LER, SIM, NAO, LER),
  ("Pedidos", "velaro.orders.update_status",     SIM, LER, SIM, SIM, LER),
  ("Financeiro e lotes", "velaro.finance.reconcile", SIM, SIM, NAO, LER, NAO),
  ("Liberação de remessa", "velaro.finance.release_shipment", SIM, SIM, NAO, SIM, NAO),
  ("Estoque", "velaro.stock.adjust",             SIM, NAO, LER, SIM, NAO),
  ("Produtos", "velaro.products.manage",         SIM, NAO, SIM, LER, NAO),
  ("Promoções", "velaro.promotions.manage",      SIM, NAO, SIM, NAO, NAO),
  ("Revendedores", "velaro.resellers.approve",   SIM, NAO, SIM, NAO, LER),
  ("Ver como revendedor", "velaro.resellers.impersonate", SIM, NAO, NAO, NAO, NAO),
  ("Pré-cadastro", "velaro.prospects.approve",   SIM, NAO, SIM, NAO, NAO),
  ("Relatórios", "velaro.reports.export",        SIM, SIM, SIM, LER, LER),
  ("Suporte", "velaro.support.resolve",          SIM, NAO, LER, NAO, SIM),
  ("Configurações", "velaro.settings.manage",    SIM, NAO, NAO, NAO, NAO),
]
matriz = tabela(
  [("Módulo / permissão",""),("Master","cell-mid"),("Financeiro","cell-mid"),
   ("Comercial","cell-mid"),("Expedição","cell-mid"),("Suporte","cell-mid")],
  [[f'<strong style="color:var(--ink)">{e(m)}</strong><br><code>{p}</code>', a, b, c, d, f]
   for m, p, a, b, c, d, f in PERM])

papeis = "".join(
  f'<div class="pickitem"><div class="pickitem__top"><strong>{e(t)}</strong>'
  f'{chip(q, "neutral", flat=True)}</div><small>{e(d)}</small>'
  f'<a class="link-gold" href="#">Editar permissões →</a></div>'
  for t, q, d in [
    ("Master","2 usuários","Acesso total, inclusive Configurações e ‘Ver como revendedor’."),
    ("Financeiro","3 usuários","Lotes, baixas, notas fiscais e liberação de remessa."),
    ("Comercial","4 usuários","Revendedores, pré-cadastro, catálogo e promoções."),
    ("Expedição","3 usuários","Estoque, produção, separação e remessas."),
    ("Suporte","2 usuários","Chamados, respostas e histórico de atendimento.")])

painel = f'''
{kpis([("users","Usuários ativos","14",up("2 no mês"),"gold"),
       ("user-plus","Convites pendentes","2",flat("expira em 7 dias"),"warn"),
       ("shield","Papéis configurados","5",flat("sem alteração"),"violet"),
       ("clock","Acessos nas últimas 24h","9",up("3 vs. ontem"),"info")])}
{filtros("Buscar por nome ou e-mail…", [("Papel","Todos"),("Status","Todos")],
  acoes=btn("Filtros","secondary","filter"))}
{card(None, tabela([("Usuário",""),("Papel",""),("Último acesso",""),("Status",""),("Ações","cell-num")], rows,
  foot=pag("Mostrando 1 a 7 de 14 usuários","1 2", '<span class="select-fake">10 por página</span>')))}
<div class="split" style="--gcols:minmax(0,1fr) 320px">
  {card("Papéis", f'<div class="stacklist">{papeis}</div>'
        + btn("+ Novo papel","secondary","plus",sm=False))}
  {card("Convidar usuário",
     form([campo("Nome completo","Bruna Nogueira",True),
           campo("E-mail corporativo","bruna.nogueira@velaroaliancas.com.br",True),
           campo("Papel","Comercial",True,"select"),
           campo("Mensagem do convite","Bem-vinda ao Painel Interno da Velaro.",tipo="textarea")], 1)
     + toggle("Exigir 2FA no primeiro acesso", None, True)
     + toggle("Enviar cópia do convite para o gestor", None, False)
     + btn("Enviar convite","primary","mail",sm=False))}
</div>
{card("Matriz de permissões por papel", matriz,
  head_extra='<span class="muted" style="font-size:var(--text-xs)">✓ editar · Leitura · — sem acesso</span>')}
{notice("O acesso ao Painel Interno exige <code>is_admin</code> mais o gate "
        "<code>access-backend</code>. Um usuário sem o gate cai no Portal do Revendedor, "
        "nunca aqui (escopo 3.3 §2).")}
{notice(AUDIT, "info")}'''
tela("51a-master-config-usuarios.html", "Usuários e permissões",
     "Controle quem acessa o Painel Interno, com qual papel e até onde cada papel enxerga.",
     painel, btn("Exportar lista","secondary","download") + btn("+ Convidar usuário","primary","user-plus"))

# ══════════════════════════ 51b · NOTIFICAÇÕES ══════════════════════════
ON  = '<span class="swcell"><span class="switch is-on"></span></span>'
OFF = '<span class="swcell"><span class="switch"></span></span>'
EVT = [
  ("Novo pré-cadastro recebido","Comercial",              ON,  OFF, ON),
  ("Revendedor aprovado","Comercial + revendedor",        ON,  ON,  ON),
  ("Novo pedido recebido","Comercial + Expedição",        ON,  ON,  ON),
  ("Pagamento do lote confirmado","Financeiro",           ON,  ON,  ON),
  ("Nota fiscal emitida","Financeiro + revendedor",       ON,  OFF, ON),
  ("Pedido liberado para remessa","Expedição + revendedor", ON, ON, ON),
  ("Pedido pronto para retirada","Revendedor",            ON,  ON,  ON),
  ("Estoque abaixo do mínimo","Expedição",                ON,  OFF, ON),
  ("Lote vencido sem pagamento","Financeiro",             ON,  ON,  ON),
  ("Novo chamado de suporte","Suporte",                   ON,  OFF, ON),
  ("Chamado sem resposta há 24h","Suporte",               ON,  ON,  ON),
]
matriz_ev = tabela(
  [("Evento",""),("Quem recebe",""),("E-mail","cell-mid"),("WhatsApp","cell-mid"),("Portal","cell-mid")],
  [[f'<strong style="color:var(--ink)">{e(t)}</strong>', f'<small class="muted">{e(q)}</small>', a, b, c]
   for t, q, a, b, c in EVT])

# Numa coluna de terco de tela o e-mail do remetente nao cabe ao lado do rotulo:
# o par rotulo/valor fica empilhado.
canais = "".join(
  f'<div class="card card--compact">{toggle(t, d, on)}'
  '<div class="datarow" style="flex-direction:column;align-items:flex-start;gap:2px">'
  f'<span class="datarow__k">{ic(i)} {e(k)}</span>'
  f'<span class="datarow__v">{e(v)}</span></div></div>'
  for i, t, d, on, k, v in [
    ("mail","E-mail","Canal padrão de toda notificação.",True,"Remetente","contato@velaroaliancas.com.br"),
    ("whats","WhatsApp","Avisos curtos de pedido, lote e retirada.",True,"Número","+55 (16) 99487-7800"),
    ("bell","Portal","Sino do Painel Interno e do Portal do Revendedor.",True,"Retenção","90 dias")])

painel = f'''
{card("Canais", f'<section class="grid g3">{canais}</section>'
  + '<a class="link-gold" href="51c-master-config-integracoes.html">Configurar as conexões em Integrações →</a>')}
{card("Eventos × canais", matriz_ev,
  head_extra='<span class="muted" style="font-size:var(--text-xs)">11 eventos · 3 canais</span>',
  acao=btn("Restaurar padrão","secondary","refresh"))}
<div class="split" style="--gcols:minmax(0,1fr) minmax(0,1fr)">
  {card("Evento selecionado · Pagamento do lote confirmado",
     toggle("E-mail","Enviado ao financeiro e ao revendedor do lote.",True)
     + toggle("WhatsApp","Mensagem curta com número do lote e valor.",True)
     + toggle("Portal","Registro no sino do Painel Interno.",True)
     + form([campo("Destinatários fixos","financeiro@velaroaliancas.com.br"),
             campo("Assunto do e-mail","Lote SEM-2405 quitado — NF liberada"),
             campo("Modelo da mensagem",
                   "Recebemos o pagamento do lote SEM-2405 (R$ 15.680,00). A nota fiscal foi emitida "
                   "e os pedidos seguem para a remessa semanal.", tipo="textarea",
                   hint="Variáveis: lote, revendedor, valor, vencimento, nota fiscal")], 1)
     + btn("Enviar teste","secondary","mail") + btn("Salvar evento","primary","check"))}
  {card("Preferências gerais",
     toggle("Resumo diário por e-mail","Um consolidado às 18h com pedidos, lotes e chamados do dia.",True)
     + toggle("Agrupar avisos do mesmo pedido","Evita uma mensagem por item do pedido.",True)
     + toggle("Silenciar notificações no fim de semana","Exceto alertas críticos de pagamento.",False)
     + toggle("Copiar o financeiro em toda emissão de NF",None,True)
     + toggle("Notificar o revendedor a cada mudança de status do pedido",None,True)
     + form([campo("Janela de envio","08:00 às 20:00",tipo="select"),
             campo("Idioma das mensagens","Português (Brasil)",tipo="select")], 1))}
</div>
{notice("O canal WhatsApp usa a conta conectada em <strong>Integrações</strong>. "
        "Sem conexão ativa, o evento cai automaticamente para e-mail.")}
{notice(AUDIT, "info")}'''
tela("51b-master-config-notificacoes.html", "Notificações",
     "Escolha o que a plataforma comunica, para quem e por qual canal.",
     painel, btn("Enviar teste","secondary","mail") + btn("Salvar preferências","primary","check"))

# ══════════════════════════ 51c · INTEGRAÇÕES ══════════════════════════
INT = [
  ("box","Bling ERP","Pedidos, notas fiscais e espelho de estoque","Conectado","ok",
   "Sincronizado hoje às 10:48","2.418 registros",""),
  ("card","Asaas · cobrança B2B","Pix e boleto do lote Velaro → lojista","Conectado","ok",
   "Sincronizado hoje às 09:15","28 lotes em aberto",""),
  ("truck","Jadlog","Frete e rastreio das remessas semanais","Erro","danger",
   "Falhou ontem às 22:10","Token expirado em 02/06/2026"," introw--erro"),
  ("truck","Correios","Cálculo de frete e rastreio alternativo","Não conectado","neutral",
   "Nunca sincronizado","Contrato não informado",""),
  ("mail","E-mail transacional (SMTP)","Notificações, notas e convites de acesso","Conectado","ok",
   "Sincronizado hoje às 10:52","1.204 envios no mês",""),
  ("whats","WhatsApp Business API","Avisos de pedido, lote e retirada","Conectado","ok",
   "Sincronizado hoje às 10:31","386 mensagens no mês",""),
]
integracoes = "".join(
  f'<div class="introw{extra}"><span class="introw__ic">{ic(i)}</span>'
  f'<span><strong>{e(t)}</strong><small>{e(d)}</small></span>'
  f'<span>{chip(st, stom)}</span>'
  f'<span><small>{e(sync)}</small><small>{e(meta)}</small></span>'
  f'{btn("Configurar","secondary","gear")}</div>'
  for i, t, d, st, stom, sync, meta, extra in INT)

hist = tabela([("Data e hora",""),("Integração",""),("Evento",""),("Registros","cell-num"),("Resultado","")],
  [[f'<span class="num">{q}</span>', it, ev, f'<span class="num">{n}</span>', chip(r, rt, flat=True)]
   for q, it, ev, n, r, rt in [
     ("03/06/2026 10:52","E-mail transacional","Fila de envio processada","36","Sucesso","ok"),
     ("03/06/2026 10:48","Bling ERP","Espelho de estoque","412","Sucesso","ok"),
     ("03/06/2026 10:31","WhatsApp Business","Avisos de retirada","14","Sucesso","ok"),
     ("03/06/2026 09:15","Asaas","Baixa de cobranças do lote","9","Sucesso","ok"),
     ("02/06/2026 22:10","Jadlog","Cotação de frete","0","Falha · 401","danger"),
     ("02/06/2026 18:40","Bling ERP","Envio de pedidos aprovados","57","Sucesso","ok")]],
  foot=pag("Mostrando as 6 execuções mais recentes","1 2 3"))

painel = f'''
{kpis([("link","Conectadas","4",flat("de 6 disponíveis"),"ok"),
       ("x","Não conectadas","1",flat("Correios"),"neutral"),
       ("info","Com erro","1",down("desde 02/06"),"danger"),
       ("clock","Última sincronização","há 12 min",flat("Bling ERP"),"info")])}
{notice("<strong>Jadlog está com erro.</strong> O token expirou em 02/06/2026 e o cálculo de frete "
        "das remessas está usando a tabela manual. Reautorize para voltar ao normal.", "danger")}
{card("Conexões", f'<div class="stacklist">{integracoes}</div>',
  acao=btn("Testar todas","secondary","refresh"))}
<div class="split" style="--gcols:minmax(0,1fr) minmax(0,1fr)">
  {card("Credenciais · Bling ERP",
     form([campo("Ambiente","Produção",tipo="select"),
           campo("Client ID","velaro-b2b-homolog"),
           campo("Client Secret","••••••••••••••••••••",hint="Atualizado em 12/05/2026 por Ana Vasques"),
           campo("URL de webhook","https://velaro.sistemavendadireta.com.br/ws/bling"),
           campo("Token OAuth v3","Válido até 04/06/2026 às 10:48",hint="Renovação automática a cada 6 horas")], 1)
     + btn("Testar conexão","secondary","refresh") + btn("Salvar credenciais","primary","check"))}
  {card("Comportamento da sincronização",
     toggle("Enviar pedidos aprovados ao ERP","Somente após a quitação do lote.",True)
     + toggle("Importar espelho de estoque do ERP","A cada 30 minutos.",True)
     + toggle("Reenviar automaticamente eventos que falharem","Até 3 tentativas, com espera crescente.",True)
     + toggle("Emitir nota fiscal pelo ERP","Desligado usa o emissor próprio da Velaro.",False)
     + form([campo("Frequência da sincronização","A cada 30 minutos",tipo="select"),
             campo("Avisar em caso de falha","financeiro@velaroaliancas.com.br",tipo="select")], 1))}
</div>
{card("Histórico de sincronização", hist)}
{notice("Credencial de integração é <strong>cifrada em repouso e nunca reexibida</strong> depois de salva — "
        "o campo mostra apenas quando e por quem foi atualizada (§3.2 do escopo 3.3).")}
{notice(AUDIT, "info")}'''
tela("51c-master-config-integracoes.html", "Integrações",
     "Conexões com sistemas externos: ERP, cobrança, transportadora e e-mail transacional.",
     painel, btn("Ver registro de auditoria","secondary","doc") + btn("+ Nova integração","primary","plus"))

# ══════════════════════════ 51d · SEGURANÇA ══════════════════════════
def qrsvg(n=25, seed=11, cell=6):
    rnd = random.Random(seed)
    size = n * cell
    reservado = set()
    for fx, fy in [(0, 0), (n - 7, 0), (0, n - 7)]:
        for dx in range(-1, 8):
            for dy in range(-1, 8):
                reservado.add((fx + dx, fy + dy))
    corpo = "".join(
      f'<rect x="{x*cell}" y="{y*cell}" width="{cell}" height="{cell}"/>'
      for y in range(n) for x in range(n)
      if (x, y) not in reservado and rnd.random() < 0.46)
    def alvo(x, y):
        return (f'<rect x="{x*cell}" y="{y*cell}" width="{7*cell}" height="{7*cell}" fill="#0c1817"/>'
                f'<rect x="{(x+1)*cell}" y="{(y+1)*cell}" width="{5*cell}" height="{5*cell}" fill="#fff"/>'
                f'<rect x="{(x+2)*cell}" y="{(y+2)*cell}" width="{3*cell}" height="{3*cell}" fill="#0c1817"/>')
    return (f'<svg viewBox="0 0 {size} {size}" role="img" '
            f'aria-label="QR code de exemplo para o aplicativo autenticador">'
            f'<rect width="{size}" height="{size}" fill="#fff"/>'
            f'<g fill="#0c1817">{corpo}</g>{alvo(0,0)}{alvo(n-7,0)}{alvo(0,n-7)}</svg>')

codigos = '<div class="codes">' + "".join(
  f'<code>{c}</code>' for c in ["9F2K-4M7Q","B3XD-8T1P","H6VN-2R9L","K4ZQ-7W3C",
                                "M8YT-5J2D","P1RB-9N6X","T7GK-3V4H","W5QM-1L8S"]) + '</div>'

SES = [("Chrome 124 · Windows 11","São Paulo / SP · 189.12.34.56","Agora","Sessão atual","ok",False),
       ("Safari 17 · iPhone 15","São Paulo / SP · 189.12.34.56","Hoje às 08:12","Ativa","info",True),
       ("Chrome 124 · macOS 14","São J. do Rio Preto / SP · 177.45.9.12","Ontem às 19:40","Ativa","info",True),
       ("Edge 124 · Windows 10","Curitiba / PR · 200.98.7.31","30/05/2026 às 14:02","Expirada","neutral",False),
       ("App Velaro · Android 14","Joinville / SC · 191.33.2.88","28/05/2026 às 09:55","Encerrada","neutral",False)]
sessoes = tabela([("Dispositivo",""),("Local e IP",""),("Último acesso",""),("Situação",""),("Ação","cell-num")],
  [[f'<strong style="color:var(--ink)">{e(dv)}</strong>', f'<small class="muted">{e(lo)}</small>',
    f'<span class="num">{e(ua)}</span>', chip(st, stom),
    ('<a class="link-gold" href="#">Encerrar</a>' if enc else '<span class="muted">—</span>')]
   for dv, lo, ua, st, stom, enc in SES])

painel = f'''
<div class="split" style="--gcols:minmax(0,1fr) minmax(0,1fr)">
  {card("Alterar senha",
     form([campo("Senha atual","••••••••••••",True),
           campo("Nova senha","••••••••••••••",True),
           campo("Confirmar nova senha","••••••••••••••",True)], 1)
     + linha_dado("Última troca de senha","12/03/2026 · por Ana Vasques","clock")
     + '<span class="eyebrow">Requisitos da nova senha</span>'
     + checklist([("ok","Mínimo de 12 caracteres",""),("ok","Letras maiúsculas e minúsculas",""),
                  ("ok","Pelo menos um número",""),("wait","Pelo menos um símbolo (! @ # $)","")])
     + btn("Atualizar senha","primary","check",sm=False))}
  {card("Verificação em duas etapas (2FA)",
     linha_dado("Método","Aplicativo autenticador (TOTP)","lock")
     + linha_dado("Ativado em","14/03/2026","calendar")
     + f'<div class="qrbox">{qrsvg()}'
       '<small class="muted">Escaneie no aplicativo autenticador</small>'
       '<code>JBSW Y3DP EHPK 3PXP</code></div>'
     + '<span class="eyebrow">Códigos de recuperação</span>' + codigos
     + '<small class="fhint">Cada código serve uma única vez. Guarde fora do navegador.</small>'
     + '<div class="row row--wrap">' + btn("Gerar novos códigos","secondary","refresh")
     + btn("Desativar 2FA","danger","x") + '</div>',
     head_extra=chip("Ativo","ok"))}
</div>
{card("Sessões ativas", sessoes
  + '<div class="row row--wrap" style="margin-top:var(--space-3)">'
  + btn("Encerrar todas as outras sessões","danger","x") + '</div>',
  head_extra='<span class="muted" style="font-size:var(--text-xs)">5 dispositivos nos últimos 30 dias</span>')}
{card("Políticas de segurança da conta",
  toggle("Exigir 2FA para todos os usuários com acesso ao Painel Interno",None,True)
  + toggle("Encerrar a sessão após 30 minutos de inatividade",None,True)
  + toggle("Bloquear a conta após 5 tentativas de senha incorretas",None,True)
  + toggle("Avisar por e-mail quando houver acesso de um novo dispositivo",None,True)
  + toggle("Restringir o acesso a uma faixa de IP","Somente a rede da fábrica e a VPN.",False)
  + form([campo("Expiração de senha","A cada 180 dias",tipo="select"),
          campo("Reutilização de senha","Bloquear as 5 últimas",tipo="select"),
          campo("Duração máxima da sessão","12 horas",tipo="select")], 3))}
{notice("Troca de senha, ativação e desativação de 2FA e encerramento de sessão geram registro em "
        "<code>audit_logs</code> com data, autor e IP de origem.")}'''
tela("51d-master-config-seguranca.html", "Segurança",
     "Senha, verificação em duas etapas e sessões ativas da sua conta.",
     painel, btn("Ver registro de auditoria","secondary","doc"))

# ══════════════════════════ 51e · FINANCEIRO E FISCAL ══════════════════════════
series = tabela([("Série",""),("Modelo",""),("Finalidade",""),("Ambiente",""),
                 ("Próximo número","cell-num"),("Última emissão",""),("Status","")],
  [[f'<strong style="color:var(--ink)">{s}</strong>', md, fi, chip(am, amt, flat=True),
    f'<span class="num">{pn}</span>', f'<span class="num">{ue}</span>', chip(st, stt)]
   for s, md, fi, am, amt, pn, ue, st, stt in [
     ("Série 1","NF-e modelo 55","Venda ao revendedor","Produção","ok","000.024.588","27/05/2026","Ativa","ok"),
     ("Série 2","NF-e modelo 55","Devolução e troca","Produção","ok","000.000.142","18/05/2026","Ativa","ok"),
     ("Série 1","NFS-e","Serviço de gravação","Produção","ok","000.001.204","20/05/2026","Ativa","ok"),
     ("Série 9","NF-e modelo 55","Testes e homologação","Homologação","warn","000.000.087","02/06/2026","Inativa","neutral")]])

formas = tabela([("Forma de pagamento",""),("Prazo",""),("Limite por lote","cell-num"),("Status",""),("Ações","cell-num")],
  [[f'<strong style="color:var(--ink)">{f}</strong>', pz, f'<span class="num">{lm}</span>',
    chip(st, stt), '<span class="muted">⋯</span>']
   for f, pz, lm, st, stt in [
     ("PIX","À vista","Sem limite","Ativo","ok"),
     ("Boleto bancário","14 dias","R$ 40.000,00","Ativo","ok"),
     ("Boleto 28/56 dias","2 parcelas","R$ 60.000,00","Ativo","ok"),
     ("Transferência bancária","À vista","Sem limite","Ativo","ok"),
     ("Cartão de crédito","—","—","Inativo","danger")]])

painel = f'''
{card("Dados fiscais da empresa",
  form([campo("Razão social","Velaro Alianças Ltda",True),
        campo("CNPJ","12.345.678/0001-90",True),
        campo("Inscrição estadual","123.456.789.112",True),
        campo("Inscrição municipal","1.234.567-8"),
        campo("Regime tributário","Simples Nacional",True,"select"),
        campo("CNAE principal","3211-6/02 · Fabricação de artefatos de joalheria",True,"select"),
        campo("Município de emissão","São Paulo / SP",True,"select"),
        campo("Código IBGE do município","3550308",True),
        campo("CFOP padrão de venda","6.101 · Venda de produção do estabelecimento",True,"select")], 3)
  + '<h3 class="fsec">Certificado digital</h3>'
  + f'<div class="docfile">{ic("doc")}<span><strong>velaro_certificado_A1.pfx</strong>'
    '<small>Válido até 12/12/2026 · instalado em 12/12/2025</small></span>'
    f'<b class="docfile__ok">{ic("check")}</b></div>'
  + notice("Faltam <strong>192 dias</strong> para o vencimento do certificado. "
           "O aviso de renovação dispara aos 30 dias.", "info"),
  acao=btn("Editar informações","secondary","edit"))}
{card("Séries de nota fiscal", series,
  acao=btn("+ Nova série","secondary","plus"),
  head_extra='<span class="muted" style="font-size:var(--text-xs)">4 séries · 1 em homologação</span>')}
{card("Condições de pagamento aceitas", formas
  + toggle("Exigir a quitação do lote antes de liberar a produção",
           "Regra do modelo B2B: nenhuma remessa sai sem o lote quitado.", True)
  + toggle("Permitir pagamento parcial do lote","Libera apenas os pedidos cobertos pelo valor pago.", False)
  + toggle("Aplicar desconto por antecipação",None, True)
  + toggle("Aceitar pagamento do consumidor final",
           "Desligado por definição: o consumidor paga direto ao lojista.", False),
  acao=btn("Gerenciar formas de pagamento","secondary","gear"))}
{card("Regras de faturamento do lote",
  form([campo("Data de corte do lote","Toda segunda-feira",tipo="select",hint="Parâmetro de lote — Anexo I §6"),
        campo("Vencimento do lote","7 dias após o corte",tipo="select"),
        campo("Valor mínimo do pedido","R$ 1.500,00"),
        campo("Juros por atraso","1% ao mês"),
        campo("Multa por atraso","2% sobre o valor do lote"),
        campo("Desconto por antecipação","2% até 3 dias antes do vencimento"),
        campo("Numeração dos pedidos","Sequencial por ano",tipo="select"),
        campo("Bloqueio por inadimplência","Após 15 dias em atraso",tipo="select"),
        campo("Conta bancária de recebimento","Itaú · Ag. 0123 · C/C 45678-9",tipo="select")], 3))}
{notice("O fluxo é <strong>recebimento identificado → baixa financeira → NF emitida → pedidos aprovados → "
        "liberação da remessa</strong>. Nenhuma remessa sai sem a quitação confirmada do lote (Anexo I §5.5).")}
{notice(AUDIT, "info")}'''
tela("51e-master-config-financeiro.html", "Financeiro e fiscal",
     "Dados fiscais, séries de nota, condições de pagamento aceitas e regras de faturamento do lote.",
     painel, btn("Descartar alterações","secondary") + btn("Salvar alterações","primary","check"))

# ══════════════════════════ 51f · PERSONALIZAÇÃO ══════════════════════════
cores = "".join(
  f'<div class="field"><label>{e(l)}</label><span class="colorbox"><i style="background:{c}"></i>{c}</span>'
  f'<small class="fhint">{e(uso)}</small></div>'
  for l, c, uso in [("Esmeralda (marca)","#012227","Barra lateral, rodapé e faixas."),
                    ("Dourado (ação)","#A97C3C","Botões, links e destaques."),
                    ("Superfície","#FAF9F7","Fundo das telas de trabalho."),
                    ("Texto","#14211F","Títulos e corpo de texto.")])

tamanhos = '<div class="iconsizes">' + "".join(
  f'<span><i style="width:{s}px;height:{s}px">{logo(int(s*0.72))}</i>{s}px</span>'
  for s in [16, 32, 64, 96]) + '</div>'

destaques = "".join(
  f'<div class="prevprod">{ringimg(v, 640+i, "thumb")}<strong>{e(n)}</strong><small>Ouro 18k</small>'
  f'<span class="prevprod__price num">{p}</span></div>'
  for i, (v, n, p) in enumerate([
    ("classica","Aliança Clássica","R$ 1.800,00"),("fosca","Aliança Tradicional","R$ 1.950,00"),
    ("conforto","Aliança Anatômica","R$ 2.250,00"),("trabalhada","Aliança com Friso","R$ 1.850,00"),
    ("diamantada","Aliança Diamantada","R$ 2.450,00"),("tricolor","Aliança Dupla","R$ 2.650,00")]))

painel = f'''
<div class="split" style="--gcols:minmax(0,1fr) minmax(0,1fr)">
  {card("Logo principal",
     f'<div class="logobox">{logo(44)}<strong>VELARO</strong><small>ALIANÇAS</small>'
     f'<span class="logobox__up">{ic("upload")} Enviar nova logo</span>'
     '<small>SVG ou PNG com fundo transparente · mínimo 480×160px · máx. 2MB</small></div>'
     + linha_dado("Arquivo atual","velaro-logo.svg · 14 KB","doc")
     + linha_dado("Atualizado em","28/04/2026 · por Ana Vasques","clock")
     + '<div class="row row--wrap">' + btn("Substituir","secondary","upload")
     + btn("Remover","danger","trash") + '</div>')}
  {card("Ícone quadrado",
     f'<div class="logobox">{logo(44)}<strong>V</strong><small>ÍCONE</small>'
     f'<span class="logobox__up">{ic("upload")} Enviar novo ícone</span>'
     '<small>PNG quadrado 512×512px · usado no favicon, no app e no e-mail</small></div>'
     + '<span class="eyebrow">Como aparece</span>' + tamanhos
     + linha_dado("Arquivo atual","velaro-icone.png · 22 KB","doc"))}
</div>
{card("Paleta da marca", f'<div class="fgrid fgrid--4">{cores}</div>'
  + '<h3 class="fsec">Tipografia</h3>'
  + form([campo("Fonte dos títulos","Jost",tipo="select"),
          campo("Fonte de texto","Inter Tight",tipo="select"),
          campo("Escala tipográfica","Padrão Velaro",tipo="select")], 3)
  + toggle("Aplicar a paleta aos e-mails transacionais",None,True)
  + toggle("Aplicar a paleta aos documentos em PDF (nota, romaneio)",None,True))}
{card("Vitrine do revendedor",
  '<div class="split" style="--gcols:minmax(0,1fr) 320px">'
  + '<div>'
    + notice("A vitrine que o consumidor final enxerga é da <strong>marca do lojista</strong>. "
             "A Velaro define aqui apenas o gabarito: o que o revendedor pode trocar e o que fica fixo.")
    + toggle("Permitir que o revendedor troque as cores da vitrine",
             "O revendedor edita em Portal → Personalização da loja.", True)
    + toggle("Permitir que o revendedor envie a própria logo",None, True)
    + toggle("Permitir banner próprio do revendedor",None, True)
    + toggle("Exibir o selo “Alianças Velaro” na vitrine",
             "Desligado por definição do modelo B2B.", False)
    + toggle("Travar o layout da grade de produtos","O revendedor escolhe as peças, não o gabarito.", True)
    + form([campo("Gabarito padrão da vitrine","Velaro · Clássico",tipo="select"),
            campo("Produtos por linha","3",tipo="select")], 2)
  + '</div>'
  + '<div class="storeprev">'
    f'<div class="storeprev__bar"><strong>TOMAZELLI</strong><span>{ic("search")} {ic("cart")}</span></div>'
    '<div class="storeprev__tabs"><b>Todos os produtos</b><span>Alianças</span><span>Solitários</span></div>'
    '<div class="storeprev__banner"><strong>SÍMBOLO DE AMOR.</strong>'
    '<span>PROMESSA PARA A VIDA TODA.</span><em>Conheça nossas alianças</em></div>'
    f'<div class="storeprev__grid"><span class="eyebrow" style="grid-column:1/-1">Destaques</span>{destaques}</div>'
  '</div></div>',
  acao=btn("Abrir a vitrine de exemplo","secondary","eye","03-vitrine-pdv.html"))}
{notice(AUDIT, "info")}'''
tela("51f-master-config-personalizacao.html", "Personalização",
     "Aparência e identidade visual da plataforma — e o gabarito da vitrine que o revendedor publica.",
     painel, btn("Pré-visualizar","secondary","eye","03-vitrine-pdv.html") + btn("Salvar alterações","primary","check"))

# ══════════════════════════ 51g · BACKUP E DADOS ══════════════════════════
EXP = [
  ("book","Catálogo","SKUs, coleções, materiais, tamanhos, preço de fábrica e preço sugerido.",
   "CSV (.csv)","Situação atual","1.842 linhas"),
  ("bag","Pedidos","Pedidos, itens, status, lote vinculado e cliente final identificado.",
   "XLSX (.xlsx)","Últimos 12 meses","12.408 linhas"),
  ("store","Revendedores","Cadastro, CNPJ, CNAEs, endereço, plano e situação.",
   "CSV (.csv)","Todos os registros","248 linhas"),
  ("coin","Financeiro","Lotes, recebimentos, baixas, notas fiscais e inadimplência.",
   "XLSX (.xlsx)","Ano corrente","3.126 linhas"),
]
exportacoes = "".join(
  card(t, f'<p class="lede" style="font-size:var(--text-sm)">{e(d)}</p>'
       + form([campo("Formato", fm, tipo="select"), campo("Período", pr, tipo="select")], 2)
       + linha_dado("Volume estimado", e(vol), "list")
       + btn("Exportar","secondary","download",sm=False),
       head_extra=f'<span class="kpi__icon kpi__icon--gold">{ic(i)}</span>')
  for i, t, d, fm, pr, vol in EXP)

historico = tabela([("Arquivo",""),("Conjunto",""),("Período",""),("Formato",""),("Tamanho","cell-num"),
                    ("Solicitado por",""),("Data",""),("Status",""),("Ação","cell-num")],
  [[f'<code>{ar}</code>', cj, pr, fm, f'<span class="num">{tm}</span>', so,
    f'<span class="num">{dt}</span>', chip(st, stt),
    ('<a class="link-gold" href="#">Baixar</a>' if st == "Concluído" else '<span class="muted">—</span>')]
   for ar, cj, pr, fm, tm, so, dt, st, stt in [
     ("pedidos_2026-06-03.xlsx","Pedidos","Mai/2026","XLSX","4,2 MB","Rafael Mendes","03/06/2026 09:40","Concluído","ok"),
     ("financeiro_2026-06-01.xlsx","Financeiro","Ano corrente","XLSX","2,8 MB","Rafael Mendes","01/06/2026 08:15","Concluído","ok"),
     ("revendedores_2026-05-28.csv","Revendedores","Todos","CSV","186 KB","Camila Souza","28/05/2026 16:22","Concluído","ok"),
     ("catalogo_2026-05-20.csv","Catálogo","Situação atual","CSV","742 KB","Camila Souza","20/05/2026 11:05","Expirado","neutral"),
     ("auditoria_2026-06-03.csv","Registro de auditoria","Últimos 90 dias","CSV","—","Ana Vasques","03/06/2026 10:58","Processando","warn")]],
  foot=pag("Mostrando 1 a 5 de 18 exportações","1 2 3 4", '<span class="select-fake">5 por página</span>'))

painel = f'''
{kpis([("download","Exportações no mês","18",up("4 vs. maio"),"gold"),
       ("clock","Em processamento","1",flat("registro de auditoria"),"warn"),
       ("check","Último backup","Hoje às 03:12",flat("1,4 GB"),"ok"),
       ("calendar","Próximo backup","04/06 às 03:00",flat("automático"),"info")])}
{card("Exportações disponíveis", f'<section class="grid g2">{exportacoes}</section>'
  + notice("A exportação roda em segundo plano. Quando terminar, o arquivo aparece no histórico abaixo "
           "e o link de download expira em 7 dias."))}
{card("Histórico de exportações", historico, acao=btn("Atualizar","secondary","refresh"))}
<div class="split" style="--gcols:minmax(0,1fr) minmax(0,1fr)">
  {card("Backup automático",
     linha_dado("Último backup concluído","03/06/2026 às 03:12 · 1,4 GB","check")
     + linha_dado("Próxima execução","04/06/2026 às 03:00","calendar")
     + linha_dado("Destino","Armazenamento externo cifrado","lock")
     + toggle("Backup completo diário","Todo dia às 03:00, fora do horário de operação.",True)
     + toggle("Backup mensal de longo prazo","No dia 1º, guardado por 12 meses.",True)
     + toggle("Avisar por e-mail em caso de falha",None,True)
     + btn("Executar backup agora","secondary","refresh",sm=False))}
  {card("Política de retenção",
     form([campo("Backups diários","Manter por 30 dias",tipo="select"),
           campo("Backups mensais","Manter por 12 meses",tipo="select"),
           campo("Arquivos de exportação","Expirar em 7 dias",tipo="select"),
           campo("Registro de auditoria","Manter por 5 anos",tipo="select",
                 hint="Prazo mínimo do Anexo I para trilha de auditoria"),
           campo("Chamados de suporte encerrados","Manter por 24 meses",tipo="select")], 1)
     + toggle("Anonimizar dados pessoais do consumidor final nas exportações",
              "Exigência de LGPD: o consumidor final não tem conta na plataforma.", True)
     + toggle("Exigir 2FA para exportar dados financeiros",None, True))}
</div>
{notice("Toda exportação registra em <code>audit_logs</code> quem pediu, qual conjunto, qual período "
        "e quantas linhas saíram. O arquivo nunca é enviado por e-mail — só pelo link do histórico.")}'''
tela("51g-master-config-backup.html", "Backup e dados",
     "Exporte os dados da operação, acompanhe as exportações e defina a retenção.",
     painel, btn("Executar backup agora","secondary","refresh") + btn("Nova exportação","primary","download"))

# ══════════════════════════ 51h · PERFIL DA EMPRESA ══════════════════════════
painel = f'''
<div class="split" style="--gcols:minmax(0,1fr) 240px">
  {card("Dados cadastrais",
     form([campo("Nome fantasia","Velaro Alianças",True),
           campo("Razão social","Velaro Alianças Ltda",True),
           campo("CNPJ","12.345.678/0001-90",True),
           campo("Inscrição estadual","123.456.789.112",True),
           campo("Inscrição municipal","1.234.567-8"),
           campo("Data de abertura","14/03/2011"),
           campo("CNAE principal","3211-6/02 · Fabricação de artefatos de joalheria",True,"select"),
           campo("Regime tributário","Simples Nacional",True,"select"),
           campo("Porte","Empresa de pequeno porte",tipo="select")], 2)
     + notice('Alterar CNPJ, razão social ou inscrições muda a emissão de nota fiscal. '
              'Confira também em <a class="link-gold" href="51e-master-config-financeiro.html">'
              'Financeiro e fiscal</a>.', "info"),
     acao=btn("Editar informações","secondary","edit"))}
  {card("Logo da empresa",
     f'<div class="logobox">{logo(44)}<strong>VELARO</strong><small>ALIANÇAS</small>'
     f'<span class="logobox__up">{ic("edit")} Alterar logo</span></div>'
     + '<small class="fhint">Aparece no Painel Interno, no Portal do Revendedor, '
       'nas notas fiscais e nos e-mails.</small>'
     + '<a class="link-gold" href="51f-master-config-personalizacao.html">Ir para Personalização →</a>')}
</div>
{card("Endereço",
  form([campo("CEP","01000-000",True),
        campo("Logradouro","Rua das Alianças",True),
        campo("Número","100",True),
        campo("Complemento","Galpão A"),
        campo("Bairro","Centro",True),
        campo("Cidade","São Paulo",True),
        campo("UF","SP",True,"select"),
        campo("País","Brasil",True,"select"),
        campo("Ponto de referência","Em frente à praça central")], 3)
  + toggle("Usar como endereço de faturamento",None,True)
  + toggle("Usar como endereço de coleta das remessas",
           "É daqui que a transportadora retira os lotes semanais.",True)
  + toggle("Exibir o endereço no site público",None,True))}
{card("Contatos",
  form([campo("E-mail comercial","contato@velaroaliancas.com.br",True),
        campo("E-mail financeiro","financeiro@velaroaliancas.com.br",True),
        campo("E-mail de suporte","suporte@velaroaliancas.com.br",True),
        campo("Telefone","(11) 98765-4321",True),
        campo("WhatsApp comercial","(16) 99487-7800",True),
        campo("Site","velaroaliancas.com.br")], 3)
  + form([campo("Horário de atendimento","Segunda a sexta, das 8h às 18h")], 1)
  + toggle("Exibir o WhatsApp comercial no site público",None,True))}
{card("Responsável",
  form([campo("Nome do responsável","Ana Vasques",True),
        campo("Cargo","Diretora administrativa",True),
        campo("CPF","000.000.000-00",True),
        campo("E-mail","ana.vasques@velaroaliancas.com.br",True),
        campo("Telefone","(11) 98765-4321",True)], 3)
  + notice("O responsável assina os contratos com os revendedores e consta na nota fiscal. "
           "Trocar o responsável não transfere o acesso — isso se faz em "
           '<a class="link-gold" href="51a-master-config-usuarios.html">Usuários e permissões</a>.'))}
{notice("Este ambiente é de <strong>homologação</strong> e os dados fiscais acima são fictícios. "
        "O CNPJ real só entra quando a plataforma sair de homologação.", "danger")}
{notice(AUDIT, "info")}'''
tela("51h-master-config-empresa.html", "Perfil da empresa",
     "Dados cadastrais, endereço, contatos e responsável legal da Velaro Alianças.",
     painel, btn("Descartar alterações","secondary") + btn("Salvar informações","primary","check"))

print("  ok · 8 subtelas de Configurações (51a–51h)")
