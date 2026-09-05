# -*- coding: utf-8 -*-
"""Mapa das telas do Velaro: rota, acesso, tabelas, permissões e regras.
Fonte: Anexo I (escopo funcional), Plano de Negócio e os protótipos aprovados.
Gera mapa.html — as telas dos mockups linkam para as âncoras daqui."""

# status: 'pronta' (mockup existe) | 'aguardando' (layout pendente do GPT)
# origem: 'core' (tabela do scaffold lida como está) | 'extensao' (tabela do core que
#         recebeu colunas Velaro: products, orders, order_items, customers, users)
#         | 'novo' (nasce no módulo Velaro)

T = []  # telas

def tela(**k): T.append(k)

# ══════════════════════════ ETAPA 1 · SITE PÚBLICO ══════════════════════════
tela(slug="site-home", n="1.1", env="Site público", titulo="Página inicial B2B",
     rota="GET /", acesso="Público", status="pronta", arquivo="01-site-publico.html",
     anexo="§3.1",
     tabelas=[("collections","novo","name, slug, description, cover_path, position, is_active"),
              ("settings","novo","company.*, contact.* — telefone, e-mail, horário de atendimento"),
              ("contact_leads","novo","origin = home — o “Fale conosco” do menu e os CTAs “Solicitar atendimento” / “Falar com especialista” não gravam nada aqui: levam para a **1.8**, que é a dona do formulário")],
     permissoes=["—"],
     regras=["Comunicação expressa de que a plataforma é exclusiva para lojistas.",
             "Nenhum preço B2B renderizado nesta rota, nem em JSON embutido.",
             "Sem venda direta ao consumidor final.",
             "O “Fale conosco” do menu aponta para `GET /contato` (tela **1.8**); esta página só origina o lead, marcando `origin`."])

tela(slug="site-sobre", n="1.2", env="Site público", titulo="Sobre nós",
     rota="GET /sobre", acesso="Público", status="pronta", arquivo="10-site-sobre.html", anexo="§3.2",
     tabelas=[("settings","novo","about.* — história, fábrica própria, diferenciais, mídia"),
              ("contact_leads","novo","origin = sobre — o CTA final “Vamos crescer juntos? · SOLICITAR ATENDIMENTO (Fale com um especialista)” encaminha para a **1.8**, que grava o lead; esta página não tem formulário próprio")],
     permissoes=["—"],
     regras=["Página institucional: fábrica própria, qualidade, atendimento consultivo, logística, posicionamento B2B.",
             "O CTA de atendimento leva para `GET /contato` (tela **1.8**); o lead nasce lá, com `origin = sobre`."])

tela(slug="site-catalogo", n="1.3", env="Site público", titulo="Catálogo público",
     rota="GET /catalogo · GET /catalogo/{colecao} · GET /produto/{slug}",
     acesso="Público", status="pronta", arquivo="11-site-catalogo.html", anexo="§3.3",
     tabelas=[("products","extensao","name, slug, sku, description, is_active, collection_id, category_id, material_id, finish_id, largura_mm, formato, permite_gravacao"),
              ("product_variants","novo","sku, aro (tamanho), is_active"),
              ("product_images","novo","path, alt, position, is_primary"),
              ("collections","novo","name, slug, position, is_active — filtro “Coleção”"),
              ("categories","novo","name, slug, parent_id, position"),
              ("materials","novo","name, slug — ex.: Prata 950, Ouro Rosé 18k, Ouro Amarelo 18k, Aço"),
              ("finishes","novo","name, slug — ex.: Diamantada, Fosca, Polida, PVD Preto e Dourado, Texturizada, Cravejada"),
              ("favorites","novo","product_id, visitor_token — ícone de coração no card; o consumidor final não tem login")],
     permissoes=["—"],
     regras=["**Bloqueio de preço:** `products.price` nunca serializado nesta rota. A checagem entra em teste automatizado.",
             "Exibe material, acabamento, largura, formato e características técnicas.",
             "Preço e condição comercial só depois do cadastro aprovado."])

tela(slug="site-cadastro", n="1.4", env="Site público", titulo="Cadastro como lojista",
     rota="GET/POST /seja-revendedor", acesso="Público", status="pronta", arquivo="12-site-cadastro.html", anexo="§3.4",
     tabelas=[("resellers","novo","razao_social, nome_fantasia, cnpj, inscricao_estadual, responsavel_nome, responsavel_cpf, email, telefone, whatsapp, cep, logradouro, numero, complemento, bairro, cidade, uf, origem_contato, observacoes, status, protocolo"),
              ("reseller_documents","novo","type (contrato_social|documento_socio|cartao_cnpj), original_name, disk, path, size_bytes, mime"),
              ("reseller_cnaes","novo","code, description, is_primary, compatible"),
              ("reseller_consents","novo","type (termos|lgpd), granted, document_version, granted_at, ip_address, user_agent"),
              ("users","extensao","name, email, password — criado em estado de pré-cadastro; reseller_id ainda nulo")],
     permissoes=["—"],
     regras=["Form Request obrigatório; CNPJ e CPF validados por regra de dígito.",
             "Aceites (termos + LGPD) gravados com data, IP e versão do texto.",
             "Upload de 3 documentos; nasce em `status = pre_cadastro`.",
             "Dispara `VerifyResellerCnpjJob` — consulta externa nunca é síncrona."])

tela(slug="site-enviada", n="1.5", env="Site público", titulo="Solicitação enviada",
     rota="GET /solicitacao/{protocolo}/enviada", acesso="Link com protocolo",
     status="pronta", arquivo="13-site-enviada.html", anexo="§3.5",
     tabelas=[("resellers","novo","protocolo, created_at")],
     permissoes=["—"],
     regras=["Confirmação de recebimento com protocolo e resumo.",
             "Informa prazo de análise e canais de acompanhamento."])

tela(slug="site-status", n="1.6", env="Site público", titulo="Status da solicitação",
     rota="GET /solicitacao/{protocol} · POST /solicitacao/{protocol}/documentos",
     acesso="Pré-cadastro (acesso limitado ao proprio acompanhamento)",
     status="pronta", arquivo="14-site-status.html", anexo="§3.6 · §2",
     tabelas=[("resellers","novo","status (pending|awaiting_info|approved|rejected|inactive), approved_at, rejected_at, rejection_reason"),
              ("reseller_verifications","novo","status, cnpj_valido, empresa_ativa, cnaes_compativeis, score, checked_at"),
              ("reseller_status_events","novo","from_status, to_status, actor_id, note, created_at — alimenta a linha do tempo"),
              ("reseller_documents","novo","type, original_name, disk, path, size_bytes, mime — reenvio quando a Velaro pede informacao adicional")],
     permissoes=["—"],
     regras=["Linha do tempo: cadastro recebido → validação automática → aprovação final → liberação de acesso.",
             "Estado de pré-cadastro dá acesso **somente** ao acompanhamento da própria solicitação.",
             "Notificação a cada transição.",
             "Em `awaiting_info` a tela abre o reenvio de documentos: é a contraparte da ação "
             "**Solicitar informações adicionais** do Painel Master (3.11), que até aqui não tinha resposta possível.",
             "Documento reenviado registra evento em `reseller_status_events` e devolve a solicitação para `pending`."])

