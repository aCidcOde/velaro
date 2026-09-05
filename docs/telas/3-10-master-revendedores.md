# 3.10 · Revendedores + cadastro manual

| | |
|---|---|
| **Ambiente** | Painel Interno Velaro |
| **Rota** | `GET /backend/revendedores · POST /backend/revendedores` |
| **Acesso** | Perfil Master — `is_admin` + gate `access-backend` |
| **Referência contratual** | Anexo I §5.10 · §2 · §7 |
| **Mockup** | [`docs/mockups/58-master-revendedores.html`](../mockups/58-master-revendedores.html) |
| **Mapa** | [mapa.html#master-revendedores](../mockups/mapa.html#master-revendedores) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
| `resellers` | novo (módulo Velaro) | todos os campos empresariais + code, status, approved_at, approved_by |
| `reseller_cnaes` | novo (módulo Velaro) | code, description, compatible |
| `reseller_documents` | novo (módulo Velaro) | contrato social, doc do sócio, cartão CNPJ |
| `reseller_verifications` | novo (módulo Velaro) | resultado da IA, score, checked_at |
| `reseller_consents` | novo (módulo Velaro) | type, granted, document_version, granted_at, revoked_at — aceites do lojista |
| `audit_logs` | core (já existe no scaffold) | action, actor_id, target_id — registra início e fim do “ver como revendedor” |

> O domínio Velaro entra em tabelas próprias e em colunas acrescentadas às tabelas do
> core. As extensões 1:1 foram descartadas — ver [decisão 1.1](../banco-de-dados.md).

## 2. Permissões

- `velaro.resellers.view`
- `velaro.resellers.create`
- `velaro.resellers.approve`
- `velaro.resellers.impersonate`

## 3. Regras críticas

1. Gestão de ativos/inativos **e** cadastro manual.
2. O cadastro manual executa verificação por IA e permite **aprovar na própria tela**, sem passar pela fila de pré-cadastro.
3. **“Ver como revendedor”** exige permissão própria e gera registro em `audit_logs` (§2 e §7) — início e fim da sessão.

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

- H1 "Revendedores" + "Gerencie os revendedores ativos e realize cadastros manuais com verificação de CNAEs por IA."
  + botão **+ Novo revendedor** (com anotação "Clique aqui para cadastro manual")
- **4 KPIs**: Revendedores ativos 248 (↑12,4%) · Pendentes de aprovação 18 (↑5,3%) ·
  **Cadastros manuais no mês** 26 (↑44,8%) · **CNAEs verificados por IA** 312 (↑21,7%)
- **Filtros**: select "Todos os status" · busca "Buscar revendedor…" · **Filtros**
- **Tabela** colunas: REVENDEDOR (avatar + nome) · CIDADE / UF · RESPONSÁVEL · STATUS (Ativo / Pendente) ·
  **TIPO DE CADASTRO** (Automático / **Manual**) · **CNAE VERIFICADO** (Compatível / Em verificação) · DATA ·
  AÇÕES (👁 ver, ⋮)
  Paginação "Mostrando 1 a 5 de 248 revendedores" · 5 por página
- **Drawer "Cadastro manual de revendedor"** — campos:
  | Nome fantasia * | Razão social * |
  | CNPJ * | Responsável * |
  | E-mail * | Telefone / WhatsApp * |
  | **CEP *** (com botão de busca 🔍) | Endereço * |
  | Número * | Complemento |
  | Bairro * | Cidade * | **UF *** |
  - **CNAEs informados** (lista com ✓): 4783-1/01 Comércio varejista de joias · 4783-1/02 Comércio varejista de relógios ·
    4789-0/01 Comércio varejista de souvenires, bijuterias e artesanatos
  - **Verificação por IA** (checklist): CNPJ válido · Empresa ativa · CNAEs compatíveis ·
    **Resultado: chip "Compatível / Pré-aprovado"**
  - **Documentos anexados** (3 cards com nome do arquivo, tamanho e ✓):
    **Contrato social \*** (Contrato_Social.pdf · 212 KB) · **Documento do sócio \*** (RG_Andre_Tomazelli.pdf · 189 KB) ·
    **Cartão CNPJ \*** (Cartao_CNPJ.pdf · 94 KB)
  - **Observações (internas)** — textarea com contador 62/500
  - Ações: **Verificar CNAEs com IA** · **Salvar cadastro** · **✓ Aprovar revendedor**
  - Aviso: "Ao aprovar, o revendedor será ativado e poderá realizar pedidos imediatamente."
> Esta tela tem os campos de endereço e os uploads que **faltam** no cadastro público (1.4) — o formulário
> público precisa ser completado com os mesmos campos (Anexo I §3.4).
