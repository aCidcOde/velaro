# -*- coding: utf-8 -*-
"""Etapa 1 · Site público + login. Campos fiéis ao protótipo, acrescidos do que o Anexo I exige."""
import importlib.util as il
s = il.spec_from_file_location("u", "_ui.py"); u = il.module_from_spec(s); s.loader.exec_module(u)
from _ui import *  # noqa
ic, e, page, card, chip, btn, campo, form, notice = u.ic, u.e, u.page, u.card, u.chip, u.btn, u.campo, u.form, u.notice
site_shell, site_hero, kpis, tabela, stepper, timeline = u.site_shell, u.site_hero, u.kpis, u.tabela, u.stepper, u.timeline
linha_dado, checklist, prodcard, filtros, toggle, drawer = u.linha_dado, u.checklist, u.prodcard, u.filtros, u.toggle, u.drawer
rings = u.rings

W = lambda f, c: (open(f, "w", encoding="utf-8").write(religar(c, f)), print("  ✓", f))

# ══════════════════════════ 1.2 SOBRE NÓS ══════════════════════════
hero = site_hero(
  "A excelência por trás<br>da <b>Velaro</b>.", eyebrow="Quem é a Velaro",
  texto=("A Velaro Alianças é uma marca especializada na fabricação e distribuição de alianças e joias "
         "de alta qualidade para lojistas em todo o Brasil. Unimos tradição, tecnologia e design para "
         "entregar produtos com acabamento impecável, prontos para valorizar sua vitrine e impulsionar suas vendas."),
  ctas=('<div class="hero__ctas">'
        + btn("Seja um revendedor", "gold", "store", "12-site-cadastro.html", sm=False)
        + btn("Ver catálogo", "ghost-gold", "book", "11-site-catalogo.html", sm=False) + '</div>'),
  art=f'<div style="width:290px">{rings.svg("classica", 900)}</div>')

pilar4 = "".join(
  f'<div class="card" style="text-align:center"><div style="display:grid;place-items:center;gap:10px">'
  f'{ic(i, style="width:34px;height:34px;color:var(--color-gold-600)")}'
  f'<h3 class="title" style="font-size:15px;letter-spacing:.06em;text-transform:uppercase">{e(t)}</h3>'
  f'<p style="margin:0;font-size:var(--text-sm);line-height:20px">{e(d)}</p></div></div>'
  for i, t, d in [
    ("factory","Fábrica própria","Produção 100% própria com tecnologia e mão de obra especializada."),
    ("diamond","Qualidade e acabamento superior","Matérias-primas selecionadas e acabamento impecável em cada detalhe."),
    ("support","Atendimento consultivo","Equipe especializada para entender seu negócio e oferecer as melhores soluções."),
    ("truck","Entrega para todo o Brasil","Logística ágil e segura para atender lojistas de todas as regiões do país.")])

numeros = "".join(
  f'<div class="row" style="gap:var(--space-4);align-items:flex-start">'
  f'{ic(i, style="width:30px;height:30px;color:var(--color-gold-400);flex:none")}'
  f'<div><strong style="display:block;color:#fff;font-size:15px">{e(t)}</strong>'
  f'<p style="margin:4px 0 0;font-size:var(--text-sm);line-height:20px;color:rgba(255,255,255,.62)">{e(d)}</p></div></div>'
  for i, t, d in [
    ("diamond","Produção com padrão premium","Processos rigorosos e controle de qualidade em todas as etapas."),
    ("globe","Atendimento nacional","Lojistas atendidos em todo o Brasil com agilidade e atendimento próximo."),
    ("ring","Coleções para diferentes perfis de loja","Modelos que acompanham tendências e diferentes públicos."),
    ("users","Parceria focada em revenda","Condições exclusivas e suporte contínuo para impulsionar seus resultados.")])

