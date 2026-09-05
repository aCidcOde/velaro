# 2.7 · Preços e margens

| | |
|---|---|
| **Ambiente** | Portal do Lojista |
| **Rota** | `GET/PUT /portal/precos` |
| **Acesso** | Parceiro Premium aprovado — tudo escopado por `reseller_id` |
| **Referência contratual** | Anexo I §4.7 · §6 |
| **Mockup** | [`docs/mockups/35-portal-precos.html`](../mockups/35-portal-precos.html) |
| **Mapa** | [mapa.html#portal-precos](../mockups/mapa.html#portal-precos) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
| `reseller_price_settings` | novo (módulo Velaro) | pricing_model, multiplier, margin_global, margin_min, margin_ideal, margin_max, rounding, rule_scope, apply_to_all, allow_manual_override, allow_promotional_prices, recalculated_at — 1:1 com o revendedor |
| `reseller_price_rules` | novo (módulo Velaro) | scope (global\|collection\|product), collection_id, product_id, mode (multiplier\|percent\|manual\|promo), value, rounding, priority, is_active |

> O domínio Velaro entra em tabelas próprias e em colunas acrescentadas às tabelas do
> core. As extensões 1:1 foram descartadas — ver [decisão 1.1](../banco-de-dados.md).

## 2. Permissões

- policy `ResellerScope`

## 3. Regras críticas

1. O preço B2C é definido pelo revendedor: multiplicador, percentual, edição manual ou promoção.
2. Markup, arredondamento, regra por coleção/produto e exportação.
3. Resolução de preço em service dedicado (`ResellerPriceResolver`), com prioridade explícita.

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

- Hero "PREÇOS E MARGENS" + "Defina suas margens e visualize os preços sugeridos para sua loja."
- **5 KPIs**: Margem média atual 48,7% (Sobre o custo) · Markup médio 95,2% (Sobre o custo) ·
  Produtos com margem abaixo do ideal 12 (Ajuste recomendado) · Preço médio de venda R$ 876,45 (Por unidade) ·
  Atualizado em 15/05/2025 10:30 (Última atualização)
- **Configuração global**: Margem global padrão (50 %) "Aplicada quando não houver regra específica" ·
  **Arredondamento de preços** (Para cima (0,99)) "Como os preços serão exibidos na loja" ·
  **Regra de preço** (Por coleção) "Defina margens diferentes por coleção" ·
  botões **Recalcular preços** · **Salvar configurações**
- **Filtros**: busca "Buscar por produto, código ou referência…" · Coleção · Material · Acabamento ·
  **Mais filtros** · **Exportar tabela**
- **Abas**: Todos os produtos · Por coleção · Regras de margem
- **Tabela** colunas: PRODUTO (miniatura + nome + "Ref. ALC18-4MM") · COLEÇÃO · CUSTO VELARO ·
  **MARGEM (%) editável** ⓘ · MARKUP (%) · **PREÇO SUGERIDO (editável)** · STATUS (Margem ideal / Margem baixa) ·
  AÇÕES (✎ editar, ⋯) — linha em edição mostra ✓ confirmar e ✗ cancelar
  Dados: Clássica 4mm/Clássica/R$100,00/50%/100%/R$200,00 · Diamantada 6mm/Diamantada/R$120,00/55%/122,2%/R$265,00 ·
  Fosca 6mm/Fosca/R$85,00/45%/81,8%/R$165,00 (Margem baixa) · Trabalhada 6mm/R$110,00/48%/92,3%/R$210,00 ·
  Conforto 4mm/R$95,00/50%/100%/R$190,00
  Paginação "Exibindo 1 a 5 de 128 produtos" · 5 por página
- **Painel RESUMO DE MARGENS** (donut 48,7% Margem média): Margem ideal (≥40%) 86 produtos ·
  Margem baixa (20%–39%) 12 produtos · Margem crítica (<20%) 2 produtos
- **CONFIGURAÇÃO RÁPIDA**: Margem mínima desejada 40% · Margem ideal 50% · Margem máxima 60% ·
  botão **Aplicar para todos os produtos**
- **DICAS PARA MELHORES MARGENS** (3 itens) + link "Saiba mais sobre precificação →"
