# Changelog 1.0

## Lançamento inicial

A versão `1.0` estabelece o ponto de partida oficial do CodaFácil Scaffold como base SaaS reutilizável.

## O que compõe a versão 1.0

### Estrutura de produto

- Site público institucional
- Front autenticado do cliente
- Painel administrativo em `/backend`
- API mobile em `/api/mobile`
- Módulo local CodaFácil IA

### Site público

- Página inicial com apresentação da proposta da base
- Página `/sobre` com visão da arquitetura do produto
- Página `/servicos` para apresentação dos módulos
- Página `/contato` com formulário integrado ao fluxo do sistema

### Front autenticado

- Dashboard do cliente
- CRUD de clientes
- CRUD de produtos
- CRUD de pedidos
- Visualização de pedido com itens
- Navegação unificada com light mode e dark mode

### Painel administrativo

- Dashboard administrativo
- Gestão de usuários
- Gestão de permissões e responsabilidades ACL
- Gestão global de clientes
- Gestão global de produtos
- Gestão global de pedidos
- Audit logs
- Changelog interno

### Autenticação e segurança

- Cadastro de usuários
- Login web
- Login mobile por bearer token
- Login com Google
- Reset de senha
- Verificação de e-mail
- Autenticação em dois fatores
- Bloqueio de usuário
- Controle de acesso por ACL no backend

### API mobile

- `auth/register`
- `auth/login`
- `auth/forgot-password`
- `auth/reset-password`
- `auth/me`
- `auth/logout`
- `dashboard`
- `customers`
- `products`
- `orders`

### Operação assíncrona

- Job para resposta do CodaFácil IA
- Job para processamento de upload do CodaFácil IA
- Scheduler para despacho de resumo diário
- Scheduler para sincronização de uploads pendentes
- Estrutura pronta para filas e rotinas adicionais

### Dados e bootstrap

- Migrações limpas para instalação do zero
- Seed inicial com usuário administrador
- Seed com conta operacional de exemplo
- Catálogo ACL sincronizável por comando
- Dados demonstrativos para validar o fluxo principal

### Documentação incluída

- README do sistema
- Arquitetura
- Modelo de dados
- Jobs e rotinas
- API mobile
- Checklist de clonagem
- Guias de agentes operacionais

## Diretriz da versão

A versão `1.0` define o núcleo estável da base.

Todo novo sistema derivado deve considerar esta release como início oficial do produto, preservando:

- os cinco blocos principais
- autenticação e segurança
- ACL e auditoria
- API mobile
- jobs, filas e rotinas
- estrutura de clientes, produtos e pedidos

## Referência operacional

Para instalar e operar a versão `1.0` localmente:

```bash
php artisan migrate:fresh --seed --no-interaction
php artisan acl:sync-backend --no-interaction
composer dev
php artisan schedule:work
```
