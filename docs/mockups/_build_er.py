# -*- coding: utf-8 -*-
"""Diagrama do banco de dados da plataforma B2B Velaro — gera er-banco.html.

Peça de referência dos mockups: as 42 tabelas do modelo agrupadas por domínio,
com campos, chaves e relações, mais o que ainda é lacuna.

Como o desenho é feito: o layout é CALCULADO aqui (camadas por dependência de
chave estrangeira + ordenação por baricentro + roteamento ortogonal em canais),
e sai como SVG inline com coordenadas absolutas. Nada de Mermaid, nada de CDN,
nada de "deixa o browser resolver". O SVG é largo de propósito e mora dentro de
.table-scroll: no celular ele rola por dentro, sem esticar a página.

Fontes: _mapa.py (as 31 telas e as tabelas que cada uma usa — é de lá que sai a
lista de telas por tabela), docs/telas/*.md e as migrations do scaffold.
"""
import importlib.util as il, collections, math

s = il.spec_from_file_location("u", "_ui.py"); u = il.module_from_spec(s); s.loader.exec_module(u)
g = globals(); g.update({k: getattr(u, k) for k in dir(u) if not k.startswith("__")})
sm = il.spec_from_file_location("mp", "_mapa.py"); mp = il.module_from_spec(sm); sm.loader.exec_module(mp)

W = lambda f, c: (open(f, "w").write(c), print("  ✓", f))
P = lambda t, b: page("Velaro · " + t, b)
esc = e

# ═══════════════════════════════════ DOMÍNIOS ═══════════════════════════════════
DOM = [
 ("catalogo", "Catálogo e estoque", "tag",
  "O que a Velaro fabrica e o que existe em cofre: produto, ficha técnica de joalheria, "
  "SKU por aro, saldo por SKU e campanha promocional B2B."),
 ("revendedores", "Revendedores e clientes", "store",
  "O lojista com CNPJ, a habilitação dele e a carteira de consumidores finais que ele atende. "
  "É aqui que nasce o eixo <code>reseller_id</code>."),
 ("pedidos", "Pedidos", "bag",
  "O pedido do lojista à Velaro: itens com preço congelado, gravação, dois status independentes "
  "(operacional e financeiro) e a linha do tempo."),
 ("financeiro", "Financeiro B2B", "coin",
  "O lote semanal Velaro → lojista, o pagamento dele e a nota fiscal. O dinheiro do consumidor "
  "final não passa por aqui: ele paga no caixa da loja."),
 ("vitrine", "Vitrine white-label", "cart",
  "A loja do lojista: identidade própria, domínio próprio e as regras com que ele forma o preço "
  "ao consumidor sobre o custo Velaro."),
 ("suporte", "Suporte", "support",
  "Chamado Velaro ↔ revendedor, com thread, anexo e nota interna que nunca atravessa para o "
  "outro lado."),
 ("config", "Configuração e operação", "gear",
  "Parâmetro do sistema, trilha de notificação transacional e relatório agendado ou exportado."),
 ("acesso", "Acesso e permissões", "lock",
  "Login único do scaffold e o conjunto ACL. O consumidor final não tem registro aqui — ele não "
  "faz login em lugar nenhum da plataforma."),
]
DOMNOME = {k: n for k, n, _i, _d in DOM}

# ═══════════════════════════════════ TABELAS ═══════════════════════════════════
# (nome, origem, domínio, chave primária, propósito, campos)
def T(nome, origem, dominio, pk, proposito, campos):
    return dict(nome=nome, origem=origem, dominio=dominio, pk=pk, proposito=proposito, campos=campos)