tela(slug="site-aprovado", n="1.7", env="Site público", titulo="Cadastro aprovado e liberação",
     rota="GET /solicitacao/{protocolo}/aprovado", acesso="Link transacional",
     status="pronta", arquivo="15-site-aprovado.html", anexo="§3.9",
     tabelas=[("resellers","novo","status = aprovado, approved_at, approved_by, code"),
              ("notification_logs","novo","type, channel (email|whatsapp), recipient, sent_at, provider_message_id, status")],
     permissoes=["—"],
     regras=["Aprovação libera o acesso de Parceiro Premium e cria o vínculo `users.reseller_id`.",
             "Aviso transacional por e-mail e/ou WhatsApp — sempre via job."])

tela(slug="site-contato", n="1.8", env="Site público", titulo="Fale conosco",
     rota="GET /contato · POST /contato", acesso="Público", status="pronta",
     arquivo="19-site-contato.html", anexo="§3.1 · §3.2",
     tabelas=[("contact_leads","novo","name, email, phone, company, subject, message, origin, status, handled_by, handled_at — **a única tela que grava esta tabela**; as chamadas “Fale conosco”, “Solicitar atendimento” e “Falar com especialista” das telas 1.1, 1.2 e 1.3 desembocam aqui, cada uma marcando o seu `origin`"),
              ("settings","novo","contact.* — telefone, WhatsApp, e-mail e horário de atendimento do bloco de canais diretos; os mesmos valores do rodapé do site, lidos com `is_public = true`")],
     permissoes=["—",
                 "A fila de leads é lida no Painel Interno; `handled_by` referencia `users.id` da equipe Velaro"],
     regras=["Rota pública com Form Request e throttle — é formulário aberto na internet.",
             "**Lead não é pré-cadastro:** o envio não cria `resellers`, não cria `users` e não libera preço. Quem quer revender é encaminhado para a **1.4**.",
             "**Lead não é chamado:** quem ainda não é revendedor não tem `support_tickets`; a mensagem nasce em `contact_leads` com `status = new`.",
             "`origin` guarda a página de partida (home, sobre, catálogo, contato) e a fila anda por `status`/`handled_by`/`handled_at`.",
             "Consentimento LGPD obrigatório para enviar, gravado com data, IP, user agent e versão do texto. `reseller_consents` **não serve** — exige `reseller_id`, e o lead ainda não é revendedor: as colunas de aceite precisam nascer em `contact_leads`.",
             "Nenhum preço B2B renderizado nesta rota."])

# ══════════════════════════ TRANSVERSAL ══════════════════════════
tela(slug="login", n="0", env="Transversal", titulo="Login único com roteamento por perfil",
     rota="GET/POST /login", acesso="Público", status="pronta", arquivo="20-login.html", anexo="§2",
     tabelas=[("users","extensao","email, password, is_admin, is_blocked, google_id, two_factor_secret, two_factor_confirmed_at, reseller_id — o vínculo com o Parceiro Premium"),
              ("audit_logs","core","actor_id, action, ip_address, user_agent — registro do login")],
     permissoes=["gate `access-backend` decide o destino do Master"],
     regras=["**Um ponto de login** identifica o perfil e direciona ao ambiente correspondente.",
             "Master → `/backend` · Parceiro Premium aprovado → `/portal` · Pré-cadastro → `/solicitacao/{protocolo}`.",
             "Revendedor reprovado ou inativo não autentica.",
             "Cliente final **não tem login** — existe só como `customers` na carteira do revendedor.",
             "Login entra em `audit_logs` (§7 exige log de ações sensíveis)."])

# ══════════════════════════ ETAPA 2 · PORTAL DO LOJISTA ══════════════════════════
P = dict(env="Portal do Lojista", acesso="Parceiro Premium aprovado — tudo escopado por `reseller_id`")

tela(slug="portal-dashboard", n="2.1", titulo="Dashboard do Lojista", **P,
     rota="GET /portal", status="pronta", arquivo="02-portal-lojista.html", anexo="§4.1",
     tabelas=[("orders","extensao","reseller_id, customer_id, public_number, operational_status, payment_status, total_amount, previsao — KPIs e tabela “Últimos pedidos”; todo somatório da tela é escopado por reseller_id"),
              ("customers","extensao","reseller_id, name — KPI “Clientes cadastrados” e coluna “Cliente final” da tabela “Últimos pedidos”"),
              ("support_tickets","novo","reseller_id, code, status — KPI “Chamados abertos” e pendências"),
              ("reseller_stores","novo","name, slogan, logo_path, banner_path, domain, is_active, published_at — cartão “Vitrine da sua loja”"),
              ("reseller_price_settings","novo","reseller_id, margin_global — item “definir margem padrão” do checklist de configuração")],
     permissoes=["policy `ResellerScope` em toda query"],
     regras=["Indicadores: em andamento, produção, prontos para retirada, pendência financeira, chamados, clientes.",
             "Nenhuma query sem filtro por `reseller_id` — vazamento entre revendedores é falha crítica."])

tela(slug="portal-catalogo", n="2.2", titulo="Catálogo Revendedor", **P,
     rota="GET /portal/catalogo", status="pronta", arquivo="30-portal-catalogo.html", anexo="§4.2",
     tabelas=[("products","extensao","price — **custo B2B**, visível só aqui; name, sku, description, is_active, collection_id, material_id, finish_id, largura_mm, formato (filtros), prazo_entrega_dias (ficha do drawer) e is_made_to_order (chip “Sob encomenda”); created_at (selo “NOVO” e ordenação “Lançamento”)"),
              ("product_variants","novo","sku, aro — disponibilidade por tamanho"),
              ("product_images","novo","path, position, is_primary — foto do card e galeria de miniaturas do drawer"),
              ("stock_items","novo","disponivel, stock_location_id — o portal apenas consulta"),
              ("collections","novo","name, slug, is_active — select “Coleção” e KPI “Coleções ativas”"),
              ("materials","novo","name, slug — select “Material”"),
              ("finishes","novo","name, slug — select “Acabamento”")],
     permissoes=["policy: revendedor aprovado"],
     regras=["Exibe o custo B2B Velaro. **Esse custo nunca chega à vitrine do consumidor.**",
             "Estoque é somente leitura: o controle físico pertence à Velaro (§6).",
             "Inclusão de item em pedido a partir daqui."])

tela(slug="portal-clientes", n="2.3", titulo="Clientes / CRM", **P,
     rota="GET /portal/clientes · /portal/clientes/{id}", status="pronta", arquivo="31-portal-clientes.html", anexo="§4.3 · §6",
     tabelas=[("customers","extensao","reseller_id, name, person_type, company_name, document (CPF), phone, email, cep, endereco, cidade, uf, data_nascimento, data_casamento, data_namoro, origem_contato, notes, created_at"),
              ("customer_consents","novo","customer_id, type (marketing|transacional), granted, granted_at, revoked_at, channel, evidence"),
              ("orders","extensao","customer_id, public_number, operational_status, created_at — coluna “último pedido” e KPI de pedidos em aberto")],
     permissoes=["policy `ResellerScope`"],
     regras=["**LGPD:** data de casamento/namoro só alimenta campanha com consentimento de marketing válido.",
             "O consentimento é registrável **e revogável** — por isso tabela própria com histórico, não booleano no cliente.",
             "Comunicação transacional e promocional são tratadas separadamente."])

