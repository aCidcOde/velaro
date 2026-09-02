# CodaFácil Scaffold 1.0

Versão inicial da base SaaS reutilizável da CodaFácil.

Este repositório nasce como ponto de partida oficial para novos sistemas Laravel com cinco blocos principais, autenticação completa, ACL administrativa, API mobile, jobs, scheduler, documentação operacional e um módulo local de IA para referência técnica.

## Escopo da versão 1.0

- Site público com `/`, `/sobre`, `/servicos` e `/contato`
- Front autenticado com dashboard e operação de `customers`, `products` e `orders`
- Admin em `/backend/*` com dashboard, usuários, ACL, clientes, produtos, pedidos, auditoria e changelog
- Autenticação web e mobile com cadastro, login, reset, verificação de e-mail, 2FA, login social Google e bloqueio de usuário
- API mobile em `/api/mobile/*`
- CodaFácil IA com conversa persistida, uploads PDF, jobs assíncronos e rotina agendada
- Seed inicial com contas de exemplo, ACL sincronizável e dados demonstrativos
- Light mode e dark mode nas interfaces principais

## Stack atual

- PHP 8.3.30
- Laravel 13
- Fortify 1 e Socialite 5
- Livewire 4 + Volt 1 + Flux 2
- Tailwind CSS 4 + Vite 7
- PHPUnit 12, Larastan 3 e Pint 1
- Laravel Boost 2 e Pail 1

## Núcleo funcional

### Público

- Página inicial institucional
- Página de arquitetura e posicionamento da base
- Landing técnica de serviços
- Página de contato integrada ao fluxo padrão do sistema

### Front do cliente

- Dashboard com visão resumida da conta
- Cadastro e edição de clientes
- Cadastro e edição de produtos
- Cadastro, edição, listagem e visualização de pedidos
- Perfil do usuário autenticado

### Admin

- Dashboard administrativo
- Gestão de usuários
- ACL por permissões e responsabilidades
- Visão global de clientes, produtos e pedidos
- Audit logs
- Changelog de versões

### CodaFácil IA

- Chat persistido por conversa
- Upload de PDFs
- Processamento assíncrono local
- Histórico de uploads
- Geração de resumo operacional diário por comando agendável

## Modelo base

- `User`
- `Customer`
- `Product`
- `Order`
- `OrderItem`
- `AuditLog`
- `AclPermission`
- `AclResponsibility`
- `AgentConversation`
- `AgentMessage`
- `AgentUpload`

## Bootstrap rápido

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --force --no-interaction
php artisan acl:sync-backend --no-interaction
npm run build
```

Se precisar popular um banco local descartável com dados demonstrativos, execute o seed de forma explícita e consciente. `migrate:fresh --seed` não faz parte do fluxo rotineiro de desenvolvimento nem dos gates de `commit/push`.

## Operação local

Fluxo principal:

```bash
composer dev
```

Para validar tarefas agendadas localmente:

```bash
php artisan schedule:work
```

## Scripts úteis

```bash
composer setup
composer dev
composer qa:gates
composer qa:test
```

## Seed padrão

- disponível apenas quando o `DatabaseSeeder` for executado explicitamente
- Admin: `admin@example.com` por padrão ou valor definido em `ADMIN_SEED_EMAIL`
- Senha do admin: valor de `ADMIN_SEED_PASSWORD` ou senha gerada no seed
- Conta operacional de exemplo: `owner@example.com`

## Guias principais

- [Arquitetura](./architecture.md)
- [Modelo de dados](./data-model.md)
- [Jobs e rotinas](./jobs-and-routines.md)
- [API mobile](./mobile/api-mobile.md)
- [Mobile](./mobile/README.md)
- [Checklist de clonagem](./clone-checklist.md)
- [Changelog 1.0](./changelog_1_0.md)
- [Índice de agentes](./agentes/README.md)

## Ponto de partida

Esta documentação descreve o sistema como ele existe na versão `1.0`.

Novos produtos devem partir desta base como estado inicial oficial, preservando os módulos estruturais e adicionando regras de negócio em camadas isoladas.