TAB = [
 # ── catálogo ──────────────────────────────────────────────────────────────
 T("collections", "novo", "catalogo", "id",
   "Coleções comerciais do catálogo Velaro (Classic, Diamond, Premium), usadas como aba e filtro no site, no portal e no master.",
   ["name", "slug", "description", "cover_path", "position", "is_active"]),
 T("categories", "novo", "catalogo", "id",
   "Taxonomia hierárquica de produtos (Alianças Tradicionais, Solitários, Acessórios), com auto-relação por parent_id.",
   ["name", "slug", "parent_id", "position"]),
 T("materials", "novo", "catalogo", "id",
   "Tabela de domínio dos materiais (Prata 950, Ouro Amarelo 18k, Ouro Rosé 18k, Aço) — filtro e ficha técnica.",
   ["name", "slug"]),
 T("finishes", "novo", "catalogo", "id",
   "Tabela de domínio dos acabamentos (Polida, Fosca, Diamantada, Cravejada, Texturizada, PVD) — filtro e ficha técnica.",
   ["name", "slug"]),
 T("products", "core", "catalogo", "id",
   "Produto mestre do catálogo. <strong>price é o CUSTO B2B</strong> e só pode ser serializado no Portal do Lojista e no Master — nunca no site público nem na vitrine.",
   ["name", "slug", "sku", "description", "price (custo B2B)", "is_active", "[core] user_id", "[core] meta"]),
 T("product_attributes", "novo", "catalogo", "id (product_id único)",
   "Extensão 1:1 do produto com a ficha técnica de joalheria e os vínculos de taxonomia. É aqui que mora tudo que o core não tem.",
   ["product_id (inferida)", "collection_id", "category_id", "material_id", "finish_id",
    "largura_mm", "formato", "permite_gravacao", "gravacao_max_chars"]),
 T("product_variants", "novo", "catalogo", "id",
   "SKU por tamanho (aro) — a unidade real de estoque e de disponibilidade.",
   ["product_id (inferida)", "sku", "aro (tamanho)", "is_active"]),
 T("product_images", "novo", "catalogo", "id",
   "Galeria do produto com ordenação e imagem principal (carrossel e miniaturas nos três ambientes).",
   ["product_id (inferida)", "path", "position", "is_primary"]),
 T("product_revisions", "novo", "catalogo", "id",
   "Histórico de alterações do produto, exposto na ação rápida “Histórico de alterações” do master.",
   ["product_id (inferida)", "(campos não declarados)"]),
 T("stock_items", "novo", "catalogo", "id (product_variant_id único)",
   "Saldo físico por SKU/aro, propriedade da Velaro. O portal do lojista lê <code>disponivel</code> e nunca escreve.",
   ["product_variant_id", "atual", "reservado", "disponivel", "minimo", "reposicao"]),
 T("stock_movements", "novo", "catalogo", "id",
   "Razão do estoque com before/after. Ajuste manual é ação sensível e exige log (Anexo I §7).",
   ["stock_item_id (inferida)", "type (entrada|saida|ajuste|reserva|producao)", "qty", "before",
    "after", "reason", "actor_id", "order_id"]),
 T("promotions", "novo", "catalogo", "id",
   "Campanha promocional B2B (Velaro → lojista). Não se confunde com a promoção que o revendedor faz na própria vitrine.",
   ["code", "name", "type (desconto_progressivo|preco_especial|frete_gratis|desconto_fixo|lancamento)",
    "starts_at", "ends_at", "status (rascunho|agendada|ativa|pausada|encerrada)", "priority",
    "show_badge", "budget"]),
 T("promotion_rules", "novo", "catalogo", "id",
   "Faixas (tiers) da promoção: “acima de R$ 1.000 → 5%”, “acima de R$ 2.000 → 10%”.",
   ["promotion_id (inferida)", "tiers (limiar → percentual)"]),
 T("promotion_products", "novo", "catalogo", "id (único por promotion_id + alvo)",
   "Pivô que amarra a promoção a produtos e/ou coleções — declarado literalmente como “pivot produto/coleção”.",
   ["promotion_id (inferida)", "product_id (inferida)", "collection_id (inferida)"]),
 T("promotion_audiences", "novo", "catalogo", "id",
   "Público-alvo e canais da campanha (“Todos os revendedores ativos”; Loja online, WhatsApp, E-mail).",
   ["promotion_id (inferida)", "publico_alvo", "canais"]),
 # ── revendedores ──────────────────────────────────────────────────────────
 T("resellers", "novo", "revendedores", "id",
   "O lojista / Parceiro Premium com CNPJ. Entidade central do modelo B2B e origem do escopo <code>reseller_id</code> em todo o resto.",
   ["razao_social", "nome_fantasia", "cnpj", "inscricao_estadual", "responsavel_nome",
    "responsavel_cpf", "email", "telefone", "whatsapp", "cep", "logradouro", "numero",
    "complemento", "bairro", "cidade", "uf", "origem_contato", "observacoes",
    "observacoes_internas", "status (pre_cadastro|aprovado|reprovado|inativo)", "protocolo",
    "code", "approved_at", "approved_by", "rejected_at", "rejection_reason", "created_at"]),
 T("reseller_documents", "novo", "revendedores", "id",
   "Os 3 uploads obrigatórios do cadastro: contrato social, documento do sócio e cartão CNPJ.",
   ["reseller_id (inferida)", "type (contrato_social|documento_socio|cartao_cnpj)",
    "original_name", "disk", "path", "size_bytes", "mime"]),
 T("reseller_cnaes", "novo", "revendedores", "id",
   "CNAEs informados pelo lojista, com a marcação de compatibilidade com o segmento produzida pela triagem por IA.",
   ["reseller_id (inferida)", "code", "description", "is_primary", "compatible"]),
 T("reseller_verifications", "novo", "revendedores", "id",
   "Resultado de cada rodada da verificação automática (VerifyResellerCnpjJob). É triagem, nunca decisão final.",
   ["reseller_id (inferida)", "status", "cnpj_valido", "empresa_ativa", "cnaes_compativeis",
    "documentacao_enviada", "score", "result (json)", "raw_payload", "checked_at"]),
 T("reseller_status_events", "novo", "revendedores", "id",
   "Linha do tempo da solicitação, com ator e justificativa de cada decisão humana: aprovar, pedir informação, reprovar.",
   ["reseller_id (inferida)", "from_status", "to_status", "actor_id", "note", "created_at"]),
 T("customers", "core", "revendedores", "id",
   "O consumidor final. <strong>Não tem login</strong>: existe só como registro na carteira de um revendedor, e o pagamento dele é feito no caixa da loja.",
   ["name", "email", "phone", "document (CPF)", "notes", "[core] user_id", "[core] company_name", "[core] meta"]),
 T("customer_velaro_details", "novo", "revendedores", "id (customer_id único)",
   "Extensão 1:1 do cliente final com o dono da carteira (reseller_id) e as datas de relacionamento que alimentam campanha.",
   ["customer_id (inferida)", "reseller_id", "cidade", "uf", "endereco", "data_nascimento",
    "data_casamento", "data_namoro", "origem_contato"]),
 T("customer_consents", "novo", "revendedores", "id",
   "Histórico de consentimento LGPD. É tabela própria, e não booleano no cliente, porque o consentimento é revogável e precisa de trilha.",
   ["customer_id (inferida)", "type (marketing|transacional)", "granted", "granted_at",
    "revoked_at", "channel", "evidence"]),
 # ── pedidos ───────────────────────────────────────────────────────────────
 T("orders", "core", "pedidos", "id (rota por public_number)",
   "Pedido do core, sempre endereçado por <code>public_number</code> — o id interno nunca é exposto. Nasce em <code>draft</code> no carrinho/PDV da vitrine.",
   ["public_number", "customer_id", "total_amount", "notes", "[core] user_id", "[core] status",
    "[core] reference", "[core] currency", "[core] meta"]),
 T("order_velaro_details", "novo", "pedidos", "id (order_id único)",
   "Extensão 1:1 do pedido com o eixo B2B: de quem é (reseller_id), em que lote entrou (batch_id) e os DOIS status independentes.",
   ["order_id (inferida)", "reseller_id", "batch_id", "operational_status", "payment_status",
    "previsao", "arrived_at", "retirado_em", "retirado_por"]),
 T("order_items", "core", "pedidos", "id",
   "Item do pedido com <code>unit_price</code> congelado no momento da seleção: mudança de preço no catálogo não altera pedido existente.",
   ["order_id (core)", "product_id", "quantity", "unit_price (snapshot imutável)",
    "[core] total_price", "[core] status", "[core] meta"]),
 T("order_item_engravings", "novo", "pedidos", "id",
   "Gravação adicional (sim/não, texto, data), cobrada e discriminada à parte. O limite de caracteres e o preço vêm de settings.",
   ["order_item_id (inferida)", "enabled", "text", "date", "chars", "price"]),
 T("order_status_events", "novo", "pedidos", "id",
   "Timeline operacional do pedido (registrado → pago → produção → transporte → pronto → retirado), com ator e nota.",
   ["order_id (inferida)", "from", "to", "actor_id", "note", "created_at"]),
 # ── financeiro ────────────────────────────────────────────────────────────
 T("order_batches", "novo", "financeiro", "id",
   "Lote semanal de faturamento Velaro → lojista: agrupa pedidos, tem data de corte e vencimento, e é a unidade de pagamento, de NF e de liberação de remessa.",
   ["code", "reseller_id", "cut_date", "due_date", "status", "total_amount", "paid_at", "shipped_at"]),
 T("payments", "novo", "financeiro", "id",
   "Recebimento do lojista PARA a Velaro por meio B2B. Não existe saldo, carteira de recebível B2C nem saque do consumidor.",
   ["batch_id (inferida)", "method (pix|boleto|transferencia)", "amount", "due_date", "paid_at",
    "status", "external_id", "receipt_path"]),
 T("invoices", "novo", "financeiro", "id",
   "NF-e da venda B2B da Velaro ao lojista. A NF do consumidor final é emitida pelo lojista, fora desta plataforma.",
   ["batch_id (inferida)", "number", "series", "amount", "issued_at", "pdf_path", "xml_path", "provider"]),
 # ── vitrine ───────────────────────────────────────────────────────────────
 T("reseller_stores", "novo", "vitrine", "id (reseller_id único; slug e domain únicos)",
   "Identidade white-label da vitrine do lojista. É a ÚNICA fonte de pintura da vitrine (--shop-*) e do roteamento por slug ou domínio próprio.",
   ["reseller_id (inferida)", "name", "slogan", "logo_path", "banner_path", "slug", "domain",
    "phone", "whatsapp", "email", "endereco", "color_primary", "color_secondary",
    "color_background", "color_text", "is_active", "published_at"]),
 T("reseller_price_rules", "novo", "vitrine", "id",
   "Regras com que o lojista forma o preço B2C sobre o custo Velaro (multiplicador, percentual, manual, promo), resolvidas por prioridade no ResellerPriceResolver.",
   ["reseller_id (inferida)", "scope (global|collection|product)", "collection_id", "product_id",
    "mode (multiplier|percent|manual|promo)", "value", "rounding", "priority", "is_active"]),
 # ── suporte ───────────────────────────────────────────────────────────────
 T("support_tickets", "novo", "suporte", "id (rota por code)",
   "Chamado Velaro ↔ revendedor, opcionalmente vinculado a um pedido e ao cliente final — que aparece como pessoa citada, nunca como participante.",
   ["code", "reseller_id", "order_id", "customer_id", "subject", "category", "priority", "status",
    "assignee_id", "channel"]),
 T("support_messages", "novo", "suporte", "id",
   "Mensagens da thread. <code>is_internal_note</code> marca observação interna que NUNCA pode ser exposta ao revendedor.",
   ["ticket_id (inferida)", "author_id", "author_role (revendedor|velaro)", "body", "is_internal_note"]),
 T("support_attachments", "novo", "suporte", "id",
   "Anexos do atendimento (fotos, PDFs) exibidos tanto na mensagem quanto na lista consolidada do chamado.",
   ["message_id (inferida)", "original_name", "path", "size_bytes", "mime"]),
 # ── config ────────────────────────────────────────────────────────────────
 T("settings", "novo", "config", "id (chave lógica key)",
   "Configuração chave/valor por grupo: alimenta o conteúdo institucional do site, os parâmetros de gravação e de lote, e a tela de Configurações do Master.",
   ["company.* (nome, razão social, CNPJ, IE, endereço, logo)",
    "contact.* (telefone, e-mail, horário de atendimento)",
    "about.* (história, fábrica própria, diferenciais, mídia)",
    "gravacao.max_chars", "gravacao.preco",
    "grupos (empresa, notificações, integrações, segurança, financeiro/fiscal, personalização, backup, parâmetros de pedido, meios de pagamento B2B)"]),
 T("notification_logs", "novo", "config", "id",
   "Trilha de envio dos avisos transacionais (aprovação de cadastro, pedido pronto para retirada) por e-mail e WhatsApp, sempre disparados por job.",
   ["type (cadastro_aprovado, pedido_pronto)", "channel (email|whatsapp)", "recipient",
    "recipient_type (revendedor|cliente)", "sent_at", "provider_message_id", "status"]),
 T("report_schedules", "novo", "config", "id",
   "Agendamento recorrente de relatório (“Toda segunda às 08:00”), com destinatários e formato.",
   ["name", "type", "cron", "recipients", "format", "is_active", "last_run_at"]),
 T("report_exports", "novo", "config", "id",
   "Cada exportação já gerada, com os filtros usados e o arquivo. Exportação pesada roda sempre em job.",
   ["type", "filters (json)", "file_path", "generated_by", "generated_at"]),
 # ── acesso ────────────────────────────────────────────────────────────────
 T("users", "extensao", "acesso", "id",
   "Login único do scaffold. Master e equipe Velaro entram por <code>is_admin</code> + gate access-backend. "
   "<strong>É a única tabela do core que ganha coluna</strong> (reseller_id) em vez de tabela 1:1 — ver lacuna 17.",
   ["name", "email", "password", "is_admin", "two_factor_*", "google_id", "reseller_id (extensão)"]),
 T("acl_*", "core", "acesso", "id (por tabela do conjunto)",
   "Conjunto ACL do scaffold (acl_permissions, acl_responsibilities, acl_responsibility_permission, "
   "acl_user_responsibility, acl_user_permission_overrides) que sustenta as ~30 permissões <code>velaro.*</code> e os gates do Master.",
   ["(campos não declarados)"]),
]
IDX = {t["nome"]: t for t in TAB}

# ═══════════════════════════════════ RELAÇÕES ═══════════════════════════════════
# (de = lado N, carrega a FK) · (para = lado 1, referenciado) · leitura: para(1):de(N)
def R(de, para, campo, card, conf, motivo):
    return dict(de=de, para=para, campo=campo, card=card, conf=conf, motivo=motivo)

