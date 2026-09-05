# 3.8 · Promoções

| | |
|---|---|
| **Ambiente** | Painel Interno Velaro |
| **Rota** | `GET /backend/promocoes` |
| **Acesso** | Perfil Master — `is_admin` + gate `access-backend` |
| **Referência contratual** | Anexo I §5.8 |
| **Mockup** | [`docs/mockups/56-master-promocoes.html`](../mockups/56-master-promocoes.html) |
| **Mapa** | [mapa.html#master-promocoes](../mockups/mapa.html#master-promocoes) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
| `promotions` | novo (módulo Velaro) | code, name, type (desconto_progressivo\|preco_especial\|frete_gratis\|desconto_fixo\|lancamento), starts_at, ends_at, status (rascunho\|agendada\|ativa\|pausada\|encerrada), priority, show_badge, budget |
| `promotion_rules` | novo (módulo Velaro) | min_amount, discount_percent, discount_amount, position — os tiers: acima de X → Y% de desconto |
| `promotion_products` | novo (módulo Velaro) | promotion_id, product_id, collection_id — pivot produto/coleção |
| `promotion_audiences` | novo (módulo Velaro) | publico_alvo, canais |
| `order_promotions` | novo (módulo Velaro) | order_id, type, discount_amount, applied_at — alimenta “pedidos com a promoção” e “desconto concedido” |

> O domínio Velaro entra em tabelas próprias e em colunas acrescentadas às tabelas do
> core. As extensões 1:1 foram descartadas — ver [decisão 1.1](../banco-de-dados.md).

## 2. Permissões

- `velaro.promotions.view`
- `velaro.promotions.manage`

## 3. Regras críticas

1. Criar, editar, pausar, duplicar e encerrar.
2. Período, produtos/regras, público-alvo, canais, condições, prioridade, aparência e pré-visualização.
3. Promoção B2B (Velaro → lojista) não se confunde com promoção do revendedor na vitrine.

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

- H1 "Promoções" + "Crie e gerencie campanhas promocionais para seus revendedores." +
  botões **Visualizar em loja** · **+ Nova promoção**
- **Coluna esquerda — lista**: busca "Buscar promoção…" · ícone filtro · select "Todas as campanhas"
  Cada cartão: imagem · nome · **chip de status** · período (dd/mm/aaaa até dd/mm/aaaa) · descrição ·
  "Tipo: …" · "Cód. PROMO-2025-05"
  **Status reais**: Ativa · Agendada · Encerrada · **Rascunho**
  **Tipos reais**: Desconto progressivo · Preço especial · Frete grátis · Desconto fixo · **Lançamento**
  Exemplos: Desconto Progressivo - Alianças · Dia dos Namorados · Frete Grátis (acima de R$1.000,00) ·
  Brilho que Encanta (20% OFF em alianças com diamantes) · Coleção Eternidade (Previsto:)
  Paginação "Mostrando 1 a 5 de 12 promoções" · 10 por página
- **Coluna central — "Editando promoção"** + chip Ativa + botões **⏸ Pausar campanha** · **Duplicar promoção**
  **Abas**: Informações básicas · **Produtos e regras** · **Público-alvo** · **Canais** · **Condições** · **Aparência**
  Campos de "Informações básicas":
  | Nome da promoção * | **Período da promoção *** (data início · até · data fim, com date pickers) |
  | Código da promoção * (PROMO-2025-05) | **Status *** (Ativa) — "Promoção está ativa e visível para os revendedores." |
  | Tipo de promoção * (Desconto progressivo) — "Descontos aplicados conforme o valor total do pedido." |
    **Prioridade de exibição** (Alta) — "Define a ordem de destaque da promoção na loja." |
  | Descrição (textarea, contador **Caracteres: 89/500**) | **Toggle "Exibir selo na loja"** — "Mostrar selo de destaque na vitrine da loja" |
  Aviso: "Esta promoção está ativa e visível para todos os revendedores elegíveis."
  Botões **Cancelar** · **Salvar alterações**
- **Coluna direita — Prévia da promoção**: peça visual "DESCONTO PROGRESSIVO EM ALIANÇAS SELECIONADAS" +
  "COMPRE MAIS, ECONOMIZE MAIS!" + **faixas 5% acima de R$1.000 / 10% acima de R$2.000 / 15% acima de R$3.000**
  · nota "Esta é uma prévia de como a promoção será exibida na loja. A aparência pode variar em diferentes dispositivos."
  · botão **Ver na loja ↗**
  **Resumo da promoção**: Tipo · Período · Status · **Canais** (Loja online, WhatsApp, E-mail) ·
  **Público-alvo** (Todos os revendedores ativos) · **Orçamento estimado** (R$ 0,00) · Criado em · Última atualização
  **Ações rápidas**: Histórico de alterações · **Relatório de desempenho** · **Excluir promoção**
