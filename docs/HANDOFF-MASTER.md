# Handoff — Painel Master (Velaro B2B)

Brief para o agente que vai construir o **Painel Interno Velaro**. Escrito por quem construiu
o Site público e o Portal do Lojista, com as armadilhas que já custaram retrabalho aqui.

**Branch:** `develop` · **Estado:** 365 testes passando, gates verdes, produção no ar em
`https://velaro.sistemavendadireta.com.br`.

---

## 1. Seu escopo

O **Painel Master** — 39 rotas sob `/backend`, 11 controllers, 12 telas (3.1 a 3.12).

### Territórios seus (ninguém mais escreve neles)

```
app/Http/Controllers/Backend/     ← só os 11 esqueletos do Velaro (ver §2)
app/Services/Backend/             ← criar
app/Http/Requests/Backend/        ← criar
resources/views/backend/velaro/   ← criar (NÃO misture com as views do scaffold)
tests/Feature/Backend/            ← criar
```

### Territórios MEUS — não toque

```
app/Http/Controllers/{Site,Portal,Vitrine}/
app/Services/{Site,Portal}/
app/Http/Requests/{Site,Portal}/
app/Http/Middleware/EnsureUserIsReseller.php · EnsureCanTrackReseller.php
app/Http/Responses/Auth/LoginResponse.php
app/Mail/
resources/views/{site,portal}/
resources/views/components/velaro/layouts/{auth,portal,site,vitrine}.blade.php
routes/velaro.php
tests/Feature/{Site,Portal,Auth}/
```

### Congelado para os dois — avise antes de mexer

```
app/Models/            database/migrations/         database/factories/
resources/css/velaro/  config/velaro-*.php          lang/
docs/mockups/_mapa.py  docs/mockups/_notas.md
```

---

## 2. A ACL já está pronta — use, não crie

Feita antes do handoff, para tirar essa dependência do seu caminho. Em
`app/Support/Acl/BackendAclCatalog.php`:

- **36 permissões `velaro.*`** em **12 módulos**, um por tela do escopo (`velaro-prospects`,
  `velaro-resellers`, `velaro-products`, `velaro-stock`, `velaro-orders`, `velaro-finance`,
  `velaro-promotions`, `velaro-reports`, `velaro-support`, `velaro-customers`,
  `velaro-dashboard`, `velaro-settings`).
- **5 responsabilidades**: `velaro.master` (todas + acesso ao backend), `velaro.comercial`,
  `velaro.operacao`, `velaro.financeiro`, `velaro.suporte`.
- Já sincronizadas no banco. Num clone novo: `php artisan acl:sync-backend`.

**Seu trabalho é aplicar essas chaves** nos gates das rotas e dos controllers. Hoje o grupo
`/backend` inteiro usa só `can:access-backend` — cada tela precisa do gate granular da sua
seção 2 em `docs/telas/3-*.md`.

A descrição de cada permissão marca quais são **ação sensível com log obrigatório** (aprovar,
reprovar, baixa financeira, ajuste de estoque, impersonate). É o §7 do Anexo I: essas exigem
justificativa registrada e entrada em `audit_logs`.

Se precisar de permissão nova, acrescente ao catálogo — mas confira antes se a chave já existe
na seção 2 do doc da tela. Não invente chave.

Os 11 controllers já existem como esqueleto, lançando `HttpException(501)`. **Substitua o corpo,
preserve o cabeçalho de autoria** e atualize a descrição.

> Atenção: `app/Http/Controllers/Backend/` tem **18 arquivos** — 7 são do scaffold e já funcionam
> (usuários, ACL, auditoria, changelog, dashboard). Os seus são os 11 que lançam 501.

---

## 3. Prioridade

**A tela 3.11 (pré-cadastro) é a mais urgente do projeto inteiro.** Sem ela ninguém aprova
lojista, e o ciclo comercial não fecha — um lojista que se cadastrar hoje fica travado em
`pending` para sempre. Ela também é a contraparte de uma coisa que já existe do outro lado:

- A ação **"Solicitar informações adicionais"** (permissão `velaro.prospects.request_info`) move
  o revendedor para `Reseller::STATUS_AWAITING_INFO`.
- Nesse estado, a tela 1.6 abre o **reenvio de documentos** para o lojista (já implementado).
- O envio devolve para `pending` e registra em `reseller_status_events`.

Depois dela: 3.10 (revendedores), 3.6 (pedidos), 3.4 (estoque), 3.5 (financeiro), o resto.

