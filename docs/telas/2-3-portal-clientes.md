# 2.3 · Clientes / CRM

| | |
|---|---|
| **Ambiente** | Portal do Lojista |
| **Rota** | `GET /portal/clientes · /portal/clientes/{id}` |
| **Acesso** | Parceiro Premium aprovado — tudo escopado por `reseller_id` |
| **Referência contratual** | Anexo I §4.3 · §6 |
| **Mockup** | [`docs/mockups/31-portal-clientes.html`](../mockups/31-portal-clientes.html) |
| **Mapa** | [mapa.html#portal-clientes](../mockups/mapa.html#portal-clientes) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
| `customers` | core (já existe no scaffold) | name, email, phone, document (CPF), notes |
| `customer_velaro_details` | novo (módulo Velaro) | reseller_id, cidade, uf, endereco, data_nascimento, data_casamento, data_namoro, origem_contato |
| `customer_consents` | novo (módulo Velaro) | type (marketing\|transacional), granted, granted_at, revoked_at, channel, evidence |

> `core` não é alterado. O domínio Velaro entra em tabelas próprias e em tabelas 1:1
> de extensão, conforme a regra de módulo isolado do scaffold.

## 2. Permissões

- policy `ResellerScope`

## 3. Regras críticas

1. **LGPD:** data de casamento/namoro só alimenta campanha com consentimento de marketing válido.
2. O consentimento é registrável **e revogável** — por isso tabela própria com histórico, não booleano no cliente.
3. Comunicação transacional e promocional são tratadas separadamente.

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

- H1 "CLIENTES" + "Gerencie os clientes da sua loja e acompanhe pedidos e relacionamento." + botão **+ Novo cliente**
- **4 KPIs**: Clientes cadastrados 486 · Clientes ativos 352 · Pedidos em aberto 28 · Último cadastro (Hoje, 10:32) — todos com "Ver detalhes →"
- **Filtros**: busca "Buscar por nome, CPF, e-mail ou telefone…" · Status (Todos) · Cidade/UF (Todas) ·
  Período do cadastro (Todas) · Limpar filtros
- **Tabela** colunas: CLIENTE (avatar + nome + cidade/UF) · CPF · TELEFONE (com ícone WhatsApp) · E-MAIL ·
  ÚLTIMO PEDIDO (data + "Pedido #5128") · STATUS (Ativo/Inativo) · AÇÕES (⋯)
  Linhas: Maria Silva (São Paulo/SP) · João Souza (Santos/SP) · Ana Costa (Campinas/SP) · Carlos Pereira (São José do Campo/SP)
- Paginação "Mostrando 1 a 4 de 486 clientes" · 1 2 3 … 122
- **Drawer "Novo cliente"** — "Preencha os dados do cliente para cadastrá-lo e começar a criar pedidos."
  | campo | obrig. |
  | Nome completo | * |
  | CPF | * |
  | Telefone/WhatsApp | * (ícone WhatsApp) |
  | E-mail | * |
  | Data de nascimento | — |
  | Data de casamento / namoro | — (com tooltip ⓘ) |
  | **Usar para campanhas de marketing** ⓘ → checkbox "Receber campanhas em datas especiais" | — |
  | Origem do contato (select: Indicação) | — |
  | Cidade/UF | * |
  | Endereço | * |
  | Observações (textarea) | — |
  Botões: **Salvar cliente** · **Salvar e criar pedido** ·
  nota "Próximo passo: com o cliente salvo, você poderá criar um pedido de alianças com mais agilidade."
