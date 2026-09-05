# 3.11 · Solicitações pré-cadastro

| | |
|---|---|
| **Ambiente** | Painel Interno Velaro |
| **Rota** | `GET /backend/pre-cadastros · /backend/pre-cadastros/{id}` |
| **Acesso** | Perfil Master — `is_admin` + gate `access-backend` |
| **Referência contratual** | Anexo I §5.11 · §3.7 · §3.8 |
| **Mockup** | [`docs/mockups/59-master-precadastro.html`](../mockups/59-master-precadastro.html) |
| **Mapa** | [mapa.html#master-precadastro](../mockups/mapa.html#master-precadastro) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
| `resellers` | novo (módulo Velaro) | status, protocolo, observacoes_internas, rejection_reason |
| `reseller_verifications` | novo (módulo Velaro) | cnpj_valido, empresa_ativa, cnaes_compativeis, documentacao_enviada, score, result (json), raw_payload |
| `reseller_status_events` | novo (módulo Velaro) | histórico e justificativa de cada decisão |

> O domínio Velaro entra em tabelas próprias e em colunas acrescentadas às tabelas do
> core. As extensões 1:1 foram descartadas — ver [decisão 1.1](../banco-de-dados.md).

## 2. Permissões

- `velaro.prospects.view`
- `velaro.prospects.approve`
- `velaro.prospects.reject`
- `velaro.prospects.request_info`

## 3. Regras críticas

1. Fila das solicitações vindas do site público, com CNPJ, responsável, endereço, CNAEs, documentos e resultado da IA.
2. **A IA é triagem/pré-aprovação. A decisão final é humana** (§3.7) e fica registrada com justificativa.
3. Ações: Aprovar cadastro · Solicitar informações adicionais · Reprovar cadastro.
4. Aprovar/reprovar é ação sensível: log obrigatório (§7).

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

- H1 "Solicitações pré-cadastro" + "Acompanhe solicitações recebidas e valide novos revendedores."
- **5 KPIs**: Solicitações recebidas 32 · **Em análise por IA** 9 · **Aguardando decisão** 14 ·
  Aprovadas no mês 18 · Reprovadas no mês 6
- Botões **Exportar** · **Atualizar status**
- **Filtros**: Status (Todos) · Período (Últimos 30 dias) · busca "Buscar empresa ou responsável…" · **Filtros**
- **Tabela** colunas: EMPRESA · RESPONSÁVEL · CIDADE/UF · DATA · **RESULTADO IA** (Compatível / Incompatível) ·
  STATUS · AÇÕES (⋯)
  **Status reais**: Aguardando decisão · Em análise · Pendente · **Solicitação enviada**
  Paginação "Mostrando 1 a 8 de 32 solicitações"
- **Painel "Detalhes da solicitação"**:
  Nome fantasia · Razão social · CNPJ · Responsável · E-mail · Telefone/WhatsApp · **CEP** · **Endereço** (completo)
  - **CNAEs informados** (lista com código + descrição)
  - **Validação por IA** (checklist): CNPJ válido · Empresa ativa · CNAEs compatíveis · **Documentação enviada**
    + **Resultado: Compatível / Pré-aprovado**
  - **Documentos anexados** (3, com tipo, tamanho e ✓): Contrato social.pdf (245 KB) · Documento do sócio.pdf (198 KB) ·
    Cartão CNPJ.pdf (142 KB)
  - **Observações internas** (textarea)
  - **Ações da solicitação**: **✓ Aprovar cadastro** · **ⓘ Solicitar informações adicionais** · **✗ Reprovar cadastro**
  - Aviso: "Ao aprovar, o revendedor poderá acessar a plataforma e realizar pedidos."
