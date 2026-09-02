# 3.2 · Clientes (base consolidada)

| | |
|---|---|
| **Ambiente** | Painel Interno Velaro |
| **Rota** | `GET /backend/clientes · /backend/clientes/{id}` |
| **Acesso** | Perfil Master — `is_admin` + gate `access-backend` |
| **Referência contratual** | Anexo I §5.2 |
| **Mockup** | [`docs/mockups/50-master-clientes.html`](../mockups/50-master-clientes.html) |
| **Mapa** | [mapa.html#master-clientes](../mockups/mapa.html#master-clientes) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
| `customers + customer_velaro_details` | core + novo | sempre com `reseller_id` visível |

> `core` não é alterado. O domínio Velaro entra em tabelas próprias e em tabelas 1:1
> de extensão, conforme a regra de módulo isolado do scaffold.

## 2. Permissões

- `velaro.customers.view`
- `velaro.customers.update`

## 3. Regras críticas

1. Consulta da base de clientes finais **sempre com o revendedor responsável identificado**.
2. Detalhe permite: Ver pedidos · Ver revendedor · Editar cadastro.
3. **Não há** cadastro manual de cliente pelo Master como fluxo comercial padrão.

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

- H1 "Clientes" + "Gerencie seus clientes, acompanhe pedidos e histórico de compras."
- **4 KPIs**: Total de clientes 1.248 (↑15% vs mês anterior) · Clientes ativos 842 (↑12%) ·
  Novos clientes (mês) 64 (↑20%) · Clientes inativos 406 (↓8%)
- **Filtros**: busca "Buscar cliente por nome, e-mail ou telefone…" · Status (Todos) · Cidade/UF (Todas) ·
  **Tipo de cliente** (Todos) · **Mais filtros**
- **Tabela** colunas: CLIENTE (avatar + nome + CPF/CNPJ) · **REVENDEDOR RESPONSÁVEL** (nome + loja) ·
  **TIPO** (Pessoa Física / Pessoa Jurídica) · CONTATO (telefone + e-mail) · CIDADE/UF ·
  ÚLTIMO PEDIDO (data + #número) · AÇÕES (⋯)
  Paginação "Mostrando 1 a 7 de 1.248 clientes" · Itens por página 10 · 1 2 3 … 125
- **Drawer de detalhe do cliente**: avatar + nome + "CPF: …" + chip Ativo ·
  **abas: Resumo · Pedidos · Dados cadastrais**
  - *Informações principais*: Tipo de cliente · E-mail · Telefone (+WhatsApp) · **Data de cadastro** · Cidade/UF
  - *Revendedor responsável*: avatar, nome, loja, telefone (+WhatsApp), e-mail, botão **Ver revendedor**
  - *Resumo de compras*: Total de pedidos (12) · Total gasto (R$ 12.450,00) · Último pedido (data + #número)
  - *Observações* (texto livre)
  - Ações: **Ver pedidos** · **Ver revendedor** · **Editar cadastro**
> Confirma a regra §5.2: não há "novo cliente" no Master; só consulta/edição, sempre com revendedor responsável.