tela(slug="portal-financeiro", n="2.4", titulo="Financeiro", **P,
     rota="GET /portal/financeiro", status="pronta", arquivo="32-portal-financeiro.html", anexo="§4.4 · §6",
     tabelas=[("order_batches","novo","code, cut_date, due_date, status, total_amount"),
              ("orders","extensao","batch_id, payment_status, total_amount — os pedidos que compõem o lote"),
              ("payments","novo","method (pix|boleto|transferencia), amount, due_date, paid_at, status, external_id, receipt_path"),
              ("invoices","novo","batch_id, number, series, amount, issued_at, pdf_path, xml_path"),
              ("invoice_items","novo","order_id, amount — rateio da nota do lote por pedido; é o que a coluna NF-E baixa")],
     permissoes=["policy `ResellerScope`"],
     regras=["Mostra pedidos e lotes da conta, custo Velaro, data máxima de pagamento, status financeiro, NF emitida.",
             "O revendedor paga **a Velaro** por meios B2B habilitados.",
             "**Não existe** saldo do consumidor, carteira de recebíveis B2C nem saque.",
             "Webhook de pagamento compara token com `hash_equals()`."])

tela(slug="portal-pedidos", n="2.5", titulo="Pedidos — lista e detalhe", **P,
     rota="GET /portal/pedidos · /portal/pedidos/{public_number}", status="pronta", arquivo="33-portal-pedidos.html", anexo="§4.5",
     tabelas=[("orders","extensao","public_number, customer_id, reseller_id, batch_id, shipment_id, operational_status, payment_status, previsao, retirado_em, retirado_por, notes, created_at, e os valores subtotal_amount, engraving_amount, shipping_amount, discount_amount, total_amount (as quatro linhas do “Resumo do pedido” mais o total)"),
              ("order_items","extensao","product_id, product_variant_id, quantity, unit_price (snapshot imutável), total_price"),
              ("order_item_engravings","novo","enabled, text, date, chars, price — card “Gravação interna”"),
              ("order_promotions","novo","promotion_id, type, discount_amount — explica a linha “Descontos” do resumo"),
              ("order_status_events","novo","scope, from_status, to_status, actor_id, note, created_at — timeline"),
              ("customers","extensao","name — coluna CLIENTE da lista e do drawer"),
              ("products","extensao","name, material_id, finish_id, formato — descrição do item no drawer (“Ouro 18k - Anat. / Polido”)"),
              ("product_variants","novo","aro"),
              ("product_images","novo","path, is_primary — miniatura do item")],
     permissoes=["policy `ResellerScope`"],
     regras=["**Campos separados** para Status do Pedido e Status do Pagamento — são independentes (§6).",
             "Detalhe registra eventual gravação adicional.",
             "Rota sempre por `public_number`; `orders.id` interno nunca é exposto."])

tela(slug="portal-loja", n="2.6", titulo="Personalização da loja", **P,
     rota="GET/PUT /portal/loja", status="pronta", arquivo="34-portal-loja.html", anexo="§4.6 · §4.9",
     tabelas=[("reseller_stores","novo","name, slogan, logo_path, banner_path, slug, domain, phone, whatsapp, email, endereco, color_primary, color_secondary, color_background, color_text, own_brand_only, hide_supplier_brand, show_prices, pickup_only, payment_in_store, is_active, published_at")],
     permissoes=["policy `ResellerScope`"],
     regras=["Estes campos são a **única fonte de pintura da vitrine** (`--shop-*`).",
             "Pré-visualização antes de publicar.",
             "Regra global de preço quando aplicável."])

tela(slug="portal-precos", n="2.7", titulo="Preços e margens", **P,
     rota="GET/PUT /portal/precos", status="pronta", arquivo="35-portal-precos.html", anexo="§4.7 · §6",
     tabelas=[("reseller_price_settings","novo","pricing_model, multiplier, margin_global, margin_min, margin_ideal, margin_max, rounding, rule_scope, apply_to_all, allow_manual_override, allow_promotional_prices, recalculated_at — 1:1 com o revendedor"),
              ("reseller_price_rules","novo","scope (global|collection|product), collection_id, product_id, mode (multiplier|percent|manual|promo), value, rounding, priority, is_active")],
     permissoes=["policy `ResellerScope`"],
     regras=["O preço B2C é definido pelo revendedor: multiplicador, percentual, edição manual ou promoção.",
             "Markup, arredondamento, regra por coleção/produto e exportação.",
             "Resolução de preço em service dedicado (`ResellerPriceResolver`), com prioridade explícita."])

tela(slug="portal-suporte", n="2.8", titulo="Suporte — chamados", **P,
     rota="GET /portal/suporte · /portal/suporte/{code}", status="pronta", arquivo="36-portal-suporte.html", anexo="§4.8 · §5.12",
     tabelas=[("support_tickets","novo","code, reseller_id, order_id, customer_id, subject, category, priority, status, assignee_id, channel"),
              ("support_messages","novo","author_id, author_role (revendedor|velaro), body, is_internal_note"),
              ("support_attachments","novo","original_name, path, size_bytes, mime"),
              ("help_categories","novo","name, slug, position — as categorias da central de ajuda"),
              ("help_articles","novo","type (faq|guia|video), title, slug, excerpt, body, video_url, file_path, is_published")],
     permissoes=["policy `ResellerScope`"],
     regras=["Vinculável a pedido, financeiro, troca, defeito, prazo, ajuste, vitrine ou dúvida operacional.",
             "Conversa é **Velaro ↔ revendedor**. O cliente final aparece só como pessoa vinculada ao pedido.",
             "`is_internal_note` nunca é exposto ao revendedor."])

tela(slug="portal-vitrine", n="2.9", titulo="Vitrine para clientes (white label)",
     env="Vitrine white label", acesso="Público, no domínio/URL do revendedor",
     rota="GET /loja/{slug} — ou domínio próprio", status="pronta", arquivo="03-vitrine-pdv.html",
     anexo="§4.9 · §6",
     tabelas=[("reseller_stores","novo","name, slogan, logo_path, banner_path, slug, domain, color_primary, color_secondary, color_background, color_text, is_active, published_at, updated_at (KPI “Última atualização”), whatsapp (“Iniciar atendimento”) — pinta 100% da tela; os toggles do bloco de configurações são own_brand_only, hide_supplier_brand, show_prices, pickup_only, payment_in_store"),
              ("reseller_store_categories","novo","category_id, position — “Categorias visíveis” e as abas da pré-visualização"),
              ("reseller_store_products","novo","product_id, position, is_featured — “Destaque de produtos”"),
              ("products","extensao","catálogo exposto ao consumidor — name, slug, description, is_active, price (**base do cálculo B2C — nunca exibido na vitrine**), category_id, collection_id, material_id, finish_id, largura_mm, formato, permite_gravacao"),
              ("product_images","novo","path, alt, position, is_primary — foto do card na vitrine"),
              ("categories","novo","name, slug, position — origem das categorias visíveis"),
              ("collections","novo","name, slug, is_active — KPI “Coleções ativas”"),
              ("reseller_price_settings","novo","pricing_model, multiplier, margin_global, rounding — a base do cálculo"),
              ("reseller_price_rules","novo","scope, collection_id, product_id, mode, value, rounding, priority — resolve o **preço B2C**"),
              ("favorites","novo","product_id, reseller_store_id, visitor_token — coração na grade da vitrine"),
              ("orders","extensao","reseller_id, created_at — KPI “Pedidos iniciados na vitrine”")],
     permissoes=["—"],
     regras=["**Zero marca Velaro ou SVD** perante o consumidor final. Vazamento de marca é pendência de escopo (§9).",
             "Preço exibido é o B2C do revendedor — nunca o custo B2B.",
             "Retirada somente na loja."])

