# 2.6 · Personalização da loja

| | |
|---|---|
| **Ambiente** | Portal do Lojista |
| **Rota** | `GET/PUT /portal/loja` |
| **Acesso** | Parceiro Premium aprovado — tudo escopado por `reseller_id` |
| **Referência contratual** | Anexo I §4.6 · §4.9 |
| **Mockup** | [`docs/mockups/34-portal-loja.html`](../mockups/34-portal-loja.html) |
| **Mapa** | [mapa.html#portal-loja](../mockups/mapa.html#portal-loja) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
| `reseller_stores` | novo (módulo Velaro) | name, slogan, logo_path, banner_path, slug, domain, phone, whatsapp, email, endereco, color_primary, color_secondary, color_background, color_text, is_active, published_at |

> `core` não é alterado. O domínio Velaro entra em tabelas próprias e em tabelas 1:1
> de extensão, conforme a regra de módulo isolado do scaffold.

## 2. Permissões

- policy `ResellerScope`

## 3. Regras críticas

1. Estes campos são a **única fonte de pintura da vitrine** (`--shop-*`).
2. Pré-visualização antes de publicar.
3. Regra global de preço quando aplicável.

## 4. Critérios de aceite

- [ ] Todos os campos da seção 5 existem na tela e persistem no banco.
- [ ] As permissões da seção 2 bloqueiam o acesso indevido (teste automatizado).
- [ ] As regras da seção 3 têm teste cobrindo o caminho feliz e a violação.
- [ ] Paridade dark/light e comportamento mobile-first.
- [ ] Escrita no backend gera registro em `audit_logs`.

---

## 5. Inventário literal do protótipo

> Transcrição do que a tela do PDF mostra, campo a campo. É a régua de aceite:
> ausência de campo aqui descrito caracteriza **pendência de escopo**, não melhoria (Anexo I §9).

- Hero "PERSONALIZAÇÃO DA LOJA" + "Configure a identidade visual, regras de preços e como sua vitrine será exibida
  para o cliente final. Todas as alterações são refletidas na vitrine do cliente."
- **Bloco ① IDENTIDADE DA LOJA**
  | campo | detalhe |
  | Logo da loja | preview + "Enviar nova logo · PNG ou JPG · Máx. 2MB" |
  | Nome da loja | Tomazelli Alianças |
  | Slogan | "Símbolo de amor. Promessa para a vida toda." |
  | Telefone | (11) 98888-2020 |
  | WhatsApp | (11) 98888-2020 |
  | E-mail | contato@tomazellialiancas.com.br |
  | Domínio / URL da loja | prefixo `https://` + tomazellialiancas.com.br |
  | Endereço | Rua das Alianças, 123 - Centro, São Paulo - SP |
  | Banner principal | preview + botão editar · **"(1920x600px recomendado)"** |
  | **Cores da marca** | Primária **#800020** · Secundária **#B8860B** · Fundo **#FFFFFF** · Texto **#1A1A1A** |
  - **Toggles**: "Exibir apenas a marca Tomazelli Alianças para o cliente final" (Sua vitrine será exibida somente
    com a marca da sua loja) · "**Ocultar marca do fornecedor**" (Remover qualquer menção à Velaro Alianças)
- **Bloco ② REGRA DE PREÇOS**
  - **Modelo de precificação (radio)**: Multiplicador (Aplicar um fator multiplicador) · Percentual (Aplicar um percentual de margem)
  - **Fator de multiplicação** stepper − **3,6x** +
  - **Toggles**: Aplicar a todos os produtos do catálogo · Permitir edição manual por produto · Permitir preços promocionais
  - **Tabela de exemplo** "Exemplo de cálculo com multiplicador 3,6x": Custo Revendedor | Multiplicador | Preço Cliente Final (exibido)
    R$500,00 ×3,6 R$1.800,00 · R$1.000,00 ×3,6 R$3.600,00 · R$2.000,00 ×3,6 R$7.200,00
  - Aviso: "O pagamento do cliente final é realizado diretamente na loja. A vitrine não processa pagamento online."
  - Botões: **SALVAR CONFIGURAÇÕES** · **PUBLICAR VITRINE** · **PRÉ-VISUALIZAR LOJA**
- **Painel PRÉ-VISUALIZAÇÃO DA LOJA PARA O CLIENTE FINAL** + seletor desktop/tablet/mobile ·
  "Assim o cliente verá sua vitrine, com a identidade da sua loja."
  Mostra: header da loja (logo, busca, sacola), abas TODOS OS PRODUTOS / ALIANÇAS / SOLITÁRIOS / ACESSÓRIOS,
  banner com slogan e CTA "CONHEÇA NOSSAS ALIANÇAS", seção **DESTAQUES** com 8 produtos
  (nome, "Ouro 18k", **preço B2C**, chip "Retirada na loja" ou "Pedido realizado na loja") + "VER TODOS OS PRODUTOS"