REL = [
 # ── declaradas: a coluna aparece literalmente na lista de campos de alguma tela ──
 R("product_attributes","collections","collection_id","1:N","declarada",
   "<code>collection_id</code> está na lista de campos de product_attributes nas telas 1.3 e 3.7. Uma coleção agrupa N produtos."),
 R("product_attributes","categories","category_id","1:N","declarada",
   "<code>category_id</code> na lista de campos de product_attributes (1.3, 3.7)."),
 R("product_attributes","materials","material_id","1:N","declarada",
   "<code>material_id</code> na lista de campos de product_attributes (1.3, 3.7); materials é tabela de domínio (Ouro 18K, Prata 950)."),
 R("product_attributes","finishes","finish_id","1:N","declarada",
   "<code>finish_id</code> na lista de campos de product_attributes (1.3, 3.7); finishes é tabela de domínio (polido, fosco, diamantado)."),
 R("categories","categories","parent_id","1:N","declarada",
   "<code>parent_id</code> na lista de campos de categories (1.3, 3.7) — auto-relação para a árvore de categorias."),
 R("stock_items","product_variants","product_variant_id","1:1","declarada",
   "<code>product_variant_id</code> na lista de campos de stock_items (3.4). Regra da tela: “controle por SKU/tamanho (aro)” — uma linha de saldo por variante. "
   "Atenção: o filtro “Local” e o campo “Local de armazenamento” do protótipo empurrariam isso para 1:N por local, mas nenhuma tabela de local foi declarada."),
 R("users","resellers","reseller_id","1:N","declarada",
   "A tela 0 declara a linha users (extensão) com “reseller_id — vínculo com o Parceiro Premium”, e a 1.7 diz que a aprovação “cria o vínculo users.reseller_id”. "
   "Como a FK fica em users, um revendedor comporta N logins; as telas, porém, só exercitam um login por lojista."),
 R("orders","customers","customer_id","1:N","declarada",
   "<code>customer_id</code> na lista de campos de orders (2.5, 3.6) e confirmado na migration do core (orders.customer_id, nullOnDelete). O consumidor final acumula vários pedidos na carteira do lojista."),
 R("order_velaro_details","resellers","reseller_id","1:N","declarada",
   "<code>reseller_id</code> na lista de campos de order_velaro_details (2.5, 3.6). É a coluna que sustenta a policy ResellerScope — pedido pertence a exatamente um lojista."),
 R("order_velaro_details","order_batches","batch_id","1:N","declarada",
   "<code>batch_id</code> na lista de campos de order_velaro_details (2.5, 3.6); o lote semanal agrupa N pedidos (3.5 mostra “Pedidos vinculados: #8765, #8766, #8767”)."),
 R("order_items","products","product_id","1:N","declarada",
   "<code>product_id</code> na lista de campos de order_items (2.5); confirmado na migration do core."),
 R("order_status_events","users","actor_id","1:N","declarada",
   "<code>actor_id</code> na lista de campos de order_status_events (2.5) — quem executou a transição. Provavelmente nulo quando a transição vem de job/sistema."),
 R("reseller_status_events","users","actor_id","1:N","declarada",
   "<code>actor_id</code> na lista de campos de reseller_status_events (1.6) — a decisão final é humana (Anexo I §3.7) e fica registrada com o ator."),
 R("stock_movements","users","actor_id","1:N","declarada",
   "<code>actor_id</code> na lista de campos de stock_movements (3.4); o protótipo mostra “Ajuste manual −2 (Admin)”."),
 R("stock_movements","orders","order_id","1:N","declarada",
   "<code>order_id</code> na lista de campos de stock_movements (3.4); o protótipo mostra “Reserva −6 (Pedido #5841)” nas últimas movimentações."),
 R("support_tickets","resellers","reseller_id","1:N","declarada",
   "<code>reseller_id</code> na lista de campos de support_tickets (2.8, 3.12) — a conversa é Velaro ↔ revendedor."),
 R("support_tickets","orders","order_id","1:N","declarada",
   "<code>order_id</code> na lista de campos de support_tickets (2.8, 3.12); o protótipo 3.12 mostra “Pedido relacionado (PED-2025-0587)”. Nulo em chamado sem pedido."),
 R("support_tickets","customers","customer_id","1:N","declarada",
   "<code>customer_id</code> na lista de campos de support_tickets (2.8, 3.12); o cliente final aparece só como pessoa vinculada ao pedido e não participa da thread."),
 R("support_tickets","users","assignee_id","1:N","declarada",
   "<code>assignee_id</code> na lista de campos de support_tickets (2.8, 3.12); a tela 3.12 tem “Responsável (Equipe Velaro Suporte)” e “Transferir atendimento”, coberto pela permissão velaro.support.assign."),
 R("support_messages","users","author_id","1:N","declarada",
   "<code>author_id</code> na lista de campos de support_messages (2.8). <code>author_role</code> (revendedor|velaro) só rotula o papel; os dois lados são usuários autenticados."),
 R("customer_velaro_details","resellers","reseller_id","1:N","declarada",
   "<code>reseller_id</code> na lista de campos de customer_velaro_details (2.3). Regra 3.2: a base de clientes é consultada “sempre com o revendedor responsável identificado” — cada cliente final pertence à carteira de um lojista."),
 R("order_batches","resellers","reseller_id","1:N","declarada",
   "<code>reseller_id</code> na lista de campos de order_batches (3.5); o lote é a fatura semanal de UM lojista (a coluna REVENDEDOR aparece na tabela de lotes)."),
 R("reseller_price_rules","collections","collection_id","1:N","declarada",
   "<code>collection_id</code> na lista de campos de reseller_price_rules (2.7); preenchido só quando scope = collection."),
 R("reseller_price_rules","products","product_id","1:N","declarada",
   "<code>product_id</code> na lista de campos de reseller_price_rules (2.7); preenchido só quando scope = product."),
 R("resellers","users","approved_by","1:N","declarada",
   "<code>approved_by</code> na lista de campos de resellers (1.7, 3.10) — quem, na equipe Velaro, aprovou o cadastro. Aprovar é ação sensível e exige log (Anexo I §7)."),
 R("report_exports","users","generated_by","1:N","declarada",
   "<code>generated_by</code> na lista de campos de report_exports (3.9)."),
 # ── inferidas: nome da tabela ou regra de negócio ──
 R("product_attributes","products","product_id","1:1","inferida",
   "Nome da tabela mais a nota repetida em todo doc de tela: “o domínio Velaro entra em tabelas próprias e em tabelas 1:1 de extensão”. A tela 3.7 edita produto e atributos no mesmo formulário, e a 1.3 lê os dois juntos."),
 R("product_variants","products","product_id","1:N","inferida",
   "Nome da tabela; a tela 3.4 mostra “Estoque por tamanho” com várias faixas de aro para o mesmo produto, e a 2.2 fala em “disponibilidade por tamanho”."),
 R("product_images","products","product_id","1:N","inferida",
   "Nome da tabela mais os campos <code>position</code>/<code>is_primary</code>, que só fazem sentido numa galeria; a tela 2.2 mostra “galeria de miniaturas (5 + seta)” e a 3.7 tem “Gerenciar imagens”."),
 R("product_revisions","products","product_id","1:N","inferida",
   "Nome da tabela mais a ação rápida “Histórico de alterações” dentro do detalhe do produto (3.7)."),
 R("reseller_documents","resellers","reseller_id","1:N","inferida",
   "Nome da tabela mais a regra 1.4 “Upload de 3 documentos” e o painel “Documentos anexados (3 cards)” das telas 3.10 e 3.11."),
 R("reseller_cnaes","resellers","reseller_id","1:N","inferida",
   "Nome da tabela mais “CNAEs informados” listados como vários por empresa nas telas 3.10 e 3.11 (com <code>is_primary</code> distinguindo o principal)."),
 R("reseller_verifications","resellers","reseller_id","1:N","inferida",
   "Nome da tabela. 1:N (e não 1:1) porque a tela 3.10 tem o botão “Verificar CNAEs com IA”, que pode ser reexecutado, e os campos <code>checked_at</code>/<code>raw_payload</code> datam cada rodada."),
 R("reseller_status_events","resellers","reseller_id","1:N","inferida",
   "Nome da tabela mais a regra 1.6: alimenta a linha do tempo da solicitação (cadastro recebido → validação → aprovação → liberação), com N eventos por revendedor."),
 R("reseller_stores","resellers","reseller_id","1:1","inferida",
   "Nome da tabela mais a rota singular GET/PUT /portal/loja (2.6) e o roteamento da vitrine por <code>slug</code>/<code>domain</code> único (2.9): uma vitrine por lojista. Nada nas telas prevê um segundo storefront."),
 R("reseller_price_rules","resellers","reseller_id","1:N","inferida",
   "Regra de negócio da tela 2.7: acesso “escopado por reseller_id” e policy ResellerScope. O preço B2C é de cada lojista, e <code>scope</code>/<code>priority</code> implicam várias regras por lojista."),
 R("order_velaro_details","orders","order_id","1:1","inferida",
   "O mapa declara o par como “orders + order_velaro_details” (2.1, 2.10) e a nota fixa de todo doc diz que a extensão do core é feita por tabela 1:1 — o core não é mutado."),
 R("customer_velaro_details","customers","customer_id","1:1","inferida",
   "O mapa declara o par como “customers + customer_velaro_details” (2.1, 3.2), mesma regra de extensão 1:1 do core."),
 R("customer_consents","customers","customer_id","1:N","inferida",
   "Nome da tabela mais a regra 2.3: “o consentimento é registrável e revogável — por isso tabela própria com histórico, não booleano no cliente”, e há tipos separados (marketing|transacional)."),
 R("order_items","orders","order_id","1:N","inferida",
   "Nome da tabela; não aparece na lista de campos de nenhuma tela, mas a migration do core (2025_11_18_075651) declara order_items.order_id com cascadeOnDelete, e os protótipos mostram “Itens do pedido (3)”."),
 R("order_item_engravings","order_items","order_item_id","1:1","inferida",
   "O nome da tabela ancora a gravação no ITEM. Ressalva: os protótipos 2.5 e 2.10 mostram um único bloco de gravação para o pedido inteiro (“Gravação interna (1 unidade) R$ 35,00”), então a granularidade item × pedido ainda está em aberto — o nome declarado foi o critério."),
 R("order_status_events","orders","order_id","1:N","inferida",
   "Nome da tabela mais a timeline de 7 etapas por pedido descrita nas telas 2.5 e 3.6."),
 R("payments","order_batches","batch_id","1:N","inferida",
   "Regra de negócio, não nome: o drawer “Pagamento à Velaro / Pagar lote semanal” (2.4) e o fluxo do Master (3.5) “recebimento identificado → baixa financeira” tratam o LOTE como a unidade de pagamento. 1:N para admitir tentativa, estorno ou segundo comprovante no mesmo lote."),
 R("invoices","order_batches","batch_id","1:N","inferida",
   "Regra de negócio: o drawer do lote em 3.5 exibe a NF dentro do lote (“Nota fiscal emitida e enviada” como passo 3 do fluxo). Ressalva: a tela 2.4 mostra uma coluna NF-E por PEDIDO, então a granularidade lote × pedido está em conflito entre as duas telas (ver lacuna 5)."),
 R("stock_movements","stock_items","stock_item_id","1:N","inferida",
   "Nome da tabela mais o drawer do item de estoque em 3.4, que lista “Últimas movimentações” daquele SKU com before/after."),
 R("support_messages","support_tickets","ticket_id","1:N","inferida",
   "Nome da tabela mais a thread de conversa por chamado nas telas 2.8 e 3.12."),
 R("support_attachments","support_messages","message_id","1:N","inferida",
   "Nome da tabela mais o protótipo 3.12, que mostra o anexo dentro da mensagem (“foto_alianca_recebida.jpg · 1.2 MB”). Ressalva: a mesma tela tem um painel “Anexos” consolidado no chamado, que pode exigir também um ticket_id."),
 R("promotion_rules","promotions","promotion_id","1:N","inferida",
   "Nome da tabela mais a descrição declarada “tiers — acima de X → Y% de desconto” e a prévia com três faixas (5%/10%/15%) na tela 3.8."),
 R("promotion_audiences","promotions","promotion_id","1:N","inferida",
   "Nome da tabela mais a aba “Público-alvo” e o resumo “Canais (Loja online, WhatsApp, E-mail)” da tela 3.8."),
 R("promotions","products","promotion_products.product_id","N:N","inferida",
   "A própria declaração de promotion_products em 3.8 é “pivot produto/coleção” — um pivô com promotion_id + product_id. Uma promoção cobre N produtos e um produto pode entrar em N campanhas (a tela tem <code>priority</code> justamente para desempatar)."),
 R("promotions","collections","promotion_products.collection_id","N:N","inferida",
   "Mesma declaração “pivot produto/coleção” (3.8): o alvo da campanha pode ser a coleção inteira em vez do produto avulso."),
]