body = f'''
<section class="band-light"><div class="band__inner">
  <div class="split" style="--gcols:minmax(0,.9fr) minmax(0,1.6fr);gap:var(--space-8)">
    <div>
      <span class="eyebrow" style="color:var(--color-gold-700)">Nossa história</span>
      <h2 class="display-md" style="margin-top:var(--space-3)">Feita para lojistas.<br>Feita para durar.</h2>
      <p class="lede" style="margin-top:var(--space-4)">Nascemos com o propósito de oferecer alianças que unem
        beleza, resistência e significado. Com fábrica própria e controle de ponta a ponta, garantimos qualidade
        superior, prazos confiáveis e um atendimento próximo, pensado especialmente para o lojista.</p>
      <p class="lede" style="margin-top:var(--space-3)">Mais do que vender alianças, construímos parcerias de longo
        prazo com quem entende o valor de um produto que representa histórias.</p>
    </div>
    <div class="grid g2">{pilar4}</div>
  </div>
</div></section>

<section class="band-dark"><div class="band__inner">
  <div class="split" style="--gcols:minmax(0,1fr) minmax(0,1fr);gap:var(--space-8)">
    <div>
      <span class="eyebrow" style="color:var(--color-gold-300)">Pensado para o seu negócio</span>
      <h2 class="display-md" style="margin-top:var(--space-3)">Pensado para abastecer vitrines com qualidade,
        consistência e confiança.</h2>
      <p class="lede" style="margin-top:var(--space-4);color:rgba(255,255,255,.7)">Cada detalhe da nossa operação é
        guiado por um compromisso: oferecer alianças que encantam seus clientes e fortalecem o seu negócio.</p>
    </div>
    <div>
      <span class="eyebrow" style="color:var(--color-gold-300)">Números que reforçam nossa essência</span>
      <div class="grid g2" style="margin-top:var(--space-4);gap:var(--space-5)">{numeros}</div>
    </div>
  </div>
</div></section>

<section class="band-dark" style="background:var(--color-brand-700);padding:40px var(--space-8)">
  <div class="band__inner row row--wrap" style="gap:var(--space-8)">
    <div style="flex:1;min-width:260px">
      <h2 class="display-sm" style="color:#fff">Vamos crescer juntos?</h2>
      <p class="lede gold" style="margin-top:6px">Faça seu cadastramento como lojista e tenha acesso às condições
        exclusivas da Velaro.</p>
    </div>
    {btn("Solicitar atendimento","gold","user","12-site-cadastro.html",sm=False)}
    {btn("Ver catálogo","ghost-gold","book","11-site-catalogo.html",sm=False)}
  </div>
</section>'''
W("10-site-sobre.html", page("Velaro · Sobre nós", site_shell("Sobre nós", hero, body), body_class="site"))

# ══════════════════════════ 1.3 CATÁLOGO PÚBLICO ══════════════════════════
CAT = [
 ("VL-DM-01","Diamond","Prata 950 | Diamantada","5mm | Acabamento polido","diamond"),
 ("VL-PR-02","Premium Rosé","Ouro Rosé 18k | Fosca","4mm | Acabamento polido","rose"),
 ("VL-CL-03","Clássica","Ouro Amarelo 18k | Polida","4mm | Acabamento polido","classica"),
 ("VL-UB-04","Urbana","Prata 950 | Diamantada","5mm | Acabamento polido","branco"),
 ("VL-UB-05","Urbana Black","Aço | PVD Preto e Dourado","6mm | Acabamento polido","urbana"),
 ("VL-PS-06","Personalizada","Ouro Amarelo 18k | Texturizada","5mm | Acabamento polido","trabalhada"),
 ("VL-FS-07","Essence","Prata 950 | Fosca","4mm | Acabamento polido","branco"),
 ("VL-PR-08","Premium Cravejada","Ouro Rosé 18k | Cravejada","3mm | Acabamento polido","rose"),
 ("VL-DM-09","Diamond Heart","Prata 950 | Diamantada","5mm | Acabamento polido","diamond"),
 ("VL-LI-10","Line","Ouro Amarelo 18k | Fosca","4mm | Acabamento polido","fosca"),
 ("VL-FS-11","Essence Rosé","Ouro Rosé 18k | Fosca","4mm | Acabamento polido","rose"),
 ("VL-DM-12","Diamond Lux","Prata 950 | Cravejada","4mm | Acabamento polido","cravejada"),
]
cards = "".join(
  prodcard(v, 300+i, sku, nome, f"{e(m)}<br>{e(l)}", "",
           acoes=btn("Ver detalhes","secondary",href="#"))
  for i, (sku, nome, m, l, v) in enumerate(CAT))

