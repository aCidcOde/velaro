# -*- coding: utf-8 -*-
"""Telas que fecham os fluxos abertos do site público, do login e da vitrine.

  16-site-produto.html              ficha pública do produto — GET /produto/{slug}, SEM preço
  17-site-privacidade.html          Política de Privacidade (LGPD)
  18-site-termos.html               Termos de Uso B2B
  19-site-contato.html              tela 1.8 Fale conosco — GET /contato · POST /contato
  21-login-senha.html               recuperação de senha, nos dois estados
  07-vitrine-produto.html           ficha do produto na vitrine WHITE LABEL, COM preço B2C
  08-vitrine-pedido-confirmado.html pedido registrado no balcão — fecha o fluxo do PDV

Escopo vinculante: docs/telas/1-3 (bloqueio de preço), 1-4 (aceites com versão),
0-login, 2-9 (zero marca Velaro na vitrine) e 2-10 (carrinho / gravação / retirada).

Rodar com:  cd docs/mockups && python3 _build_novas_site.py
"""
import importlib.util as il
s = il.spec_from_file_location("u", "_ui.py"); u = il.module_from_spec(s); s.loader.exec_module(u)
g = globals(); g.update({k: getattr(u, k) for k in dir(u) if not k.startswith("__")})
W = lambda f, c: (open(f, "w", encoding="utf-8").write(religar(c, f)), print("  ✓", f))

# ═════════════════════════════════════════════════════════════════════════════
# 1.3b · DETALHE DO PRODUTO NO SITE PÚBLICO  ·  GET /produto/{slug}
# Regra crítica do escopo 1.3: products.price NUNCA é serializado nesta rota.
# No lugar do preço entra o convite ao cadastro de lojista.
# ═════════════════════════════════════════════════════════════════════════════
def optset(rotulo, opcoes, nota=None):
    chips = "".join(f'<span class="optchip{" is-on" if on else ""}">{e(t)}</span>' for t, on in opcoes)
    nt = f'<small class="fhint">{e(nota)}</small>' if nota else ""
    return f'<div class="optset"><span class="optset__lab">{e(rotulo)}</span><div class="optrow">{chips}</div>{nt}</div>'

gal = ('<div class="pdpgal">'
  f'<div class="pdpgal__main">{rings.svg("diamond", 950)}</div>'
  '<div class="pdpthumbs">' + "".join(
    f'<span class="pdpthumb{" is-on" if i == 0 else ""}" title="{e(t)}">{rings.thumb(v, 960 + i)}</span>'
    for i, (v, t) in enumerate([("diamond", "Diamantada"), ("branco", "Polida"), ("cravejada", "Cravejada"),
                                ("fosca", "Fosca"), ("classica", "Ouro amarelo 18k")])) + '</div>'
  '<small class="fhint">Imagens ilustrativas do feitio. O acabamento final é confirmado no pedido do lojista.</small>'
  '</div>')

opcoes = (
  optset("Larguras disponíveis", [("3mm", 0), ("4mm", 0), ("5mm", 1), ("6mm", 0)])
  + optset("Feitios", [("Reta", 1), ("Anatômica", 0), ("Conforto", 0), ("Quadrada", 0)])
  + optset("Metais", [("Prata 950", 1), ("Ouro Amarelo 18k", 0), ("Ouro Rosé 18k", 0), ("Aço", 0)])
  + optset("Acabamentos", [("Diamantada", 1), ("Polida", 0), ("Fosca", 0), ("Texturizada", 0), ("Cravejada", 0)],
           "Combinações fora da grade são produzidas sob encomenda para lojistas aprovados."))

ficha = "".join(u.linha_dado(k, e(v), i) for i, k, v in [
  ("tag", "Referência", "VL-DM-01"),
  ("diamond", "Coleção", "Diamond"),
  ("book", "Categoria", "Alianças"),
  ("ring", "Aros disponíveis", "8 ao 34 — inclusive meios-aros"),
  ("edit", "Gravação", "Interna · até 20 caracteres + data"),
  ("box", "Peso aproximado", "4,2 g por peça (5mm, aro 18)"),
  ("clock", "Prazo de produção", "Até 7 dias úteis"),
  ("shield", "Garantia", "12 meses contra defeito de fabricação"),
  ("factory", "Origem", "Fabricação própria Velaro"),
])

pricelock = f'''<div class="pricelock">
  <span class="pricelock__tag">{ic("lock")} Preço exclusivo para lojistas</span>
  <h2 class="display-sm">Condição comercial liberada após a aprovação do cadastro.</h2>
  <p>A Velaro é fábrica e vende <strong>somente para lojistas com CNPJ</strong>. Preço de fábrica, desconto por
     volume e prazo de pagamento aparecem no Portal do Lojista assim que seu cadastro for aprovado.</p>
  {btn("Quero ser revendedor", "gold", "store", "12-site-cadastro.html", sm=False)}
  {btn("Ver condições comerciais", "ghost-gold", "doc", "#condicoes", sm=False)}
  <small class="pricelock__note">O consumidor final não compra na Velaro: ele compra na loja do revendedor.</small>
</div>'''

condicoes = "".join(
  f'<div class="row" style="gap:var(--space-4);align-items:flex-start">'
  f'{ic(i, style="width:30px;height:30px;color:var(--color-gold-400);flex:none")}'
  f'<div><strong style="display:block;color:#fff;font-size:15px">{e(t)}</strong>'
  f'<p style="margin:4px 0 0;font-size:var(--text-sm);line-height:20px;color:rgba(255,255,255,.62)">{e(d)}</p></div></div>'
  for i, t, d in [
    ("box", "Pedido mínimo", "A partir de 10 peças por pedido, sem exigência de mix de modelos."),
    ("clock", "Produção sob demanda", "Até 7 dias úteis para itens de catálogo; 12 dias úteis com gravação ou aro fora da grade."),
    ("card", "Pagamento B2B", "Pix, boleto ou transferência — sempre Velaro → lojista. A plataforma não processa pagamento do consumidor final."),
    ("truck", "Entrega e retirada", "Envio para todo o Brasil ou retirada na fábrica, com rastreio dentro do Portal.")])

relacionados = "".join(
  u.prodcard(v, 970 + i, sku, nome, f"{e(m)}<br>{e(l)}", "",
             chip_html='<div class="row" style="gap:5px">' + chip("Preço após cadastro", "neutral", flat=True) + '</div>',
             acoes=btn("Ver detalhes", "secondary", href="16-site-produto.html"))
  for i, (sku, nome, m, l, v) in enumerate([
    ("VL-DM-09", "Diamond Heart", "Prata 950 | Diamantada", "5mm | Acabamento polido", "diamond"),
    ("VL-DM-12", "Diamond Lux", "Prata 950 | Cravejada", "4mm | Acabamento polido", "cravejada"),
    ("VL-CL-03", "Clássica", "Ouro Amarelo 18k | Polida", "4mm | Acabamento polido", "classica"),
    ("VL-PR-02", "Premium Rosé", "Ouro Rosé 18k | Fosca", "4mm | Acabamento polido", "rose")]))

hero = site_hero("Diamond", eyebrow="Catálogo › Alianças › Diamond",
  sub="Ref. VL-DM-01 · Prata 950 · Diamantada · 5mm",
  texto=("Aliança de perfil reto em prata 950 com superfície diamantada, brilho uniforme e acabamento polido "
         "nas bordas. Produzida na nossa fábrica, com controle de peso e de aro peça a peça."),
  ctas=('<div class="hero__ctas">'
        + btn("Quero ser revendedor", "gold", "store", "12-site-cadastro.html", sm=False)
        + btn("← Voltar ao catálogo", "ghost-gold", "book", "11-site-catalogo.html", sm=False) + '</div>'),
  extra=f'<p class="hero__note">{ic("lock")} Catálogo público sem preço: a condição comercial é exclusiva de lojista aprovado.</p>',
  art=f'<div style="width:300px">{rings.svg("diamond", 951)}</div>')

body = f'''
<section class="band-light"><div class="band__inner">
  <div class="split split--wide" style="--gcols:minmax(0,1.1fr) minmax(0,1fr)">
    <div class="stack">
      {card(None, gal)}
      {card("Ficha técnica", ficha)}
    </div>
    <div class="stack">
      {pricelock}
      {card("Opções de fabricação", opcoes)}
      {card("Gravação personalizada",
        '<p class="lede" style="font-size:var(--text-sm)">Gravação interna de nome e data, feita na fábrica antes do envio. '
        'O limite de caracteres e o valor por peça são parametrizáveis e aparecem no Portal do Lojista.</p>'
        + '<ul class="cklist" style="margin-top:var(--space-3)">'
        + "".join(f'<li class="ck--ok">{ic("check")}<span>{e(t)}</span></li>' for t in [
            "Até 20 caracteres por aliança",
            "Texto e data no mesmo pedido",
            "Prazo adicional de 5 dias úteis"]) + '</ul>')}
      {u.notice("<strong>Sem preço nesta página.</strong> A rota pública não serializa <code>products.price</code> "
                "(regra 1 do escopo 1.3 · Anexo I §3.3).")}
    </div>
  </div>
</div></section>

<section class="band-dark" id="condicoes"><div class="band__inner">
  <div class="split" style="--gcols:minmax(0,1fr) minmax(0,1.15fr);gap:var(--space-8)">
    <div>
      <span class="eyebrow" style="color:var(--color-gold-300)">Condições comerciais</span>
      <h2 class="display-md" style="margin-top:var(--space-3)">Como a Velaro vende para a sua loja.</h2>
      <p class="lede" style="margin-top:var(--space-4);color:rgba(255,255,255,.7)">
        Toda relação financeira desta plataforma é Velaro → lojista. Quem vende ao consumidor final é você,
        pelo preço que você define na sua vitrine.</p>
      <div class="row row--wrap" style="margin-top:var(--space-6)">
        {btn("Fazer cadastro como lojista", "gold", "user", "12-site-cadastro.html", sm=False)}
        {btn("Falar com especialista", "ghost-gold", "support", "19-site-contato.html", sm=False)}
      </div>
    </div>
    <div class="grid g2" style="gap:var(--space-5)">{condicoes}</div>
  </div>
</div></section>

<section class="band-light"><div class="band__inner">
  <div class="section-head">
    <span class="eyebrow" style="color:var(--color-gold-700)">Modelos relacionados</span>
    <h2 class="display-md" style="margin-top:var(--space-2)">Quem escolhe a Diamond também leva</h2>
    <div class="rule"><span>VELARO</span></div>
  </div>
  <div class="prods">{relacionados}</div>
</div></section>'''