# ═══════════════════════════════════ LACUNAS ═══════════════════════════════════
LAC = [
 ("audit_logs não é declarado por nenhuma tela", "trilha",
  "audit_logs não aparece em NENHUMA lista de tabelas das 31 telas, mas é exigido pelo critério de aceite de todas elas "
  "(“Escrita no backend gera registro em audit_logs”) e por 5 regras críticas: login, configurações, aprovação/reprovação de "
  "cadastro, baixa financeira e ajuste de estoque. A tabela existe no core (migration 2026_01_22_143503, com actor_id, action, "
  "target morfológico, before/after, ip_address, user_agent) — a lacuna é de DECLARAÇÃO, não de schema. Falta explicitar o que "
  "grava e, principalmente, o par de eventos início/fim da sessão de “Ver como revendedor” (impersonate), que a tela 3.10 exige nominalmente."),
 ("products.slug não existe no core", "conflito",
  "<code>products.slug</code> é declarado pelas telas 1.3 e 3.7 e é a chave da rota pública GET /produto/{slug}, mas a tabela "
  "products do core NÃO tem coluna slug (migration 2025_11_18_075644: name, sku, description, price, is_active, meta) — e a regra "
  "do mapa é que o core não pode ser mutado. Ou o slug vira coluna de product_attributes, ou a regra de imutabilidade do core "
  "precisa abrir exceção. O mesmo vale para <code>collections.slug</code>/<code>categories.slug</code>, que são novos e não têm esse problema."),
 ("Não há tabela de remessa / transporte", "faltando",
  "A regra 4 da tela 3.6 é explícita: “campos e status de transporte já entram no escopo mesmo sem a API da transportadora (§7.2)”. "
  "A 3.5 tem “Liberação logística”, “Previsão de envio: 31/05/2024” e “próxima remessa semanal”; o estado “Em transporte para a loja” "
  "está na timeline. O único campo existente é <code>order_batches.shipped_at</code> — falta transportadora, código de rastreio, data "
  "de expedição, previsão e status da remessa."),
 ("Pedido não tem frete nem desconto", "faltando",
  "Os protótipos 2.5 (“Subtotal R$ 450,00 · Gravação R$ 35,00 · Frete R$ 0,00 · Descontos R$ 0,00 · Total”), 2.10 e o drawer de 2.4 "
  "mostram as quatro linhas, mas orders só tem total_amount e order_velaro_details não declara nenhuma delas. Falta também o vínculo do "
  "pedido com a promoção aplicada: promotions existe, mas nada liga uma campanha a um pedido, e o tipo frete_gratis exige exatamente esse vínculo."),
 ("Granularidade da nota fiscal em conflito", "conflito",
  "A tela 3.5 emite a NF dentro do fluxo do LOTE (passo 3 de 5), mas a 2.4 tem uma coluna “NF-E (Baixar NF | —)” linha a linha por PEDIDO. "
  "invoices não declara nem batch_id nem order_id — é preciso decidir antes de migrar, porque a escolha muda a chave do documento fiscal."),
 ("notification_logs não diz a quem se refere", "faltando",
  "Tem type, channel, recipient e recipient_type (revendedor|cliente), mas nenhuma FK para orders, resellers ou customers. Sem isso, a tela "
  "2.11 (“painel de disparo/histórico” com reenvio) e o bloco “Notificações enviadas” da 3.6 não conseguem listar os envios de um pedido."),
 ("order_velaro_details.retirado_por não tem alvo possível", "conflito",
  "O cliente final não tem login (regra da tela 0), então a coluna não pode apontar para users. Falta definir se é texto livre (nome de quem "
  "retirou), documento, ou FK para customers — e a tela 3.6 ainda distingue “Confirmar retirada por pedido” de “Confirmar retirada do lote "
  "inteiro”, o que sugere que a confirmação por lote também precisa de campo próprio em order_batches."),
 ("Os aceites do lojista não têm tabela", "faltando",
  "A regra 2 da tela 1.4 é literal: “aceites (termos + LGPD) gravados com data, IP e versão do texto”, e o protótipo tem 3 checkboxes "
  "obrigatórios (declaração de lojista, autorização de validação de CNPJ/CNAE, política de privacidade). customer_consents cobre só o "
  "CONSUMIDOR FINAL; nada cobre o revendedor. É requisito de LGPD, não detalhe."),
 ("As configurações da vitrine não cabem em reseller_stores", "faltando",
  "A tela 2.9 lista toggles (“Mostrar preços ao cliente final”, “Retirada somente na loja”, “Pagamento realizado diretamente na loja”, "
  "“Exibir apenas a marca do lojista”) e a 2.6 acrescenta “Ocultar marca do fornecedor” — nenhum existe entre os 17 campos declarados. "
  "Faltam também “Categorias visíveis” (N:N revendedor × categories) e “Destaque de produtos — 12 produtos selecionados” (N:N revendedor × products)."),
 ("Os parâmetros de margem do lojista não têm onde morar", "faltando",
  "reseller_price_rules guarda regras pontuais (scope/mode/value/rounding/priority), mas as telas 2.6 e 2.7 pedem configuração global por "
  "revendedor: multiplicador padrão (3,6x), margem global (50%), margem mínima/ideal/máxima (40/50/60%), arredondamento global (“Para cima "
  "(0,99)”) e três toggles. Ou vira uma linha scope=global com semântica ampliada, ou pede uma tabela reseller_price_settings 1:1."),
 ("Estoque não tem local", "faltando",
  "A tela 3.4 tem o filtro “Local (Todos)” e a ficha “Local de armazenamento: Matriz - Cofre A1”; stock_items não tem coluna de local e não "
  "existe tabela de locais. Se houver mais de um local, a relação stock_items → product_variants deixa de ser 1:1."),
 ("Solicitação de produção / reposição não é entidade", "faltando",
  "Há a permissão velaro.stock.request_production, o botão “Solicitar produção”, o KPI “Reposições pendentes 23” e o atalho “Reposição "
  "sugerida — 20 unidades (Gerar pedido →)”, mas o único registro é stock_movements.type='producao', que é um lançamento consumado e não uma "
  "solicitação com status, quantidade pedida e prazo."),
 ("support_tickets não cobre metade do painel da 3.12", "faltando",
  "Faltam TAGS (Troca, Tamanho, Aliança, Ouro 18K), Ambiente (Produção), Navegador, Sistema operacional e IP de acesso. O campo "
  "<code>channel</code> existe e cobre “Canal de origem (Portal do Revendedor)”. Falta também o histórico de status do chamado — "
  "support_messages não serve, porque o protótipo mostra uma timeline separada de transições com ator e data."),
 ("Nada cobre conteúdo de ajuda nem lead do site", "faltando",
  "A tela 2.8 oferece “Perguntas frequentes”, “Guias e manuais”, “Vídeos tutoriais” e “Central de ajuda completa”; o site público (1.2, 1.3) "
  "tem “Fale Conosco”, “SOLICITAR ATENDIMENTO” e “FALAR COM ESPECIALISTA”. Nem FAQ/artigos nem contatos/leads existem no modelo "
  "(settings.about.* cobre só texto institucional)."),
 ("Duas escalas de escopo sem regra de precedência", "conflito",
  "O core escopa customers, products e orders por <code>user_id</code> (as três migrations têm foreignId('user_id')->cascadeOnDelete), enquanto "
  "o módulo Velaro escopa por <code>reseller_id</code> em tabelas de extensão. Nenhuma tela diz o que vai em orders.user_id / customers.user_id / "
  "products.user_id — e um cascadeOnDelete no usuário errado apaga a carteira de um lojista inteiro."),
 ("Três status de pedido convivem sem hierarquia", "conflito",
  "<code>orders.status</code> do core (draft → awaiting_payment → paid → in_progress → completed, com canceled/error terminais) e os dois novos e "
  "independentes de order_velaro_details (<code>operational_status</code> com 7 estados e <code>payment_status</code>). As telas 2.5/3.6 só falam "
  "dos dois novos; nada diz se orders.status continua sendo escrito, quem manda no conflito, e o que o carrinho grava ao nascer em draft."),
 ("users.reseller_id é a única mutação do core", "conflito",
  "Todas as outras extensões (orders, customers) usam tabela 1:1, e a nota fixa de cada doc afirma “nenhuma tabela do núcleo é mutada”. Ou a "
  "afirmação precisa de ressalva, ou o vínculo vira uma tabela reseller_user."),
 ("Detalhes literais do protótipo sem coluna", "faltando",
  "“TIPO DE CADASTRO (Automático | Manual)” do revendedor na 3.10 (resellers.origem_contato é outra coisa — Site/Indicação); “TIPO (Pessoa Física | "
  "Pessoa Jurídica)” do cliente final e o filtro correspondente na 3.2; “Prazo de entrega (Até 2 dias úteis)” e o regime “Sob encomenda” do produto "
  "na 2.2 (stock_items.disponivel não distingue “sob encomenda” de “sem estoque”); e o favoritar (♡) presente nos cards da vitrine (2.9/2.10) e do "
  "catálogo público (1.3), que não tem tabela alguma."),
]

