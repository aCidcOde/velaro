# -*- coding: utf-8 -*-
"""Diagrama do banco de dados da plataforma B2B Velaro — gera er-banco.html.

Peça de referência dos mockups: as tabelas do banco `velaro` agrupadas por
domínio, com campos, chaves e relações, mais o registro de como cada lacuna
da modelagem foi fechada.

Como o desenho é feito: o layout é CALCULADO aqui (camadas por dependência de
chave estrangeira + ordenação por baricentro + roteamento ortogonal em canais),
e sai como SVG inline com coordenadas absolutas. Nada de Mermaid, nada de CDN,
nada de "deixa o browser resolver". O SVG é largo de propósito e mora dentro de
.table-scroll: no celular ele rola por dentro, sem esticar a página.

Fontes: o schema real do banco `velaro`, extraído de information_schema — 71
tabelas, 101 chaves estrangeiras, 54 migrations aplicadas. É dele que sai cada
coluna e cada relação desenhada aqui. _mapa.py entra só para dizer quais das 31
telas usam cada tabela, e docs/banco-de-dados.md registra as decisões que
produziram este schema.

Requer Python 3.12 ou mais novo (f-string aninhada, PEP 701). O `python3` do
sistema é 3.9 e morre com SyntaxError: rode `python3.14 _build_er.py`.
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
  "O que a Velaro fabrica e o que existe em cofre: produto com a ficha técnica de joalheria na "
  "própria tabela, SKU por aro, saldo por SKU em cada local, solicitação de produção e campanha "
  "promocional B2B."),
 ("revendedores", "Revendedores e clientes", "store",
  "O lojista com CNPJ, a habilitação dele e a carteira de consumidores finais que ele atende. "
  "É aqui que nasce o eixo <code>reseller_id</code>."),
 ("pedidos", "Pedidos", "bag",
  "O pedido do lojista à Velaro: itens com preço congelado, gravação, dois status independentes "
  "(operacional e financeiro) e a linha do tempo."),
 ("financeiro", "Financeiro B2B", "coin",
  "O lote semanal Velaro → lojista, o pagamento dele, a nota fiscal rateada por pedido e a remessa "
  "que sai da fábrica. O dinheiro do consumidor final não passa por aqui: ele paga no caixa da loja."),
 ("vitrine", "Vitrine white-label", "cart",
  "A loja do lojista: identidade própria, domínio próprio, a vitrine que ele monta (categorias "
  "visíveis, destaques e favoritos) e os parâmetros com que forma o preço ao consumidor sobre o "
  "custo Velaro."),
 ("suporte", "Suporte", "support",
  "Chamado Velaro ↔ revendedor, com thread, anexo, etiqueta, histórico de status e nota interna "
  "que nunca atravessa para o outro lado."),
 ("config", "Configuração e conteúdo", "gear",
  "Parâmetro do sistema, trilha de notificação transacional, relatório agendado ou exportado, o "
  "conteúdo da central de ajuda e o lead que chega pelo site."),
 ("acesso", "Acesso e permissões", "lock",
  "Login único do scaffold — com <code>reseller_id</code> na própria tabela <code>users</code> —, "
  "o conjunto ACL e a trilha de auditoria. O consumidor final não tem registro aqui: ele não faz "
  "login em lugar nenhum da plataforma."),
]
DOMNOME = {k: n for k, n, _i, _d in DOM}

# ═══════════════════════════════════ TABELAS ═══════════════════════════════════
# (nome, origem, domínio, chave primária, propósito, campos)
def T(nome, origem, dominio, pk, proposito, campos):
    return dict(nome=nome, origem=origem, dominio=dominio, pk=pk, proposito=proposito, campos=campos)

TAB = [
 # ── catálogo ──────────────────────────────────────────────────────────────
 T("collections", "novo", "catalogo", "id (slug único)",
   "Coleções comerciais do catálogo Velaro (Classic, Diamond, Premium), usadas como aba e filtro no site, no portal e no master.",
   ["name", "slug", "description", "cover_path", "position", "is_active"]),
 T("categories", "novo", "catalogo", "id (slug único)",
   "Taxonomia hierárquica de produtos (Alianças Tradicionais, Solitários, Acessórios), com auto-relação por <code>parent_id</code>.",
   ["parent_id", "name", "slug", "position", "is_active"]),
 T("materials", "novo", "catalogo", "id (slug único)",
   "Tabela de domínio dos materiais (Prata 950, Ouro Amarelo 18k, Ouro Rosé 18k, Aço) — filtro e ficha técnica.",
   ["name", "slug", "position", "is_active"]),
 T("finishes", "novo", "catalogo", "id (slug único)",
   "Tabela de domínio dos acabamentos (Polida, Fosca, Diamantada, Cravejada, Texturizada, PVD) — filtro e ficha técnica.",
   ["name", "slug", "position", "is_active"]),
 T("products", "extensao", "catalogo", "id (sku e slug únicos)",
   "Produto mestre do catálogo. <strong>A tabela 1:1 <code>product_attributes</code> foi eliminada</strong>: a ficha técnica de joalheria "
   "e os vínculos de taxonomia moram aqui, e <code>slug</code> virou coluna do core para sustentar a rota pública. "
   "<code>price</code> é o CUSTO B2B e só pode ser serializado no Portal do Lojista e no Master — nunca no site público nem na vitrine.",
   ["[core] user_id", "name", "slug", "collection_id", "category_id", "material_id", "finish_id",
    "sku", "description", "largura_mm", "formato", "permite_gravacao", "gravacao_max_chars",
    "prazo_entrega_dias", "is_made_to_order", "price (custo B2B)", "is_active", "[core] meta"]),
 T("product_variants", "novo", "catalogo", "id (product_id + aro único)",
   "SKU por tamanho (aro) — a unidade real de estoque, de disponibilidade e de solicitação de produção. O item do pedido aponta para cá, não só para o produto.",
   ["product_id", "sku", "aro (tamanho)", "is_active"]),
 T("product_images", "novo", "catalogo", "id",
   "Galeria do produto com ordenação e imagem principal (carrossel e miniaturas nos três ambientes).",
   ["product_id", "path", "alt", "position", "is_primary"]),
 T("product_revisions", "novo", "catalogo", "id",
   "Histórico de alterações do produto com before/after em JSON, exposto na ação rápida “Histórico de alterações” do master.",
   ["product_id", "actor_id", "action", "before (json)", "after (json)"]),
 T("stock_locations", "novo", "catalogo", "id (code único)",
   "Locais físicos de guarda do estoque Velaro (Matriz — Cofre A1). Fecha a lacuna 11: sem ela o filtro “Local” da tela 3.4 não tinha alvo "
   "e o saldo não podia deixar de ser uma linha única por variante.",
   ["code", "name", "description", "is_default", "is_active"]),
 T("stock_items", "novo", "catalogo", "id (variante + local único)",
   "Saldo físico por SKU/aro <strong>e por local</strong>, propriedade da Velaro. Deixou de ser 1:1 com a variante — o mesmo aro pode ter "
   "saldo em mais de um cofre. O portal do lojista lê <code>disponivel</code> e nunca escreve.",
   ["product_variant_id", "stock_location_id", "atual", "reservado", "disponivel", "minimo", "reposicao"]),
 T("stock_movements", "novo", "catalogo", "id",
   "Razão do estoque com before/after. Ajuste manual é ação sensível e exige log (Anexo I §7).",
   ["stock_item_id", "type (entrada|saida|ajuste|reserva|producao)", "qty", "before", "after",
    "reason", "actor_id", "order_id"]),
 T("production_requests", "novo", "catalogo", "id",
   "Solicitação de produção ou reposição como entidade própria: quantidade pedida × entregue, status, prioridade, prazo e solicitante. "
   "Fecha a lacuna 12 — <code>stock_movements</code> registra o lançamento consumado, não o pedido em aberto por trás do KPI “Reposições pendentes”.",
   ["product_variant_id", "stock_location_id", "qty_requested", "qty_delivered", "status",
    "priority", "due_date", "note", "requested_by", "completed_at"]),
 T("promotions", "novo", "catalogo", "id (code único)",
   "Campanha promocional B2B (Velaro → lojista). Não se confunde com a promoção que o revendedor faz na própria vitrine, que sai das regras de preço dele.",
   ["code", "name", "description",
    "type (desconto_progressivo|preco_especial|frete_gratis|desconto_fixo|lancamento)",
    "status (rascunho|agendada|ativa|pausada|encerrada)", "starts_at", "ends_at", "priority",
    "show_badge", "budget"]),
 T("promotion_rules", "novo", "catalogo", "id",
   "Faixas (tiers) da promoção: “acima de R$ 1.000 → 5%”, “acima de R$ 2.000 → 10%”. Cada linha é um limiar com percentual ou valor fixo.",
   ["promotion_id", "min_amount", "discount_percent", "discount_amount", "position"]),
 T("promotion_products", "novo", "catalogo", "id (único por promoção + alvo)",
   "Pivô que amarra a promoção a produtos e/ou coleções — declarado literalmente como “pivot produto/coleção”.",
   ["promotion_id", "product_id", "collection_id"]),
 T("promotion_audiences", "novo", "catalogo", "id",
   "Público-alvo e canais da campanha (“Todos os revendedores ativos”; Loja online, WhatsApp, E-mail).",
   ["promotion_id", "publico_alvo", "canais (json)"]),
 # ── revendedores ──────────────────────────────────────────────────────────
 T("resellers", "novo", "revendedores", "id (protocolo, cnpj e code únicos)",
   "O lojista / Parceiro Premium com CNPJ. Entidade central do modelo B2B e origem do escopo <code>reseller_id</code> em todo o resto. "
   "Tem SoftDeletes: o cadastro sai de cena sem levar pedido nem nota fiscal junto.",
   ["protocolo", "code", "razao_social", "nome_fantasia", "cnpj", "inscricao_estadual",
    "responsavel_nome", "responsavel_cpf", "email", "telefone", "whatsapp", "cep", "logradouro",
    "numero", "complemento", "bairro", "cidade", "uf", "origem_contato",
    "registration_type (automatico|manual)", "observacoes", "observacoes_internas",
    "status (pre_cadastro|aprovado|reprovado|inativo)", "approved_at", "approved_by",
    "rejected_at", "rejection_reason", "deleted_at"]),
 T("reseller_documents", "novo", "revendedores", "id",
   "Os 3 uploads obrigatórios do cadastro: contrato social, documento do sócio e cartão CNPJ.",
   ["reseller_id", "type (contrato_social|documento_socio|cartao_cnpj)", "original_name", "disk",
    "path", "size_bytes", "mime"]),
 T("reseller_cnaes", "novo", "revendedores", "id (reseller_id + code único)",
   "CNAEs informados pelo lojista, com a marcação de compatibilidade com o segmento produzida pela triagem por IA.",
   ["reseller_id", "code", "description", "is_primary", "compatible"]),
 T("reseller_verifications", "novo", "revendedores", "id",
   "Resultado de cada rodada da verificação automática de CNPJ e CNAE. É triagem, nunca decisão final — a decisão é humana e fica em reseller_status_events.",
   ["reseller_id", "status", "cnpj_valido", "empresa_ativa", "cnaes_compativeis",
    "documentacao_enviada", "score", "result (json)", "raw_payload (json)", "checked_at"]),
 T("reseller_status_events", "novo", "revendedores", "id",
   "Linha do tempo da solicitação, com ator e justificativa de cada decisão humana: aprovar, pedir informação, reprovar.",
   ["reseller_id", "from_status", "to_status", "actor_id", "note"]),
 T("reseller_consents", "novo", "revendedores", "id",
   "Aceites do lojista no cadastro (declaração de lojista, autorização de consulta de CNPJ/CNAE, política de privacidade), gravados com "
   "versão do texto, IP e user-agent. Fecha a lacuna 8: <code>customer_consents</code> cobria só o consumidor final, e o aceite do "
   "revendedor é requisito de LGPD, não detalhe.",
   ["reseller_id", "type", "granted", "document_version", "granted_at", "revoked_at",
    "ip_address", "user_agent"]),
 T("customers", "extensao", "revendedores", "id",
   "O consumidor final. <strong>Não tem login</strong>: existe só como registro na carteira de um revendedor, e o pagamento dele é feito "
   "no caixa da loja. <strong>A tabela 1:1 <code>customer_velaro_details</code> foi eliminada</strong> — o dono da carteira "
   "(<code>reseller_id</code>), o endereço e as datas de relacionamento que alimentam campanha moram aqui.",
   ["[core] user_id", "reseller_id", "name", "person_type (PF|PJ)", "[core] company_name", "email",
    "phone", "document (CPF/CNPJ)", "cep", "endereco", "cidade", "uf", "data_nascimento",
    "data_casamento", "data_namoro", "origem_contato", "notes", "[core] meta"]),
 T("customer_consents", "novo", "revendedores", "id",
   "Histórico de consentimento LGPD do consumidor final. É tabela própria, e não booleano no cliente, porque o consentimento é revogável e precisa de trilha.",
   ["customer_id", "type (marketing|transacional)", "granted", "granted_at", "revoked_at",
    "channel", "evidence"]),
 # ── pedidos ───────────────────────────────────────────────────────────────
 T("orders", "extensao", "pedidos", "id (rota por public_number)",
   "Pedido do lojista à Velaro, sempre endereçado por <code>public_number</code> — o id interno nunca é exposto. "
   "<strong>A tabela 1:1 <code>order_velaro_details</code> foi eliminada</strong>: o eixo B2B, o lote, a remessa, os dois status "
   "independentes, as quatro linhas de valor (<code>subtotal_amount</code>, <code>engraving_amount</code>, <code>shipping_amount</code>, "
   "<code>discount_amount</code>), a previsão e a retirada (<code>previsao</code>, <code>arrived_at</code>, <code>retirado_em</code>, "
   "<code>retirado_por</code>, <code>retirado_por_documento</code>) moram aqui. "
   "<code>status</code> do scaffold permanece como espelho derivado e não é autoridade — quem manda é o par operacional/financeiro.",
   ["[core] user_id", "reseller_id", "customer_id", "batch_id", "shipment_id", "public_number",
    "operational_status", "payment_status", "total_amount", "retirado_por_customer_id"]),
 T("order_items", "extensao", "pedidos", "id",
   "Item do pedido com <code>unit_price</code> congelado no momento da seleção: mudança de preço no catálogo não altera pedido existente. "
   "Ganhou <code>product_variant_id</code> — o item aponta para o SKU/aro, que é o que sai do cofre.",
   ["order_id", "product_id", "product_variant_id", "quantity", "unit_price (snapshot imutável)",
    "[core] total_price", "[core] status", "[core] meta"]),
 T("order_item_engravings", "novo", "pedidos", "id (order_item_id único)",
   "Gravação adicional (sim/não, texto, data), cobrada e discriminada à parte, uma linha por item. O limite de caracteres e o preço vêm "
   "de <code>settings</code>; o total cobrado sobe para <code>orders.engraving_amount</code>.",
   ["order_item_id", "enabled", "text", "date", "chars", "price"]),
 T("order_status_events", "novo", "pedidos", "id",
   "Timeline do pedido com ator e nota. <code>scope</code> diz de qual dos dois status é a transição — operacional "
   "(registrado → pago → produção → transporte → pronto → retirado) ou financeiro — porque os dois correm independentes.",
   ["order_id", "scope (operacional|financeiro)", "from_status", "to_status", "actor_id", "note"]),
 T("order_promotions", "novo", "pedidos", "id (pedido + promoção único)",
   "Campanha efetivamente aplicada a um pedido, com o desconto congelado em reais no momento da aplicação. Fecha a metade da lacuna 4 "
   "que <code>orders.discount_amount</code> não cobre: sem este vínculo, o tipo <code>frete_gratis</code> não tem como ser auditado depois.",
   ["order_id", "promotion_id", "type", "discount_amount", "applied_at"]),
 # ── financeiro ────────────────────────────────────────────────────────────
 T("order_batches", "novo", "financeiro", "id (code único)",
   "Lote semanal de faturamento Velaro → lojista: agrupa pedidos, tem data de corte e vencimento, e é a unidade de pagamento, de nota "
   "fiscal e de liberação de remessa. Ganhou os campos de retirada do lote inteiro, que a tela 3.6 distingue da retirada por pedido.",
   ["code", "reseller_id", "cut_date", "due_date", "status", "total_amount", "paid_at",
    "shipped_at", "arrived_at", "retirado_em", "retirado_por", "retirado_por_documento"]),
 T("payments", "novo", "financeiro", "id",
   "Recebimento do lojista PARA a Velaro por meio B2B. Não existe saldo, carteira de recebível B2C nem saque do consumidor. "
   "<code>reconciled_by</code> guarda quem deu a baixa — ação sensível, exige log.",
   ["batch_id", "method (pix|boleto|transferencia)", "amount", "due_date", "paid_at", "status",
    "external_id", "receipt_path", "reconciled_by"]),
 T("invoices", "novo", "financeiro", "id (série + número únicos)",
   "NF-e da venda B2B da Velaro ao lojista, emitida <strong>por lote</strong>. A NF do consumidor final é emitida pelo lojista, fora desta plataforma.",
   ["batch_id", "number", "series", "amount", "status", "issued_at", "pdf_path", "xml_path",
    "provider", "issued_by"]),
 T("invoice_items", "novo", "financeiro", "id (nota + pedido único)",
   "Rateio da nota do lote pelos pedidos que a compõem. Fecha a lacuna 5 sem escolher um lado: a chave do documento fiscal é o lote "
   "(tela 3.5) e a coluna “NF-E” por pedido da tela 2.4 resolve por aqui.",
   ["invoice_id", "order_id", "amount"]),
 T("shipments", "novo", "financeiro", "id (code único)",
   "Remessa física do lote até a loja: transportadora, código e link de rastreio, liberação logística com ator, e as datas de expedição, "
   "previsão e entrega. Fecha a lacuna 3 — antes o transporte inteiro cabia em <code>order_batches.shipped_at</code>.",
   ["code", "order_batch_id", "reseller_id", "status", "carrier", "tracking_code", "tracking_url",
    "released_by", "released_at", "shipped_at", "estimated_at", "delivered_at", "notes"]),
 # ── vitrine ───────────────────────────────────────────────────────────────
 T("reseller_stores", "novo", "vitrine", "id (reseller_id, slug e domain únicos)",
   "Identidade white-label da vitrine do lojista. É a ÚNICA fonte de pintura da vitrine (--shop-*) e do roteamento por slug ou domínio "
   "próprio. Os cinco toggles de comportamento (mostrar preço, retirada e pagamento na loja, marca própria, ocultar o fornecedor) "
   "moram aqui — parte da lacuna 9.",
   ["reseller_id", "name", "slogan", "logo_path", "banner_path", "slug", "domain", "phone",
    "whatsapp", "email", "endereco", "color_primary", "color_secondary", "color_background",
    "color_text", "own_brand_only", "hide_supplier_brand", "show_prices", "pickup_only",
    "payment_in_store", "is_active", "published_at"]),
 T("reseller_store_categories", "novo", "vitrine", "id (vitrine + categoria único)",
   "As categorias que o lojista escolhe exibir na própria vitrine, na ordem que ele definir. Sem ela a vitrine herdaria a taxonomia "
   "inteira da Velaro, que é o oposto do que a tela 2.9 pede.",
   ["reseller_store_id", "category_id", "position"]),
 T("reseller_store_products", "novo", "vitrine", "id (vitrine + produto único)",
   "Curadoria e destaque de produtos na vitrine (“12 produtos selecionados”), com ordem e marcação de destaque. É seleção do lojista "
   "sobre o catálogo Velaro, não um catálogo paralelo.",
   ["reseller_store_id", "product_id", "position", "is_featured"]),
 T("reseller_price_settings", "novo", "vitrine", "id (reseller_id único)",
   "Configuração global de precificação de um lojista: modelo, multiplicador padrão, margem global/mínima/ideal/máxima, arredondamento "
   "e os três toggles. Fecha a lacuna 10 — <code>reseller_price_rules</code> guarda a exceção, esta guarda o padrão.",
   ["reseller_id", "pricing_model", "multiplier", "margin_global", "margin_min", "margin_ideal",
    "margin_max", "rounding", "rule_scope", "apply_to_all", "allow_manual_override",
    "allow_promotional_prices", "recalculated_at"]),
 T("reseller_price_rules", "novo", "vitrine", "id",
   "Regras com que o lojista forma o preço B2C sobre o custo Velaro (multiplicador, percentual, manual, promo), resolvidas por prioridade "
   "e por escopo — do global ao produto avulso.",
   ["reseller_id", "scope (global|collection|product)", "collection_id", "product_id",
    "mode (multiplier|percent|manual|promo)", "value", "rounding", "priority", "is_active"]),
 T("favorites", "novo", "vitrine", "id (visitante + produto único)",
   "O ♡ dos cards da vitrine e do catálogo público. Como o consumidor final não faz login, a chave é o <code>visitor_token</code> do "
   "navegador; <code>customer_id</code> só é preenchido quando o lojista já conhece a pessoa. Parte da lacuna 18.",
   ["product_id", "reseller_store_id", "customer_id", "visitor_token"]),
 # ── suporte ───────────────────────────────────────────────────────────────
 T("support_tickets", "novo", "suporte", "id (rota por code)",
   "Chamado Velaro ↔ revendedor, opcionalmente vinculado a um pedido e ao cliente final — que aparece como pessoa citada, nunca como "
   "participante. Ganhou o bloco de diagnóstico (ambiente, navegador, sistema, IP) e os marcos de SLA que a tela 3.12 exige.",
   ["code", "reseller_id", "order_id", "customer_id", "subject", "category", "priority", "status",
    "assignee_id", "channel", "environment", "browser", "os", "ip_address", "first_response_at",
    "resolved_at", "closed_at"]),
 T("support_messages", "novo", "suporte", "id",
   "Mensagens da thread. <code>is_internal_note</code> marca observação interna que NUNCA pode ser exposta ao revendedor.",
   ["ticket_id", "author_id", "author_role (revendedor|velaro)", "body", "is_internal_note"]),
 T("support_attachments", "novo", "suporte", "id",
   "Anexos do atendimento (fotos, PDFs). Pendura sempre no chamado e, quando veio dentro de uma mensagem, também nela — é o que faz a "
   "lista consolidada e a mensagem mostrarem o mesmo arquivo.",
   ["ticket_id", "message_id", "original_name", "disk", "path", "size_bytes", "mime"]),
 T("support_tags", "novo", "suporte", "id (slug único)",
   "Vocabulário de etiquetas do suporte (Troca, Tamanho, Aliança, Ouro 18K). Tabela própria, e não texto solto no chamado, para o filtro "
   "por tag da tela 3.12 fazer sentido. Parte da lacuna 13.",
   ["name", "slug"]),
 T("support_ticket_tag", "novo", "suporte", "id (chamado + tag único)",
   "Pivô chamado × etiqueta: um chamado carrega várias tags e a mesma tag atravessa vários chamados. Sem timestamps — é vínculo, não evento.",
   ["support_ticket_id", "support_tag_id"]),
 T("support_status_events", "novo", "suporte", "id",
   "Timeline de transições do chamado, com ator, canal e nota. <code>support_messages</code> não servia: a tela 3.12 mostra a mudança de "
   "status separada da conversa.",
   ["ticket_id", "from_status", "to_status", "actor_id", "channel", "note"]),
 # ── config ────────────────────────────────────────────────────────────────
 T("settings", "novo", "config", "id (chave lógica key)",
   "Configuração chave/valor por grupo: conteúdo institucional do site (<code>company.*</code>, <code>contact.*</code>, "
   "<code>about.*</code>), parâmetros de gravação (<code>gravacao.max_chars</code>, <code>gravacao.preco</code>) e de lote, e os nove "
   "grupos da tela de Configurações do Master. <code>is_public</code> separa o que a vitrine pode ler do que só o Master vê.",
   ["group", "key", "value", "type", "is_public"]),
 T("notification_logs", "novo", "config", "id",
   "Trilha de envio dos avisos transacionais (cadastro aprovado, pedido pronto para retirada) por e-mail e WhatsApp, sempre disparados "
   "por job. Ganhou as três FKs opcionais que faltavam — sem elas não dava para listar os envios de um pedido.",
   ["type", "channel (email|whatsapp)", "recipient", "recipient_type (revendedor|cliente)",
    "order_id", "reseller_id", "customer_id", "status", "sent_at", "provider_message_id",
    "error_message"]),
 T("report_schedules", "novo", "config", "id",
   "Agendamento recorrente de relatório (“Toda segunda às 08:00”), com destinatários, filtros e formato.",
   ["name", "type", "cron", "recipients (json)", "filters (json)", "format", "is_active", "last_run_at"]),
 T("report_exports", "novo", "config", "id",
   "Cada exportação já gerada, com os filtros usados e o arquivo. Exportação pesada roda sempre em job, e a linha nasce antes do arquivo existir.",
   ["report_schedule_id", "type", "filters (json)", "format", "status", "file_path",
    "generated_by", "generated_at"]),
 T("help_categories", "novo", "config", "id (slug único)",
   "Seções da central de ajuda do portal (primeiros passos, pedidos, financeiro), ordenáveis e desligáveis sem apagar o conteúdo.",
   ["name", "slug", "position", "is_active"]),
 T("help_articles", "novo", "config", "id (slug único)",
   "Conteúdo da central de ajuda nos três formatos que a tela 2.8 oferece: pergunta frequente, guia/manual e vídeo tutorial — daí "
   "<code>type</code>, <code>video_url</code> e <code>file_path</code> convivendo na mesma tabela. Parte da lacuna 14.",
   ["help_category_id", "type (faq|guia|video)", "title", "slug", "excerpt", "body", "video_url",
    "file_path", "position", "is_published"]),
 T("contact_leads", "novo", "config", "id",
   "Contato vindo do site público (“Fale Conosco”, “Solicitar atendimento”, “Falar com especialista”). É lead, não chamado: quem ainda "
   "não é revendedor não tem <code>support_tickets</code>. Fecha a outra metade da lacuna 14.",
   ["name", "email", "phone", "company", "subject", "message", "origin", "status", "handled_by",
    "handled_at"]),
 # ── acesso ────────────────────────────────────────────────────────────────
 T("users", "extensao", "acesso", "id (email e google_id únicos)",
   "Login único do scaffold. Master e equipe Velaro entram por <code>is_admin</code> + gate access-backend; o lojista entra amarrado ao "
   "CNPJ por <code>reseller_id</code>, que hoje é coluna assumida do core e não mais uma anomalia. O consumidor final "
   "<strong>não tem linha aqui</strong> — ele não faz login em lugar nenhum da plataforma.",
   ["name", "email", "phone", "document", "password", "google_id", "two_factor_secret",
    "two_factor_confirmed_at", "is_admin", "is_agent", "is_blocked", "reseller_id",
    "theme_preference", "api_token_hash", "api_token_expires_at"]),
 T("audit_logs", "core", "acesso", "id",
   "Trilha imutável de escrita no backend: ator, ação, alvo polimórfico, before/after e a origem da requisição. Era a lacuna 1 — a "
   "tabela sempre existiu no core, o que falta é declarar o que cada tela grava aqui, incluindo o par início/fim da sessão de "
   "“ver como revendedor”.",
   ["actor_id", "action", "target_type", "target_id", "before (json)", "after (json)",
    "ip_address", "user_agent"]),
 T("acl_*", "core", "acesso", "id (por tabela do conjunto)",
   "Conjunto ACL do scaffold (<code>acl_permissions</code>, <code>acl_responsibilities</code>, "
   "<code>acl_responsibility_permission</code>, <code>acl_user_responsibility</code>, <code>acl_user_permission_overrides</code>) que "
   "sustenta as ~36 chaves <code>velaro.*</code> e os gates do Master. O schema está de pé; as permissões ainda não foram semeadas.",
   ["key", "module", "label", "name", "responsibility_id", "permission_id", "user_id",
    "is_allowed", "assigned_by"]),
]
IDX = {t["nome"]: t for t in TAB}

# ═══════════════════════════════════ RELAÇÕES ═══════════════════════════════════
# (de = lado N, carrega a FK) · (para = lado 1, referenciado) · leitura: para(1):de(N)
def R(de, para, campo, card, conf, motivo):
    return dict(de=de, para=para, campo=campo, card=card, conf=conf, motivo=motivo)

REL = [
 # ── catálogo: o produto e as tabelas de domínio que o descrevem ──
 R("products","collections","collection_id","1:N","declarada",
   "A coleção é a aba comercial do catálogo (Classic, Diamond, Premium) e agrupa N peças. Virou coluna do produto quando a extensão 1:1 foi dissolvida — "
   "sem isso toda rota pública pagaria um JOIN. <code>SET NULL</code>: aposentar uma coleção não pode tirar a peça do ar, ela só fica sem coleção até ser reclassificada."),
 R("products","categories","category_id","1:N","declarada",
   "A taxonomia de navegação (Alianças Tradicionais, Solitários, Acessórios). Uma peça mora em uma categoria só; a hierarquia fica na auto-relação de <code>categories</code>. "
   "<code>SET NULL</code> pelo mesmo motivo da coleção: reorganizar a árvore não pode derrubar catálogo."),
 R("products","materials","material_id","1:N","declarada",
   "<code>materials</code> é tabela de domínio (Prata 950, Ouro Amarelo 18k, Ouro Rosé 18k, Aço): serve de filtro na vitrine e de linha da ficha técnica de joalheria. "
   "Uma peça é de um material; o material vale para N peças."),
 R("products","finishes","finish_id","1:N","declarada",
   "A outra metade da ficha de joalheria (Polida, Fosca, Diamantada, Cravejada, Texturizada, PVD). Domínio fechado e editável pelo Master, não texto livre — "
   "é o que faz o filtro de acabamento existir."),
 R("products","users","user_id","1:N","declarada",
   "Quem cadastrou a peça. Herança do scaffold que deixou de ser destrutiva: passou de <code>NOT NULL</code>/<code>CASCADE</code> para <code>nullable</code>/<code>SET NULL</code>, "
   "porque o catálogo é da Velaro e não do operador que digitou — apagar um usuário não pode evaporar produto."),
 R("categories","categories","parent_id","1:N","declarada",
   "Auto-relação: a taxonomia é árvore, não lista. <code>SET NULL</code> promove a subcategoria a raiz em vez de derrubar o ramo inteiro junto com a categoria-mãe."),
 R("product_variants","products","product_id","1:N","declarada",
   "O aro é a unidade real de venda e de cofre: um produto rende N SKUs, um por tamanho, com <code>UNIQUE(product_id, aro)</code> impedindo aro repetido. "
   "<code>CASCADE</code> — variante sem produto não significa nada."),
 R("product_images","products","product_id","1:N","declarada",
   "A galeria da peça. <code>position</code> e <code>is_primary</code> decidem a capa e a ordem do carrossel nos três ambientes (site, vitrine e portal); "
   "<code>CASCADE</code> porque imagem é parte do produto, não ativo independente."),
 R("product_revisions","products","product_id","1:N","declarada",
   "Histórico <code>before</code>/<code>after</code> de cada edição da peça — é o que a ação “Histórico de alterações” abre no Master. "
   "<code>products.price</code> é o custo B2B: mexer nele tem que deixar rastro."),
 R("product_revisions","users","actor_id","1:N","declarada",
   "Quem editou. <code>SET NULL</code> mantém a revisão legível depois que o operador sai da empresa — a linha do histórico não pode desaparecer junto com ele."),

 # ── estoque e produção ──
 R("stock_items","product_variants","product_variant_id","1:N","declarada",
   "Saldo por SKU/aro, propriedade da Velaro (o portal do lojista lê <code>disponivel</code> e nunca escreve). Deixou de ser 1:1 ao ganhar local: "
   "a chave é <code>UNIQUE(product_variant_id, stock_location_id)</code>, ou seja, uma linha de saldo por variante <em>por cofre</em>."),
 R("stock_items","stock_locations","stock_location_id","1:N","declarada",
   "O “Local de armazenamento” (Matriz - Cofre A1) e o filtro por local da tela de estoque. <code>SET NULL</code> porque desativar um cofre não pode zerar saldo: "
   "a linha fica sem local até ser realocada."),
 R("stock_movements","stock_items","stock_item_id","1:N","declarada",
   "O razão do estoque: entrada, saída, ajuste, reserva e produção, cada uma gravando <code>before</code> e <code>after</code> daquele saldo. "
   "<code>CASCADE</code> — o extrato morre com a conta que ele explica."),
 R("stock_movements","users","actor_id","1:N","declarada",
   "Ajuste manual é ação sensível e exige log com responsável (“Ajuste manual −2 (Admin)”). Nulo quando o lançamento nasce de job, e é justamente essa distinção que a coluna guarda."),
 R("stock_movements","orders","order_id","1:N","declarada",
   "Amarra a reserva ao pedido que a causou (“Reserva −6 (Pedido #5841)”), fechando a conta entre o que está separado e o que foi vendido. "
   "<code>SET NULL</code>: se o pedido for expurgado, o movimento continua explicando o saldo."),
 R("production_requests","product_variants","product_variant_id","1:N","declarada",
   "A solicitação de produção pede um SKU, não um produto genérico — reposição é sempre por aro. É o registro que faltava entre “reposição sugerida” e o lançamento consumado: "
   "aqui há <code>qty_requested</code> × <code>qty_delivered</code>, status e prazo."),
 R("production_requests","stock_locations","stock_location_id","1:N","declarada",
   "Para qual local a produção deve entrar quando ficar pronta. <code>SET NULL</code> mantém a solicitação viva se o destino for desativado no meio do caminho."),
 R("production_requests","users","requested_by","1:N","declarada",
   "Quem apertou “Solicitar produção”. A solicitação é um compromisso em aberto, com prioridade e vencimento — por isso tem dono, ao contrário do movimento de estoque, que é fato consumado."),

 # ── promoções ──
 R("promotion_rules","promotions","promotion_id","1:N","declarada",
   "As faixas da campanha: “acima de R$ 1.000 → 5%”, “acima de R$ 2.000 → 10%”. N regras por promoção, avaliadas na ordem de <code>position</code>. "
   "<code>CASCADE</code> — faixa não sobrevive à campanha que a criou."),
 R("promotion_audiences","promotions","promotion_id","1:N","declarada",
   "Público-alvo e canais da campanha (“todos os revendedores ativos”; loja online, WhatsApp, e-mail). Fica em tabela própria porque um mesmo público se repete entre campanhas "
   "e os canais são lista, não coluna."),
 R("promotions","products","promotion_products.product_id","N:N","declarada",
   "Uma campanha cobre N peças e uma peça entra em N campanhas — <code>promotions.priority</code> existe exatamente para desempatar quando duas pegam o mesmo produto. "
   "O pivô é puro (promoção + alvo, nada mais) e tem <code>UNIQUE</code> em cada par, o que impede incluir a mesma peça duas vezes na mesma campanha."),
 R("promotions","collections","promotion_products.collection_id","N:N","declarada",
   "O mesmo pivô aceita a coleção inteira como alvo, em vez do produto avulso: é assim que “20% em toda a Diamond” se monta sem listar peça por peça, "
   "e é por isso que as duas colunas de alvo convivem nulas uma de cada vez."),
 R("order_promotions","orders","order_id","1:N","declarada",
   "O desconto que a campanha deu <em>àquele</em> pedido, congelado em <code>discount_amount</code> no momento em que ela pegou. Sem esta linha, "
   "<code>orders.discount_amount</code> seria um número sem procedência — e o tipo frete grátis não teria como se justificar."),
 R("order_promotions","promotions","promotion_id","1:N","declarada",
   "<code>RESTRICT</code> é o que importa aqui: campanha já aplicada a pedido não pode ser apagada. Encerrar promoção é mudar <code>status</code>; "
   "o histórico comercial trava a exclusão."),

 # ── revendedores: habilitação e o eixo reseller_id ──
 R("users","resellers","reseller_id","1:N","declarada",
   "O login do lojista, criado quando a aprovação sai. A FK mora em <code>users</code>, então um revendedor comporta N logins (a equipe da loja) sem duplicar cadastro. "
   "<code>SET NULL</code> deixa o login órfão em vez de sumir com a pessoa."),
 R("resellers","users","approved_by","1:N","declarada",
   "Quem, na equipe Velaro, aprovou o CNPJ. A decisão final é humana e é ação sensível: fica registrada com ator e <code>approved_at</code>. "
   "Esta relação e a anterior formam o único ciclo real do modelo — o desenho o quebra para conseguir empilhar as camadas."),
 R("reseller_documents","resellers","reseller_id","1:N","declarada",
   "Os uploads obrigatórios do cadastro: contrato social, documento do sócio e cartão CNPJ. <code>CASCADE</code> porque apagar o lojista de verdade "
   "tem que levar os documentos pessoais junto — é o apagamento LGPD, não um arquivamento."),
 R("reseller_cnaes","resellers","reseller_id","1:N","declarada",
   "Uma empresa declara vários CNAEs: <code>is_primary</code> marca o principal e <code>compatible</code> guarda o veredito da checagem de compatibilidade com joalheria. "
   "<code>UNIQUE(reseller_id, code)</code> impede o mesmo código duas vezes."),
 R("reseller_verifications","resellers","reseller_id","1:N","declarada",
   "1:N e não 1:1 porque a verificação de CNPJ/CNAE é reexecutável: cada rodada grava <code>checked_at</code>, <code>score</code> e o <code>raw_payload</code> recebido, "
   "e o valor da tabela está justamente em comparar rodadas."),
 R("reseller_status_events","resellers","reseller_id","1:N","declarada",
   "A linha do tempo da habilitação — cadastro recebido → validação → aprovação → liberação. É o histórico que <code>resellers.status</code>, sozinho, não conta."),
 R("reseller_status_events","users","actor_id","1:N","declarada",
   "Quem promoveu a transição. Nulo quando o evento vem da verificação automática, que também entra na mesma timeline."),
 R("reseller_consents","resellers","reseller_id","1:N","declarada",
   "Os aceites do lojista com data, IP, user-agent e <code>document_version</code>. É tabela e não booleano porque consentimento se revoga: "
   "<code>granted_at</code> e <code>revoked_at</code> convivem, e a versão exata do texto aceito precisa ser reconstituível anos depois."),

 # ── clientes finais ──
 R("customers","resellers","reseller_id","1:N","declarada",
   "A carteira de consumidores finais pertence ao lojista, e é esta coluna que escopa toda leitura. <code>CASCADE</code> assumido de propósito: "
   "<code>resellers</code> tem SoftDeletes, então o hard delete é ato deliberado de apagamento LGPD — e aí a carteira tem mesmo que ir junto."),
 R("customers","users","user_id","1:N","declarada",
   "Contraste direto com a linha acima: era <code>CASCADE</code> pelo scaffold e virou <code>SET NULL</code>. Apagar um usuário não pode mais apagar a carteira inteira de um lojista. "
   "O consumidor final não faz login em lugar nenhum da plataforma; esta coluna é só o operador que cadastrou."),
 R("customer_consents","customers","customer_id","1:N","declarada",
   "Consentimento do consumidor final por tipo (marketing, transacional), registrável e revogável — histórico com evidência, não flag na ficha. "
   "<code>CASCADE</code> segue o apagamento do titular."),

 # ── pedidos ──
 R("orders","resellers","reseller_id","1:N","declarada",
   "O eixo do modelo: todo pedido pertence a exatamente um lojista, e é esta coluna que a policy de escopo lê. <code>RESTRICT</code> — "
   "pedido é registro fiscal e trava a exclusão do cadastro. Encerrar lojista é mudar status, nunca deletar linha."),
 R("orders","customers","customer_id","1:N","declarada",
   "Para quem o lojista comprou; nulo em compra de reposição da própria vitrine, sem consumidor final. <code>SET NULL</code>: "
   "atender um pedido de esquecimento do cliente não pode apagar a venda que sustenta a nota fiscal."),
 R("orders","users","user_id","1:N","declarada",
   "Quem digitou o pedido. <code>SET NULL</code> pela mesma razão de produtos e clientes: usuário é login, não dono do dado de negócio."),
 R("orders","order_batches","batch_id","1:N","declarada",
   "O lote semanal agrupa os pedidos do lojista numa fatura só — é assim que a Velaro cobra. Nulo enquanto o pedido não entrou no fechamento; "
   "<code>SET NULL</code> devolve o pedido à fila se o lote for desfeito, em vez de destruí-lo."),
 R("orders","shipments","shipment_id","1:N","declarada",
   "A remessa que leva o pedido à loja. Transportadora, rastreio e datas moram em <code>shipments</code> porque uma remessa carrega N pedidos; "
   "a coluna fica nula até a liberação logística acontecer."),
 R("orders","customers","retirado_por_customer_id","1:N","declarada",
   "Quem retirou no balcão, <em>quando</em> é alguém da carteira. Para terceiro identificado existem <code>retirado_por</code> e <code>retirado_por_documento</code> em texto: "
   "o consumidor final não tem login, então a retirada nunca poderia apontar para <code>users</code>."),
 R("order_items","orders","order_id","1:N","declarada",
   "As linhas do pedido. <code>CASCADE</code> — item não existe fora do pedido que o contém."),
 R("order_items","products","product_id","1:N","declarada",
   "A peça comprada. <code>unit_price</code> é cópia congelada do custo B2B no instante da seleção: reajustar <code>products.price</code> depois "
   "não reescreve pedido nenhum, que é o que torna o histórico auditável."),
 R("order_items","product_variants","product_variant_id","1:N","declarada",
   "O aro escolhido — é ele que de fato sai do cofre e move estoque. Nulo em item anterior ao controle por SKU; "
   "<code>SET NULL</code> preserva a linha do pedido se a variante for aposentada do catálogo."),
 R("order_item_engravings","order_items","order_item_id","1:1","declarada",
   "A gravação é do ITEM, não do pedido: <code>UNIQUE(order_item_id)</code> garante um bloco por linha, com texto, data, contagem de caracteres e preço próprios. "
   "É o que permite gravar uma aliança do par e deixar a outra lisa."),
 R("order_status_events","orders","order_id","1:N","declarada",
   "A linha do tempo do pedido. <code>scope</code> separa os dois ciclos independentes — operacional e financeiro — para que ambos caibam no mesmo histórico sem se atropelarem."),
 R("order_status_events","users","actor_id","1:N","declarada",
   "Quem moveu o status. Nulo quando a transição vem de job: confirmação de pagamento e baixa automática também precisam aparecer na timeline."),

 # ── financeiro B2B: lote, pagamento, nota e remessa ──
 R("order_batches","resellers","reseller_id","1:N","declarada",
   "O lote é a fatura semanal de UM lojista, com data de corte, vencimento e total. <code>RESTRICT</code> pela mesma razão do pedido: "
   "documento financeiro não some junto com o cadastro que o originou."),
 R("payments","order_batches","batch_id","1:N","declarada",
   "O dinheiro entra por lote, não por pedido — o consumidor final paga no caixa da loja e nunca transaciona na plataforma. "
   "1:N para admitir tentativa, estorno e segundo comprovante dentro do mesmo lote."),
 R("payments","users","reconciled_by","1:N","declarada",
   "Quem deu a baixa financeira. Baixa é ação sensível e exige responsável registrado ao lado de <code>external_id</code> e <code>receipt_path</code>, "
   "porque é ela que libera a produção do lote."),
 R("invoices","order_batches","batch_id","1:N","declarada",
   "A NF-e é emitida no fluxo do lote, e é o lote que identifica o documento fiscal. <code>RESTRICT</code>: lote com nota emitida não pode ser apagado — "
   "o número da nota já saiu para a SEFAZ."),
 R("invoices","users","issued_by","1:N","declarada",
   "Quem emitiu a nota. <code>SET NULL</code> preserva o documento fiscal mesmo depois de o emissor deixar a equipe."),
 R("invoice_items","invoices","invoice_id","1:N","declarada",
   "O rateio da nota pelos pedidos do lote — foi assim que a emissão por lote e a coluna “NF-e” exibida pedido a pedido passaram a conviver sem escolher uma só granularidade. "
   "<code>CASCADE</code>: a linha de rateio pertence à nota."),
 R("invoice_items","orders","order_id","1:N","declarada",
   "Quanto da nota cabe a cada pedido, com <code>UNIQUE(invoice_id, order_id)</code> impedindo rateio duplicado. "
   "<code>RESTRICT</code> — pedido já faturado não pode ser removido por baixo do documento fiscal."),
 R("shipments","order_batches","order_batch_id","1:N","declarada",
   "A remessa costuma ser a expedição do lote inteiro, mas é entidade separada porque tem ciclo próprio (liberação → despacho → entrega) e pode sair fora do lote. "
   "<code>SET NULL</code> não derruba o rastreio já emitido."),
 R("shipments","resellers","reseller_id","1:N","declarada",
   "Para qual loja a caixa vai — obrigatório mesmo quando não há lote associado. <code>RESTRICT</code>, como todo registro logístico-fiscal do lojista."),
 R("shipments","users","released_by","1:N","declarada",
   "Quem fez a liberação logística e quando. É o carimbo humano que antecede o rastreio da transportadora, e o que separa “pronto” de “despachado”."),

 # ── vitrine white-label ──
 R("reseller_stores","resellers","reseller_id","1:1","declarada",
   "Uma vitrine por lojista — <code>UNIQUE(reseller_id)</code>. A loja tem identidade visual, <code>slug</code> e <code>domain</code> próprios e únicos, "
   "que é o que faz o roteamento white-label funcionar; nada no produto prevê um segundo storefront."),
 R("reseller_store_categories","reseller_stores","reseller_store_id","1:N","declarada",
   "Quais categorias aparecem na vitrine e em que ordem. É curadoria, não espelho do catálogo: o lojista escolhe o que quer expor."),
 R("reseller_store_categories","categories","category_id","1:N","declarada",
   "O outro lado da curadoria, com <code>UNIQUE(reseller_store_id, category_id)</code> impedindo repetição. <code>CASCADE</code> nos dois lados: "
   "some a categoria do catálogo, some a escolha que a exibia."),
 R("reseller_store_products","reseller_stores","reseller_store_id","1:N","declarada",
   "A faixa de destaques da vitrine: produtos escolhidos a dedo, com <code>position</code> e <code>is_featured</code> comandando a home da loja."),
 R("reseller_store_products","products","product_id","1:N","declarada",
   "A peça destacada. O preço mostrado ali nunca é <code>products.price</code> (que é o custo B2B): o valor ao consumidor sai das regras de formação do próprio lojista."),
 R("reseller_price_settings","resellers","reseller_id","1:1","declarada",
   "Os parâmetros globais de preço da loja — modelo, multiplicador, margem global/mínima/ideal/máxima, arredondamento e os toggles de sobrescrita manual e preço promocional. "
   "<code>UNIQUE(reseller_id)</code> porque é configuração: existe uma e só uma por lojista."),
 R("reseller_price_rules","resellers","reseller_id","1:N","declarada",
   "As exceções pontuais sobre esse padrão: N regras por lojista, resolvidas por <code>priority</code>. É aqui que “esta coleção sai com margem menor” cabe sem mexer no global."),
 R("reseller_price_rules","collections","collection_id","1:N","declarada",
   "Preenchida só quando <code>scope = collection</code> — a regra vale para a coleção inteira, e o produto novo que entrar nela já nasce com o preço certo."),
 R("reseller_price_rules","products","product_id","1:N","declarada",
   "Preenchida só quando <code>scope = product</code>. As duas colunas de alvo ficam nulas quando o escopo é global — é o mesmo padrão de alvo alternativo do pivô de promoções."),
 R("favorites","products","product_id","1:N","declarada",
   "O ♡ dos cards da vitrine e do catálogo público. Como o consumidor final não tem login, o favorito se ancora em <code>visitor_token</code> e não em usuário."),
 R("favorites","reseller_stores","reseller_store_id","1:N","declarada",
   "Em qual vitrine o coração foi dado — <code>UNIQUE(visitor_token, product_id, reseller_store_id)</code> impede o duplo clique virar dois registros. "
   "Nulo quando o favorito veio do catálogo público da Velaro, fora de qualquer loja."),
 R("favorites","customers","customer_id","1:N","declarada",
   "Preenchido quando dá para reconhecer o visitante como cliente da carteira, o que transforma interesse anônimo em oportunidade nominal para o lojista. "
   "<code>CASCADE</code> obedece ao apagamento do titular."),

 # ── suporte ──
 R("support_tickets","resellers","reseller_id","1:N","declarada",
   "O chamado é sempre Velaro ↔ revendedor, por isso obrigatório. <code>CASCADE</code>: o histórico de atendimento acompanha o apagamento LGPD do lojista."),
 R("support_tickets","orders","order_id","1:N","declarada",
   "O pedido relacionado, quando existe — é o que dá contexto ao atendimento sem precisar reconstruí-lo na conversa. Nulo em dúvida de sistema ou de cadastro."),
 R("support_tickets","customers","customer_id","1:N","declarada",
   "O consumidor final entra como pessoa citada no caso, nunca como participante: ele não tem login e não lê a thread."),
 R("support_tickets","users","assignee_id","1:N","declarada",
   "O responsável pelo atendimento na equipe Velaro; transferir chamado é trocar esta coluna. Nulo enquanto o ticket está na fila, sem dono."),
 R("support_messages","support_tickets","ticket_id","1:N","declarada",
   "A thread do chamado. <code>is_internal_note</code> marca a mensagem que nunca atravessa para o outro lado — nota interna divide tabela com a resposta ao lojista, "
   "mas não divide visibilidade."),
 R("support_messages","users","author_id","1:N","declarada",
   "Quem escreveu. <code>author_role</code> rotula o lado (revendedor ou Velaro), e como os dois são usuários autenticados a mesma FK atende aos dois."),
 R("support_attachments","support_tickets","ticket_id","1:N","declarada",
   "Anexo sempre pendurado no chamado — é o que sustenta o painel “Anexos” consolidado, independentemente da mensagem em que o arquivo entrou."),
 R("support_attachments","support_messages","message_id","1:N","declarada",
   "E também na mensagem, quando o arquivo veio dentro de uma. Por isso a coluna é nula: anexo agregado direto ao chamado não tem mensagem para apontar."),
 R("support_status_events","support_tickets","ticket_id","1:N","declarada",
   "A timeline de transições do chamado (aberto → em atendimento → resolvido → fechado), separada da thread: mensagem é conversa, evento é mudança de estado — "
   "e é o evento que alimenta métrica de primeira resposta e de resolução."),
 R("support_status_events","users","actor_id","1:N","declarada",
   "Quem mudou o estado. Nulo quando o fechamento é automático por inatividade."),
 R("support_ticket_tag","support_tickets","support_ticket_id","1:N","declarada",
   "Pivô das etiquetas do chamado (Troca, Tamanho, Aliança, Ouro 18k). <code>UNIQUE(support_ticket_id, support_tag_id)</code> impede a mesma etiqueta duas vezes no mesmo ticket."),
 R("support_ticket_tag","support_tags","support_tag_id","1:N","declarada",
   "O outro lado do pivô. A etiqueta é vocabulário compartilhado — criada uma vez, reaproveitada em N chamados — e é isso que torna “quantos chamados de troca este mês” "
   "uma pergunta respondível."),

 # ── configuração, notificação e conteúdo ──
 R("notification_logs","orders","order_id","1:N","declarada",
   "O aviso transacional precisa dizer de qual pedido fala; sem isso não há como listar as notificações enviadas dentro do pedido nem reenviar o aviso de retirada."),
 R("notification_logs","resellers","reseller_id","1:N","declarada",
   "O alvo quando o aviso é para a loja (cadastro aprovado, lote fechado). <code>SET NULL</code> nos três alvos: a trilha de envio sobrevive ao apagamento do destinatário, "
   "porque <code>recipient</code> guarda o endereço em texto."),
 R("notification_logs","customers","customer_id","1:N","declarada",
   "O alvo quando o aviso é para o consumidor final (“seu pedido está pronto para retirada”). <code>recipient_type</code> diz qual dos alvos vale em cada linha."),
 R("report_exports","report_schedules","report_schedule_id","1:N","declarada",
   "Liga a exportação ao agendamento que a disparou (“toda segunda às 08:00”). Nulo na exportação avulsa, pedida na mão; "
   "<code>SET NULL</code> permite desligar o agendamento sem apagar os arquivos que ele já gerou."),
 R("report_exports","users","generated_by","1:N","declarada",
   "Quem pediu a exportação. Relatório pesado roda em job, então é este registro que devolve o arquivo pronto à pessoa certa."),
 R("help_articles","help_categories","help_category_id","1:N","declarada",
   "A central de ajuda agrupa FAQ, guias e vídeos por seção. <code>SET NULL</code> deixa o artigo publicado e sem seção em vez de tirá-lo do ar quando a categoria é removida."),
 R("contact_leads","users","handled_by","1:N","declarada",
   "Quem atendeu o lead vindo de “Fale Conosco” e “Falar com especialista”. Com <code>handled_at</code>, é o que separa contato novo de contato já tratado — "
   "o lead do site é a porta de entrada do cadastro de revendedor."),

 # ── acesso ──
 R("audit_logs","users","actor_id","1:N","declarada",
   "A trilha imutável: toda escrita de backend grava ator, ação, alvo e o par <code>before</code>/<code>after</code>. É o único ator obrigatório do modelo — "
   "log sem responsável não é log. <code>CASCADE</code> é herança do scaffold e a exceção incômoda da política de exclusão: aqui apagar o usuário leva a trilha dele junto."),
 R("acl_*","users","user_id","1:N","declarada",
   "Todo o conjunto ACL pendura no login: a responsabilidade dá o papel e a exceção por usuário abre ou fecha uma permissão avulsa, ambas com <code>UNIQUE</code> por usuário "
   "e <code>CASCADE</code> — some o usuário, some o acesso dele. As duas ainda guardam quem concedeu (<code>SET NULL</code>), para que a revogação tenha autor. "
   "Aresta agregada: as cinco tabelas do conjunto viram uma caixa só no desenho."),
]

# ═══════════════════════════════════ LACUNAS ═══════════════════════════════════
LAC = [
 ("audit_logs não é declarado por nenhuma tela", "resolvida",
  "Diagnóstico: audit_logs não aparecia em NENHUMA lista de tabelas das 31 telas, embora fosse exigido pelo critério de aceite de todas elas "
  "(“Escrita no backend gera registro em audit_logs”) e por 5 regras críticas — login, configurações, aprovação/reprovação de cadastro, baixa "
  "financeira e ajuste de estoque. Fechada <b>sem migration</b>: a tabela do core já traz <code>actor_id</code>, <code>action</code>, o alvo "
  "morfológico <code>target_type</code>/<code>target_id</code>, <code>before</code>/<code>after</code>, <code>ip_address</code> e "
  "<code>user_agent</code> — cobre tudo o que as telas pedem. O que sobrou é dívida de documentação, não de schema: declarar tela a tela o que "
  "cada escrita grava, com destaque para o par início/fim da sessão de “Ver como revendedor” (impersonate) que a 3.10 exige nominalmente."),
 ("products.slug não existe no core", "resolvida",
  "Diagnóstico: <code>products.slug</code> era declarado pelas telas 1.3 e 3.7 e é a chave da rota pública GET /produto/{slug}, mas a tabela "
  "products do core não tinha a coluna (name, sku, description, price, is_active, meta) — e a regra vigente proibia mutar o core. Das duas "
  "saídas possíveis, pendurar o slug numa tabela de extensão 1:1 ou abrir exceção na regra de imutabilidade, venceu a segunda: <b>o core deixou "
  "de ser imutável</b> e <code>products.slug</code> nasceu como coluna única na própria products. Pagar um JOIN em toda rota pública para "
  "sustentar uma regra que a própria doc já furava não se justificava. <code>collections.slug</code> e <code>categories.slug</code>, que são "
  "tabelas novas, nunca tiveram o problema."),
 ("Não há tabela de remessa / transporte", "resolvida",
  "Diagnóstico: a regra 4 da tela 3.6 é explícita — “campos e status de transporte já entram no escopo mesmo sem a API da transportadora (§7.2)” "
  "—, a 3.5 tem “Liberação logística”, “Previsão de envio: 31/05/2024” e “próxima remessa semanal”, e o único campo existente era "
  "<code>order_batches.shipped_at</code>. Nasceu <b>shipments</b>, com <code>carrier</code>, <code>tracking_code</code>, "
  "<code>tracking_url</code>, <code>status</code>, a liberação logística com ator (<code>released_by</code>/<code>released_at</code>), "
  "<code>shipped_at</code>, <code>estimated_at</code> e <code>delivered_at</code>. <code>orders.shipment_id</code> amarra o pedido à remessa e "
  "<code>shipments.order_batch_id</code> amarra a remessa ao lote; no pedido, <code>previsao</code> e <code>arrived_at</code> fecham a timeline."),
 ("Pedido não tem frete nem desconto", "resolvida",
  "Diagnóstico: os protótipos 2.5 (“Subtotal R$ 450,00 · Gravação R$ 35,00 · Frete R$ 0,00 · Descontos R$ 0,00 · Total”), 2.10 e o drawer de 2.4 "
  "mostram as quatro linhas, mas orders só tinha <code>total_amount</code>. As quatro viraram coluna no próprio pedido: "
  "<code>subtotal_amount</code>, <code>engraving_amount</code>, <code>shipping_amount</code> e <code>discount_amount</code>. O vínculo com a "
  "campanha aplicada — que faltava, e sem o qual o tipo frete_gratis não se sustenta — é <b>order_promotions</b>, pivô com <code>type</code>, "
  "<code>discount_amount</code> rateado e <code>applied_at</code>, único por par pedido × promoção."),
 ("Granularidade da nota fiscal em conflito", "resolvida",
  "Diagnóstico: a tela 3.5 emite a NF dentro do fluxo do LOTE (passo 3 de 5), mas a 2.4 tem uma coluna “NF-E (Baixar NF | —)” linha a linha por "
  "PEDIDO, e invoices não declarava nem batch_id nem order_id. As duas telas foram atendidas, em vez de uma vencer: "
  "<code>invoices.batch_id</code> é a chave do documento fiscal, e <b>invoice_items</b> liga a nota a cada pedido do lote com o valor rateado "
  "(<code>amount</code>, único por par nota × pedido). A coluna da 2.4 baixa a NF do lote a que aquele pedido pertence."),
 ("notification_logs não diz a quem se refere", "resolvida",
  "Diagnóstico: a tabela tinha <code>type</code>, <code>channel</code>, <code>recipient</code> e <code>recipient_type</code> "
  "(revendedor|cliente), mas nenhuma FK — sem isso, a tela 2.11 (“painel de disparo/histórico” com reenvio) e o bloco “Notificações enviadas” da "
  "3.6 não conseguiam listar os envios de um pedido. Ganhou três FKs nullable — <code>order_id</code>, <code>reseller_id</code> e "
  "<code>customer_id</code>, todas ON DELETE SET NULL, para que o log sobreviva ao apagamento do alvo. O reenvio se apoia em "
  "<code>status</code>, <code>sent_at</code>, <code>provider_message_id</code> e <code>error_message</code>."),
 ("retirado_por não tem alvo possível", "resolvida",
  "Diagnóstico: o cliente final não tem login (regra da tela 0), então a coluna não podia apontar para users, e faltava decidir entre texto "
  "livre, documento ou FK para customers. Resolvida pelos três ao mesmo tempo, em orders: <code>retirado_por</code> (nome livre), "
  "<code>retirado_por_documento</code> e <code>retirado_por_customer_id</code> (FK opcional para customers), com <code>retirado_em</code> "
  "carimbando o ato — cobre tanto o cliente da carteira quanto o terceiro apenas identificado no balcão. A distinção da 3.6 entre “Confirmar "
  "retirada por pedido” e “Confirmar retirada do lote inteiro” virou schema: order_batches recebeu <code>retirado_em</code>, "
  "<code>retirado_por</code> e <code>retirado_por_documento</code> próprios."),
 ("Os aceites do lojista não têm tabela", "resolvida",
  "Diagnóstico: a regra 2 da tela 1.4 é literal — “aceites (termos + LGPD) gravados com data, IP e versão do texto” — e o protótipo tem 3 "
  "checkboxes obrigatórios (declaração de lojista, autorização de validação de CNPJ/CNAE, política de privacidade); customer_consents cobria só "
  "o CONSUMIDOR FINAL, e nada cobria o revendedor. Nasceu <b>reseller_consents</b>, espelhando a lógica do consentimento do cliente: "
  "<code>type</code> por aceite, <code>document_version</code> para a versão do texto, <code>ip_address</code> e <code>user_agent</code> como "
  "prova, e o par <code>granted_at</code>/<code>revoked_at</code> — aceite é histórico revogável, não booleano no cadastro."),
 ("As configurações da vitrine não cabem em reseller_stores", "resolvida",
  "Diagnóstico: a tela 2.9 lista toggles (“Mostrar preços ao cliente final”, “Retirada somente na loja”, “Pagamento realizado diretamente na "
  "loja”, “Exibir apenas a marca do lojista”) e a 2.6 acrescenta “Ocultar marca do fornecedor” — nenhum existia entre os 17 campos declarados. "
  "Os cinco viraram coluna na própria reseller_stores: <code>show_prices</code>, <code>pickup_only</code>, <code>payment_in_store</code>, "
  "<code>own_brand_only</code> e <code>hide_supplier_brand</code>. O que era N:N ganhou pivô próprio: <b>reseller_store_categories</b> "
  "(categorias visíveis, ordenadas por <code>position</code>) e <b>reseller_store_products</b> (o “Destaque de produtos — 12 produtos "
  "selecionados”, com <code>position</code> e <code>is_featured</code>). Ambos pendem da loja, não do revendedor, porque reseller_stores é 1:1 "
  "com o lojista."),
 ("Os parâmetros de margem do lojista não têm onde morar", "resolvida",
  "Diagnóstico: reseller_price_rules guarda regras pontuais (scope/mode/value/rounding/priority), mas as telas 2.6 e 2.7 pedem configuração "
  "global por revendedor. Em vez de espremer isso numa linha scope=global de semântica ampliada, nasceu <b>reseller_price_settings</b>, uma "
  "linha por lojista (<code>UNIQUE(reseller_id)</code>): <code>pricing_model</code>, <code>multiplier</code> (o 3,6x), "
  "<code>margin_global</code> e a faixa <code>margin_min</code>/<code>margin_ideal</code>/<code>margin_max</code> (40/50/60%), "
  "<code>rounding</code> (“Para cima (0,99)”), <code>rule_scope</code>, <code>recalculated_at</code> e os três toggles "
  "<code>apply_to_all</code>, <code>allow_manual_override</code> e <code>allow_promotional_prices</code>. As regras pontuais continuam em "
  "reseller_price_rules — configuração e exceção não se misturam na mesma linha."),
 ("Estoque não tem local", "resolvida",
  "Diagnóstico: a tela 3.4 tem o filtro “Local (Todos)” e a ficha “Local de armazenamento: Matriz - Cofre A1”, mas stock_items não tinha coluna "
  "de local e não existia tabela de locais. Nasceu <b>stock_locations</b> (<code>code</code>, <code>name</code>, <code>description</code>, "
  "<code>is_default</code>, <code>is_active</code>) e stock_items ganhou <code>stock_location_id</code>. A consequência antecipada no "
  "diagnóstico se confirmou: a chave única passou a ser <code>UNIQUE(product_variant_id, stock_location_id)</code> — o saldo "
  "(atual/reservado/disponível/mínimo/reposição) deixou de ser 1:1 com a variante e passou a existir por local, como a 3.4 exige."),
 ("Solicitação de produção / reposição não é entidade", "resolvida",
  "Diagnóstico: havia a permissão velaro.stock.request_production, o botão “Solicitar produção”, o KPI “Reposições pendentes 23” e o atalho "
  "“Reposição sugerida — 20 unidades (Gerar pedido →)”, mas o único registro era <code>stock_movements.type='producao'</code>, que é lançamento "
  "consumado e não solicitação. Nasceu <b>production_requests</b>, ancorada na variante e no local: <code>qty_requested</code> × "
  "<code>qty_delivered</code> (a entrega parcial que o movimento não sabia representar), <code>status</code>, <code>priority</code>, "
  "<code>due_date</code>, <code>requested_by</code>, <code>note</code> e <code>completed_at</code>. O movimento continua sendo o lançamento; a "
  "solicitação é o que o antecede."),
 ("support_tickets não cobre metade do painel da 3.12", "resolvida",
  "Diagnóstico: faltavam TAGS (Troca, Tamanho, Aliança, Ouro 18K), Ambiente, Navegador, Sistema operacional e IP de acesso — só "
  "<code>channel</code> existia, cobrindo “Canal de origem (Portal do Revendedor)” —, e faltava o histórico de status, que support_messages não "
  "resolvia porque o protótipo mostra uma timeline separada de transições com ator e data. Fechada em três frentes: <b>support_tags</b> mais o "
  "pivô <b>support_ticket_tag</b>; as colunas <code>environment</code>, <code>browser</code>, <code>os</code> e <code>ip_address</code> no "
  "chamado; e <b>support_status_events</b> (<code>from_status</code> → <code>to_status</code>, <code>actor_id</code>, <code>channel</code>, "
  "<code>note</code>) para a timeline."),
 ("Nada cobre conteúdo de ajuda nem lead do site", "resolvida",
  "Diagnóstico: a tela 2.8 oferece “Perguntas frequentes”, “Guias e manuais”, “Vídeos tutoriais” e “Central de ajuda completa”, e o site público "
  "(1.2, 1.3) tem “Fale Conosco”, “SOLICITAR ATENDIMENTO” e “FALAR COM ESPECIALISTA” — nada disso existia no modelo (settings.about.* cobre só "
  "texto institucional). Nasceram <b>help_categories</b> e <b>help_articles</b>, com <code>type</code> distinguindo FAQ, guia e vídeo dentro do "
  "mesmo acervo, mais <code>excerpt</code>, <code>body</code>, <code>video_url</code>, <code>file_path</code>, <code>position</code> e "
  "<code>is_published</code>. O lado do site virou <b>contact_leads</b>, com <code>origin</code> dizendo de qual chamada o lead veio e "
  "<code>status</code>/<code>handled_by</code>/<code>handled_at</code> sustentando a triagem."),
 ("Duas escalas de escopo sem regra de precedência", "resolvida",
  "Diagnóstico: o core escopava customers, products e orders por <code>user_id</code> NOT NULL com cascadeOnDelete, enquanto o módulo Velaro "
  "escopa por <code>reseller_id</code> — e um cascade no usuário errado apagava a carteira de clientes e o histórico de pedidos de um lojista "
  "inteiro. Precedência definida: <b>reseller_id é o eixo de escopo</b>. <code>user_id</code> ficou nas três tabelas, mas nullable e ON DELETE "
  "SET NULL — usuário é login, não dono de dado de negócio. As exclusões foram separadas por natureza do dado: "
  "<code>orders.reseller_id</code> é RESTRICT (pedido é registro fiscal, não some junto com o cadastro) e <code>customers.reseller_id</code> é "
  "CASCADE (a carteira pertence ao lojista, e resellers tem SoftDeletes, então o hard delete é ato deliberado de apagamento LGPD). products não "
  "recebeu reseller_id: o catálogo é da Velaro, e o recorte por lojista vive em reseller_price_rules e reseller_store_products."),
 ("Três status de pedido convivem sem hierarquia", "resolvida",
  "Diagnóstico: <code>orders.status</code> do core (draft → awaiting_payment → paid → in_progress → completed, com canceled/error terminais) "
  "convivia com dois campos novos e independentes; as telas 2.5/3.6 só falam dos dois novos, e nada dizia quem manda no conflito. Hierarquia "
  "declarada: <code>orders.operational_status</code> (registrado → pagamento_confirmado → producao_andamento → producao_finalizada → "
  "em_transporte → pronto_retirada → retirado) e <code>orders.payment_status</code> são <b>canônicos e independentes entre si</b>, como manda a "
  "regra 2 da tela 3.6. <code>orders.status</code> permanece na tabela como espelho derivado, mantido só para compatibilidade com o "
  "OrderWorkflowStatusService — nada no módulo Velaro deve lê-lo como autoridade. Em order_status_events, <code>scope</code> diz a qual dos "
  "dois eixos pertence cada transição."),
 ("users.reseller_id é a única mutação do core", "resolvida",
  "Diagnóstico: a nota fixa de cada doc afirmava “nenhuma tabela do núcleo é mutada”, e a saída oferecida era transformar o vínculo num pivô "
  "N:N entre revendedor e usuário. Resolvida na direção oposta: a afirmação caiu. <code>users.reseller_id</code> permanece como coluna "
  "(nullable, ON DELETE SET NULL) e deixou de ser exceção — é a mesma decisão que absorveu no core os campos Velaro de products, orders e "
  "customers. Nenhuma tela "
  "exercita um usuário vinculado a dois revendedores, e o pivô N:N cobraria um JOIN em toda leitura escopada."),
 ("Detalhes literais do protótipo sem coluna", "resolvida",
  "Diagnóstico: “TIPO DE CADASTRO (Automático | Manual)” do revendedor na 3.10 (<code>resellers.origem_contato</code> é outra coisa — "
  "Site/Indicação); “TIPO (Pessoa Física | Pessoa Jurídica)” do cliente final e o filtro correspondente na 3.2; “Prazo de entrega (Até 2 dias "
  "úteis)” e o regime “Sob encomenda” do produto na 2.2; e o favoritar (♡) dos cards da vitrine (2.9/2.10) e do catálogo público (1.3). Cada um "
  "virou coluna: <code>resellers.registration_type</code>, <code>customers.person_type</code>, <code>products.prazo_entrega_dias</code> e "
  "<code>products.is_made_to_order</code> — este último desfazendo a ambiguidade apontada, já que <code>stock_items.disponivel</code> zerado "
  "não distinguia “sob encomenda” de “sem estoque”. O ♡ ganhou tabela: <b>favorites</b>, chaveada por <code>visitor_token</code> para o "
  "visitante anônimo, com <code>customer_id</code> e <code>reseller_store_id</code> opcionais e unicidade por visitante × produto × loja."),
]

# ═════════════════════════ TELAS QUE USAM CADA TABELA ═════════════════════════
# Fonte: _mapa.py. O mapa cita as tabelas do conjunto ACL uma a uma; aqui elas
# colapsam na caixa agregada "acl_*", que é como o diagrama as desenha.
DESDOBRA = {t: ["acl_*"] for t in
            ("acl_permissions", "acl_responsibilities", "acl_responsibility_permission",
             "acl_user_responsibility", "acl_user_permission_overrides")}
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
                 "1:N — a FK mora na tabela do lado N e aponta para o lado 1"),
                (140, "edge edge--inf", "crow crow--inf", "N",
                 "tracejada — aresta que o desenho gera ao abrir um N:N pelo pivô"),
                (216, "edge", "crow", "1",
                 "1:1 — a FK tem índice único: uma linha para cada linha do alvo")]
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
      for o, d in [("novo", "Nasceu no módulo Velaro: criada pelas migrations do módulo, não existia no scaffold."),
                   ("extensao", "Tabela do core que ganhou coluna Velaro: <code>users</code>, <code>products</code>, <code>orders</code>, <code>customers</code> e <code>order_items</code>. O core deixou de ser imutável, e as três tabelas 1:1 de extensão foram absorvidas por ele."),
                   ("core", "Já existia no scaffold e é lida como está, sem nenhuma coluna nova.")])
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
      f'<div><h3>{esc(tit)}<span class="ergap__t">{ {"faltando":"falta tabela ou coluna","conflito":"conflito entre telas","trilha":"trilha de auditoria","resolvida":"fechada no schema"}[tom] }</span></h3>'
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
    <p>As {len(TAB)} tabelas do banco B2B da Velaro, agrupadas por domínio, com campos, chaves,
       relações e as telas que usam cada uma. Este desenho sai de um dump: cada coluna e cada relação
       foi extraída do <code>information_schema</code> do banco em pé, com as 54 migrations já
       aplicadas — <strong>o banco existe</strong>, e o que está aqui é o retrato dele, não uma
       proposta. As {len(mp.T)} telas do mapa entram só para dizer quem usa o quê.</p>
    <p>O eixo do modelo é <code>reseller_id</code>. Ele desce por <code>users</code>,
       <code>orders</code>, <code>customers</code>, <code>order_batches</code>, <code>shipments</code>,
       <code>reseller_stores</code>, <code>reseller_price_rules</code>, <code>reseller_price_settings</code>
       e <code>support_tickets</code> — sempre como coluna da própria tabela, sem 1:1 no meio.
       O consumidor final nunca é um <code>users</code>: ele é um <code>customers</code> dentro da
       carteira de um revendedor, e a relação financeira registrada aqui é sempre Velaro → lojista.</p>
    <div class="erstats">
      <div class="erstat"><b>{len(TAB)}</b><span>caixas no diagrama (de 71 no banco)</span></div>
      <div class="erstat"><b>{novos}</b><span>novas do módulo Velaro</span></div>
      <div class="erstat"><b>{core + ext_}</b><span>do core ({ext_} com coluna nova)</span></div>
      <div class="erstat"><b>{len(REL)}</b><span>relações (chaves estrangeiras reais)</span></div>
      <div class="erstat"><b>{len(DOM)}</b><span>domínios</span></div>
      <div class="erstat"><b>{len(LAC)}</b><span>lacunas fechadas</span></div>
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
    {notice("<strong>Toda linha deste diagrama é uma chave estrangeira que existe.</strong> "
            "Cada uma foi lida do <code>information_schema</code> do banco <code>velaro</code>, com a "
            "coluna que a carrega e a tabela referenciada — nada aqui é deduzido do nome da tabela nem "
            "da regra de negócio, e nada aqui espera confirmação para virar migration: as 54 migrations "
            "já rodaram. A única linha tracejada é a aresta que o próprio desenho gera ao abrir um "
            "N:N pelo pivô.")}
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
      <em>para(1) : de(N)</em>. Todas saíram do <code>information_schema</code> — a coluna “por quê”
      diz o que cada uma sustenta na aplicação.</p>
    {reltab}
  </section>

  <section class="ercard" id="lacunas">
    <h2>As {len(LAC)} lacunas — e como cada uma foi fechada</h2>
    <p class="lede">O que as telas exigiam e o modelo não sustentava. Nenhuma ficou em aberto: cada
      item virou coluna, tabela nova ou decisão de arquitetura, e as migrations que fecharam a lista já
      estão aplicadas — a chave do documento fiscal virou o lote com rateio por pedido, e o cascade no
      usuário errado virou <code>ON DELETE SET NULL</code>. O registro decisão por decisão está em
      <code>docs/banco-de-dados.md</code>.</p>
    <ol class="ergaps">{gaps}</ol>
  </section>

  <p class="erfim">Gerado por <code>_build_er.py</code> a partir de <code>_mapa.py</code> e do schema
    real do banco. Mudou o banco, mudou o desenho: rode <code>python3.14 _build_er.py</code> de novo —
    o <code>python3</code> do sistema é 3.9 e não roda este arquivo (f-string aninhada exige 3.12+).</p>
</div>'''

if __name__ == "__main__":
    W("er-banco.html", P("Diagrama do banco de dados", render()))
    print(f"  {len(TAB)} tabelas · {len(REL)} relações · {len(LIG)} ligações desenhadas · {len(LAC)} lacunas")