W("16-site-produto.html", page("Velaro · Diamond VL-DM-01", site_shell("Catálogo", hero, body), body_class="site"))

# ═════════════════════════════════════════════════════════════════════════════
# DOCUMENTOS LEGAIS · 17 Privacidade e 18 Termos
# Linkados do rodapé de todas as telas do site e do aceite do cadastro (1.4 §3.2:
# o aceite grava data, IP e VERSÃO do texto — por isso a versão fica no cabeçalho).
# ═════════════════════════════════════════════════════════════════════════════
def _p(txt):   return f'<p>{txt}</p>'
def _ul(itens): return '<ul>' + "".join(f'<li>{t}</li>' for t in itens) + '</ul>'

def doc_legal(arquivo, titulo_pagina, titulo, eyebrow, resumo, versao, vigencia, atualizacao,
              secoes, par_arquivo, par_rotulo, variante, uid):
    """secoes: (id, titulo, corpo_html). Índice lateral + corpo numerado."""
    idx = "".join(
      f'<a href="#{sid}"><b>{n:02d}</b><span>{e(t)}</span></a>'
      for n, (sid, t, _) in enumerate(secoes, 1))
    corpo = "".join(
      f'<section id="{sid}"><h3><span>{n}.</span>{e(t)}</h3>{c}</section>'
      for n, (sid, t, c) in enumerate(secoes, 1))
    meta = "".join(
      f'<div class="identcell">{ic(i, style="color:var(--color-gold-600)")}'
      f'<span><small>{e(k)}</small><strong>{e(v)}</strong></span></div>'
      for i, k, v in [
        ("doc", "Versão do documento", versao),
        ("calendar", "Vigente desde", vigencia),
        ("refresh", "Última atualização", atualizacao),
        ("users", "Aplica-se a", "Lojistas com CNPJ e visitantes do site"),
        ("shield", "Encarregado (DPO)", "privacidade@velaro.com.br")])

    hero = site_hero(titulo, eyebrow=eyebrow, sub=f"Versão {versao} · vigente desde {vigencia}",
      texto=resumo,
      extra=f'<p class="hero__note">{ic("info")} Este texto se aplica à relação entre a Velaro e o lojista. '
            f'O consumidor final não possui conta nesta plataforma.</p>',
      art=f'<div style="width:250px">{rings.svg(variante, uid)}</div>')

    body = f'''
<section class="band-light"><div class="band__inner">
  <div class="identbar">{meta}</div>
  <div class="split" style="--gcols:300px minmax(0,1fr);margin-top:var(--space-4)">
    <nav class="legalidx" aria-label="Índice do documento">
      <strong>Neste documento</strong>
      {idx}
      <a class="legalidx__alt" href="{par_arquivo}">{ic("link")}<span>{e(par_rotulo)}</span></a>
    </nav>
    <div class="stack">
      <div class="card"><div class="legaltext">{corpo}</div></div>
      {u.notice("<strong>Registro do aceite.</strong> Ao enviar o cadastro de lojista, a plataforma grava "
                f"data, hora, IP e a <strong>versão {e(versao)}</strong> deste texto, conforme o escopo 1.4 (Anexo I §3.4).")}
      <div class="card">
        <div class="spread">
          <div><h2 class="title">Dúvidas sobre este documento?</h2>
            <p class="lede" style="margin-top:4px;font-size:var(--text-sm)">
              Fale com o encarregado de dados pelo e-mail privacidade@velaro.com.br ou pelo nosso atendimento.</p></div>
          <div class="row row--wrap">
            {btn("Ir para o cadastro", "primary", "user-plus", "12-site-cadastro.html", sm=False)}
            {btn(par_rotulo, "secondary", "doc", par_arquivo, sm=False)}
          </div>
        </div>
      </div>
    </div>
  </div>
</div></section>'''
    W(arquivo, page(titulo_pagina, site_shell("", hero, body), body_class="site"))

# ─────────────────────────────── 17 · PRIVACIDADE ───────────────────────────────
PRIV = [
 ("p1", "Quem somos e o que esta política cobre",
  _p("A Velaro Alianças Ltda., CNPJ 45.123.456/0001-09, com sede em Ribeirão Preto/SP, é a controladora dos "
     "dados pessoais tratados neste site e na plataforma B2B. Esta política explica quais dados coletamos, "
     "por que coletamos, com quem compartilhamos e como você exerce seus direitos, nos termos da "
     "Lei nº 13.709/2018 (LGPD).")
  + _p("A Velaro é <strong>fábrica e fornecedora</strong>: vendemos exclusivamente a lojistas com CNPJ. "
       "O consumidor final não cria conta, não compra e não paga nesta plataforma.")),

 ("p2", "Dados que tratamos",
  _p("Tratamos conjuntos diferentes de dados conforme o seu papel:")
  + _ul(["<strong>Visitante do site</strong> — dados de navegação, páginas visitadas e origem do acesso.",
         "<strong>Candidato a revendedor</strong> — razão social, nome fantasia, CNPJ, inscrição estadual, "
         "nome e CPF do responsável, e-mail, telefone/WhatsApp, endereço completo, origem do contato e os "
         "documentos enviados (contrato social, documento do sócio e comprovante de CNPJ).",
         "<strong>Revendedor aprovado</strong> — dados cadastrais acima, credenciais de acesso, histórico de "
         "pedidos, títulos financeiros e registros de suporte.",
         "<strong>Cliente final do revendedor</strong> — nome, CPF, contato e dados do pedido, cadastrados "
         "<em>pelo revendedor</em> na carteira dele."])),

 ("p3", "Bases legais e finalidades",
  _ul(["<strong>Execução de contrato</strong> — analisar o cadastro, liberar o acesso, processar pedidos, "
       "emitir documento fiscal e cobrar o lojista.",
       "<strong>Cumprimento de obrigação legal</strong> — guarda fiscal e contábil dos documentos emitidos.",
       "<strong>Legítimo interesse</strong> — prevenção a fraude, segurança da plataforma e registro de "
       "auditoria das ações sensíveis.",
       "<strong>Consentimento</strong> — comunicações de marketing e campanhas em datas especiais. "
       "É opcional, registrável e revogável a qualquer momento."])),

 ("p4", "Validação automática de CNPJ e CNAE",
  _p("Ao enviar o cadastro, o CNPJ informado é consultado em bases públicas para conferir situação cadastral, "
     "atividade econômica (CNAE) e compatibilidade com o segmento. O resultado dessa consulta é armazenado "
     "junto à sua solicitação.")
  + _p("A triagem automatizada apenas <strong>organiza</strong> a análise: a decisão de aprovar ou reprovar é "
       "sempre humana e fica registrada com justificativa. Você pode pedir a revisão dessa decisão.")),

 ("p5", "O cliente final do revendedor",
  _p("Quando o revendedor cadastra um cliente na carteira dele ou registra um pedido de balcão, o "
     "<strong>revendedor é o controlador</strong> desses dados e a Velaro atua como <strong>operadora</strong>, "
     "tratando-os apenas para viabilizar o pedido, a produção e o aviso de retirada.")
  + _p("As mensagens enviadas ao consumidor saem em nome da loja do revendedor. A Velaro não usa a base de "
       "clientes de um revendedor para vender a outro nem para campanhas próprias.")),

 ("p6", "Compartilhamento",
  _p("Compartilhamos dados apenas com quem é necessário para a operação, sempre sob contrato:")
  + _ul(["provedores de hospedagem e infraestrutura em nuvem;",
         "serviços de envio de e-mail e de mensagens por WhatsApp;",
         "serviços de consulta cadastral de CNPJ e CNAE;",
         "transportadoras, para entrega dos pedidos;",
         "contabilidade e autoridades fiscais, no que a lei exige."])
  + _p("Não vendemos dados pessoais e não cedemos sua base de clientes a terceiros.")),

 ("p7", "Cookies",
  _p("Usamos cookies necessários (sessão, autenticação e segurança), que não podem ser desativados, e cookies "
     "de medição de audiência, que dependem do seu aceite no banner de cookies. A preferência pode ser alterada "
     "a qualquer momento.")),

 ("p8", "Segurança e retenção",
  _p("Adotamos criptografia em trânsito, controle de acesso por perfil e registro de auditoria das ações "
     "sensíveis, incluindo login, aprovação de cadastro e alteração de preço.")
  + _ul(["Cadastro reprovado: dados mantidos por 24 meses, para evitar reanálise indevida e comprovar a decisão.",
         "Revendedor ativo: dados mantidos durante a relação comercial.",
         "Documentos fiscais: mantidos pelo prazo legal de guarda.",
         "Consentimento de marketing: revogável a qualquer momento, com registro da revogação."])),

 ("p9", "Seus direitos",
  _p("A LGPD garante a você confirmação do tratamento, acesso, correção, anonimização, portabilidade, "
     "eliminação dos dados tratados com base em consentimento, informação sobre compartilhamentos e revisão "
     "de decisões automatizadas.")
  + _p("Para exercer qualquer desses direitos, escreva para <strong>privacidade@velaro.com.br</strong>. "
       "Respondemos em até 15 dias.")),

 ("p10", "Alterações desta política",
  _p("Esta política pode ser atualizada. Cada versão recebe número e data de vigência, e as alterações "
     "relevantes são comunicadas por e-mail e no primeiro acesso ao Portal. As versões anteriores ficam "
     "disponíveis mediante solicitação.")),
]