tela(slug="portal-carrinho", n="2.10", titulo="Carrinho de compras (tablet / PDV)",
     env="Vitrine white label", acesso="Público / vendedor da loja",
     rota="GET /loja/{slug}/carrinho", status="pronta", arquivo="03-vitrine-pdv.html",
     anexo="§4.10 · §4.11 · §6",
     tabelas=[("orders","extensao","nasce em `draft`, vinculado a reseller_id e customer_id; subtotal_amount, engraving_amount, shipping_amount, discount_amount, total_amount — as quatro linhas de “Totais” mais o TOTAL"),
              ("order_items","extensao","product_id, product_variant_id, quantity, unit_price = snapshot do preço B2C no momento da seleção, total_price"),
              ("order_item_engravings","novo","enabled, text, date, chars, price"),
              ("order_promotions","novo","promotion_id, type, discount_amount — origem da linha “Descontos”"),
              ("settings","novo","group, key, value — gravacao.max_chars e gravacao.preco, parametrizáveis"),
              ("reseller_stores","novo","slug, name, slogan, logo_path, banner_path, show_prices, pickup_only, payment_in_store, endereco — cabeçalho, banner, retirada na loja e pagamento no caixa"),
              ("reseller_store_categories","novo","category_id, position — abas Todos / Alianças / Solitários / Acessórios"),
              ("categories","novo","name, slug, position — rótulo de cada aba"),
              ("reseller_store_products","novo","product_id, position, is_featured — grade “Todos os produtos”"),
              ("reseller_price_settings","novo","pricing_model, multiplier, margin_global, rounding — origem do preço B2C exibido"),
              ("reseller_price_rules","novo","scope, collection_id, product_id, mode, value, rounding, priority — exceções que resolvem o preço B2C do card"),
              ("favorites","novo","product_id, reseller_store_id, customer_id, visitor_token — o ♡ dos cards"),
              ("products","extensao","name, price, category_id, material_id, finish_id, permite_gravacao, gravacao_max_chars"),
              ("product_variants","novo","aro"),
              ("product_images","novo","path, is_primary — miniatura do card e da linha do carrinho")],
     permissoes=["—"],
     regras=["Atendimento presencial em tablet. O carrinho totaliza e orienta pagamento **no caixa do revendedor**.",
             "**Nenhum** processamento de Pix, cartão, link de pagamento ou recebimento do consumidor pela Velaro/SVD.",
             "Gravação adicional: Sim/Não, texto, data, limite de caracteres parametrizável e valor **discriminado separadamente**."])

tela(slug="portal-retirada", n="2.11", titulo="Pedido pronto para retirada",
     env="Portal do Lojista", acesso="Parceiro Premium + notificação ao cliente",
     rota="GET /portal/pedidos/{public_number} (estado) · job de notificação",
     status="pronta", arquivo="38-portal-retirada.html", anexo="§4.12 · §6",
     tabelas=[("orders","extensao","public_number, reseller_id, customer_id, operational_status = pronto_retirada, arrived_at, retirado_em, retirado_por, retirado_por_documento, retirado_por_customer_id"),
              ("order_batches","novo","arrived_at, retirado_em, retirado_por, retirado_por_documento — retirada confirmada por lote inteiro"),
              ("order_status_events","novo","to_status = pronto_retirada / retirado, actor_id, note, created_at"),
              ("notification_logs","novo","type = pedido_pronto, channel, recipient, recipient_type (revendedor|cliente), order_id, reseller_id, customer_id, status, sent_at, provider_message_id, error_message — log de envio e reenvio"),
              ("reseller_stores","novo","name, endereco, whatsapp, email — remetente e endereço na mensagem"),
              ("customers","extensao","name, phone, email — destinatário da notificação")],
     permissoes=["policy `ResellerScope`"],
     regras=["Chegada na loja dispara comunicação automática por WhatsApp e/ou e-mail **em nome do revendedor**.",
             "Notifica o consumidor **e** informa o revendedor no Portal.",
             "Confirmação de retirada disponível por pedido."])

# ══════════════════════════ ETAPA 3 · PAINEL INTERNO VELARO ══════════════════════════
M = dict(env="Painel Interno Velaro", acesso="Perfil Master — `is_admin` + gate `access-backend`")

tela(slug="master-dashboard", n="3.1", titulo="Dashboard Master", **M,
     rota="GET /backend", status="pronta", arquivo="04-painel-master.html", anexo="§5.1",
     tabelas=[("orders","extensao","pedidos recebidos e em produção — contagem por operational_status e payment_status"),
              ("resellers","novo","status = pre_cadastro — fila de solicitações"),
              ("order_batches","novo","lotes em aberto e vencidos"),
              ("payments","novo","pagamentos B2B pendentes"),
              ("invoices","novo","notas emitidas no período"),
              ("support_tickets","novo","chamados abertos")],
     permissoes=["`velaro.dashboard.view`"],
     regras=["Visão consolidada da operação.",
             "Fluxo do lote em destaque: recebimento → baixa → NF → pedidos aprovados → liberação."])

tela(slug="master-clientes", n="3.2", titulo="Clientes (base consolidada)", **M,
     rota="GET /backend/clientes · /backend/clientes/{id}", status="pronta", arquivo="50-master-clientes.html", anexo="§5.2",
     tabelas=[("customers","extensao","reseller_id (sempre visível), name, person_type (filtro Pessoa Física/Jurídica), company_name, document, email, phone, cidade, uf, origem_contato, notes, created_at"),
              ("resellers","novo","razao_social, nome_fantasia, code, telefone, whatsapp, email — bloco “Revendedor responsável”"),
              ("reseller_stores","novo","name — nome da loja exibido junto ao revendedor"),
              ("orders","extensao","customer_id, public_number, total_amount, created_at — coluna “último pedido” e resumo de compras")],
     permissoes=["`velaro.customers.view`","`velaro.customers.update`"],
     regras=["Consulta da base de clientes finais **sempre com o revendedor responsável identificado**.",
             "Detalhe permite: Ver pedidos · Ver revendedor · Editar cadastro.",
             "**Não há** cadastro manual de cliente pelo Master como fluxo comercial padrão."])

tela(slug="master-config", n="3.3", titulo="Configurações", **M,
     rota="GET/PUT /backend/configuracoes", status="pronta", arquivo="51-master-config.html", anexo="§5.3",
     tabelas=[("settings","novo","grupos: empresa, notificações, integrações, segurança, financeiro/fiscal, personalização, backup, parâmetros de pedido, meios de pagamento B2B"),
              ("users","extensao","name, email, is_admin, is_agent, is_blocked, reseller_id — usuários do painel"),
              ("acl_permissions","core","key, module, label — permissões granulares"),
              ("acl_responsibilities","core","key, name — papéis"),
              ("acl_user_responsibility","core","user_id, responsibility_id, assigned_by"),
              ("acl_user_permission_overrides","core","user_id, permission_id, is_allowed — exceção por usuário"),
              ("audit_logs","core","actor_id, action, before, after — toda escrita de configuração")],
     permissoes=["`velaro.settings.manage`"],
     regras=["Toda escrita gera `AuditLog`.",
             "Credencial de integração cifrada em repouso, nunca exibida após salvar.",
             "Parâmetros de lote (data de corte, vencimento) e de gravação moram aqui."])