# ═════════════════════════ TELAS QUE USAM CADA TABELA ═════════════════════════
# Fonte: _mapa.py. As linhas combinadas ("orders + order_velaro_details",
# "users / acl_*") são desdobradas nas tabelas que representam.
DESDOBRA = {"orders + order_velaro_details": ["orders", "order_velaro_details"],
            "customers + customer_velaro_details": ["customers", "customer_velaro_details"],
            "users / acl_*": ["users", "acl_*"]}
TELA = {}      # n -> (titulo, arquivo, slug)
USOS = collections.defaultdict(list)
for t in mp.T:
    TELA[t["n"]] = (t["titulo"], t.get("arquivo"), t["slug"])
    for nome, _o, _c in t["tabelas"]:
        for alvo in DESDOBRA.get(nome, [nome]):
            if alvo in IDX and t["n"] not in USOS[alvo]:
                USOS[alvo].append(t["n"])
_ordem = {t["n"]: i for i, t in enumerate(mp.T)}
for k in USOS:
    USOS[k].sort(key=lambda n: _ordem[n])

# ═════════════════════════ GRAFO DESENHÁVEL ═════════════════════════
# O N:N é desenhado PELO PIVÔ (promotion_products), que é tabela de verdade:
# a linha "promotions —N:N— products" da tabela de relações vira duas arestas.
LIG = []
_vis = set()
def _lig(de, para, campo, card, conf):
    if (de, para, campo) in _vis: return
    _vis.add((de, para, campo)); LIG.append((de, para, campo, card, conf))
for r in REL:
    if r["card"] == "N:N" and "." in r["campo"]:
        piv, col = r["campo"].split(".")
        _lig(piv, r["para"], col, "1:N", r["conf"])
        alvo = r["de"]
        _lig(piv, alvo, (alvo[:-1] if alvo.endswith("s") else alvo) + "_id", "1:N", "inferida")
    else:
        _lig(r["de"], r["para"], r["campo"], r["card"], r["conf"])

FKS = collections.defaultdict(dict)          # tabela -> {campo: (para, card, conf)}
for de, para, campo, card, conf in LIG:
    FKS[de][campo] = (para, card, conf)

# ═════════════════════════ MÉTRICA DAS CAIXAS ═════════════════════════
BOXW, BOXW2, GAP, VGAP = 226, 402, 112, 26
HEADH, ROWH, PADT, PADB = 44, 16, 7, 11
GW, GH = 200, 48                              # caixa-fantasma (tabela de outro domínio)
LANE = 13

def _limpa(c):
    """Nome de coluna como ele entra no desenho: sem parêntese, sem nota."""
    c = c.replace("[core] ", "")
    for sep in (" (", " —", " ["):
        i = c.find(sep)
        if i > 0: c = c[:i]
    return c.strip()

def _linhas(t, ncols):
    """(texto, tipo) por linha da caixa. tipo: pk | fk | core | livre"""
    lim = 24 if ncols == 2 else 27
    out = [("id", "pk")]
    for c in t["campos"]:
        nome = _limpa(c)
        if nome.startswith("("):
            out.append(("— não declarados —", "livre")); continue
        tipo = "fk" if nome in FKS[t["nome"]] else ("core" if c.startswith("[core] ") else "livre")
        out.append((nome if len(nome) <= lim else nome[:lim - 1] + "…", tipo))
    return out

def _caixa(t):
    campos = t["campos"]
    ncols = 2 if len(campos) + 1 > 14 else 1
    linhas = _linhas(t, ncols)
    nl = math.ceil(len(linhas) / ncols)
    return dict(tipo="tab", nome=t["nome"], t=t, ncols=ncols, linhas=linhas, nl=nl,
                w=(BOXW2 if ncols == 2 else BOXW), h=HEADH + PADT + nl * ROWH + PADB)

def _fantasma(nome):
    return dict(tipo="ghost", nome=nome, t=IDX[nome], w=GW, h=GH)

# ═════════════════════════ LAYOUT EM CAMADAS ═════════════════════════
def _quebra_ciclos(nos, arestas):
    """DFS que marca as arestas de retorno — sem isso a camada entra em laço
    (users → resellers → users é um ciclo real do modelo)."""
    adj = collections.defaultdict(list)
    for i, (a, b) in enumerate(arestas):
        if a != b: adj[a].append((b, i))
    cor, volta = {}, set()
    def dfs(v):
        cor[v] = 1
        for w, i in adj[v]:
            c = cor.get(w, 0)
            if c == 0: dfs(w)
            elif c == 1: volta.add(i)
        cor[v] = 2
    for n in nos:
        if cor.get(n, 0) == 0: dfs(n)
    return volta

def _camadas(nos, arestas, volta):
    """camada(de) = camada(para) + 1 → o lado 1 fica à esquerda, o lado N à direita."""
    dep = collections.defaultdict(list)
    for i, (a, b) in enumerate(arestas):
        if a != b and i not in volta: dep[a].append(b)
    cam, andando = {}, set()
    def calc(v):
        if v in cam: return cam[v]
        if v in andando: return 0
        andando.add(v)
        cam[v] = (max([calc(w) for w in dep[v]]) + 1) if dep[v] else 0
        andando.discard(v)
        return cam[v]
    for n in nos: calc(n)
    return cam

def _baricentro(ordem, viz):
    """Quatro varreduras de baricentro — é o que tira o espaguete das linhas."""
    ks = sorted(ordem)
    for it in range(6):
        seq = ks if it % 2 == 0 else ks[::-1]
        for L in seq:
            ref = L - 1 if it % 2 == 0 else L + 1
            if ref not in ordem: continue
            pos = {n: i for i, n in enumerate(ordem[ref])}
            atual = {n: i for i, n in enumerate(ordem[L])}
            def chave(n):
                v = [pos[m] for m in viz[n] if m in pos]
                return (sum(v) / len(v) if v else atual[n], atual[n])
            ordem[L].sort(key=chave)
    return ordem

def _faixas(itens):
    """Aloca faixa (lane) para segmentos horizontais que não podem se sobrepor."""
    faixa, ocupado = {}, []
    for k, a, b in sorted(itens, key=lambda x: abs(x[2] - x[1])):
        lo, hi = min(a, b), max(a, b)
        for i, ivs in enumerate(ocupado):
            if all(hi < x0 - 8 or lo > x1 + 8 for x0, x1 in ivs):
                ivs.append((lo, hi)); faixa[k] = i; break
        else:
            ocupado.append([(lo, hi)]); faixa[k] = len(ocupado) - 1
    return faixa, len(ocupado)

# ═════════════════════════ PRIMITIVAS DE SVG ═════════════════════════
def _sg(v): return (v > 0) - (v < 0)

def poli(pts, r=7):
    """Caminho ortogonal com canto arredondado. Pontos repetidos são descartados
    (senão o canto degenera e o path some)."""
    p = [pts[0]]
    for q in pts[1:]:
        if abs(q[0] - p[-1][0]) > .5 or abs(q[1] - p[-1][1]) > .5: p.append(q)
    if len(p) < 2: return ""
    d = "M%.1f %.1f" % p[0]
    for i in range(1, len(p) - 1):
        (x0, y0), (x1, y1), (x2, y2) = p[i - 1], p[i], p[i + 1]
        rr = min(r, math.hypot(x1 - x0, y1 - y0) / 2, math.hypot(x2 - x1, y2 - y1) / 2)
        dx1, dy1 = _sg(x1 - x0), _sg(y1 - y0)
        dx2, dy2 = _sg(x2 - x1), _sg(y2 - y1)
        d += " L%.1f %.1f" % (x1 - dx1 * rr, y1 - dy1 * rr)
        d += " Q%.1f %.1f %.1f %.1f" % (x1, y1, x1 + dx2 * rr, y1 + dy2 * rr)
    d += " L%.1f %.1f" % p[-1]
    return d

_D = {"e": (-1, 0), "d": (1, 0), "c": (0, -1), "b": (0, 1)}   # esquerda, direita, cima, baixo

def pe_galinha(x, y, lado, cls):
    """Pé-de-galinha no lado N. dx/dy é a direção em que a linha SAI da caixa."""
    dx, dy = _D[lado]
    px, py = -dy, dx                       # perpendicular
    ax, ay = x + dx * 11, y + dy * 11      # apice, sobre a linha
    return "".join(
      '<path class="%s" d="M%.1f %.1f L%.1f %.1f"/>' % (cls, ax, ay, x + px * o, y + py * o)
      for o in (-5.5, 0, 5.5))

def traco_um(x, y, lado, cls):
    """Traço perpendicular no lado 1."""
    dx, dy = _D[lado]
    px, py = -dy, dx
    cx, cy = x + dx * 8, y + dy * 8
    return '<path class="%s" d="M%.1f %.1f L%.1f %.1f"/>' % (cls, cx - px * 4.5, cy - py * 4.5,
                                                             cx + px * 4.5, cy + py * 4.5)

ORIGLAB = {"novo": "novo", "extensao": "extensão do core", "core": "core"}

