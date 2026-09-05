# 3.9 · Relatórios

| | |
|---|---|
| **Ambiente** | Painel Interno Velaro |
| **Rota** | `GET /backend/relatorios` |
| **Acesso** | Perfil Master — `is_admin` + gate `access-backend` |
| **Referência contratual** | Anexo I §5.9 · §7 |
| **Mockup** | [`docs/mockups/57-master-relatorios.html`](../mockups/57-master-relatorios.html) |
| **Mapa** | [mapa.html#master-relatorios](../mockups/mapa.html#master-relatorios) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
| `report_schedules` | novo (módulo Velaro) | name, type, cron, recipients, format, is_active, last_run_at |
| `report_exports` | novo (módulo Velaro) | type, filters (json), file_path, generated_by, generated_at |

> O domínio Velaro entra em tabelas próprias e em colunas acrescentadas às tabelas do
> core. As extensões 1:1 foram descartadas — ver [decisão 1.1](../banco-de-dados.md).

## 2. Permissões

- `velaro.reports.view`
- `velaro.reports.export`
- `velaro.reports.schedule`

## 3. Regras críticas

1. Faturamento B2B, pedidos por status, estoque, financeiro, revendedores, produtos, clientes, inadimplência e indicadores operacionais.
2. Exportação e agendamento conforme previsto no protótipo.
3. Exportação pesada sempre via job — nunca síncrona no controller.

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

- H1 "Relatórios" + "Acompanhe o desempenho da sua operação com dados atualizados." +
  botões **Agendar relatórios** · **Exportar**
- **Filtros**: **Período** (01/05/2025 até 31/05/2025) · **Comparar com** (Período anterior) ·
  **Revendedor** (Todos) · **Categoria** (Todas) · **Limpar filtros**
- **5 KPIs** (com variação vs. período anterior): Faturamento bruto R$ 245.780,00 (↑18,6%) ·
  Pedidos realizados 248 (↑12,4%) · Itens vendidos 612 (↑15,2%) · Ticket médio R$ 990,24 (↑5,7%) ·
  Novos clientes 34 (↑21,4%)
- **Gráfico "Faturamento ao longo do tempo"** (linha, Período atual vs. Período anterior tracejado, eixo R$0–R$60 mil,
  01/05 a 31/05) + seletor **Diário**
- **Donut "Pedidos por status"** — Total 248 pedidos: Concluídos 115 (46,4%) · Em produção 58 (23,4%) ·
  Em transporte 16 (6,5%) · Aguardando pagamento 24 (9,7%) · Cancelados 12 (4,8%) · Outros 23 (9,3%) + seletor Diário
- **Top revendedores por faturamento** (tabela): POSIÇÃO · REVENDEDOR · FATURAMENTO · PEDIDOS · **TICKET MÉDIO**
  + "Ver ranking completo →"
- **Top produtos por quantidade** (tabela): PRODUTO (miniatura + nome + material) · QUANTIDADE · FATURAMENTO
  + "Ver todos os produtos →"
- **Resumo financeiro**: Recebimentos confirmados R$238.150,00 · A receber R$32.480,00 ·
  **Inadimplência R$7.380,00** (vermelho) · **Taxa de inadimplência 2,9%** · Descontos concedidos R$6.250,00
  + "Ver relatório financeiro completo →"
- **Relatórios rápidos** (5 atalhos): Vendas por período · Pedidos por status · Estoque atual · Financeiro · Top produtos
  + "Ver todos os relatórios →"
- **Relatórios agendados** (lista com chip Ativo e ⋯): Relatório semanal de vendas (Toda segunda-feira às 08:00) ·
  Relatório de estoque (Todo dia 1º às 09:00) · Relatório financeiro mensal (Todo dia 5 às 10:00)
  + "Gerenciar agendamentos →"
