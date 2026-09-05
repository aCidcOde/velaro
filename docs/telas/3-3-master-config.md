# 3.3 · Configurações

| | |
|---|---|
| **Ambiente** | Painel Interno Velaro |
| **Rota** | `GET/PUT /backend/configuracoes` |
| **Acesso** | Perfil Master — `is_admin` + gate `access-backend` |
| **Referência contratual** | Anexo I §5.3 |
| **Mockup** | [`docs/mockups/51-master-config.html`](../mockups/51-master-config.html) |
| **Mapa** | [mapa.html#master-config](../mockups/mapa.html#master-config) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
| `settings` | novo (módulo Velaro) | grupos: empresa, notificações, integrações, segurança, financeiro/fiscal, personalização, backup, parâmetros de pedido, meios de pagamento B2B |
| `users` | core + colunas Velaro | name, email, is_admin, is_agent, is_blocked, reseller_id — usuários do painel |
| `acl_permissions` | core (já existe no scaffold) | key, module, label — permissões granulares |
| `acl_responsibilities` | core (já existe no scaffold) | key, name — papéis |
| `acl_user_responsibility` | core (já existe no scaffold) | user_id, responsibility_id, assigned_by |
| `acl_user_permission_overrides` | core (já existe no scaffold) | user_id, permission_id, is_allowed — exceção por usuário |
| `audit_logs` | core (já existe no scaffold) | actor_id, action, before, after — toda escrita de configuração |

> O domínio Velaro entra em tabelas próprias e em colunas acrescentadas às tabelas do
> core. As extensões 1:1 foram descartadas — ver [decisão 1.1](../banco-de-dados.md).

## 2. Permissões

- `velaro.settings.manage`

## 3. Regras críticas

1. Toda escrita gera `AuditLog`.
2. Credencial de integração cifrada em repouso, nunca exibida após salvar.
3. Parâmetros de lote (data de corte, vencimento) e de gravação moram aqui.

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

- H1 "Configurações" + "Gerencie as preferências e parâmetros da sua conta e da plataforma."
- **Menu lateral de seções (9)**: Perfil da empresa (Dados da sua loja e informações gerais) ·
  Usuários e permissões (Gerencie acessos e níveis de permissão) · Notificações (Preferências de alertas e comunicações) ·
  Integrações (Conexões com sistemas externos) · Segurança (Senha, 2FA e sessões ativas) ·
  Financeiro (Configurações financeiras e fiscais) · Personalização (Aparência e identidade visual) ·
  Backup e dados (Exportar e gerenciar dados)  [+ Solicitações pré-cadastro e Suporte no menu principal]
- **Perfil da empresa** (botão "Editar informações"): Nome fantasia · Razão social · CNPJ · Inscrição estadual ·
  E-mail comercial · Telefone (+WhatsApp) · Endereço (Rua das Alianças, 100 – Centro · São Paulo / SP – 01000-000) ·
  logo + botão **Alterar logo**
- **Configurações gerais**: Moeda padrão (Real (R$)) · Fuso horário ((GMT-03:00) Brasília) · Idioma (Português (Brasil)) ·
  toggles: **Exibir estoque negativo** (Permitir visualização de produtos com estoque abaixo de zero) ·
  **Permitir pedidos sem estoque** (Permitir que revendedores façam pedidos mesmo sem estoque disponível) ·
  **Aprovação automática de pedidos** (Pedidos serão aprovados automaticamente após o pagamento) — ATIVO ·
  **Notificações por e-mail** — ATIVO
- **Configurações de pedidos**: **Prazo padrão de produção** (7 dias úteis) · **Validade do orçamento** (15 dias) ·
  **Cancelamento automático** (Cancelar pedidos não pagos após 7 dias) · **Numeração dos pedidos** (Sequencial por ano)
- **Informações fiscais**: Regime tributário (Simples Nacional) · **Série da nota fiscal** (1) ·
  botão **Configurar notas fiscais**
- **Formas de pagamento** (tabela FORMA DE PAGAMENTO | STATUS): PIX (Ativo) · Boleto Bancário (Ativo) ·
  Cartão de Crédito (Inativo) · botão **Gerenciar formas de pagamento**
- **Outras configurações**: Exportar dados (Faça o download dos dados da sua conta) ·
  Limpar cache (Melhore a performance do sistema) · **Excluir conta** (Atenção: esta ação não pode ser desfeita)