hero = site_hero("CATÁLOGO VELARO", sub="Coleções que unem design, qualidade e confiança.",
  texto=("Conheça nossas coleções de alianças. Preços e condições comerciais são liberados após "
         "cadastro e aprovação do lojista."),
  art=f'<div style="width:280px">{rings.svg("diamantada", 901)}</div>')

body = f'''
<section class="band-light"><div class="band__inner">
  {filtros("Buscar modelos, coleções ou materiais…",
     [("Coleção","Todas"),("Material","Todos"),("Acabamento","Todos"),("Largura","Todas")],
     acoes='<span class="select-fake">Todos os modelos</span>')}
  <div class="prods" style="margin-top:var(--space-4)">{cards}</div>
  {notice("<strong>Catálogo público sem preço interno.</strong> Condições exclusivas para lojistas disponíveis após aprovação do cadastro.")}
</div></section>

<section class="band-dark" style="background:var(--color-brand-700);padding:40px var(--space-8)" id="contato">
  <div class="band__inner row row--wrap" style="gap:var(--space-8)">
    <div style="flex:1;min-width:260px">
      <h2 class="display-sm" style="color:#fff">Seja um revendedor Velaro.</h2>
      <p class="lede gold" style="margin-top:6px">Tenha acesso às condições especiais, lançamentos e suporte dedicado.</p>
    </div>
    {btn("Fazer cadastro como lojista","gold","user","12-site-cadastro.html",sm=False)}
    {btn("Falar com especialista","ghost-gold","support","#",sm=False)}
  </div>
</section>'''
W("11-site-catalogo.html", page("Velaro · Catálogo público", site_shell("Catálogo", hero, body), body_class="site"))

# ══════════════════════════ 1.4 CADASTRO COMO LOJISTA ══════════════════════════
# Campos do protótipo + os que o Anexo I §3.4 exige e o protótipo não mostra (marcados como novos).
dados_empresa = form([
  campo("Razão social","Digite a razão social da empresa",True),
  campo("Nome fantasia","Digite o nome fantasia",True),
  campo("CNPJ","00.000.000/0000-00",True),
  campo("Inscrição estadual","Digite a inscrição estadual",False,hint="Quando aplicável"),
  campo("Nome do responsável","Digite o nome do responsável",True),
  campo("CPF do responsável / sócio","000.000.000-00",True,hint="Exigido pelo Anexo I §3.4"),
], 3, "Dados da empresa")

endereco = form([
  campo("CEP","00000-000",True,hint="Preenchimento automático do endereço"),
  campo("Endereço","Rua, avenida…",True),
  campo("Número","1234",True),
  campo("Complemento","Sala, bloco…",False),
  campo("Bairro","Centro",True),
  campo("Cidade / UF","Selecione a cidade / UF",True,"select"),
], 3, "Endereço")

contato = form([
  campo("E-mail","seuemail@exemplo.com.br",True),
  campo("WhatsApp","(00) 00000-0000",True),
  campo("Origem do contato","Selecione a origem",True,"select"),
  campo("Criar senha","Mínimo 8 caracteres",True),
  campo("Confirmar senha","Confirme sua senha",True),
], 3, "Contato e acesso")

docs = ('<h3 class="fsec">Documentos</h3><div class="fgrid fgrid--3">' + "".join(
  f'<div class="upload"><span class="upload__ic">{ic("upload")}</span>'
  f'<strong>{e(t)}<i class="req">*</i></strong><small>PDF, PNG ou JPG · máx. 5MB</small></div>'
  for t in ["Contrato social","Documento do sócio / responsável","Cartão ou comprovante do CNPJ"]) + '</div>')

