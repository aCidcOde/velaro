# CodaFácil Framework

Scaffold Laravel reutilizável da CodaFácil para acelerar novos sistemas com base operacional pronta.

O projeto já entrega cinco blocos principais:

- site público com `/`, `/sobre`, `/servicos` e `/contato`
- painel autenticado com dashboard e operação de `customers`, `products` e `orders`
- backend em `/backend/*` com ACL, auditoria, changelog e visão global da operação
- API mobile em `/api/mobile/*`
- módulo local de agente em `/agente/*` com conversas persistidas, uploads e processamento assíncrono

## O que o projeto é

Esta base existe para servir como ponto de partida de novos produtos. Ela preserva a infraestrutura comum do sistema e deixa o domínio específico do novo projeto entrar em módulos isolados.

O scaffold já vem com:

- autenticação web e mobile com Fortify, login social com Google e 2FA
- CRUD web e API para clientes, produtos e pedidos
- ACL administrativa baseada em permissões e responsabilidades
- auditoria e changelog no backend
- jobs, filas, scheduler e módulo local de IA
- suporte a light mode e dark mode

## Stack

### Backend

- PHP 8.3.30
- Laravel 13
- Laravel Fortify 1
- Laravel Socialite 5

### Frontend

- Livewire 4
- Volt 1
- Flux 2
- Tailwind CSS 4
- Vite 7
- Chart.js
- Bootstrap 5
- Tabler

### Qualidade e tooling

- PHPUnit 12
- Larastan 3
- Pint 1
- Laravel Boost 2
- Laravel Pail 1

## Como subir localmente

### Fluxo rápido

```bash
composer setup
php artisan acl:sync-backend --no-interaction
composer dev
```

Esse fluxo instala dependências, cria o `.env` se necessário, gera a chave da aplicação, roda as migrations de forma segura e sobe:

- servidor Laravel
- fila local
- logs com Pail
- Vite em modo de desenvolvimento

Por padrão, a aplicação sobe em `http://127.0.0.1:8000`.

### Fluxo manual

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --force --no-interaction
php artisan acl:sync-backend --no-interaction
npm run build
composer dev
```

### Scheduler local

Se a tarefa exigir rotinas agendadas em desenvolvimento, rode também:

```bash
php artisan schedule:work
```

### Dados demonstrativos

Se você realmente precisar recriar um banco local descartável com dados de demonstração, execute isso manualmente:

```bash
php artisan migrate:fresh --seed --no-interaction
```

Esse reset não faz parte do fluxo normal de desenvolvimento nem dos gates de `commit/push`.

## Qualidade

```bash
composer qa:gates
composer qa:test
```

## Documentação

- [Visão geral da documentação](./docs/README.md)
- [Arquitetura](./docs/architecture.md)
- [Modelo de dados](./docs/data-model.md)
- [Jobs e rotinas](./docs/jobs-and-routines.md)
- [API mobile](./docs/mobile/api-mobile.md)
- [Guia mestre](./docs/guidelines/00-master-guideline.md)
