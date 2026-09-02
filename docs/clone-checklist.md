# Checklist de Fork

Use ao iniciar um produto novo a partir do CodaFácil Scaffold (branch `main`, versão `1.1`).

> O scaffold é **base genérica**. Nada de marca, domínio ou credencial de produto específico
> deve permanecer no `main` — se algo assim aparecer aqui, é bug do scaffold, não do fork.

## 1. Identidade do projeto

1. `composer.json` — `name`, `description`, `keywords`
2. `package.json` — se houver identificação
3. `.env` e `.env.example` — `APP_NAME`, `APP_URL`, `MAIL_FROM_*`, `MAIL_REPLY_TO_*`
4. `.gitleaks.toml` — `title`
5. `README.md` — nome, descrição e stack do novo produto
6. `public/logo.webp`, `logo-light.webp`, `logo-dark.webp` e `favicon.ico` / `apple-touch-icon.png`
7. `public/images/icons/` — jogo de ícones PWA (`manifest.json` e `browserconfig.xml` já apontam para essa pasta)
8. `public/robots.txt` — adicionar o `Sitemap:` do domínio novo
9. `DESIGN.md` — paleta, tipografia e *creative north star* do produto
10. Git: novo remote, e definir a branch tronco

## 2. Configuração

1. `composer setup` (ou o bootstrap manual do `README.md`)
2. Preencher no `.env` as credenciais **novas** — nunca herdar credencial do scaffold ou de outro produto
3. `php artisan storage:link` — confirmar que `public/storage` aponta para o storage **deste** projeto
4. `php artisan acl:sync-backend --no-interaction`
5. `ADMIN_SEED_EMAIL` / `ADMIN_SEED_PASSWORD` antes de qualquer seed
6. Seed apenas em banco local descartável e com autorização explícita

## 3. Deploy

1. `pipeline.sh` — ajustar `DEPLOY_HML_DIR`, `DEPLOY_PROD_DIR`, `SUPERVISOR_HML_PROGRAM`, `SUPERVISOR_PROD_PROGRAM`
2. Conferir o programa do supervisor e o serviço PHP-FPM do servidor alvo

## 4. Governança

1. Definir o arquivo de changelog da série (`docs/changelog_X_Y.md`) e a baseline de comparação
2. Rodar o checklist de adaptação da guideline `116` (§8): prefixo de commit, idioma, comandos oficiais de cada gate
3. Revisar `docs/guidelines/` — remover o que for específico do scaffold e adicionar as guidelines do domínio novo
4. Revisar `docs/agentes/` — os wrappers seguem válidos; ajustar o índice se surgirem agentes do produto

## 5. Conteúdo do produto

1. Textos públicos de `/`, `/sobre`, `/servicos` e `/contato`
2. Ilustrações das páginas públicas (`about.blade.php`, `welcome.blade.php`, `services.blade.php`)
3. Nomenclatura do CodaFácil IA, se o produto exigir branding próprio
4. Seeders, factories e dados demonstrativos
5. Domínio específico em **módulos isolados** — sem mutar o core (`customers`, `products`, `orders`, ACL, auth, auditoria)

## 6. Validação técnica

1. `composer qa:gates` — os 10 gates da guideline `112`
2. `php artisan route:list --except-vendor` — sem rota quebrada
3. Validar web pública, dashboard do cliente, admin, API mobile
4. Validar fila e scheduler
5. `npm run build`

## 7. Higiene herdada (conferir sempre)

1. `grep -rn` por nomes de marca de terceiros em `resources/views/` e `public/`
2. Nenhuma credencial de outro produto no `.env`
3. `.claude/settings.local.json` — allowlist sem caminho de outro repositório
4. `phpstan-baseline.neon` — regenerar se herdar entrada de arquivo inexistente
5. Nenhum arquivo com dado real versionado (`storage/`, fixtures, dumps)