---

## 4. As armadilhas que já custaram retrabalho

**1. Nunca invente valor de enum.** Quatro constantes foram removidas por isso — slugs cunhados
na hora, que não existiam em migration nem em documento. Um `where('status', STATUS_X)` volta
vazio **em silêncio** se a aplicação gravar outro termo. Se o vocabulário não está acordado, use
o `default` da migration ou deixe a coluna fora, e reporte.

**2. O schema está em INGLÊS.** `protocol`, `legal_name`, `trade_name`, `width_mm`, `ring_size`,
`on_hand`, `available`, `picked_up_at`. Ficaram em português só `cnpj`, `cpf`, `pix`, `boleto`.
Parte da documentação ainda diz o contrário — **o banco é a fonte**:

```bash
mysql -h127.0.0.1 -uroot -proot -N -e "SELECT column_name FROM information_schema.columns WHERE table_schema='velaro' AND table_name='X';"
```

Isso já quebrou coisa em produção: o middleware comparava com `'aprovado'` e negava acesso a
todo mundo; o layout lia `nome_fantasia` e mostrava "Sua loja" em silêncio, porque `?->` em Blade
devolve null sem erro.

**3. `docs/telas/*.md` são GERADOS.** Saem de `_build_docs.py` a partir de `_mapa.py` + `_notas.md`.
Editar o `.md` é trabalho perdido. Correção de tela entra no `_mapa.py`, depois regenera.

**4. Use `/opt/homebrew/bin/python3.14`.** O `python3` do PATH é 3.9 e não roda os geradores
(f-string aninhada, PEP 701).

**5. O layout Velaro não carrega Alpine.** `base.blade.php` injeta só `velaro.css` e `velaro.js`,
e esse JS só fecha `<details>`. Uma tela que dependa de Alpine fica **inerte, sem sinal visual**.
Use `<details>` nativo ou avise.

**6. Interatividade do Master.** As telas 3.x têm drawer, aba, filtro e modal. Resolva com o que
o design system já tem — não introduza dependência de JS nova sem avisar.

---

## 5. Regras do projeto

- **Cabeçalho de autoria** em todo `.php` e `.blade.php` novo. Formato em `CLAUDE.md`,
  `@since` com a data de criação, descrição de uma linha em pt-BR **sem acentos**.
- **Controller fino**: validação em Form Request, lógica em Service, nada de `$request->validate()`.
- **`audit_logs` em toda escrita do backend** — é critério de aceite de todas as 12 telas, e o
  Anexo I §7 exige log em ação sensível (aprovar, reprovar, baixa financeira, ajuste de estoque).
  Use o `AdminAuditLogger` que já existe.
- **Constantes de model**, nunca string crua.
- **Ações sensíveis** (aprovação, reprovação, baixa, ajuste) precisam de justificativa registrada.
- **Gates antes de commit**: `vendor/bin/pint --test`, `composer qa:static`,
  `php artisan test --compact`, `composer qa:anti-debug`. Um vermelho = BLOQUEADO.
- **Commit e push só com autorização explícita do dono.** Prefixo `[FEAT|FIX|...]`, descrição em
  pt-BR, changelog em `docs/changelog_1_1.md` no formato da guideline 112.

## 6. Como testar

```
lojista@velaro.test  / lojista-velaro    (revendedor aprovado)
lojista2@velaro.test / lojista-velaro    (segundo, para provar isolamento)
admin@velaro.local   / ADMIN_SEED_PASSWORD do .env
```

`php artisan db:seed --class=VelaroSeeder` é idempotente. Servidor:
`php artisan serve`; mockups: `php -S 127.0.0.1:8010 -t docs/mockups`.

**Não rode `migrate:fresh`** sem autorização — a guideline 112 é explícita.

---

## 7. O que eu estou fazendo em paralelo

1. **Jornada do lojista** (em curso): `reseller_id` amarrado no cadastro, e-mail de boas-vindas,
   e o `/portal` passando a aceitar lojista não aprovado com painel por estágio. Mexe em
   `LoginResponse`, no middleware `reseller` e no dashboard do portal.
2. **Vitrine white label** (5 rotas, `Vitrine\LojaController`) depois.

Se precisar de algo no meu território, me avise em vez de editar — foi assim que a rodada da
anglicização virou retrabalho: dois agentes seguiram estratégias opostas no mesmo arquivo.