def svg_caixa(n):
    """Uma tabela desenhada: faixa de cabeçalho, chip de origem e lista de campos."""
    x, y, w, h = n["x"], n["y"], n["w"], n["h"]
    t = n["t"]
    if n["tipo"] == "ghost":
        return ('<g class="ergroup">'
          f'<rect class="nbox nbox--ghost" x="{x}" y="{y}" width="{w}" height="{h}" rx="9"/>'
          f'<rect class="ndom d-{t["dominio"]}" x="{x}" y="{y+9}" width="3" height="{h-18}" rx="1.5"/>'
          f'<text class="tghost" x="{x+14}" y="{y+20}">{esc(n["nome"])}</text>'
          f'<text class="tghostdom" x="{x+14}" y="{y+35}">↗ {esc(DOMNOME[t["dominio"]])}</text>'
          '</g>')
    org = t["origem"]
    chipw = 7 * len(ORIGLAB[org]) + 14
    linhas = n["linhas"]; nl = n["nl"]; cw = w / n["ncols"]
    cel = []
    for i, (txt, tipo) in enumerate(linhas):
        col, lin = i // nl, i % nl
        cx = x + col * cw + 11
        cy = y + HEADH + PADT + lin * ROWH + 11
        bad = {"pk": ("PK", "bpk"), "fk": ("FK", "bfk")}.get(tipo)
        if bad:
            cel.append(f'<rect class="{bad[1]}" x="{cx}" y="{cy-8.5}" width="19" height="11" rx="2.5"/>'
                       f'<text class="tbadge" x="{cx+9.5}" y="{cy}">{bad[0]}</text>')
        cls = "tfield" + (" tfield--pk" if tipo == "pk" else " tfield--core" if tipo == "core" else "")
        cel.append(f'<text class="{cls}" x="{cx+24}" y="{cy}">{esc(txt)}</text>')
    if n["ncols"] == 2:
        cel.append(f'<path class="nsplit" d="M{x+cw} {y+HEADH+4} V{y+h-5}"/>')
    pk = t["pk"] if t["pk"] != "id" else ""
    pkt = f'<text class="tpknote" x="{x+18+chipw}" y="{y+35}">{esc(pk)}</text>' if pk else ""
    return ('<g class="ergroup">'
      f'<rect class="nbox" x="{x}" y="{y}" width="{w}" height="{h}" rx="9"/>'
      f'<path class="nhead" d="M{x} {y+9}a9 9 0 019-9h{w-18}a9 9 0 019 9v{HEADH-9}H{x}Z"/>'
      f'<text class="tname" x="{x+11}" y="{y+18}">{esc(n["nome"])}</text>'
      f'<rect class="chip-{org}" x="{x+11}" y="{y+25}" width="{chipw}" height="13" rx="6.5"/>'
      f'<text class="tchip tchip--{org}" x="{x+11+chipw/2}" y="{y+34.5}">{ORIGLAB[org]}</text>'
      f'{pkt}<path class="nrule" d="M{x} {y+HEADH} H{x+w}"/>'
      + "".join(cel) + '</g>')

# ═════════════════════════ DIAGRAMA DE UM DOMÍNIO ═════════════════════════
def diagrama(dom, uid):
    proprias = [t["nome"] for t in TAB if t["dominio"] == dom]
    dentro = set(proprias)
    arestas = [l for l in LIG if l[0] in dentro or l[1] in dentro]
    externas = []
    for de, para, *_ in arestas:
        for x in (de, para):
            if x not in dentro and x not in externas: externas.append(x)
    nos = proprias + externas
    par = [(a, b) for a, b, *_ in arestas]
    volta = _quebra_ciclos(nos, par)
    cam = _camadas(nos, par, volta)

    viz = collections.defaultdict(list)
    for a, b in par:
        if a != b: viz[a].append(b); viz[b].append(a)
    ordem = collections.defaultdict(list)
    for n in nos: ordem[cam[n]].append(n)
    ordem = _baricentro(dict(ordem), viz)

    N = {}
    for n in nos:
        N[n] = _fantasma(n) if n in externas else _caixa(IDX[n])
    # x por camada (a camada é tão larga quanto a caixa mais larga dela)
    larg = {L: max(N[n]["w"] for n in ns) for L, ns in ordem.items()}
    xs, acc = {}, 0
    for L in sorted(ordem):
        xs[L] = acc; acc += larg[L] + GAP
    largura = acc - GAP
    # y: cada camada empilhada e centrada na mais alta
    alt = {L: sum(N[n]["h"] for n in ns) + VGAP * (len(ns) - 1) for L, ns in ordem.items()}
    maxalt = max(alt.values())
    for L, ns in ordem.items():
        y = (maxalt - alt[L]) / 2
        for n in ns:
            N[n]["x"] = xs[L] + larg[L] - N[n]["w"] if N[n]["tipo"] == "ghost" else xs[L]
            N[n]["y"] = y; y += N[n]["h"] + VGAP

    # ── classificação e ancoragem das arestas ──
    esq, dir_, cima, baixo = (collections.defaultdict(list) for _ in range(4))
    lig = []
    for de, para, campo, card, conf in arestas:
        S, Tt = N[de], N[para]
        if de == para:                       tipo = "self"
        elif cam[de] - cam[para] == 1:       tipo = "reta"
        elif cam[de] > cam[para]:            tipo = "baixo"
        else:                                tipo = "cima"
        a = dict(de=de, para=para, campo=campo, card=card, conf=conf, tipo=tipo, S=S, T=Tt)
        lig.append(a)
        if tipo == "reta":  esq[de].append(a);  dir_[para].append(a)
        elif tipo == "baixo": baixo[de].append(a); baixo[para].append(a)
        elif tipo == "cima":  cima[de].append(a);  cima[para].append(a)
    cen = lambda n: (N[n]["y"] + N[n]["h"] / 2, N[n]["x"] + N[n]["w"] / 2)
    for n, ls in esq.items():
        ls.sort(key=lambda a: cen(a["para"])[0])
        for i, a in enumerate(ls): a["sy"] = N[n]["y"] + N[n]["h"] * (i + 1) / (len(ls) + 1)
    for n, ls in dir_.items():
        ls.sort(key=lambda a: cen(a["de"])[0])
        for i, a in enumerate(ls): a["ty"] = N[n]["y"] + N[n]["h"] * (i + 1) / (len(ls) + 1)
    for grupo, chave in ((baixo, "by"), (cima, "cy")):
        for n, ls in grupo.items():
            ls.sort(key=lambda a: cen(a["para"] if a["de"] == n else a["de"])[1])
            for i, a in enumerate(ls):
                px = N[n]["x"] + N[n]["w"] * (i + 1) / (len(ls) + 1)
                if a["de"] == n: a["sx"] = px
                else: a["tx"] = px
    # canal vertical de cada aresta reta, dentro da folga entre camadas
    porgap = collections.defaultdict(list)
    for a in lig:
        if a["tipo"] == "reta": porgap[cam[a["de"]]].append(a)
    for L, ls in porgap.items():
        ls.sort(key=lambda a: (a["S"]["y"], a["sy"]))
        x0 = xs[L] - GAP + 16; x1 = xs[L] - 16
        for i, a in enumerate(ls):
            a["mx"] = x0 if len(ls) == 1 else x0 + (x1 - x0) * i / (len(ls) - 1)
    fb, nb = _faixas([(id(a), a["sx"], a["tx"]) for a in lig if a["tipo"] == "baixo"])
    fc, nc = _faixas([(id(a), a["sx"], a["tx"]) for a in lig if a["tipo"] == "cima"])

    topo = 22 + nc * LANE
    base = 22 + nb * LANE
    altura = maxalt + topo + base
    MX = 44
    for a in lig:
        if a["tipo"] == "baixo": a["chy"] = maxalt + 20 + fb[id(a)] * LANE
        if a["tipo"] == "cima":  a["chy"] = -20 - fc[id(a)] * LANE

    # ── caminhos ──
    saida = []
    for a in lig:
        S, Tt = a["S"], a["T"]
        c = "edge" + ("" if a["conf"] == "declarada" else " edge--inf")
        cm = "crow" + ("" if a["conf"] == "declarada" else " crow--inf")
        if a["tipo"] == "self":
            y1, y2 = S["y"] + S["h"] * .35, S["y"] + S["h"] * .65
            d = poli([(S["x"], y1), (S["x"] - 34, y1), (S["x"] - 34, y2), (S["x"], y2)])
            pts = [(S["x"], y1, "e"), (S["x"], y2, "e")]
        elif a["tipo"] == "reta":
            sx, sy = S["x"], a["sy"]; tx, ty = Tt["x"] + Tt["w"], a["ty"]
            d = poli([(sx, sy), (a["mx"], sy), (a["mx"], ty), (tx, ty)])
            pts = [(sx, sy, "e"), (tx, ty, "d")]
            saida.append(f'<text class="tlabel" x="{tx+9:.1f}" y="{ty-5:.1f}">{esc(a["campo"])}</text>')
        elif a["tipo"] == "baixo":
            sx, tx, ch = a["sx"], a["tx"], a["chy"]
            d = poli([(sx, S["y"] + S["h"]), (sx, ch), (tx, ch), (tx, Tt["y"] + Tt["h"])])
            pts = [(sx, S["y"] + S["h"], "b"), (tx, Tt["y"] + Tt["h"], "b")]
            saida.append(f'<text class="tlabel tlabel--mid" x="{(sx+tx)/2:.1f}" y="{ch-4:.1f}">{esc(a["campo"])}</text>')
        else:
            sx, tx, ch = a["sx"], a["tx"], a["chy"]
            d = poli([(sx, S["y"]), (sx, ch), (tx, ch), (tx, Tt["y"])])
            pts = [(sx, S["y"], "c"), (tx, Tt["y"], "c")]
            saida.append(f'<text class="tlabel tlabel--mid" x="{(sx+tx)/2:.1f}" y="{ch-4:.1f}">{esc(a["campo"])}</text>')
        (sx0, sy0, l0), (tx0, ty0, l1) = pts
        marca = (traco_um(sx0, sy0, l0, cm) if a["card"] == "1:1" else pe_galinha(sx0, sy0, l0, cm))
        marca += traco_um(tx0, ty0, l1, cm)
        saida.insert(0, f'<path class="{c}" d="{d}"/>' + marca)
    corpo = "".join(saida) + "".join(svg_caixa(N[n]) for n in nos)
    return (f'<svg class="er-svg" width="{largura+MX*2:.0f}" height="{altura:.0f}" '
            f'viewBox="0 0 {largura+MX*2:.0f} {altura:.0f}" role="img" '
            f'aria-label="Diagrama de entidade e relacionamento do domínio {esc(DOMNOME[dom])}">'
            f'<g transform="translate({MX},{topo})">{corpo}</g></svg>'), len(arestas), externas