tela(slug="master-estoque", n="3.4", titulo="Estoque", **M,
     rota="GET /backend/estoque", status="pronta", arquivo="52-master-estoque.html", anexo="§5.4 · §6",
     tabelas=[("stock_locations","novo","code, name, description, is_default, is_active — o “Local de armazenamento” do protótipo"),
              ("stock_items","novo","product_variant_id, stock_location_id, atual, reservado, disponivel, minimo, reposicao"),
              ("stock_movements","novo","type (entrada|saida|ajuste|reserva|producao), qty, before, after, reason, actor_id, order_id"),
              ("production_requests","novo","product_variant_id, stock_location_id, qty_requested, qty_delivered, status, priority, due_date, requested_by, completed_at")],
     permissoes=["`velaro.stock.view`","`velaro.stock.adjust`","`velaro.stock.request_production`"],
     regras=["Controle por SKU/tamanho (aro). O estoque físico principal pertence à Velaro.",
             "Ajuste manual, entrada/reabastecimento, solicitação de produção e histórico.",
             "**Ajuste de estoque é ação sensível: exige log (§7).** `before`/`after` gravados no movimento."])

tela(slug="master-financeiro", n="3.5", titulo="Financeiro B2B", **M,
     rota="GET /backend/financeiro", status="pronta", arquivo="53-master-financeiro.html", anexo="§5.5 · §6",
     tabelas=[("order_batches","novo","code, reseller_id, cut_date, due_date, status, total_amount, paid_at, shipped_at, arrived_at"),
              ("payments","novo","method, amount, paid_at, status, external_id, receipt_path, reconciled_by"),
              ("invoices","novo","batch_id, number, series, amount, issued_at, pdf_path, xml_path, provider, issued_by — a nota é **do lote**"),
              ("invoice_items","novo","order_id, amount — rateio da nota pelos pedidos do lote"),
              ("shipments","novo","code, order_batch_id, status, carrier, tracking_code, released_by, released_at, shipped_at — a liberação logística")],
     permissoes=["`velaro.finance.view`","`velaro.finance.reconcile`","`velaro.finance.issue_invoice`","`velaro.finance.release_shipment`"],
     regras=["Fluxo obrigatório: **recebimento identificado → baixa financeira → NF emitida/enviada → pedidos aprovados → liberação para a remessa.**",
             "A Velaro emite a NF da venda B2B ao lojista; o lojista emite a do consumidor final.",
             "Baixa financeira e liberação logística são ações sensíveis: log obrigatório (§7).",
             "Nenhuma remessa sai sem quitação confirmada do lote."])

tela(slug="master-pedidos", n="3.6", titulo="Pedidos — ciclo completo", **M,
     rota="GET /backend/pedidos · /backend/pedidos/{public_number}", status="pronta", arquivo="54-master-pedidos.html",
     anexo="§5.6 · §6 · §7.2",
     tabelas=[("orders","extensao","public_number, customer_id, reseller_id, batch_id, shipment_id, operational_status, payment_status, previsao, arrived_at, retirado_em, retirado_por, retirado_por_documento, retirado_por_customer_id, notes, created_at (data/hora do pedido na lista e no detalhe), e os valores subtotal_amount, discount_amount, total_amount"),
              ("order_items","extensao","product_id, product_variant_id, quantity, unit_price, total_price"),
              ("order_item_engravings","novo","text, date — coluna ESPECIFICAÇÕES (“Gravação: M ❤ S”)"),
              ("order_status_events","novo","scope, from_status, to_status, actor_id, note, created_at — timeline de 7 etapas e histórico de atualizações"),
              ("order_batches","novo","code, arrived_at, retirado_em, retirado_por, retirado_por_documento — confirmação de chegada/retirada **por lote inteiro**"),
              ("shipments","novo","code, status, carrier, tracking_code, tracking_url, released_by, released_at, shipped_at, estimated_at, delivered_at — o transporte, mesmo sem API da transportadora (§7.2)"),
              ("payments","novo","method, status, paid_at — “Forma de pagamento (PIX)”"),
              ("notification_logs","novo","recipient_type (revendedor|cliente), channel, status, sent_at — card “Notificações enviadas”"),
              ("customers","extensao","name, document — Cliente (nome + CPF)"),
              ("resellers","novo","razao_social, nome_fantasia, code, logradouro, numero, cidade, uf, cep — Revendedor e endereço de entrega (loja do revendedor)"),
              ("products","extensao","name, material_id"),
              ("product_variants","novo","sku, aro — coluna CÓDIGO e ESPECIFICAÇÕES"),
              ("product_images","novo","path, is_primary — miniatura do item")],
     permissoes=["`velaro.orders.view`","`velaro.orders.update_status`","`velaro.orders.confirm_pickup`","`velaro.orders.confirm_batch_pickup`"],
     regras=["Estados: registrado → pagamento confirmado → produção em andamento → produção finalizada → em transporte → pronto para retirada → retirado.",
             "**Status operacional é independente do status financeiro.**",
             "Confirmação de chegada/retirada por pedido **e** por lote inteiro.",
             "Campos e status de transporte já entram no escopo mesmo sem a API da transportadora (§7.2)."])

tela(slug="master-produtos", n="3.7", titulo="Produtos — catálogo mestre", **M,
     rota="GET /backend/produtos", status="pronta", arquivo="55-master-produtos.html", anexo="§5.7",
     tabelas=[("products","extensao","name, slug, sku, description, price (B2B), is_active, collection_id, category_id, material_id, finish_id, largura_mm, formato, permite_gravacao, gravacao_max_chars, prazo_entrega_dias, is_made_to_order"),
              ("product_variants","novo","sku, aro/tamanho, is_active"),
              ("product_images","novo","path, alt, position, is_primary — carrossel e “Gerenciar imagens”"),
              ("collections","novo","name, slug, position, is_active — aba “Coleções”; tabela própria, não enum"),
              ("categories","novo","name, slug, parent_id, position — aba “Categorias” e select da lista"),
              ("materials","novo","name, slug, position, is_active — aba “Materiais”"),
              ("finishes","novo","name, slug, position, is_active — aba “Acabamentos”"),
              ("stock_items","novo","disponivel — “Estoque disponível” no resumo do produto"),
              ("resellers","novo","status — “Revendedores ativos” no resumo do produto"),
              ("product_revisions","novo","action, actor_id, before, after — “Histórico de alterações”")],
     permissoes=["`velaro.products.view`","`velaro.products.manage`","`velaro.products.duplicate`","`velaro.products.deactivate`"],
     regras=["Novo produto, SKU/referência, categoria, coleção, material, acabamento, largura, formato, aro, preço B2B, disponibilidade, gravação, imagens, status, duplicação, histórico, inativação.",
             "Produto inativo não aparece para revendedores.",
             "Mudança de preço **não** afeta pedido já criado — `unit_price` é snapshot."])

tela(slug="master-promocoes", n="3.8", titulo="Promoções", **M,
     rota="GET /backend/promocoes", status="pronta", arquivo="56-master-promocoes.html", anexo="§5.8",
     tabelas=[("promotions","novo","code, name, type (desconto_progressivo|preco_especial|frete_gratis|desconto_fixo|lancamento), starts_at, ends_at, status (rascunho|agendada|ativa|pausada|encerrada), priority, show_badge, budget"),
              ("promotion_rules","novo","min_amount, discount_percent, discount_amount, position — os tiers: acima de X → Y% de desconto"),
              ("promotion_products","novo","promotion_id, product_id, collection_id — pivot produto/coleção"),
              ("promotion_audiences","novo","publico_alvo, canais"),
              ("order_promotions","novo","order_id, type, discount_amount, applied_at — alimenta “pedidos com a promoção” e “desconto concedido”")],
     permissoes=["`velaro.promotions.view`","`velaro.promotions.manage`"],
     regras=["Criar, editar, pausar, duplicar e encerrar.",
             "Período, produtos/regras, público-alvo, canais, condições, prioridade, aparência e pré-visualização.",
             "Promoção B2B (Velaro → lojista) não se confunde com promoção do revendedor na vitrine."])