doc_legal("17-site-privacidade.html", "Velaro · Política de Privacidade",
  "Política de Privacidade", "Documentos legais",
  ("Como a Velaro trata os dados pessoais de quem visita o site, de quem pede cadastro como lojista e dos "
   "clientes cadastrados pelo revendedor na plataforma B2B."),
  "2.1", "01/08/2026", "01/08/2026", PRIV,
  "18-site-termos.html", "Ler os Termos de Uso", "branco", 905)

# ─────────────────────────────── 18 · TERMOS DE USO ───────────────────────────────
TERMOS = [
 ("t1", "Objeto e aceitação",
  _p("Estes Termos regem o uso do site institucional, do catálogo público, da plataforma B2B (Portal do "
     "Lojista) e da vitrine white label disponibilizada ao revendedor pela Velaro Alianças Ltda.")
  + _p("Ao enviar o cadastro de lojista ou ao acessar o Portal, você declara que leu e aceita estes Termos "
       "na versão vigente. O aceite é registrado com data, hora, IP e número da versão.")),

 ("t2", "Quem pode usar a plataforma",
  _p("O acesso é restrito a <strong>pessoas jurídicas com CNPJ ativo</strong> e atividade econômica compatível "
     "com o segmento de joias e alianças — joalherias, lojas de alianças e revendedores formalizados.")
  + _p("O <strong>consumidor final não possui conta</strong>: ele não compra, não paga e não se cadastra na "
       "Velaro. Ele existe na plataforma apenas como cliente vinculado à carteira do revendedor.")),

 ("t3", "Cadastro, aprovação e credenciais",
  _ul(["O cadastro passa por validação automática de CNPJ e CNAE e por aprovação final da equipe Velaro.",
       "A Velaro pode recusar cadastro sem que isso gere direito a indenização, informando o motivo.",
       "As credenciais são pessoais e intransferíveis; o lojista responde pelo uso feito por sua equipe.",
       "É obrigação do lojista manter dados cadastrais e documentos atualizados."])),

 ("t4", "Catálogo, preços e condições comerciais",
  _p("O catálogo público não exibe preço. Preço de fábrica, faixa de desconto, pedido mínimo e prazo de "
     "pagamento são exibidos apenas dentro do Portal, para lojistas aprovados, e são "
     "<strong>confidenciais</strong>.")
  + _p("Fotos e ilustrações do catálogo são referenciais. Pequenas variações de acabamento, peso e tonalidade "
       "são próprias da fabricação artesanal e não caracterizam defeito.")
  + _p("A Velaro pode alterar preços e condições a qualquer tempo. Pedido já confirmado mantém o preço "
       "registrado no momento da confirmação.")),

 ("t5", "Pedidos, produção e prazos",
  _ul(["O pedido é confirmado após a aprovação do pagamento ou a liberação de crédito do lojista.",
       "Peças de catálogo têm prazo de até 7 dias úteis de produção; peças com gravação ou aro fora da grade, "
       "até 12 dias úteis.",
       "Prazos de produção não incluem o tempo de transporte, informado no momento do envio.",
       "Cancelamento é possível enquanto o pedido não entrar em produção."])),

 ("t6", "Pagamento e faturamento",
  _p("A relação financeira desta plataforma é <strong>exclusivamente Velaro → lojista</strong>, por Pix, boleto "
     "ou transferência, com emissão de nota fiscal contra o CNPJ cadastrado.")
  + _p("A plataforma <strong>não processa</strong> pagamento do consumidor final: nem Pix, nem cartão, nem link "
       "de pagamento. O recebimento do consumidor é feito diretamente pelo revendedor, fora desta plataforma, e "
       "é responsabilidade exclusiva dele.")
  + _p("O atraso no pagamento sujeita o lojista a juros e multa contratuais e pode suspender novos pedidos.")),

 ("t7", "Entrega e retirada",
  _p("A entrega é feita no endereço cadastrado do lojista ou por retirada na fábrica, conforme escolhido no "
     "pedido. A conferência do volume no recebimento é obrigatória e divergências devem ser comunicadas em até "
     "48 horas, com registro fotográfico.")),

 ("t8", "Vitrine white label e responsabilidades do revendedor",
  _p("A Velaro disponibiliza ao revendedor uma vitrine personalizável com a marca dele. Sobre essa vitrine:")
  + _ul(["o <strong>preço ao consumidor é definido pelo revendedor</strong>, que responde por sua adequação legal;",
         "a venda ao consumidor final é celebrada entre consumidor e revendedor, sem participação da Velaro;",
         "o revendedor é o controlador dos dados dos clientes que cadastra e responde pelo consentimento deles;",
         "o revendedor não pode apresentar a Velaro como vendedora ao consumidor, nem usar a marca Velaro "
         "sem autorização escrita."])),

 ("t9", "Gravação e personalização",
  _p("Peças com gravação são personalizadas e produzidas sob encomenda. Por isso, salvo defeito de fabricação, "
     "<strong>não são passíveis de troca, devolução ou arrependimento</strong>. O texto gravado é de "
     "responsabilidade de quem o informa, e o limite de caracteres é o exibido no momento do pedido.")),

 ("t10", "Garantia, trocas e assistência",
  _p("As peças têm 12 meses de garantia contra defeito de fabricação, contados da data de emissão da nota "
     "fiscal. A garantia não cobre desgaste natural, amassamento, riscos, contato com produtos químicos, "
     "redimensionamento por terceiros ou uso indevido.")
  + _p("A solicitação é aberta pelo lojista no Portal, no menu de Suporte, com fotos e número do pedido.")),

 ("t11", "Propriedade intelectual",
  _p("Marca, logotipo, catálogo, fotos, textos e o software desta plataforma pertencem à Velaro. O revendedor "
     "aprovado recebe licença limitada e revogável para usar imagens do catálogo na divulgação dos produtos "
     "que revende, sem direito a sublicenciar, modificar a marca ou registrar domínio com o nome Velaro.")),

 ("t12", "Suspensão e encerramento",
  _p("A Velaro pode suspender ou encerrar o acesso em caso de inadimplência, informação cadastral falsa, uso "
     "indevido da marca, compartilhamento de preço confidencial ou violação destes Termos. O encerramento não "
     "afeta pedidos já pagos nem as obrigações financeiras já constituídas.")),

 ("t13", "Limitação de responsabilidade",
  _p("A Velaro não responde por lucros cessantes do revendedor, por indisponibilidade decorrente de caso "
     "fortuito ou força maior, nem por atos praticados na relação entre o revendedor e o consumidor final, "
     "inclusive preço, prazo prometido no balcão e formas de recebimento.")),

 ("t14", "Alterações, lei aplicável e foro",
  _p("Estes Termos podem ser alterados. A nova versão recebe número e data de vigência e é comunicada no "
     "primeiro acesso ao Portal. O uso após a comunicação implica aceite.")
  + _p("Aplica-se a lei brasileira. Fica eleito o foro da comarca de Ribeirão Preto/SP para dirimir "
       "controvérsias, com renúncia a qualquer outro.")),
]

doc_legal("18-site-termos.html", "Velaro · Termos de Uso",
  "Termos de Uso", "Documentos legais",
  ("As regras da relação B2B entre a Velaro e o lojista: quem pode se cadastrar, como funcionam pedidos, "
   "preços, pagamento, entrega e a vitrine white label."),
  "2.1", "01/08/2026", "01/08/2026", TERMOS,
  "17-site-privacidade.html", "Ler a Política de Privacidade", "classica", 906)

# ═════════════════════════════════════════════════════════════════════════════
# 1.8 · FALE CONOSCO  ·  GET /contato · POST /contato
# A tela que faltava no escopo: "Fale conosco" era só uma âncora para o bloco de
# CTA do catálogo, e `contact_leads` existia no banco sem formulário que a
# alimentasse. Aqui o lead nasce de verdade.
#
# Regra que a tela precisa deixar explícita: lead NÃO é pré-cadastro. Quem quer
# revender continua obrigado a passar pela 1.4 — este formulário não cria
# revendedor, não cria acesso e não substitui a análise de CNPJ.
#
# Telefone, e-mail, WhatsApp e horário saem de `settings` grupo contact.* — são
# os mesmos valores do rodapé montado pelo site_shell().
# ═════════════════════════════════════════════════════════════════════════════
CANAIS = [
  ("phone", "Telefone comercial",  "+55 (16) 99487-7800"),
  ("whats", "WhatsApp",            "+55 (16) 99487-7800"),
  ("mail",  "E-mail comercial",    "vendas@velaro.com.br"),
  ("clock", "Horário de atendimento", "Segunda a sexta, das 8h às 18h"),
]
canais = "".join(
  f'<div class="identcell">{ic(i, style="color:var(--color-gold-600)")}'
  f'<span><small>{e(k)}</small><strong>{e(v)}</strong></span></div>'
  for i, k, v in CANAIS)

ASSUNTOS = ["Condições comerciais e catálogo", "Acompanhar solicitação de cadastro",
            "Suporte a lojista já aprovado", "Prazo de produção e entrega",
            "Imprensa e parcerias", "Outro assunto"]