aceites = '<h3 class="fsec">Aceites</h3><div class="stacklist">' + "".join(
  f'<span class="checkline"><span class="cbox is-on">✓</span>{t}</span>' for t in [
    "Declaro que sou lojista / empresa formalizada.",
    "Autorizo a validação automática do meu CNPJ e CNAE.",
    'Li e concordo com a <a href="#" class="link-gold">Política de Privacidade</a> e os <a href="#" class="link-gold">Termos de Uso</a>.',
  ]) + '</div>'

passos = "".join(
  f'<li><span class="num">{n}</span><div><strong>{e(t)}</strong><p>{e(d)}</p></div></li>'
  for n, (t, d) in enumerate([
    ("Cadastro","Preencha seus dados e envie seu cadastro."),
    ("Validação automática CNPJ + CNAE","Nosso sistema valida as informações automaticamente."),
    ("Aprovação final Velaro","Nossa equipe analisa e confirma a compatibilidade."),
    ("Acesso liberado","Você recebe seus acessos e começa a comprar.")], 1))

quem = "".join(f'<li>{ic("check")}{e(t)}</li>' for t in
  ["Joalherias","Lojas de alianças","Empresas com CNPJ","Atividade compatível com o segmento"])

selos = "".join(
  f'<div class="row" style="gap:10px;align-items:flex-start">{ic(i, style="color:var(--color-gold-400);flex:none")}'
  f'<div><strong style="display:block;color:#fff;font-size:var(--text-sm)">{e(t)}</strong>'
  f'<small style="color:rgba(255,255,255,.6);font-size:var(--text-xs);line-height:17px">{e(d)}</small></div></div>'
  for i, t, d in [
    ("shield","Ambiente seguro","Seus dados protegidos com criptografia e privacidade."),
    ("support","Atendimento consultivo","Suporte dedicado para lojistas em todas as etapas."),
    ("tag","Condições exclusivas","Preços e condições especiais para parceiros aprovados."),
    ("book","Catálogo completo","Acesso ao catálogo completo após aprovação.")])

hero = site_hero("CADASTRO COMO LOJISTA",
  sub="Solicite seu acesso à plataforma B2B Velaro.",
  texto=("Cadastro exclusivo para lojistas com CNPJ e atividade compatível com o segmento. Após o cadastro, "
         "seu CNPJ e CNAE passam por validação automática e aprovação final da equipe Velaro."),
  art=f'<div style="width:270px">{rings.svg("cravejada", 902)}</div>')

body = f'''
<section class="band-light"><div class="band__inner">
  <div class="split split--wide">
    <div class="card">
      <div class="card__head"><h2 class="title">{ic("user-plus")} Faça seu cadastro como lojista</h2></div>
      {dados_empresa}{endereco}{contato}{docs}{aceites}
      <a class="btn btn--primary" style="width:100%;margin-top:var(--space-6)" href="13-site-enviada.html">
        Enviar cadastro ›</a>
      <p class="muted" style="text-align:center;margin:var(--space-3) 0 0;font-size:var(--text-xs)">
        {ic("mail")} {ic("whats")} Você receberá atualizações por e-mail e WhatsApp.</p>
    </div>
    <div class="stack">
      <div class="card panel-dark">
        <h3 class="title" style="color:var(--color-gold-300)">Como funciona</h3>
        <ol class="howto">{passos}</ol>
      </div>
      <div class="card panel-dark">
        <h3 class="title" style="color:var(--color-gold-300)">Quem pode se cadastrar?</h3>
        <ul class="cklist cklist--dark">{quem}</ul>
      </div>
      <div class="card panel-dark"><div class="grid g2" style="gap:var(--space-4)">{selos}</div></div>
    </div>
  </div>
</div></section>'''
W("12-site-cadastro.html", page("Velaro · Cadastro como lojista", site_shell("Seja um revendedor", hero, body), body_class="site"))