tela(slug="master-relatorios", n="3.9", titulo="Relatórios", **M,
     rota="GET /backend/relatorios", status="pronta", arquivo="57-master-relatorios.html", anexo="§5.9 · §7",
     tabelas=[("report_schedules","novo","name, type, cron, recipients, format, is_active, last_run_at"),
              ("report_exports","novo","type, filters (json), file_path, generated_by, generated_at")],
     permissoes=["`velaro.reports.view`","`velaro.reports.export`","`velaro.reports.schedule`"],
     regras=["Faturamento B2B, pedidos por status, estoque, financeiro, revendedores, produtos, clientes, inadimplência e indicadores operacionais.",
             "Exportação e agendamento conforme previsto no protótipo.",
             "Exportação pesada sempre via job — nunca síncrona no controller."])

tela(slug="master-revendedores", n="3.10", titulo="Revendedores + cadastro manual", **M,
     rota="GET /backend/revendedores · POST /backend/revendedores", status="pronta", arquivo="58-master-revendedores.html",
     anexo="§5.10 · §2 · §7",
     tabelas=[("resellers","novo","todos os campos empresariais + code, status, approved_at, approved_by"),
              ("reseller_cnaes","novo","code, description, compatible"),
              ("reseller_documents","novo","contrato social, doc do sócio, cartão CNPJ"),
              ("reseller_verifications","novo","resultado da IA, score, checked_at"),
              ("reseller_consents","novo","type, granted, document_version, granted_at, revoked_at — aceites do lojista"),
              ("audit_logs","core","action, actor_id, target_id — registra início e fim do “ver como revendedor”")],
     permissoes=["`velaro.resellers.view`","`velaro.resellers.create`","`velaro.resellers.approve`","`velaro.resellers.impersonate`"],
     regras=["Gestão de ativos/inativos **e** cadastro manual.",
             "O cadastro manual executa verificação por IA e permite **aprovar na própria tela**, sem passar pela fila de pré-cadastro.",
             "**“Ver como revendedor”** exige permissão própria e gera registro em `audit_logs` (§2 e §7) — início e fim da sessão."])

tela(slug="master-precadastro", n="3.11", titulo="Solicitações pré-cadastro", **M,
     rota="GET /backend/pre-cadastros · /backend/pre-cadastros/{id}", status="pronta", arquivo="59-master-precadastro.html",
     anexo="§5.11 · §3.7 · §3.8",
     tabelas=[("resellers","novo","status, protocolo, observacoes_internas, rejection_reason"),
              ("reseller_verifications","novo","cnpj_valido, empresa_ativa, cnaes_compativeis, documentacao_enviada, score, result (json), raw_payload"),
              ("reseller_status_events","novo","histórico e justificativa de cada decisão")],
     permissoes=["`velaro.prospects.view`","`velaro.prospects.approve`","`velaro.prospects.reject`","`velaro.prospects.request_info`"],
     regras=["Fila das solicitações vindas do site público, com CNPJ, responsável, endereço, CNAEs, documentos e resultado da IA.",
             "**A IA é triagem/pré-aprovação. A decisão final é humana** (§3.7) e fica registrada com justificativa.",
             "Ações: Aprovar cadastro · Solicitar informações adicionais · Reprovar cadastro.",
             "Aprovar/reprovar é ação sensível: log obrigatório (§7)."])

tela(slug="master-suporte", n="3.12", titulo="Suporte — atendimento", **M,
     rota="GET /backend/suporte · /backend/suporte/{code}", status="pronta", arquivo="60-master-suporte.html", anexo="§5.12",
     tabelas=[("support_tickets","novo","code, reseller_id, order_id, customer_id, subject, category, priority, status, assignee_id, channel, environment, browser, os, ip_address, first_response_at, resolved_at, closed_at"),
              ("support_messages","novo","author_role, body, is_internal_note"),
              ("support_attachments","novo","original_name, path, size_bytes"),
              ("support_tags","novo","name, slug"),
              ("support_ticket_tag","novo","support_ticket_id, support_tag_id — as “Tags” do protótipo"),
              ("support_status_events","novo","from_status, to_status, actor_id, channel, note — o “Histórico de status”")],
     permissoes=["`velaro.support.view`","`velaro.support.reply`","`velaro.support.assign`","`velaro.support.resolve`"],
     regras=["Atendimento aos chamados abertos pelos revendedores.",
             "**A conversa é Velaro ↔ revendedor.** O cliente final aparece apenas como pessoa vinculada ao pedido e não participa.",
             "Observação interna nunca é visível ao revendedor."])


# ══════════════════════════ GERADOR DO mapa.html ══════════════════════════
import html, re, collections

MOCKNAV = """
<style>
  /* Barra de navegação dos mockups — auxiliar de revisão, não faz parte do produto. */
  .mocknav { position: fixed; right: 16px; bottom: 16px; z-index: 999;
    display: flex; align-items: center; gap: 4px; padding: 7px 9px; border-radius: 999px;
    background: rgba(1,26,29,.94); border: 1px solid rgba(219,167,101,.34);
    box-shadow: 0 10px 30px rgba(0,0,0,.30); backdrop-filter: blur(6px);
    font-family: "Inter Tight", ui-sans-serif, system-ui, sans-serif; }
  .mocknav > span { font-size: 10px; letter-spacing: .16em; text-transform: uppercase;
    color: rgba(255,255,255,.42); padding: 0 6px 0 4px; }
  .mocknav a { padding: 5px 11px; border-radius: 999px; font-size: 12px; font-weight: 500;
    color: rgba(255,255,255,.74); white-space: nowrap; }
  .mocknav a:hover { background: rgba(255,255,255,.10); color: #fff; }
  .mocknav a.is-on { background: var(--color-gold-500); color: #06110f; font-weight: 600; }
  .mocknav a.map { border: 1px solid rgba(219,167,101,.40); color: var(--color-gold-300); }
  .mocknav a.map:hover { background: rgba(219,167,101,.16); color: #fff; }
  @media print { .mocknav { display: none; } }
  @media (max-width: 620px) { .mocknav > span { display: none; } .mocknav a { padding: 5px 8px; font-size: 11px; } }
</style>
<nav class="mocknav" aria-label="Navegação entre os mockups">
  <span>Velaro</span>
  <a href="index.html">Índice</a>
  <a href="01-site-publico.html">Site</a>
  <a href="02-portal-lojista.html">Portal</a>
  <a href="03-vitrine-pdv.html">Vitrine</a>
  <a href="04-painel-master.html">Master</a>
  <a class="map" href="mapa.html">Mapa · __TOTAL_TELAS__ telas</a>
</nav>
<script>
(function(){
  var f = (location.pathname.split('/').pop() || 'index.html');
  var a = document.querySelector('.mocknav a[href="' + f + '"]');
  if (a) a.classList.add('is-on');
})();
</script>
"""
# O contador do nav sai do proprio T. MOCKNAV nao pode ser f-string (o bloco leva
# CSS e JS com chaves), entao o numero entra por substituicao. A copia do mesmo nav
# em _ui.py, que nao enxerga T, precisa ser mantida em dia a mao.
MOCKNAV = MOCKNAV.replace("__TOTAL_TELAS__", str(len(T)))

ENV_ORDER = ["Transversal", "Site público", "Portal do Lojista",
             "Vitrine white label", "Painel Interno Velaro"]
ENV_META = {
  "Transversal":          ("0", "Ponto único de entrada"),
  "Site público":         ("1", "Captação e aprovação"),
  "Portal do Lojista":    ("2", "Parceiro Premium"),
  "Vitrine white label":  ("2", "Marca do revendedor"),
  "Painel Interno Velaro":("3", "Perfil Master"),
}
ORIGEM_LABEL = {"core":"core","extensao":"extensão","novo":"novo","—":"—"}