dados_contato = form([
  campo("Nome", "Como podemos chamar você?", True),
  campo("E-mail", "seuemail@exemplo.com.br", True),
  campo("Telefone / WhatsApp", "(00) 00000-0000", True,
        hint="O retorno pode sair pelo mesmo número, por WhatsApp."),
  campo("Empresa", "Nome fantasia da sua loja", False,
        hint="Opcional — ajuda a direcionar o atendimento."),
  campo("Assunto", "Selecione o assunto", True, "select", largura=2,
        hint="Opções: " + " · ".join(ASSUNTOS)),
  campo("Mensagem", "Conte o que você precisa. Quanto mais contexto, mais direta é a resposta.",
        True, "textarea", largura=2, hint="Até 1.000 caracteres."),
], 2, "Sua mensagem")

# .checkline tambem e flex sem wrap: cada trecho solto (texto, link, asterisco)
# virava um item e a frase descia em colunas no celular. Rotulo inteiro num <span>.
consentimento = ('<h3 class="fsec">Consentimento</h3><div class="stacklist">'
  '<span class="checkline" style="align-items:flex-start">'
  '<span class="cbox is-on" style="flex:none;margin-top:2px">✓</span>'
  '<span>Li e concordo com a <a href="#" class="link-gold">Política de Privacidade</a> e autorizo '
  'a Velaro a usar os dados acima para responder a este contato.<i class="req">*</i></span></span>'
  '</div>'
  f'<p class="fhint" style="margin-top:var(--space-3)">{ic("shield")} '
  'O aceite é obrigatório para enviar e fica registrado com data, hora, IP e a versão do texto '
  'vigente — a mesma prova exigida no cadastro de lojista.</p>')

passos = "".join(
  f'<li><span class="num">{n}</span><div><strong>{e(t)}</strong><p>{e(d)}</p></div></li>'
  for n, (t, d) in enumerate([
    ("Mensagem recebida", "O contato entra na fila de atendimento com a página de origem registrada."),
    ("Triagem pelo assunto", "A equipe assume o contato; a partir daí ele tem responsável e data de retorno."),
    ("Resposta em até 1 dia útil", "Respondemos por e-mail ou WhatsApp, no canal que você preferir.")], 1))

nao_substitui = "".join(f'<li>{ic("x")}{e(t)}</li>' for t in
  ["Não cria cadastro de revendedor", "Não libera preço nem condição comercial",
   "Não dá acesso ao Portal do Lojista", "Não dispensa o envio dos documentos"])

hero = site_hero("FALE CONOSCO", eyebrow="Atendimento a lojistas",
  sub="Uma conversa direta com quem fabrica a aliança.",
  texto=("Dúvida sobre coleção, prazo de produção, condição comercial ou uma solicitação de cadastro em "
         "andamento: escreva para o time comercial da Velaro e receba retorno em até 1 dia útil."),
  # .hero__note e flex: o texto precisa vir num unico <span>, senao cada trecho
  # separado pelo <strong> vira um item de flex e a frase quebra em colunas.
  extra=f'<p class="hero__note">{ic("info")}<span>A Velaro é fábrica e vende <strong>somente para lojistas '
        f'com CNPJ</strong>. Este canal é atendimento — quem quer revender precisa do pré-cadastro.</span></p>',
  art=f'<div style="width:250px">{rings.svg("bicolor", 907)}</div>')

body = f'''
<section class="band-light"><div class="band__inner">
  <div class="identbar">{canais}</div>
  <div class="split" style="--gcols:minmax(0,1fr) 400px;margin-top:var(--space-4)">
    <div class="card">
      <div class="card__head"><h2 class="title">{ic("mail")} Envie sua mensagem</h2></div>
      {dados_contato}
      {consentimento}
      <a class="btn btn--primary" style="width:100%;margin-top:var(--space-6)" href="#">
        Enviar mensagem ›</a>
      <p class="muted" style="text-align:center;margin:var(--space-3) 0 0;font-size:var(--text-xs)">
        {ic("info")} Registramos de qual página do site você veio, para direcionar o atendimento.</p>
    </div>
    <div class="stack">
      <div class="card panel-dark">
        <h3 class="title" style="color:var(--color-gold-300)">Quer revender a Velaro?</h3>
        <p style="margin:var(--space-3) 0 0;font-size:var(--text-sm);line-height:22px;color:rgba(255,255,255,.72)">
          Este formulário <strong>não substitui o pré-cadastro</strong>. Para receber preço de fábrica e acesso
          ao Portal do Lojista, envie o cadastro completo: CNPJ, CNAE compatível e os três documentos da empresa.</p>
        <ul class="cklist cklist--dark" style="margin-top:var(--space-4)">{nao_substitui}</ul>
        <div style="margin-top:var(--space-5)">
          {btn("Quero ser revendedor", "gold", "user-plus", "12-site-cadastro.html", sm=False)}
        </div>
      </div>
      <div class="card panel-dark">
        <h3 class="title" style="color:var(--color-gold-300)">Como funciona o atendimento</h3>
        <ol class="howto">{passos}</ol>
      </div>
      <div class="card">
        <h3 class="title">Já é lojista Velaro?</h3>
        <p class="lede" style="margin-top:var(--space-2);font-size:var(--text-sm)">
          Pedido, financeiro e produção se resolvem mais rápido pelo chamado de suporte dentro do Portal,
          que já chega com o histórico da sua loja.</p>
        <div class="row row--wrap" style="margin-top:var(--space-4)">
          {btn("Entrar no Portal", "primary", "user", "20-login.html")}
          {btn("Acompanhar solicitação", "secondary", "search", "14-site-status.html")}
        </div>
      </div>
    </div>
  </div>
  {notice("<strong>Contato não é chamado.</strong> Quem ainda não é revendedor não abre chamado de suporte: "
          "a mensagem vira um lead na fila comercial, com responsável e data de atendimento registrados.")}
</div></section>'''

W("19-site-contato.html", page("Velaro · Fale conosco",
  site_shell("Fale conosco", hero, body), body_class="site"))

# ═════════════════════════════════════════════════════════════════════════════
# 0b · RECUPERAR SENHA  ·  GET/POST /recuperar-senha
# Mesmo layout .loginwrap da 20-login.html. Duas etapas no mesmo protótipo:
# o formulário e o estado "link enviado".
# ═════════════════════════════════════════════════════════════════════════════
regras = "".join(
  f'<li>{ic(i)}<span>{t}</span></li>' for i, t in [
    ("clock", "O link vale por <b>30 minutos</b> e só pode ser usado uma vez."),
    ("mail", "A mensagem sai para o e-mail do responsável cadastrado."),
    ("shield", "A resposta é sempre a mesma, exista ou não conta com aquele e-mail."),
    ("lock", "Pedido, envio e troca de senha entram em <code>audit_logs</code> (Anexo I §7)."),
    ("x", "Cadastro reprovado ou inativo não recebe link — ele não autentica.")])

body = f'''
<div class="loginwrap">
  <div class="loginaside">
    <div class="row" style="gap:12px">{logo(38)}{wordmark(24)}</div>
    <div>
      <h1 class="display-md" style="color:#fff">Esqueceu a senha?<br>A gente reabre a porta.</h1>
      <p class="lede" style="color:rgba(255,255,255,.7);margin-top:var(--space-4)">
        O mesmo e-mail que você usou no cadastro de lojista recebe um link temporário para criar uma senha nova.
        Nada além disso muda: perfil, permissões e vínculo com o revendedor continuam os mesmos.</p>
    </div>
    <div class="routerbox">
      <span class="eyebrow" style="color:var(--color-gold-300)">Regras do link de recuperação</span>
      <ul class="cklist cklist--dark" style="margin-top:var(--space-3)">{regras}</ul>
    </div>
    <p class="muted" style="font-size:var(--text-xs);color:rgba(255,255,255,.5)">
      Ainda em pré-cadastro? O mesmo login serve para acompanhar a solicitação em
      <code>/solicitacao/{{protocolo}}</code>.</p>
  </div>
  <div class="loginmain">
    <div class="stack" style="width:100%;max-width:420px;gap:var(--space-6)">

      <div>
        <span class="eyebrow">Estado 1 · pedido de recuperação</span>
        <div class="card" style="margin-top:var(--space-2)">
          <h2 class="title">Recuperar senha</h2>
          <p class="lede" style="margin:6px 0 var(--space-5)">Informe o e-mail cadastrado e enviaremos um link
            para você criar uma senha nova.</p>
          {form([campo("E-mail", "contato@tomazelli.com.br", True, largura=2)], 2)}
          <a class="btn btn--primary" style="width:100%;margin-top:var(--space-5)" href="#enviado">
            {ic("mail")} Enviar link de recuperação</a>
          <p class="muted" style="text-align:center;margin:var(--space-4) 0 0;font-size:var(--text-sm)">
            Lembrou a senha? <a class="link-gold" href="20-login.html">← Voltar para o login</a></p>
          {u.notice("Por segurança, mostramos sempre a mesma confirmação — exista ou não uma conta com esse e-mail.", "info")}
        </div>
      </div>

      <div id="enviado">
        <span class="eyebrow">Estado 2 · link enviado</span>
        <div class="card" style="margin-top:var(--space-2)">
          <div class="row" style="gap:var(--space-4);align-items:flex-start">
            <span class="bigcheck">{ic("mail")}</span>
            <div>
              <h2 class="title">Link enviado</h2>
              <p class="lede" style="margin-top:6px">Se houver uma conta para
                <strong>contato@tomazelli.com.br</strong>, o link de recuperação já está a caminho.</p>
            </div>
          </div>
          <div style="margin-top:var(--space-5)">
            {u.linha_dado("Enviado em", "Hoje, 10:42", "clock")}
            {u.linha_dado("Validade do link", "30 minutos", "lock")}
            {u.linha_dado("Uso", "Uma única vez", "shield")}
            {u.linha_dado("Canal", "E-mail do responsável cadastrado", "mail")}
          </div>
          <h3 class="fsec">Não recebeu?</h3>
          <ul class="cklist">
            <li class="ck--ok">{ic("check")}<span>Confira a caixa de spam e a aba de promoções.</span></li>
            <li class="ck--ok">{ic("check")}<span>Confirme se o e-mail é o mesmo do cadastro de lojista.</span></li>
            <li class="ck--wait">{ic("clock")}<span>Novo envio liberado em 45 segundos.</span><b>00:45</b></li>
          </ul>
          <div class="row row--wrap" style="margin-top:var(--space-5)">
            {btn("Reenviar link", "secondary", "refresh", sm=False)}
            {btn("Voltar para o login", "primary", "user", "20-login.html", sm=False)}
          </div>
        </div>
      </div>

      <p class="muted" style="text-align:center;font-size:var(--text-sm)">
        Ainda não é parceiro? <a class="link-gold" href="12-site-cadastro.html">Cadastre-se como lojista</a>
      </p>
    </div>
  </div>
</div>'''
W("21-login-senha.html", page("Velaro · Recuperar senha", body, body_class="site"))

