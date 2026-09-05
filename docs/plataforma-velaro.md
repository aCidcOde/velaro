# Plataforma B2B Velaro — rotas, ambientes e cascos

Este documento descreve **como o código está organizado** para as 31 telas contratadas
(+33 internas). O *quê* de cada tela está em [`docs/telas/`](telas/README.md); o
visual aprovado, em [`docs/mockups/`](mockups/index.html).

## Os quatro ambientes

| Ambiente | Prefixo | Quem entra | Casco Blade | Middleware |
|---|---|---|---|---|
| Site público | `/` | qualquer um | `x-velaro.layouts.site` | — |
| Portal do Lojista | `/portal` | Parceiro Premium **aprovado** | `x-velaro.layouts.portal` | `auth`, `verified`, `not_blocked`, **`reseller`** |
| Vitrine white label | `/loja/{slug}` | consumidor final (sem login) | `x-velaro.layouts.vitrine` | — |
| Painel Master | `/backend` | equipe Velaro (`is_admin` + gate `access-backend`) | `x-velaro.layouts.master` | `auth`, `verified`, `not_blocked`, `can:access-backend` |

Login e senha continuam com o Fortify (`/login`, `/forgot-password`, `/reset-password`,
`/two-factor-challenge`), dentro do casco `x-velaro.layouts.auth`.

**Login único com roteamento por perfil (tela 0).** `App\Http\Responses\Auth\LoginResponse`
manda Master para `/backend` e revendedor aprovado para `/portal`; `/dashboard`
(`PainelRedirectController`) faz o mesmo para quem chega já logado. Quem está em
pré-cadastro vai para `/solicitacao/{protocolo}`.

## Onde está cada coisa

- **`routes/velaro.php`** — o contrato: caminho, nome de rota, parâmetro e `Controller@método`
  de cada tela. Os caminhos são os que `docs/mockups/_mapa.py` declara. `routes/web.php`
  guarda só a infraestrutura do scaffold que sobrou (perfil/2FA, agente, usuários/ACL/auditoria).
- **`config/velaro-telas.php`** — nome de rota → número da tela, título, mockup, doc de aceite.
- **`config/velaro-nav.php`** — menus dos três ambientes (os cascos os renderizam).
- **`config/velaro-icones.php`** — os 50 ícones do design system (espelho de `_ui.py`).
- **`app/Http/Controllers/{Site,Portal,Vitrine,Backend}/`** — controllers finos, um namespace por ambiente.
- **`resources/views/velaro/{site,portal,vitrine,backend}/`** — as telas, portadas dos mockups.
- **`resources/views/components/velaro/`** — cascos (`layouts/*`) e componentes (`icon`, `logo`,
  `wordmark`, `ring`, `search`, `mobile-nav`, `nav-links`).
- **`resources/css/velaro.css`** — o bundle: fontes auto-hospedadas + `velaro/velaro-tokens|ui|screens.css`
  (espelhos de `docs/mockups/`) + `velaro/vitrine.css`.

## Regras que o código carrega

- **O site público não mostra preço.** Nunca. O CTA no lugar do preço é "Quero ser revendedor".
- **A vitrine não tem marca Velaro.** É pintada só por `--shop-*`, que vem de `reseller_stores`.
  O preço ali é o do lojista ao consumidor, não o custo Velaro.
- **Tudo no portal é escopado por `reseller_id`.** O middleware `reseller` garante o vínculo;
  a consulta ainda assim filtra — nunca um `findOrFail` solto.
- **A relação financeira é Velaro → lojista.** O consumidor final não paga a plataforma.

## Como manter

- **Mudou o design nos mockups?** `php artisan velaro:sync-css && npm run build`.
- **Tela nova?** Rota em `routes/velaro.php` → entrada em `config/velaro-telas.php` → controller
  no namespace do ambiente → view em `resources/views/velaro/<ambiente>/` usando o casco.
- **Todos os links, com parâmetro real do banco:** `php artisan velaro:links` (`--json` para máquina).
  O cenário de demonstração vem de `php artisan db:seed --class=VelaroDemoSeeder` (idempotente).
- **Grade responsiva:** nunca `style="grid-template-columns:…"` inline; use `style="--gcols:…"`.
  Estilo inline vence media query e foi o que quebrou 15 telas no mobile dos mockups.