# ══════════════════════════ 1.5 SOLICITAÇÃO ENVIADA ══════════════════════════
resumo = "".join(linha_dado(k, f"<span>{e(v)}</span>", i) for i, k, v in [
  ("store","Razão social","Tomazelli Alianças Ltda."),
  ("doc","CNPJ","12.345.678/0001-90"),
  ("user","Responsável","Edemar Tomazeli"),
  ("mail","E-mail","contato@tomazelli.com.br"),
  ("pin","Cidade/UF","São Paulo / SP"),
  ("globe","Origem do contato","Site"),
  ("shield","Protocolo","<strong>VEL-2026-0148</strong>"),
])
como = "".join(
  f'<div class="row" style="gap:10px;align-items:flex-start">{ic(i, style="color:var(--color-gold-600);flex:none")}'
  f'<div><strong style="display:block;font-size:var(--text-sm);color:var(--ink)">{e(t)}</strong>'
  f'<small style="color:var(--ink-muted);font-size:var(--text-xs);line-height:18px">{e(d)}</small></div></div>'
  for i, t, d in [
    ("mail","Notificações por e-mail","Enviaremos atualizações sempre que houver novidades."),
    ("whats","Avisos via WhatsApp","Você também será informado pelo WhatsApp cadastrado."),
    ("user","Acompanhe no site","Faça login com o e-mail e senha criados no cadastro para ver o status atualizado a qualquer momento.")])

hero = site_hero("SOLICITAÇÃO ENVIADA", sub="Recebemos seu cadastro com sucesso!",
  texto=("Sua solicitação está em análise pela equipe Velaro. Enquanto isso, você pode acompanhar cada etapa "
         "do processo diretamente aqui no site."),
  art=f'<div style="width:250px">{rings.svg("premium", 903)}</div>')

body = f'''
<section class="band-light"><div class="band__inner">
  <div class="split split--wide">
    <div class="stack">
      <div class="card">
        <div class="row" style="gap:var(--space-4);align-items:flex-start">
          <span class="bigcheck">{ic("check")}</span>
          <div><h2 class="title">Cadastro recebido com sucesso!</h2>
            <p class="lede" style="margin-top:6px">Sua solicitação foi recebida e está em análise.</p>
            <p class="lede" style="margin-top:var(--space-3);font-size:var(--text-sm)">Nossa equipe está verificando as
              informações enviadas. Você será notificado por e-mail e WhatsApp sobre cada atualização, e poderá
              acompanhar o status aqui no site.</p>
            <div class="row row--wrap" style="margin-top:var(--space-5)">
              {btn("Acompanhar minha solicitação","primary","search","14-site-status.html",sm=False)}
              {btn("← Voltar ao início","secondary",href="01-site-publico.html",sm=False)}
            </div>
          </div>
        </div>
      </div>
      {card("Acompanhe o andamento do seu cadastro", stepper([
        ("Cadastro recebido","done","Concluído"),
        ("Validação automática CNPJ e CNAE","now","Em andamento"),
        ("Aprovação final Velaro","todo","Aguardando"),
        ("Acesso liberado","locked","Aguardando")]))}
    </div>
    <div class="stack">
      <div class="card panel-dark">
        <span class="eyebrow" style="color:var(--color-gold-300)">Status atual</span>
        <h2 class="display-sm" style="color:#fff;margin-top:var(--space-2)">{ic("doc")} Em análise</h2>
        <p style="margin-top:var(--space-3);font-size:var(--text-sm);line-height:21px;color:rgba(255,255,255,.7)">
          Assim que houver novidades, entraremos em contato.</p>
      </div>
      {card("Resumo da solicitação", resumo)}
      {card("Como acompanhar sua solicitação", f'<div class="stack">{como}</div>')}
      {notice("<strong>Importante.</strong> Guarde seu e-mail e senha. Eles serão seu acesso para acompanhar e entrar na plataforma quando seu cadastro for aprovado.")}
    </div>
  </div>
</div></section>'''
W("13-site-enviada.html", page("Velaro · Solicitação enviada", site_shell("Seja um revendedor", hero, body,
  foot_pillars=[("shield","Ambiente seguro","Seus dados protegidos com criptografia e confidencialidade."),
                ("brain","Validação automática","Verificação rápida e precisa de CNPJ e CNAE."),
                ("support","Atendimento consultivo","Nossa equipe está pronta para te orientar em cada etapa."),
                ("book","Catálogo liberado","Acesso ao catálogo completo após a aprovação.")]), body_class="site"))