# ═════════════════════════════════════════════════════════════════════════════
# AMBIENTE 3 · VITRINE WHITE LABEL  (07 ficha do produto · 08 pedido registrado)
#
# REGRA 1 DO DOC 2-9: zero marca Velaro ou SVD perante o consumidor final.
# Por isso o CSS destas duas telas mora AQUI, na própria página, e não no
# design system da Velaro: a vitrine é pintada só pelas variáveis --shop-*,
# que vêm de `reseller_stores`. Trocar de lojista = trocar o bloco :root abaixo.
# ═════════════════════════════════════════════════════════════════════════════
SHOP_CSS = """
  .shop {
    --shop-primary:   #800020;   /* reseller_stores.color_primary    */
    --shop-secondary: #b8860b;   /* reseller_stores.color_secondary  */
    --shop-bg:        #ffffff;   /* reseller_stores.color_background */
    --shop-text:      #1a1a1a;   /* reseller_stores.color_text       */
    --shop-surface:   #faf8f7;
    --shop-border:    #e8e2e0;
    --shop-muted:     #6b6360;
  }

  body { background: #d9d4cf; display: grid; place-items: start center; padding: var(--space-6); }

  /* Moldura de tablet — o atendimento do fluxo 4.10 é presencial, em tablet. */
  .tablet { width: min(1320px, 100%); border-radius: 26px; padding: 16px;
    background: linear-gradient(160deg,#2c2c2e,#111113); box-shadow: 0 30px 70px rgba(0,0,0,.32); }
  .tablet__screen { border-radius: 14px; overflow: hidden; background: var(--shop-bg); }
  .tablet__hint { text-align: center; color: rgba(255,255,255,.55); font-size: var(--text-xs); padding-top: 10px; }
  .vitlegend { width: min(1320px,100%); margin-top: var(--space-5); }
  .vitlegend .notice { background: #fff; }

  .shop { background: var(--shop-bg); color: var(--shop-text); font-family: var(--font-sans);
    display: grid; grid-template-columns: 1fr 400px; min-height: 780px; }
  .shop--single { grid-template-columns: 1fr; }
  .shop__main { padding: 0 0 var(--space-6); min-width: 0; }

  .shop__nav { display: flex; align-items: center; gap: var(--space-6);
    padding: var(--space-4) var(--space-6); border-bottom: 1px solid var(--shop-border); }
  .shop__logo { display: grid; line-height: 1; }
  .shop__logo strong { font-family: var(--font-display); font-size: 21px; letter-spacing: .20em;
    color: var(--shop-primary); font-weight: 600; }
  .shop__logo small { font-size: 8px; letter-spacing: .34em; color: var(--shop-secondary); margin-top: 5px; }
  .shop__tabs { display: flex; gap: var(--space-2); margin-left: var(--space-6); }
  .shop__tabs a { padding: 8px 14px; border-radius: var(--radius-pill); font-size: var(--text-sm); color: var(--shop-muted); }
  .shop__tabs a.is-active { background: var(--shop-primary); color: #fff; font-weight: 600; }
  .shop__navicons { margin-left: auto; display: flex; align-items: center; gap: var(--space-4); color: var(--shop-muted); }
  .shop__bag { display: inline-flex; align-items: center; gap: 6px; font-size: var(--text-sm); font-weight: 600; color: var(--shop-text); }
  .shop__bag b { min-width: 20px; height: 20px; display: grid; place-items: center; border-radius: 999px;
    background: var(--shop-primary); color: #fff; font-size: 11px; }

  .shop__crumb { display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
    padding: var(--space-4) var(--space-6) 0; font-size: var(--text-sm); color: var(--shop-muted); }
  .shop__crumb b { color: var(--shop-text); font-weight: 600; }

  .shop__section { padding: var(--space-6) var(--space-6) 0; }
  .shop__section h3 { font-family: var(--font-display); font-size: 19px; margin: 0 0 var(--space-4);
    color: var(--shop-text); font-weight: 600; }

  .prods { display: grid; grid-template-columns: repeat(4, 1fr); gap: var(--space-3); }
  .prod { border: 1px solid var(--shop-border); border-radius: var(--radius-md); background: var(--shop-bg);
    padding: var(--space-3); display: grid; gap: 8px; align-content: start; }
  .prod:hover { border-color: var(--shop-primary); }
  .prod__img { height: 104px; border-radius: var(--radius-sm); overflow: hidden; display: grid;
    place-items: center; padding: 6px; background: linear-gradient(155deg,#fdf9f2,#efe4d2); }
  .prod__img svg { width: 100%; height: 100%; }
  .prod h4 { margin: 0; font-size: var(--text-body); font-weight: 600; color: var(--shop-text); }
  .prod small { font-size: var(--text-xs); color: #857b78; line-height: 16px; }
  .prod .price { font-family: var(--font-display); font-size: 19px; font-weight: 600; color: var(--shop-primary); }
  .prod .btn { width: 100%; }

  /* ---------------- ficha do produto (07) ---------------- */
  .pdp2 { display: grid; grid-template-columns: minmax(0,1fr) minmax(0,1fr);
    gap: var(--space-6); padding: var(--space-4) var(--space-6) 0; align-items: start; }
  .pdp2__gal { display: grid; gap: 10px; align-content: start; min-width: 0; }
  .pdp2__main { border-radius: var(--radius-md); overflow: hidden; display: grid; place-items: center;
    padding: var(--space-5); background: linear-gradient(155deg,#fdf9f2,#efe4d2); }
  .pdp2__main svg { width: 100%; height: auto; }
  .pdp2__thumbs { display: grid; grid-template-columns: repeat(auto-fit, minmax(58px,1fr)); gap: 8px; }
  .pdp2__thumbs span { border: 1px solid var(--shop-border); border-radius: var(--radius-sm); padding: 4px;
    background: linear-gradient(150deg,#fdf9f2,#eee2cf); display: grid; place-items: center; }
  .pdp2__thumbs span.is-on { border-color: var(--shop-primary); }
  .pdp2__thumbs svg { width: 100%; height: auto; }
  .pdp2__info { display: grid; gap: var(--space-4); align-content: start; min-width: 0; }
  .pdp2__info h1 { margin: 0; font-family: var(--font-display); font-size: 28px; line-height: 34px;
    font-weight: 600; color: var(--shop-text); }
  .pdp2__ref { font-size: var(--text-xs); letter-spacing: .08em; text-transform: uppercase; color: var(--shop-muted); }
  .pdp2__price { font-family: var(--font-display); font-size: 34px; font-weight: 600; line-height: 1.1;
    color: var(--shop-primary); font-variant-numeric: tabular-nums; }
  .pdp2__note { font-size: var(--text-sm); color: var(--shop-muted); margin: 4px 0 0; }

  .sopt { display: grid; gap: 8px; }
  .sopt > span:first-child { font-size: var(--text-xs); font-weight: 700; letter-spacing: .10em;
    text-transform: uppercase; color: var(--shop-text); }
  .sopt__row { display: flex; flex-wrap: wrap; gap: 6px; }
  .schip { display: inline-flex; align-items: center; justify-content: center; min-height: 40px; min-width: 44px;
    padding: 8px 14px; border: 1px solid var(--shop-border); border-radius: var(--radius-pill);
    background: #fff; font-size: var(--text-sm); color: var(--shop-muted); }
  .schip.is-on { border-color: var(--shop-primary); background: var(--shop-primary); color: #fff; font-weight: 600; }
  .schip.is-off { opacity: .45; text-decoration: line-through; }

  .engrave { border: 1px solid var(--shop-border); border-radius: var(--radius-md); background: #fff; padding: var(--space-4); }
  .engrave > header { display: flex; align-items: baseline; gap: 8px; margin-bottom: 10px; }
  .engrave h4 { margin: 0; font-size: var(--text-xs); font-weight: 700; letter-spacing: .12em;
    text-transform: uppercase; color: var(--shop-text); }
  .engrave .opt { display: inline-flex; align-items: center; gap: 7px; font-size: var(--text-sm); margin-right: var(--space-4); }
  .radio { width: 16px; height: 16px; border-radius: 50%; border: 2px solid var(--shop-border);
    display: inline-grid; place-items: center; }
  .radio.is-on { border-color: var(--shop-primary); }
  .radio.is-on::after { content:""; width: 8px; height: 8px; border-radius: 50%; background: var(--shop-primary); }
  .engrave .grid2 { display: grid; grid-template-columns: 1.3fr 1fr; gap: 10px; margin-top: 10px; }
  .engrave .counter { font-size: 11px; color: #9a908d; margin-top: 6px; }

  /* campos de formulário pintados pela loja — nada de token de marca Velaro */
  .sfields { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: var(--space-3); }
  .sfield { display: grid; gap: 5px; min-width: 0; }
  .sfield--full { grid-column: 1 / -1; }
  .sfield > span { font-size: var(--text-xs); color: var(--shop-muted); }
  .sfield i { color: #b42318; font-style: normal; }
  .sinput { display: flex; align-items: center; min-height: 42px; padding: 8px 10px;
    border: 1px solid var(--shop-border); border-radius: var(--radius-sm); background: #fff;
    font-size: var(--text-body); color: var(--shop-text); }
  .sinput.is-ph { color: #9a908d; }
  .sfield small { font-size: 11px; color: #9a908d; }
  .scheck { display: flex; align-items: flex-start; gap: 9px; font-size: var(--text-sm); color: var(--shop-muted); }
  .scheck i { width: 17px; height: 17px; flex: none; border-radius: 4px; border: 1px solid var(--shop-border);
    display: grid; place-items: center; font-size: 11px; font-style: normal; color: transparent; margin-top: 1px; }
  .scheck i.is-on { background: var(--shop-primary); border-color: var(--shop-primary); color: #fff; }

  .sbox { border: 1px solid var(--shop-border); border-radius: var(--radius-md); background: #fff;
    padding: var(--space-5); display: grid; gap: var(--space-4); align-content: start; min-width: 0; }
  .sbox > header { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
  .sbox h3 { margin: 0; font-size: var(--text-xs); font-weight: 700; letter-spacing: .12em;
    text-transform: uppercase; color: var(--shop-text); }
  .sbox h3 + small { color: var(--shop-muted); font-size: var(--text-xs); }
  .srow { display: flex; justify-content: space-between; gap: var(--space-4); padding: 9px 0;
    border-bottom: 1px solid var(--shop-border); font-size: var(--text-sm); color: var(--shop-muted); }
  .srow:last-child { border-bottom: 0; }
  .srow b { color: var(--shop-text); font-weight: 600; text-align: right; font-variant-numeric: tabular-nums; }
  .stag { display: inline-flex; align-items: center; gap: 6px; padding: 5px 11px; border-radius: 999px;
    font-size: var(--text-xs); font-weight: 700; background: var(--shop-surface); color: var(--shop-muted); }
  .stag--ok { background: #e8f6ee; color: #0a7a48; }
  .stag--wait { background: #fff5e6; color: #a35a06; }

  /* ---------------- linhas de item, totais e carrinho ---------------- */
  .line { display: grid; grid-template-columns: 52px 1fr auto; gap: 10px; align-items: center; }
  .line__img { width: 52px; height: 52px; border-radius: var(--radius-sm); overflow: hidden;
    display: grid; place-items: center; padding: 3px; background: linear-gradient(150deg,#fdf9f2,#eee2cf); }
  .line__img svg { width: 100%; height: 100%; }
  .line h5 { margin: 0; font-size: var(--text-sm); font-weight: 600; color: var(--shop-text); }
  .line small { font-size: var(--text-xs); color: #8a807d; }
  .line .money { font-size: var(--text-sm); font-weight: 700; color: var(--shop-text); font-variant-numeric: tabular-nums; }
  .qty { display: inline-flex; align-items: center; border: 1px solid var(--shop-border);
    border-radius: var(--radius-pill); background: #fff; margin-top: 4px; }
  .qty button { width: 30px; height: 30px; border: 0; background: none; color: var(--shop-muted); }
  .qty span { min-width: 22px; text-align: center; font-size: var(--text-sm); font-variant-numeric: tabular-nums; }

  .cart { border-left: 1px solid var(--shop-border); background: var(--shop-surface); display: flex; flex-direction: column; }
  .cart__head { display: flex; align-items: center; gap: 10px; padding: var(--space-4) var(--space-5);
    border-bottom: 1px solid var(--shop-border); }
  .cart__head h3 { margin: 0; font-size: var(--text-sm); font-weight: 700; letter-spacing: .12em;
    text-transform: uppercase; color: var(--shop-text); }
  .cart__body { flex: 1; padding: var(--space-4) var(--space-5); display: grid; gap: var(--space-3); align-content: start; }
  .totals { padding: var(--space-4) var(--space-5); border-top: 1px solid var(--shop-border); display: grid; gap: 8px; }
  .totals .row { display: flex; justify-content: space-between; font-size: var(--text-sm); color: var(--shop-muted); }
  .totals .row strong { color: var(--shop-text); font-variant-numeric: tabular-nums; }
  .totals .grand { display: flex; justify-content: space-between; align-items: baseline;
    padding-top: 10px; border-top: 1px solid var(--shop-border); }
  .totals .grand span { font-size: var(--text-sm); font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
  .totals .grand strong { font-family: var(--font-display); font-size: 27px; color: var(--shop-primary);
    font-variant-numeric: tabular-nums; }
  .cart__foot { padding: 0 var(--space-5) var(--space-5); display: grid; gap: 10px; }
  .cart__note { text-align: center; font-size: 11px; color: #8a807d; }
  .pickup { display: flex; gap: 10px; align-items: flex-start; padding: var(--space-3); margin: 0;
    border-radius: var(--radius-sm); background: #fff; border: 1px solid var(--shop-border);
    font-size: var(--text-xs); line-height: 17px; color: var(--shop-muted); }
  .pickup .ic { flex: none; color: var(--shop-primary); }
  .btn-checkout { width: 100%; min-height: 54px; border-radius: var(--radius-md); border: 0;
    background: var(--shop-primary); color: #fff; font-weight: 700; font-size: var(--text-body);
    letter-spacing: .06em; text-transform: uppercase; display: inline-flex; align-items: center;
    justify-content: center; gap: 10px; text-align: center; }
  .btn-ghost { width: 100%; min-height: 46px; border-radius: var(--radius-md); background: #fff;
    border: 1px solid var(--shop-border); color: var(--shop-text); font-weight: 600; font-size: var(--text-sm);
    display: inline-flex; align-items: center; justify-content: center; gap: 8px; }

  .msgprev { border: 1px solid var(--shop-border); border-radius: var(--radius-md);
    background: var(--shop-surface); padding: var(--space-4); }
  .msgprev__head { font-size: 11px; letter-spacing: .06em; text-transform: uppercase;
    color: var(--shop-muted); margin-bottom: 8px; }
  .msgprev p { margin: 0 0 6px; font-size: var(--text-sm); line-height: 19px; color: var(--shop-text); }
  .msgprev p:last-child { margin-bottom: 0; }
  .msgprev .ok { color: #0a7a48; font-weight: 600; }

  /* ---------------- comprovante do balcão (08) ---------------- */
  .okhead { display: flex; gap: var(--space-4); align-items: center; flex-wrap: wrap;
    padding: var(--space-6) var(--space-6) 0; }
  .okhead__mark { width: 56px; height: 56px; border-radius: 50%; flex: none; display: grid; place-items: center;
    background: #e8f6ee; color: #0a7a48; }
  .okhead__mark .ic { width: 28px; height: 28px; }
  .okhead h1 { margin: 0; font-family: var(--font-display); font-size: 28px; line-height: 34px;
    font-weight: 600; color: var(--shop-text); }
  .okhead p { margin: 4px 0 0; font-size: var(--text-sm); color: var(--shop-muted); }
  .okhead__tags { margin-left: auto; display: flex; gap: 8px; flex-wrap: wrap; }
  .stepline { display: flex; gap: var(--space-3); flex-wrap: wrap; padding: var(--space-4) var(--space-6) 0; }
  .stepline span { display: inline-flex; align-items: center; gap: 7px; padding: 8px 14px;
    border-radius: var(--radius-pill); background: var(--shop-surface); border: 1px solid var(--shop-border);
    font-size: var(--text-sm); color: var(--shop-muted); }
  .stepline span.is-on { background: var(--shop-primary); border-color: var(--shop-primary); color: #fff; font-weight: 600; }
  .stepline span.is-done { background: #e8f6ee; border-color: #cbe9d8; color: #0a7a48; }
  .okacts { display: flex; gap: var(--space-3); flex-wrap: wrap; padding: var(--space-5) var(--space-6) 0; }
  .okacts .btn-checkout, .okacts .btn-ghost { width: auto; min-width: 210px; padding: 0 var(--space-6); }

  @media (max-width: 1100px) {
    .shop { grid-template-columns: 1fr; }
    .pdp2 { grid-template-columns: 1fr; }
    .prods { grid-template-columns: repeat(2,1fr); }
    .cart { border-left: 0; border-top: 1px solid var(--shop-border); }
  }
  @media (max-width: 640px) {
    body { display: block; padding: 0 0 72px; }
    .tablet { width: 100%; padding: 0; border-radius: 0; box-shadow: none; background: #fff; }
    .tablet__screen { border-radius: 0; }
    .tablet__hint { display: none; }
    .shop { min-height: 100vh; }
    .shop__nav { flex-wrap: wrap; gap: var(--space-3); padding: var(--space-4); }
    .shop__tabs { order: 3; width: 100%; margin-left: 0; overflow-x: auto; padding-bottom: 2px; }
    .shop__tabs a { flex: none; min-height: 44px; display: inline-flex; align-items: center; }
    .shop__section, .pdp2, .okhead, .shop__crumb, .stepline, .okacts {
      padding-left: var(--space-4); padding-right: var(--space-4); }
    .prods { grid-template-columns: 1fr; }
    .prod { grid-template-columns: 112px 1fr; align-items: center; }
    .prod__img { grid-row: 1 / span 4; height: 112px; }
    .prod .btn { min-height: 44px; }
    .pdp2__info h1, .okhead h1 { font-size: 23px; line-height: 29px; }
    .pdp2__price { font-size: 28px; }
    .okhead__tags { margin-left: 0; }
    .sfields { grid-template-columns: 1fr; }
    .engrave .grid2 { grid-template-columns: 1fr; }
    .sinput { min-height: 44px; font-size: 16px; }
    .qty button { width: 44px; height: 44px; }
    .cart__head, .cart__body, .totals, .cart__foot { padding-left: var(--space-4); padding-right: var(--space-4); }
    .okacts .btn-checkout, .okacts .btn-ghost { width: 100%; min-width: 0; }
    .vitlegend { margin: var(--space-4); width: auto; }
  }
"""