# ═════════════════════════ MAPA GERAL DE DOMÍNIOS ═════════════════════════
DPOS = {"catalogo": (40, 250), "pedidos": (300, 250), "revendedores": (560, 250),
        "acesso": (820, 250), "vitrine": (170, 80), "suporte": (690, 80),
        "config": (896, 80), "financeiro": (430, 420)}
DW, DH = 190, 82
DESVIO = {("catalogo", "acesso"): 600, ("pedidos", "acesso"): 560}   # rotas por baixo

def _borda(b, alvo):
    cx, cy = b[0] + DW / 2, b[1] + DH / 2
    dx, dy = alvo[0] - cx, alvo[1] - cy
    tx = (DW / 2) / abs(dx) if dx else 1e9
    ty = (DH / 2) / abs(dy) if dy else 1e9
    t = min(tx, ty)
    return cx + dx * t, cy + dy * t

def mapa_dominios():
    cruz = collections.Counter()
    for de, para, *_ in LIG:
        a, b = IDX[de]["dominio"], IDX[para]["dominio"]
        if a != b: cruz[tuple(sorted((a, b)))] += 1
    linhas, rots = [], []
    for (a, b), n in sorted(cruz.items(), key=lambda x: -x[1]):
        A, B = DPOS[a], DPOS[b]
        ca = (A[0] + DW / 2, A[1] + DH / 2); cb = (B[0] + DW / 2, B[1] + DH / 2)
        y = DESVIO.get((a, b)) or DESVIO.get((b, a))
        if y:
            ax = ca[0] + (0 if a == "catalogo" else 0); bx = cb[0] + (-20 if y == 600 else 25)
            pts = [(ax, A[1] + DH), (ax, y), (bx, y), (bx, B[1] + DH)]
            linhas.append(f'<path class="dedge" d="{poli(pts, 12)}"/>')
            rots.append(((ax + bx) / 2, y - 7, n))
        else:
            p1 = _borda(A, cb); p2 = _borda(B, ca)
            linhas.append('<path class="dedge" d="M%.1f %.1f L%.1f %.1f"/>' % (p1 + p2))
            rots.append(((p1[0] + p2[0]) / 2, (p1[1] + p2[1]) / 2 - 3, n))
    caixas = []
    for k, nome, icone, _d in DOM:
        x, y = DPOS[k]
        nt = sum(1 for t in TAB if t["dominio"] == k)
        ni = sum(1 for de, para, *_ in LIG if IDX[de]["dominio"] == k and IDX[para]["dominio"] == k)
        caixas.append(
          f'<a href="#dom-{k}"><g><rect class="dbox" x="{x}" y="{y}" width="{DW}" height="{DH}" rx="11"/>'
          f'<rect class="ndom d-{k}" x="{x}" y="{y+12}" width="4" height="{DH-24}" rx="2"/>'
          f'<text class="dname" x="{x+18}" y="{y+30}">{esc(nome)}</text>'
          f'<text class="dnum" x="{x+18}" y="{y+54}">{nt}</text>'
          f'<text class="dsub" x="{x+18+(17 if nt<10 else 28)}" y="{y+54}">tabelas</text>'
          f'<text class="dsub" x="{x+18}" y="{y+70}">{ni} relações internas</text></g></a>')
    marc = "".join(f'<text class="dcount" x="{x:.1f}" y="{y:.1f}">{n}</text>' for x, y, n in rots)
    return (f'<svg class="er-svg" width="1126" height="600" viewBox="0 0 1126 600" role="img" '
            f'aria-label="Mapa dos oito domínios do banco e das relações que atravessam cada par">'
            f'<g transform="translate(20,-48)">{"".join(linhas)}{marc}{"".join(caixas)}</g></svg>')

# ═════════════════════════ LEGENDA DESENHADA ═════════════════════════
def legenda():
    """Mesma máquina do resto da página: a legenda é desenhada com as mesmas
    funções que desenham o diagrama, então ela não pode mentir sobre ele."""
    cx = _caixa(IDX["orders"]); cx["x"], cx["y"] = 16, 26
    gh = _fantasma("customers"); gh["x"], gh["y"] = 272, 176
    notas = [(30, "PK", "chave primária da tabela"),
             (56, "FK", "a coluna que carrega a relação"),
             (82, "", "campo em cinza itálico já existe no core"),
             (108, "", "o chip sob o nome diz a origem da tabela")]
    txt = ""
    for y, b, t in notas:
        yy = cx["y"] + y
        if b:
            txt += (f'<rect class="b{b.lower()}" x="272" y="{yy-8.5}" width="19" height="11" rx="2.5"/>'
                    f'<text class="tbadge" x="281.5" y="{yy}">{b}</text>')
        txt += f'<text class="lnota" x="{300 if b else 272}" y="{yy}">{esc(t)}</text>'
    ln = [f'<text class="lnota" x="486" y="{gh["y"]+22}">tabela de outro domínio: o desenho</text>',
          f'<text class="lnota" x="486" y="{gh["y"]+40}">inteiro dela está na seção dela</text>']
    exemplos = [(64, "edge", "crow", "N",
                 "relação DECLARADA — a coluna aparece literalmente na tela"),
                (140, "edge edge--inf", "crow crow--inf", "N",
                 "relação INFERIDA — deduzida do nome da tabela ou da regra"),
                (216, "edge edge--inf", "crow crow--inf", "1",
                 "1:1 — extensão do core: uma linha por linha da tabela do núcleo")]
    for y, ce, cm, lado_n, rot in exemplos:
        ln.append(f'<path class="{ce}" d="M770 {y} H910"/>')
        ln.append(traco_um(770, y, "d", cm))
        ln.append(pe_galinha(910, y, "e", cm) if lado_n == "N" else traco_um(910, y, "e", cm))
        ln.append(f'<text class="lcard" x="750" y="{y+4}">1</text>')
        ln.append(f'<text class="lcard" x="930" y="{y+4}">{lado_n}</text>')
        ln.append(f'<text class="lnota" x="750" y="{y+30}">{esc(rot)}</text>')
    return ('<svg class="er-svg" width="1126" height="266" viewBox="0 0 1126 266" role="img" '
            'aria-label="Legenda: como ler as caixas e as linhas do diagrama">'
            + svg_caixa(cx) + svg_caixa(gh) + txt + "".join(ln) + '</svg>')

# ═════════════════════════ BLOCOS DE HTML ═════════════════════════
CHIPORIG = {"novo": "warn", "extensao": "info", "core": "ok"}

def telas_de(nome):
    out = []
    for n in USOS.get(nome, []):
        ti, arq, slug = TELA[n]
        if arq:
            out.append(f'<a class="ertela" href="{arq}" title="{esc(ti)}">{esc(n)} · {esc(slug)}</a>')
        else:
            out.append(f'<span class="ertela ertela--off">{esc(n)}</span>')
    return f'<span class="ertelas">{"".join(out)}</span>' if out else '<span class="muted">—</span>'

def tabela_do_dominio(dom):
    linhas = []
    for t in [x for x in TAB if x["dominio"] == dom]:
        fks = [f'<code>{esc(c)}</code> → {esc(v[0])}' for c, v in FKS[t["nome"]].items()]
        campos = ", ".join(f'<code>{esc(c)}</code>' for c in t["campos"])
        linhas.append([
          f'<strong class="ertab">{esc(t["nome"])}</strong>'
          f'<br><small class="muted">{t["proposito"]}</small>',
          chip(ORIGLAB[t["origem"]], CHIPORIG[t["origem"]], flat=True)
          + f'<br><small class="muted er-pk">PK {esc(t["pk"])}</small>',
          f'<span class="ercampos">{campos}</span>'
          + (f'<br><small class="muted">FK: {" · ".join(fks)}</small>' if fks else ""),
          telas_de(t["nome"])])
    return tabela([("Tabela", ""), ("Origem", ""), ("Campos", "ercampos-col"), ("Telas que usam", "")], linhas)

def secao_dominio(k, nome, icone, desc, i):
    svg, nrel, ext = diagrama(k, i)
    nt = sum(1 for t in TAB if t["dominio"] == k)
    ni = sum(1 for de, para, *_ in LIG if IDX[de]["dominio"] == k and IDX[para]["dominio"] == k)
    fora = (f'<p class="ernota">Em cinza tracejado, {len(ext)} tabela'
            f'{"s" if len(ext)>1 else ""} de outros domínios que este desenho precisa citar: '
            + ", ".join(f'<code>{esc(x)}</code>' for x in ext) + ".</p>") if ext else ""
    return f'''
<section class="erdom" id="dom-{k}">
  <header class="erdom__head">
    <span class="erdom__ic d-{k}-bg">{ic(icone)}</span>
    <div><h2>{esc(nome)}</h2><p>{desc}</p></div>
    <span class="erdom__n"><b>{nt}</b> tabelas<br>{ni} relações internas</span>
  </header>
  <div class="er-canvas table-scroll" tabindex="0" aria-label="Diagrama do domínio {esc(nome)} — role para o lado para ver o desenho inteiro">{svg}</div>
  <p class="erhint">↔ arraste o desenho para o lado no celular</p>
  {fora}
  {tabela_do_dominio(k)}
</section>'''