# ══════════════════════════ 1.6 STATUS DA SOLICITAÇÃO ══════════════════════════
ident = "".join(
  f'<div class="identcell">{ic(i, style="color:var(--color-gold-600)")}'
  f'<span><small>{e(k)}</small><strong>{e(v)}</strong></span></div>'
  for i, k, v in [
    ("store","Parceiro","Tomazelli Alianças Ltda."),
    ("shield","Protocolo","VEL-2026-0148"),
    ("user","Responsável","Edemar Tomazelli"),
    ("mail","Login vinculado","contato@tomazelli.com.br"),
    ("clock","Última atualização","Hoje, 10:42")])

ia = checklist([
  ("ok","Consulta de CNPJ","Concluído"),
  ("ok","Validação de CNAE","Concluído"),
  ("wait","Compatibilidade com o segmento","Em análise"),
  ("ok","Verificação de dados cadastrais","Concluído"),
  ("wait","Análise complementar de documentos","Em processamento")])

tl = timeline([
  ("done","Cadastro recebido",None,"09:58"),
  ("done","Solicitação recebida",None,"10:01"),
  ("done","IA iniciou validação",None,"10:05"),
  ("done","Validação de CNPJ concluída",None,"10:10"),
  ("done","Validação de CNAE concluída",None,"10:18"),
  ("now","Em análise atual",None,"10:42")])

dados = "".join(linha_dado(k, e(v), i) for i, k, v in [
  ("doc","CNPJ","32.123.456/0001-78"),
  ("pin","Cidade / UF","Caxias do Sul / RS"),
  ("globe","Origem do contato","Indicação de lojista parceiro"),
  ("whats","WhatsApp","(54) 9 9999-8888"),
  ("mail","E-mail","contato@tomazelli.com.br")])

proximas = "".join(f'<li><span class="num">{n}</span><div><strong>{e(t)}</strong></div></li>'
  for n, t in enumerate([
    "Conclusão da validação automática com IA.",
    "Envio de contrato para aprovação final da equipe Velaro.",
    "Aprovação concluída: acesso liberado à plataforma do parceiro."], 1))

acomp = "".join(
  f'<div class="row" style="gap:10px;align-items:flex-start">{ic(i, style="color:var(--color-gold-600);flex:none")}'
  f'<small style="font-size:var(--text-sm);line-height:20px;color:var(--ink-body)">{e(t)}</small></div>'
  for i, t in [
    ("eye","Veja o andamento sempre que quiser fazendo login na área do parceiro."),
    ("bell","Você receberá atualizações por e-mail e WhatsApp."),
    ("lock","Se aprovado, o mesmo login dará acesso à plataforma completa.")])

hero = site_hero("STATUS DA SUA SOLICITAÇÃO", eyebrow="Área do lojista / Pré-cadastro",
  texto="Acompanhe em tempo real a validação automática do seu cadastro e as próximas etapas até a liberação do acesso.",
  extra=f'<p class="hero__note">{ic("info")} Faça login para acompanhar sua solicitação.</p>',
  art=f'<div style="width:250px">{rings.svg("diamond", 904)}</div>')