def md(s):
    """Negrito e código inline — o suficiente para o texto das regras."""
    s = html.escape(s)
    s = re.sub(r'\*\*(.+?)\*\*', r'<strong>\1</strong>', s)
    s = re.sub(r'`(.+?)`', r'<code>\1</code>', s)
    return s

def render():
    por_env = collections.OrderedDict((e, []) for e in ENV_ORDER)
    for t in T:
        por_env[t["env"]].append(t)

    # ---- sumário de tabelas novas ----
    tabelas = {}
    for t in T:
        for nome, origem, campos in t["tabelas"]:
            if origem in ("—",):
                continue
            e = tabelas.setdefault(nome, {"origem": origem, "telas": set(), "campos": set()})
            e["telas"].add(t["n"])
            e["campos"].add(campos)

    nav = "".join(
      f'<a href="#env-{i}"><b>Etapa {ENV_META[e][0]}</b>{html.escape(e)}'
      f'<span>{len(por_env[e])} tela{"s" if len(por_env[e])>1 else ""}</span></a>'
      for i, e in enumerate(ENV_ORDER) if por_env[e])

    secoes = []
    for i, e in enumerate(ENV_ORDER):
        telas = por_env[e]
        if not telas:
            continue
        cards = []
        for t in telas:
            pronta = t["status"] == "pronta"
            badge = (f'<a class="pill pill--ok" href="{t["arquivo"]}">mockup pronto ↗</a>'
                     if pronta else '<span class="pill">a desenhar</span>')
            tbs = "".join(
              f'<tr><td><code>{html.escape(n)}</code></td>'
              f'<td><span class="orig orig--{o.split("/")[0]}">{ORIGEM_LABEL.get(o,o)}</span></td>'
              f'<td class="campos">{md(c)}</td></tr>'
              for n, o, c in t["tabelas"])
            perms = "".join(f'<li>{md(p)}</li>' for p in t["permissoes"])
            regras = "".join(f'<li>{md(r)}</li>' for r in t["regras"])
            cards.append(f'''
      <article class="tela" id="{t['slug']}">
        <header class="tela__head">
          <span class="tela__n">{t['n']}</span>
          <div class="tela__id">
            <h3>{html.escape(t['titulo'])}</h3>
            <p class="tela__rota"><code>{html.escape(t['rota'])}</code></p>
          </div>
          <div class="tela__badges">{badge}<span class="pill pill--ghost">{html.escape(t['anexo'])}</span></div>
        </header>
        <p class="tela__acesso"><b>Acesso</b> {md(t['acesso'])}</p>
        <div class="tela__body">
          <div>
            <h4>Tabelas e campos</h4>
            <div class="table-scroll"><table class="tb"><tbody>{tbs}</tbody></table></div>
          </div>
          <div>
            <h4>Permissões</h4><ul class="lst">{perms}</ul>
            <h4 style="margin-top:18px">Regras críticas</h4><ul class="lst lst--rule">{regras}</ul>
          </div>
        </div>
      </article>''')
        secoes.append(f'''
    <section class="env" id="env-{i}">
      <header class="env__head">
        <span class="env__n">Etapa {ENV_META[e][0]}</span>
        <h2>{html.escape(e)}</h2>
        <span class="env__sub">{ENV_META[e][1]} · {len(telas)} tela{"s" if len(telas)>1 else ""}</span>
      </header>
      {''.join(cards)}
    </section>''')

    linhas = "".join(
      f'<tr><td><code>{html.escape(n)}</code></td>'
      f'<td><span class="orig orig--{d["origem"].split("/")[0]}">{ORIGEM_LABEL.get(d["origem"],d["origem"])}</span></td>'
      f'<td class="num">{len(d["telas"])}</td>'
      f'<td class="campos">{", ".join(sorted(d["telas"]))}</td></tr>'
      for n, d in sorted(tabelas.items(), key=lambda x: (x[1]["origem"] != "novo", x[0])))
    novas = sum(1 for d in tabelas.values() if d["origem"] == "novo")

    return f'''<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Velaro · Mapa das {len(T)} telas</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Jost:wght@300;400;500;600&family=Inter+Tight:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="velaro-tokens.css">
<link rel="stylesheet" href="velaro-ui.css">
<style>
  body {{ background: var(--color-gray-100); }}
  .wrap {{ max-width: 1180px; margin: 0 auto; padding: 0 var(--space-6) 64px; }}

  .top {{ background: var(--silk); color: rgba(255,255,255,.76); padding: var(--space-12) 0 var(--space-8); margin-bottom: var(--space-8); }}
  .top .wrap {{ padding-bottom: 0; }}
  .top h1 {{ font-family: var(--font-display); font-weight: 300; font-size: 40px; line-height: 1.1;
    letter-spacing: -.03em; color: #fff; margin: var(--space-4) 0 0; }}
  .top h1 b {{ font-weight: 500; color: var(--color-gold-300); }}
  .top p {{ max-width: 74ch; margin: var(--space-4) 0 0; font-size: 15px; line-height: 25px; color: rgba(255,255,255,.70); }}
  .crumbs {{ display: flex; gap: var(--space-3); flex-wrap: wrap; font-size: var(--text-sm); }}
  .crumbs a {{ color: var(--color-gold-300); }}
  .crumbs a:hover {{ color: #fff; }}

  .stats {{ display: grid; grid-template-columns: repeat(auto-fit,minmax(150px,1fr)); gap: var(--space-3); margin-top: var(--space-8); }}
  .stat {{ padding: var(--space-4); border: 1px solid rgba(219,167,101,.26); border-radius: var(--radius-md); background: rgba(255,255,255,.03); }}
  .stat b {{ display: block; font-family: var(--font-display); font-size: 30px; font-weight: 500;
    letter-spacing: -.02em; color: #fff; font-variant-numeric: tabular-nums; }}
  .stat span {{ font-size: var(--text-xs); color: var(--color-gold-300); }}

  .jump {{ display: grid; grid-template-columns: repeat(auto-fit,minmax(190px,1fr)); gap: var(--space-3); margin-bottom: var(--space-8); }}
  .jump a {{ display: grid; gap: 2px; padding: var(--space-4); background: var(--surface);
    border: 1px solid var(--border); border-radius: var(--radius-md); }}
  .jump a:hover {{ border-color: var(--color-gold-400); }}
  .jump b {{ font-size: var(--text-xs); letter-spacing: .14em; text-transform: uppercase; color: var(--action-link); }}
  .jump span {{ font-size: var(--text-xs); color: var(--ink-muted); }}

  .env {{ margin-bottom: var(--space-12); }}
  .env__head {{ display: flex; align-items: baseline; gap: var(--space-3); flex-wrap: wrap;
    padding-bottom: var(--space-3); margin-bottom: var(--space-5); border-bottom: 2px solid var(--color-brand-900); }}
  .env__n {{ font-size: var(--text-xs); font-weight: 600; letter-spacing: .16em; text-transform: uppercase;
    color: #fff; background: var(--color-brand-900); padding: 4px 10px; border-radius: var(--radius-pill); }}
  .env__head h2 {{ font-family: var(--font-display); font-weight: 500; font-size: 27px;
    letter-spacing: -.02em; color: var(--ink); margin: 0; }}
  .env__sub {{ font-size: var(--text-sm); color: var(--ink-muted); margin-left: auto; }}

  .tela {{ background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg);
    padding: var(--space-5) var(--space-6) var(--space-6); margin-bottom: var(--space-4); scroll-margin-top: 20px; }}
  .tela:target {{ border-color: var(--color-gold-400); box-shadow: 0 0 0 4px rgba(219,167,101,.16); }}
  .tela__head {{ display: flex; align-items: flex-start; gap: var(--space-4); }}
  .tela__n {{ flex: none; min-width: 44px; height: 30px; padding: 0 10px; display: grid; place-items: center;
    border-radius: var(--radius-sm); background: var(--color-gray-100); color: var(--ink-body);
    font-size: var(--text-sm); font-weight: 600; font-variant-numeric: tabular-nums; }}
  .tela__id {{ flex: 1; min-width: 0; }}
  .tela__id h3 {{ font-family: var(--font-display); font-weight: 500; font-size: 20px;
    letter-spacing: -.015em; color: var(--ink); margin: 0; }}
  .tela__rota {{ margin: 4px 0 0; font-size: var(--text-xs); color: var(--ink-muted); }}
  .tela__rota code {{ background: none; padding: 0; }}
  .tela__badges {{ display: flex; gap: 6px; flex-wrap: wrap; justify-content: flex-end; }}
  .pill {{ display: inline-block; padding: 4px 10px; border-radius: var(--radius-pill);
    font-size: var(--text-xs); font-weight: 600; background: var(--color-gray-100); color: var(--ink-muted); }}
  .pill--ok {{ background: var(--color-brand-900); color: var(--color-gold-300); }}
  .pill--ok:hover {{ background: var(--color-brand-700); }}
  .pill--ghost {{ background: none; border: 1px solid var(--border); color: var(--ink-muted); font-weight: 500; }}
  .tela__acesso {{ margin: var(--space-4) 0 0; padding: 10px var(--space-3); border-radius: var(--radius-sm);
    background: var(--surface-subtle); font-size: var(--text-sm); color: var(--ink-body); }}
  .tela__acesso b {{ font-size: var(--text-xs); letter-spacing: .12em; text-transform: uppercase;
    color: var(--ink-muted); margin-right: 8px; }}
  .tela__body {{ display: grid; grid-template-columns: 1.25fr 1fr; gap: var(--space-6); margin-top: var(--space-5); }}
  .tela__body > * {{ min-width: 0; }}
  .tela h4 {{ font-size: var(--text-xs); font-weight: 600; letter-spacing: .14em; text-transform: uppercase;
    color: var(--ink-muted); margin: 0 0 var(--space-2); }}

  .tb {{ font-size: var(--text-sm); }}
  /* As rotas sao strings sem espaco ("GET /solicitacao/{{protocolo}}/aprovar")
     e por isso nao quebravam em celular estreito. */
  code {{ overflow-wrap: anywhere; word-break: break-word; }}
  .tela__rota {{ min-width: 0; }}
  /* A tabela de campos e larga de proposito; no celular ela rola por
     dentro em vez de esticar a pagina inteira. */
  .table-scroll {{ overflow-x: auto; -webkit-overflow-scrolling: touch; }}
  @media (max-width: 640px) {{ .tb {{ min-width: 520px; }} }}
  .tb td {{ padding: 8px 10px 8px 0; border-bottom: 1px solid var(--border); vertical-align: top; }}
  .tb tr:last-child td {{ border-bottom: 0; }}
  .tb code {{ font-size: 12px; color: var(--ink); }}
  .campos {{ color: var(--ink-muted); font-size: var(--text-xs); line-height: 18px; }}
  .num {{ text-align: right; font-variant-numeric: tabular-nums; }}
  .orig {{ display: inline-block; padding: 2px 8px; border-radius: var(--radius-pill);
    font-size: 11px; font-weight: 600; white-space: nowrap; }}
  .orig--novo {{ background: var(--color-gold-100); color: var(--color-gold-800); }}
  .orig--extensao {{ background: var(--color-info-100); color: var(--color-info-700); }}
  .orig--core {{ background: var(--color-success-100); color: var(--color-success-700); }}

  .lst {{ margin: 0; padding: 0; list-style: none; display: grid; gap: 6px; font-size: var(--text-sm); }}
  .lst li {{ padding-left: 16px; position: relative; color: var(--ink-body); line-height: 20px; }}
  .lst li::before {{ content: ""; position: absolute; left: 0; top: 8px; width: 5px; height: 5px;
    border-radius: 50%; background: var(--color-gold-400); }}
  .lst code {{ font-size: 12px; background: var(--color-gray-100); padding: 1px 5px; border-radius: 4px; }}
  .lst--rule li::before {{ background: var(--color-brand-500); }}

  .resumo {{ background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: var(--space-6); }}
  .resumo h2 {{ font-family: var(--font-display); font-weight: 500; font-size: 24px; letter-spacing: -.02em;
    color: var(--ink); margin: 0 0 var(--space-2); }}

  @media (max-width: 900px) {{ .tela__body {{ grid-template-columns: 1fr; }} .tela__badges {{ justify-content: flex-start; }} }}
</style>
</head>
<body>

<div class="top">
  <div class="wrap">
    <p class="crumbs"><a href="index.html">← Mockups</a> <span style="opacity:.4">·</span>
      <a href="01-site-publico.html">Site</a> <a href="02-portal-lojista.html">Portal</a>
      <a href="03-vitrine-pdv.html">Vitrine</a> <a href="04-painel-master.html">Master</a></p>
    <h1>Mapa das <b>{len(T)} telas</b></h1>
    <p>Rota, perfil de acesso, tabelas e campos, permissões e regras críticas — tela a tela.
       Derivado do Anexo I, do Plano de Negócio e dos protótipos aprovados. Este mapa não depende do
       layout: ele diz o que cada tela precisa existir no banco e na ACL, independente de como for desenhada.
       Cada tela tem mockup navegável e documentação própria em <code>docs/telas/</code>.</p>
    <div class="stats">
      <div class="stat"><b>{len(T)}</b><span>telas no escopo</span></div>
      <div class="stat"><b>{sum(1 for t in T if t["status"] == "pronta")}</b><span>mockups prontos</span></div>
      <div class="stat"><b>{novas}</b><span>tabelas novas</span></div>
      <div class="stat"><b>4</b><span>ambientes + login</span></div>
    </div>
  </div>
</div>

<div class="wrap">
  <nav class="jump">{nav}</nav>
  {''.join(secoes)}

  <section class="resumo" id="tabelas">
    <h2>Resumo — todas as tabelas envolvidas</h2>
    <p class="lede" style="margin-bottom:var(--space-5)">
      <span class="orig orig--novo">novo</span> nasce no módulo Velaro ·
      <span class="orig orig--extensao">extensão</span> tabela do core que recebeu colunas Velaro ·
      <span class="orig orig--core">core</span> tabela do scaffold lida como está.
      O core deixou de ser imutável: <code>products</code>, <code>orders</code>, <code>order_items</code>,
      <code>customers</code> e <code>users</code> ganharam colunas próprias, e as tabelas de extensão 1:1
      deixaram de existir.
    </p>
    <div class="table-scroll">
      <table class="table">
        <thead><tr><th>Tabela</th><th>Origem</th><th class="cell-num">Telas</th><th>Aparece em</th></tr></thead>
        <tbody>{linhas}</tbody>
      </table>
    </div>
  </section>
</div>
{MOCKNAV}</body>
</html>'''

if __name__ == "__main__":
    open("mapa.html", "w").write(render())
    print(f"mapa.html gerado — {len(T)} telas")