def render():
    novos = sum(1 for t in TAB if t["origem"] == "novo")
    ext_ = sum(1 for t in TAB if t["origem"] == "extensao")
    core = sum(1 for t in TAB if t["origem"] == "core")
    decl = sum(1 for r in REL if r["conf"] == "declarada")
    infe = len(REL) - decl
    cruz = sum(1 for de, para, *_ in LIG if IDX[de]["dominio"] != IDX[para]["dominio"])

    pulo = "".join(
      f'<a href="#dom-{k}"><b>{esc(n)}</b>'
      f'<span>{sum(1 for t in TAB if t["dominio"]==k)} tabelas</span></a>' for k, n, _i, _d in DOM)

    porig = "".join(
      f'<tr><td>{chip(ORIGLAB[o], CHIPORIG[o], flat=True)}</td>'
      f'<td class="cell-num">{sum(1 for t in TAB if t["origem"]==o)}</td><td>{d}</td></tr>'
      for o, d in [("novo", "Nasce no módulo Velaro. Nenhuma existe hoje."),
                   ("extensao", "Acrescenta coluna a uma tabela do núcleo — só <code>users.reseller_id</code>."),
                   ("core", "Já existe no scaffold e é lida como está. <code>orders</code> e <code>customers</code> ganham comportamento Velaro por tabela 1:1, sem serem alteradas.")])
    pdom = "".join(
      f'<tr><td><a href="#dom-{k}"><strong>{esc(n)}</strong></a></td>'
      f'<td class="cell-num">{sum(1 for t in TAB if t["dominio"]==k)}</td>'
      f'<td class="cell-num">{sum(1 for de,para,*_ in LIG if IDX[de]["dominio"]==k and IDX[para]["dominio"]==k)}</td>'
      f'<td class="ercampos">{", ".join(f"<code>{esc(t['nome'])}</code>" for t in TAB if t["dominio"]==k)}</td></tr>'
      for k, n, _i, _d in DOM)

    rel_rows = [[f'<code class="ercod">{esc(r["de"])}</code>', f'<code>{esc(r["campo"])}</code>',
                 f'<code class="ercod">{esc(r["para"])}</code>',
                 f'<span class="ercardin">{esc(r["card"])}</span>',
                 chip("declarada", "ok", flat=True) if r["conf"] == "declarada" else chip("inferida", "warn", flat=True),
                 f'<span class="ermotivo">{r["motivo"]}</span>']
                for r in sorted(REL, key=lambda r: (r["conf"] != "declarada", r["de"]))]
    reltab = tabela([("De (lado N · carrega a FK)", "cell-nowrap"), ("Campo", "cell-nowrap"),
                     ("Para (lado 1)", "cell-nowrap"), ("Cardinalidade", "cell-nowrap"),
                     ("Confiança", "cell-nowrap"), ("Por quê", "")], rel_rows)

    gaps = "".join(
      f'<li class="ergap ergap--{tom}"><div class="ergap__n">{i}</div>'
      f'<div><h3>{esc(tit)}<span class="ergap__t">{ {"faltando":"falta tabela ou coluna","conflito":"conflito entre telas","trilha":"trilha de auditoria"}[tom] }</span></h3>'
      f'<p>{txt}</p></div></li>'
      for i, (tit, tom, txt) in enumerate(LAC, 1))

    doms = "".join(secao_dominio(k, n, i_, d, j) for j, (k, n, i_, d) in enumerate(DOM))

    return f'''
<div class="ertop">
  <div class="erwrap">
    <p class="ercrumbs"><a href="index.html">← Mockups</a> <span style="opacity:.4">·</span>
      <a href="mapa.html">Mapa das 31 telas</a> <a href="01-site-publico.html">Site</a>
      <a href="02-portal-lojista.html">Portal</a> <a href="03-vitrine-pdv.html">Vitrine</a>
      <a href="04-painel-master.html">Master</a></p>
    <h1>Diagrama do <b>banco de dados</b></h1>
    <p>As {len(TAB)} tabelas do modelo B2B da Velaro, agrupadas por domínio, com campos, chaves,
       relações e as telas que usam cada uma. O desenho sai das {len(mp.T)} telas declaradas no mapa,
       dos documentos de tela e das migrations do scaffold — não de um dump: <strong>este banco ainda
       não existe</strong>, e é justamente por isso que ele precisa estar desenhado antes.</p>
    <p>O eixo do modelo é <code>reseller_id</code>. Ele desce por <code>users</code>,
       <code>order_velaro_details</code>, <code>order_batches</code>, <code>customer_velaro_details</code>,
       <code>reseller_stores</code>, <code>reseller_price_rules</code> e <code>support_tickets</code>.
       O consumidor final nunca é um <code>users</code>: ele é um <code>customers</code> dentro da
       carteira de um revendedor, e a relação financeira registrada aqui é sempre Velaro → lojista.</p>
    <div class="erstats">
      <div class="erstat"><b>{len(TAB)}</b><span>tabelas no modelo</span></div>
      <div class="erstat"><b>{novos}</b><span>novas do módulo Velaro</span></div>
      <div class="erstat"><b>{core + ext_}</b><span>do core ({ext_} com coluna nova)</span></div>
      <div class="erstat"><b>{len(REL)}</b><span>relações ({decl} declaradas · {infe} inferidas)</span></div>
      <div class="erstat"><b>{len(DOM)}</b><span>domínios</span></div>
      <div class="erstat"><b>{len(LAC)}</b><span>lacunas em aberto</span></div>
    </div>
  </div>
</div>

<div class="erwrap">
  <nav class="erjump">{pulo}</nav>

  <section class="ercard" id="como-ler">
    <h2>Como ler</h2>
    <p class="lede">Cada caixa é uma tabela: nome, origem, chave primária e a lista de campos. Cada
      linha é uma chave estrangeira, desenhada <strong>do lado que carrega a coluna para o lado
      referenciado</strong> — o pé-de-galinha marca o lado N, o traço simples marca o lado 1.</p>
    {notice("<strong>Linha cheia é fato; linha tracejada é dedução.</strong> "
            "Cheia = a coluna aparece literalmente na lista de campos de alguma tela declarada no mapa. "
            "Tracejada = a relação foi inferida do nome da tabela ou da regra de negócio, e o motivo de "
            "cada uma está na tabela de relações. Inferida não é opinião solta: é hipótese com fonte — "
            "mas é o que precisa de confirmação antes de virar migration.")}
    <div class="er-canvas table-scroll" tabindex="0" aria-label="Legenda do diagrama">{legenda()}</div>
    <p class="erhint">↔ arraste para o lado no celular</p>
    <p class="ernota">O diagrama completo em um quadro só ficaria ilegível: são {len(TAB)} tabelas e
      {len(LIG)} ligações. Por isso ele vem em <strong>um mapa geral de domínios</strong> e depois
      <strong>um diagrama por domínio</strong>. Quando uma tabela de outro domínio precisa aparecer, ela
      entra como caixa cinza tracejada, só com o nome — o desenho inteiro dela está na seção do domínio dela.
      As duas relações N:N (promoção × produto e promoção × coleção) são desenhadas pelo pivô
      <code>promotion_products</code>, que é tabela de verdade.</p>
  </section>

  <section class="ercard" id="mapa">
    <h2>Mapa geral dos domínios</h2>
    <p class="lede">Oito domínios e as {cruz} ligações que atravessam a fronteira entre eles. O número
      sobre a linha é quantas chaves estrangeiras cruzam aquele par. Clique num domínio para ir ao diagrama dele.</p>
    <div class="er-canvas table-scroll" tabindex="0" aria-label="Mapa geral dos domínios">{mapa_dominios()}</div>
    <p class="erhint">↔ arraste para o lado no celular</p>
  </section>

  <section class="ercard" id="sumario">
    <h2>Sumário</h2>
    <div class="ersum">
      <div>
        <h3>Por origem</h3>
        <div class="table-scroll"><table class="table"><thead><tr><th>Origem</th>
          <th class="cell-num">Tabelas</th><th>O que significa</th></tr></thead>
          <tbody>{porig}</tbody></table></div>
      </div>
      <div>
        <h3>Por domínio</h3>
        <div class="table-scroll"><table class="table"><thead><tr><th>Domínio</th>
          <th class="cell-num">Tabelas</th><th class="cell-num">Relações internas</th>
          <th>Tabelas</th></tr></thead><tbody>{pdom}</tbody></table></div>
      </div>
    </div>
  </section>
{doms}
  <section class="ercard er-rel" id="relacoes">
    <h2>As {len(REL)} relações, uma a uma</h2>
    <p class="lede">Convenção: <strong>de</strong> é a tabela que carrega a chave estrangeira (lado N),
      <strong>para</strong> é a tabela referenciada (lado 1), e a cardinalidade se lê
      <em>para(1) : de(N)</em>. {decl} são declaradas e {infe} são inferidas — a coluna “por quê” diz
      exatamente de onde cada uma saiu.</p>
    {reltab}
  </section>

  <section class="ercard" id="lacunas">
    <h2>{len(LAC)} lacunas — pendência explícita, não detalhe</h2>
    <p class="lede">O que as telas exigem e o modelo ainda não sustenta. Cada item aqui é decisão a
      tomar <strong>antes</strong> da primeira migration: uma delas muda a chave de um documento fiscal,
      outra pode apagar a carteira inteira de um lojista com um cascade no usuário errado.</p>
    <ol class="ergaps">{gaps}</ol>
  </section>

  <p class="erfim">Gerado por <code>_build_er.py</code> a partir de <code>_mapa.py</code>.
    Mudou tela, mudou tabela: rode <code>python3 _build_er.py</code> de novo.</p>
</div>'''

if __name__ == "__main__":
    W("er-banco.html", P("Diagrama do banco de dados", render()))
    print(f"  {len(TAB)} tabelas · {len(REL)} relações · {len(LIG)} ligações desenhadas · {len(LAC)} lacunas")