body = f'''
<section class="band-light"><div class="band__inner">
  <div class="identbar">{ident}</div>
  <div class="split split--wide" style="margin-top:var(--space-4)">
    <div class="stack">
      {card(None, stepper([
        ("Cadastro recebido","done","Concluído"),
        ("Validação automática","now","Em andamento"),
        ("Aprovação final Velaro","todo","Aguardando"),
        ("Acesso liberado","locked","Bloqueado até aprovação")]))}
      <div class="card">
        <div class="split" style="--gcols:180px minmax(0,1fr);gap:var(--space-6);align-items:center">
          <div style="display:grid;place-items:center;gap:10px;text-align:center">
            {ic("brain", style="width:46px;height:46px;color:var(--color-gold-600)")}
            <strong style="font-size:var(--text-sm);color:var(--ink)">Validação<br>automática com IA</strong>
          </div>
          <div>{ia}</div>
        </div>
      </div>
      <div class="split" style="--gcols:1fr 1fr">
        {card("Linha do tempo da solicitação", tl)}
        {card("Dados da solicitação", dados)}
      </div>
      <div class="row row--wrap">
        {btn("Atualizar status","gold","refresh","#",sm=False)}
        {btn("Falar com nossa equipe","secondary","support","#",sm=False)}
      </div>
    </div>
    <div class="stack">
      <div class="card panel-dark">
        <span class="eyebrow" style="color:var(--color-gold-300)">Status atual</span>
        <h2 class="display-sm" style="color:#fff;margin-top:var(--space-2)">Em validação automática</h2>
        <p style="margin-top:var(--space-3);font-size:var(--text-sm);line-height:21px;color:rgba(255,255,255,.7)">
          Nossa IA já concluiu parte da análise. Assim que esta etapa for finalizada, seu cadastro seguirá para
          aprovação final da equipe Velaro.</p>
      </div>
      {card("Como acompanhar", f'<div class="stack">{acomp}</div>')}
      {card("Próximas etapas", f'<ol class="howto">{proximas}</ol>')}
      {notice("A IA faz a <strong>triagem</strong>. A decisão final é sempre <strong>humana</strong> e fica registrada com justificativa.")}
    </div>
  </div>
</div></section>'''
W("14-site-status.html", page("Velaro · Status da solicitação", site_shell("Seja um revendedor", hero, body,
  foot_pillars=[("shield","Ambiente seguro","Seus dados protegidos com criptografia e segurança."),
                ("brain","Validação automática","Processos com IA para mais agilidade e precisão."),
                ("support","Atendimento consultivo","Nossa equipe está sempre para te apoiar em cada etapa."),
                ("lock","Acesso liberado após aprovação","Acesso total à plataforma após a aprovação final.")]), body_class="site"))

# ══════════════════════════ 1.7 CADASTRO APROVADO ══════════════════════════
agora = "".join(f'<li>{ic("check")}{e(t)}</li>' for t in [
  "Explorar o catálogo completo","Consultar preços exclusivos",
  "Realizar pedidos com agilidade","Acompanhar seus pedidos e novidades"])
passos = "".join(
  f'<li><span class="num">{n}</span><div><strong>{e(t)}</strong><p>{e(d)}</p></div></li>'
  for n, (t, d) in enumerate([
    ("Cadastro","Preencha seus dados e envie seu cadastro."),
    ("Validação automática CNPJ + CNAE","Nosso sistema valida as informações automaticamente."),
    ("Aprovação final Velaro","Nossa equipe analisa e confirma a compatibilidade."),
    ("Acesso liberado","Você recebe seus acessos e começa a comprar.")], 1))

hero = site_hero("CADASTRO APROVADO!", sub="Parabéns! Seu cadastro foi aprovado com sucesso.",
  texto="Agora você já pode acessar a plataforma B2B Velaro e aproveitar todas as vantagens exclusivas para lojistas.",
  art=f'''<div class="notifcard">
    <div class="notifcard__head">{ic("whats", style="color:#25D366")}<strong>WhatsApp</strong><span>agora</span></div>
    <strong style="color:var(--ink)">Velaro Alianças</strong>
    <p>Olá! Seu cadastro foi aprovado. Seu acesso à plataforma B2B já está liberado.</p>
    <span class="notifcard__ok">{ic("check")}</span>
  </div>''')