def shop_nav(ativo="Todos os produtos", itens=4):
    tabs = "".join(
      '<a href="03-vitrine-pdv.html"' + (' class="is-active"' if t == ativo else '') + f'>{e(t)}</a>'
      for t in ["Todos os produtos", "Alianças", "Solitários", "Acessórios"])
    return f'''<nav class="shop__nav">
      <span class="shop__logo"><strong>TOMAZELLI</strong><small>ALIANÇAS</small></span>
      <span class="shop__tabs">{tabs}</span>
      <span class="shop__navicons">{ic("search")}
        <a class="shop__bag" href="03-vitrine-pdv.html">{ic("cart")} Sacola <b>{itens}</b></a></span>
    </nav>'''

def sfield(rot, val, obrig=False, hint=None, full=False, ph=False):
    r = '<i>*</i>' if obrig else ""
    h = f'<small>{e(hint)}</small>' if hint else ""
    return (f'<label class="sfield{" sfield--full" if full else ""}"><span>{e(rot)}{r}</span>'
            f'<span class="sinput{" is-ph" if ph else ""}">{e(val)}</span>{h}</label>')

def linha_item(variante, uid, nome, spec, valor, qtd=None):
    q = (f'<span class="qty"><button>−</button><span class="num">{qtd}</span><button>+</button></span>'
         if qtd else "")
    return (f'<div class="line"><span class="line__img">{rings.thumb(variante, uid)}</span>'
            f'<div><h5>{e(nome)}</h5><small>{e(spec)}</small>{q}</div>'
            f'<span class="money">{e(valor)}</span></div>')

# ─────────────────── 07 · FICHA DO PRODUTO NA VITRINE (com preço B2C) ───────────────────
FICHA_VITRINE = '''<div class="sbox">
    <header><h3>Ficha técnica</h3></header>
    <div>
      <div class="srow"><span>Material</span><b>Ouro 18k</b></div>
      <div class="srow"><span>Acabamento</span><b>Diamantado</b></div>
      <div class="srow"><span>Largura</span><b>6mm</b></div>
      <div class="srow"><span>Feitio</span><b>Anel reto</b></div>
      <div class="srow"><span>Peso aproximado</span><b>5,8 g por peça</b></div>
      <div class="srow"><span>Prazo de produção</span><b>Até 7 dias úteis</b></div>
      <div class="srow"><span>Garantia</span><b>12 meses contra defeito de fabricação</b></div>
    </div>
  </div>'''

gal2 = ('<div class="pdp2__gal">'
  f'<div class="pdp2__main">{rings.svg("diamantada", 700)}</div>'
  '<div class="pdp2__thumbs">' + "".join(
    '<span' + (' class="is-on"' if i == 0 else '') + f'>{rings.thumb(v, 710 + i)}</span>'
    for i, v in enumerate(["diamantada", "classica", "fosca", "conforto", "bicolor"])) + '</div>'
  '<p class="pdp2__note">Fotos ilustrativas. A peça é produzida sob encomenda no aro escolhido.</p>'
  + FICHA_VITRINE + '</div>')

def sopt(rot, opcoes, nota=None):
    ch = "".join(f'<span class="schip {c}">{e(t)}</span>' for t, c in opcoes)
    nt = f'<p class="pdp2__note">{e(nota)}</p>' if nota else ""
    return f'<div class="sopt"><span>{e(rot)}</span><div class="sopt__row">{ch}</div>{nt}</div>'

info = f'''<div class="pdp2__info">
  <div>
    <span class="pdp2__ref">Ref. ALTD-6MM · Alianças</span>
    <h1>Aliança Diamantada 6mm</h1>
    <p class="pdp2__note">Ouro 18k · anel · acabamento diamantado, com brilho uniforme e bordas polidas.
      Produção própria, com conferência de peso e de aro peça a peça.</p>
  </div>
  <div>
    <div class="pdp2__price">R$ 265,00</div>
    <p class="pdp2__note">ou <strong>3x de R$ 88,33</strong> — parcelamento simulado, acertado no caixa da loja.</p>
  </div>
  {sopt("Largura", [("4mm", ""), ("5mm", ""), ("6mm", "is-on")])}
  {sopt("Metal", [("Ouro 18k", "is-on"), ("Ouro branco 18k", ""), ("Ouro rosé 18k", ""), ("Prata 950", "is-off")],
        "Prata 950 indisponível neste feitio.")}
  {sopt("Tamanho do aro", [(t, c) for t, c in [
        ("14", ""), ("15", ""), ("16", ""), ("17", ""), ("18", "is-on"), ("19", ""), ("20", ""),
        ("21", ""), ("22", ""), ("24", ""), ("26", ""), ("Outro aro", "")]],
        "Aros fora da grade são produzidos sob encomenda, com 5 dias úteis a mais.")}
  <div class="engrave">
    <header><h4>Gravação adicional</h4><span style="font-size:11px;color:#9a908d">(opcional)</span></header>
    <p style="margin:0 0 10px;font-size:var(--text-sm);color:var(--shop-muted)">Deseja gravação adicional?</p>
    <span class="opt"><span class="radio is-on"></span> Sim, desejo gravação</span>
    <span class="opt"><span class="radio"></span> Não, obrigado</span>
    <div class="grid2">
      <label class="sfield"><span>Texto / nome</span><span class="sinput">Ana &amp; Pedro</span></label>
      <label class="sfield"><span>Data</span><span class="sinput">12/05/2026</span></label>
    </div>
    <div class="spread" style="margin-top:8px">
      <span class="counter">11/20 caracteres · cobrada à parte, por aliança.</span>
      <strong style="font-size:var(--text-sm);color:var(--shop-text)" class="num">R$ 30,00</strong>
    </div>
  </div>
  <div class="sopt"><span>Quantidade</span>
    <div class="sopt__row">
      <span class="qty" style="margin-top:0"><button>−</button><span class="num">1</span><button>+</button></span>
      <span class="pdp2__note" style="align-self:center">Par de alianças = 2 peças.</span>
    </div>
  </div>
  <a class="btn-checkout" href="03-vitrine-pdv.html">{ic("cart")} Adicionar ao carrinho</a>
  <p class="pickup">{ic("store")}
    <span><strong style="color:var(--shop-text)">Retirada exclusiva na loja.</strong> O pedido fica disponível
      para retirada na loja e o pagamento é feito no caixa — não há cobrança online.</span></p>
</div>'''

CARRINHO = [("classica", 720, "Aliança Clássica 4mm", "Ouro 18k · Anel · Polido · Aro 18", "R$ 200,00"),
            ("diamantada", 721, "Aliança Diamantada 6mm", "Ouro 18k · Anel · Diamantado · Aro 18", "R$ 265,00"),
            ("fosca", 722, "Aliança Fosca 6mm", "Ouro 18k · Anel · Fosca · Aro 18", "R$ 165,00"),
            ("cravejada", 723, "Aliança Cravejada 4mm", "Ouro 18k · Anel · Cravejada · Aro 18", "R$ 340,00")]

rail = f'''<aside class="cart">
  <header class="cart__head">{ic("cart")}<h3>Carrinho de compras</h3>
    <span class="stag push">4 itens</span></header>
  <div class="cart__body">{"".join(linha_item(v, u_, n, s, p, qtd=1) for v, u_, n, s, p in CARRINHO)}
    <p class="pickup">{ic("edit")}<span><strong style="color:var(--shop-text)">Gravação “Ana &amp; Pedro”</strong>
      aplicada a 1 aliança · R$ 30,00</span></p>
  </div>
  <div class="totals">
    <div class="row"><span>Subtotal</span><strong>R$ 970,00</strong></div>
    <div class="row"><span>Adicional de gravação</span><strong>R$ 30,00</strong></div>
    <div class="row"><span>Frete</span><strong style="color:#0a7a48">Retirada na loja</strong></div>
    <div class="grand"><span>Total</span><strong>R$ 1.000,00</strong></div>
  </div>
  <div class="cart__foot">
    <a class="btn-checkout" href="03-vitrine-pdv.html">{ic("bag")} Ver carrinho</a>
    <p class="cart__note">O pagamento é realizado no caixa da loja.</p>
  </div>
</aside>'''

sugestoes = "".join(
  f'<div class="prod"><div class="prod__img">{rings.svg(v, 730 + i)}</div>'
  f'<h4>{e(n)}</h4><small>{e(d)}<br>Aro: 18</small>'
  f'<span class="price num">{e(p)}</span>'
  f'<a class="btn btn--secondary btn--sm" href="07-vitrine-produto.html">Ver detalhes</a></div>'
  for i, (v, n, d, p) in enumerate([
    ("classica", "Aliança Clássica 4mm", "Ouro 18k · Anel · Polido", "R$ 200,00"),
    ("fosca", "Aliança Fosca 6mm", "Ouro 18k · Anel · Fosca", "R$ 165,00"),
    ("trabalhada", "Aliança Trabalhada 6mm", "Ouro 18k · Anel · Trabalhada", "R$ 210,00"),
    ("cravejada", "Aliança Cravejada 4mm", "Ouro 18k · Anel · Cravejada", "R$ 340,00")]))