body = f'''
<section class="band-light"><div class="band__inner">
  <div class="split split--wide">
    <div class="card">
      <h2 class="title">{ic("store")} Seu cadastro foi aprovado</h2>
      {notice("<strong>Seu acesso à plataforma B2B Velaro foi liberado com sucesso.</strong> Você já pode explorar o catálogo, consultar preços e realizar pedidos.","ok")}
      <h3 class="fsec">Próximo passo</h3>
      <div class="row" style="gap:var(--space-4);align-items:flex-start">
        {ic("eye", style="width:34px;height:34px;color:var(--color-gold-600);flex:none")}
        <p class="lede">Acesse sua plataforma e aproveite todas as vantagens exclusivas para lojistas parceiros Velaro.</p>
      </div>
      <a class="btn btn--primary" style="width:100%;margin-top:var(--space-6);min-height:56px" href="02-portal-lojista.html">
        Acessar minha plataforma ›</a>
      <p class="muted" style="text-align:center;margin:var(--space-3) 0 0;font-size:var(--text-xs)">
        {ic("mail")} {ic("whats")} Enviamos os dados de acesso para seu e-mail e WhatsApp.</p>
    </div>
    <div class="stack">
      <div class="card panel-dark"><h3 class="title" style="color:var(--color-gold-300)">Como funciona</h3>
        <ol class="howto">{passos}</ol></div>
      <div class="card panel-dark"><h3 class="title" style="color:var(--color-gold-300)">O que você pode fazer agora?</h3>
        <ul class="cklist cklist--dark">{agora}</ul></div>
    </div>
  </div>
</div></section>'''
W("15-site-aprovado.html", page("Velaro · Cadastro aprovado", site_shell("Seja um revendedor", hero, body,
  autenticado="Tomazelli Alianças"), body_class="site"))

# ══════════════════════════ 0 · LOGIN ══════════════════════════
rotas = "".join(
  f'<div class="routerow"><span class="chip chip--{c}">{e(p)}</span>{ic("arrow-up", style="transform:rotate(90deg);color:var(--ink-muted)")}'
  f'<code>{e(d)}</code><small>{e(o)}</small></div>'
  for p, c, d, o in [
    ("Perfil Master","brand","/backend","Equipe Velaro · exige is_admin + gate access-backend"),
    ("Parceiro Premium","ok","/portal","Revendedor aprovado · tudo escopado por reseller_id"),
    ("Pré-cadastro","warn","/solicitacao/{protocolo}","Acesso limitado ao acompanhamento da própria solicitação"),
    ("Reprovado / inativo","danger","—","Não autentica"),
  ])

body = f'''
<div class="loginwrap">
  <div class="loginaside">
    <div class="row" style="gap:12px">{logo(38)}{wordmark(24)}</div>
    <div>
      <h1 class="display-md" style="color:#fff">Um login.<br>O ambiente certo.</h1>
      <p class="lede" style="color:rgba(255,255,255,.7);margin-top:var(--space-4)">
        O mesmo ponto de entrada identifica o perfil autorizado e direciona o usuário ao ambiente correspondente.</p>
    </div>
    <div class="routerbox">
      <span class="eyebrow" style="color:var(--color-gold-300)">Roteamento por perfil</span>
      <div class="stack" style="margin-top:var(--space-3)">{rotas}</div>
    </div>
    <p class="muted" style="font-size:var(--text-xs);color:rgba(255,255,255,.5)">
      O cliente final não possui login. Ele existe apenas como cliente vinculado à carteira do Parceiro Premium.</p>
  </div>
  <div class="loginmain">
    <div class="card" style="width:100%;max-width:420px">
      <h2 class="title">Entrar</h2>
      <p class="lede" style="margin:6px 0 var(--space-5)">Acesse com o e-mail e a senha do seu cadastro.</p>
      {form([campo("E-mail","seuemail@exemplo.com.br",True,largura=2),
             campo("Senha","••••••••",True,largura=2)], 2)}
      <div class="spread" style="margin-top:var(--space-4)">
        <span class="checkline"><span class="cbox"></span>Manter conectado</span>
        <a class="link-gold" href="#">Esqueci minha senha</a>
      </div>
      <a class="btn btn--primary" style="width:100%;margin-top:var(--space-5)" href="02-portal-lojista.html">Entrar</a>
      <div class="divider" style="margin:var(--space-5) 0"></div>
      <a class="btn btn--secondary" style="width:100%" href="#">{ic("globe")} Entrar com Google</a>
      <p class="muted" style="text-align:center;margin-top:var(--space-5);font-size:var(--text-sm)">
        Ainda não é parceiro? <a class="link-gold" href="12-site-cadastro.html">Cadastre-se como lojista</a></p>
      {notice("Autenticação em duas etapas disponível. Todo login entra em <code>audit_logs</code> (Anexo I §7).","info")}
    </div>
  </div>
</div>'''
W("20-login.html", page("Velaro · Entrar", body, body_class="site"))