body = f'''
<a class="skip-link" href="#ficha">Ir para a ficha do produto</a>
<div class="tablet">
  <div class="tablet__screen">
    <div class="shop">
      <div class="shop__main" id="ficha">
        {shop_nav()}
        <p class="shop__crumb"><a href="03-vitrine-pdv.html">Todos os produtos</a> ›
          <a href="03-vitrine-pdv.html">Alianças</a> › <b>Aliança Diamantada 6mm</b></p>
        <div class="pdp2">{gal2}{info}</div>
        <section class="shop__section">
          <h3>Você também pode gostar</h3>
          <div class="prods">{sugestoes}</div>
        </section>
      </div>
      {rail}
    </div>
  </div>
  <p class="tablet__hint">Ambiente 3 · Ficha do produto na vitrine — atendimento presencial em tablet na loja do revendedor</p>
</div>
<div class="vitlegend">
  <p class="notice">{ic("shield")}
    <span><strong>Zero vazamento de marca.</strong> Nome, logo, cores e preço desta tela vêm de
    <code>reseller_stores</code> e de <code>reseller_price_rules</code>. O preço exibido é o
    <strong>B2C do revendedor</strong> — nunca o custo B2B. Nenhuma referência a Velaro ou SVD aparece aqui
    (doc 2-9, regra 1).</span></p>
</div>'''
W("07-vitrine-produto.html", page("Vitrine · Aliança Diamantada 6mm", body, extra_css=SHOP_CSS))

# ─────────────────── 08 · PEDIDO REGISTRADO NO BALCÃO (fecha o fluxo do PDV) ───────────────────
cliente = f'''<div class="sbox">
  <header><h3>1 · Identificação do cliente final</h3>
    <span class="stag stag--ok">{ic("check")} Cliente vinculado à loja</span></header>
  <div class="sfields">
    {sfield("Nome completo", "Maria Silva", True, full=True)}
    {sfield("WhatsApp", "(11) 98765-4321", True, "Canal do aviso de retirada.")}
    {sfield("CPF", "123.456.789-00", True, "Usado na nota e na retirada.")}
    {sfield("E-mail", "maria.silva@email.com", False, "Opcional — segunda via do comprovante.")}
    {sfield("Data do casamento", "12/05/2026", False, "Opcional.")}
  </div>
  <div>
    <span class="scheck"><i class="is-on">✓</i>
      <span>Aceito receber novidades e ofertas da <strong>Tomazelli Alianças</strong> por WhatsApp.</span></span>
    <p class="pdp2__note" style="margin-top:8px">Consentimento opcional, registrado com data e revogável a
      qualquer momento. O aviso de retirada é transacional e não depende dele.</p>
  </div>
  <p class="pickup">{ic("user")}
    <span>O cliente final <strong style="color:var(--shop-text)">não tem login</strong>: ele fica cadastrado na
      carteira desta loja e acompanha o pedido pelo WhatsApp.</span></p>
  <div class="sopt"><span>Forma de retirada</span>
    <div class="sopt__row">
      <span class="schip is-on">Retirada na loja</span>
      <span class="schip is-off">Entrega</span>
    </div>
    <p class="pdp2__note">Retirada somente na loja — configuração desta vitrine.</p>
  </div>
</div>'''

aviso = f'''<div class="sbox">
  <header><h3>3 · Aviso automático de retirada</h3>
    <span class="stag">{ic("eye")} Prévia</span></header>
  <p class="pdp2__note" style="margin:0">Quando o pedido chegar à loja, esta mensagem sai sozinha, em nome da
    <strong>Tomazelli Alianças</strong>. O cliente não precisa acompanhar nada além do WhatsApp dele.</p>
  <div class="msgprev">
    <div class="msgprev__head">WhatsApp · para (11) 98765-4321</div>
    <p>Olá, Maria Silva! Seu pedido <strong>#2413</strong> já chegou à loja e está pronto para retirada.</p>
    <p>📍 Rua das Alianças, 123 - Centro</p>
    <p>🕐 Seg. a sex., das 9h às 18h.</p>
    <p class="ok">✓ Estamos te esperando!</p>
  </div>
  <div>
    <div class="srow"><span>Canais</span><b>WhatsApp + e-mail</b></div>
    <div class="srow"><span>Disparo</span><b>Automático, na chegada à loja</b></div>
    <div class="srow"><span>Remetente</span><b>Tomazelli Alianças</b></div>
    <div class="srow"><span>Confirmação de retirada</span><b>No balcão, com documento</b></div>
  </div>
</div>'''

itens = "".join(linha_item(v, 740 + i, n, s, p)
                for i, (v, _u, n, s, p) in enumerate(CARRINHO))

comprovante = f'''<div class="sbox">
  <header><h3>2 · Comprovante do pedido</h3>
    <span class="stag push">{ic("print")} Via da loja</span></header>
  <div>
    <div class="srow"><span>Número do pedido</span><b>#2413</b></div>
    <div class="srow"><span>Registrado em</span><b>24/05/2026 às 15:12</b></div>
    <div class="srow"><span>Atendente</span><b>Camila R. · balcão 1</b></div>
    <div class="srow"><span>Cliente</span><b>Maria Silva · (11) 98765-4321</b></div>
  </div>
  <div>
    <h3>Itens</h3>
    <div class="stack" style="gap:var(--space-3);margin-top:var(--space-3)">{itens}
      <p class="pickup">{ic("edit")}<span><strong style="color:var(--shop-text)">Gravação “Ana &amp; Pedro”
        · 12/05/2026</strong> — 1 aliança · R$ 30,00</span></p>
    </div>
  </div>
  <div>
    <div class="srow"><span>Subtotal</span><b>R$ 970,00</b></div>
    <div class="srow"><span>Adicional de gravação</span><b>R$ 30,00</b></div>
    <div class="srow"><span>Frete</span><b>Retirada na loja</b></div>
    <div class="srow"><span>Total</span><b style="font-size:19px;color:var(--shop-primary)">R$ 1.000,00</b></div>
    <div class="srow"><span>Pagamento</span><b>Recebido no caixa da loja</b></div>
  </div>
  <div>
    <h3>Prazo e retirada</h3>
    <div style="margin-top:var(--space-3)">
      <div class="srow"><span>Produção</span><b>Até 7 dias úteis (gravação inclusa)</b></div>
      <div class="srow"><span>Previsão de chegada à loja</span><b>02/06/2026</b></div>
      <div class="srow"><span>Local de retirada</span><b>Rua das Alianças, 123 — Centro</b></div>
      <div class="srow"><span>Horário</span><b>Seg. a sex., das 9h às 18h</b></div>
    </div>
  </div>
  <p class="pickup">{ic("whats")}
    <span>Assim que o pedido chegar, <strong style="color:var(--shop-text)">a loja avisa a Maria pelo
      WhatsApp e por e-mail</strong>. A retirada é confirmada no balcão com documento.</span></p>
</div>'''

body = f'''
<a class="skip-link" href="#comprovante">Ir para o comprovante</a>
<div class="tablet">
  <div class="tablet__screen">
    <div class="shop shop--single">
      <div class="shop__main" id="comprovante">
        {shop_nav(itens=0)}
        <div class="okhead">
          <span class="okhead__mark">{ic("check")}</span>
          <div>
            <h1>Pedido registrado no balcão</h1>
            <p>Pedido <strong>#2413</strong> · Maria Silva · 24/05/2026 às 15:12 · 4 alianças</p>
          </div>
          <div class="okhead__tags">
            <span class="stag stag--ok">{ic("coin")} Pago no caixa</span>
            <span class="stag stag--wait">{ic("clock")} Aguardando produção</span>
          </div>
        </div>
        <div class="stepline">
          <span class="is-done">{ic("check")} Pedido registrado</span>
          <span class="is-on">{ic("factory")} Em produção</span>
          <span>{ic("truck")} A caminho da loja</span>
          <span>{ic("store")} Pronto para retirada</span>
        </div>
        <div class="pdp2"><div class="stack" style="gap:var(--space-4)">{cliente}{aviso}</div>{comprovante}</div>
        <div class="okacts">
          <a class="btn-checkout" href="#">{ic("print")} Imprimir comprovante</a>
          <a class="btn-ghost" href="#">{ic("whats")} Enviar por WhatsApp</a>
          <a class="btn-ghost" href="03-vitrine-pdv.html">{ic("plus")} Novo atendimento</a>
        </div>
      </div>
    </div>
  </div>
  <p class="tablet__hint">Ambiente 3 · Pedido registrado no balcão — encerra o atendimento presencial iniciado no carrinho do PDV</p>
</div>
<div class="vitlegend">
  <p class="notice">{ic("shield")}
    <span><strong>Zero marca e zero cobrança online.</strong> O pedido nasce vinculado ao revendedor e ao cliente
    da carteira dele; o recebimento é do lojista, no caixa da loja. Nenhuma referência a Velaro ou SVD aparece
    para o consumidor final (doc 2-9 regra 1 · doc 2-10 regra 2).</span></p>
</div>'''
W("08-vitrine-pedido-confirmado.html", page("Vitrine · Pedido #2413 registrado", body, extra_css=SHOP_CSS))

print("\n  7 telas novas geradas por _build_novas_site.py")
