# Arquitetura

## Visão geral

O CodaFácil Scaffold 1.0 é organizado em cinco blocos principais:

- público
- front autenticado
- admin
- API mobile
- CodaFácil IA

Cada bloco reutiliza os mesmos serviços estruturais de autenticação, autorização, jobs, scheduler, auditoria e seed inicial.

## Ambientes

### Público

Responsável pela apresentação institucional do produto.

Inclui:

- `/`
- `/sobre`
- `/servicos`
- `/contato`

### Front autenticado

Responsável pela operação do usuário dono da conta.

Inclui:

- dashboard do cliente
- clientes
- produtos
- pedidos
- perfil

### Admin

Responsável pela visão global da operação e governança.

Inclui:

- `/backend`
- usuários
- ACL
- clientes
- produtos
- pedidos
- auditoria
- changelog

### API mobile

Responsável por acesso programático ao mesmo domínio operacional do front.

Inclui:

- autenticação por token
- dashboard resumido
- CRUD de clientes
- CRUD de produtos
- CRUD de pedidos

### CodaFácil IA

Responsável por demonstrar um módulo assíncrono local desacoplado do core.

Inclui:

- conversa persistida
- mensagens por job
- uploads PDF
- histórico de uploads
- rotina diária de resumo

## Blocos transversais

### Autenticação

- Laravel Fortify
- reset de senha
- verificação de e-mail
- 2FA
- login Google

### Autorização

- ACL administrativa baseada em catálogo
- responsabilidades
- permissões
- overrides por usuário

### Auditoria

- logs administrativos
- changelog interno

### Assíncrono

- filas
- jobs
- comandos agendáveis

### Frontend

- Blade
- Livewire
- Flux UI
- Tailwind CSS
- suporte a light mode e dark mode

## Princípios de extensão

1. O domínio base de `customers`, `products` e `orders` permanece como núcleo reutilizável.
2. Novas regras de negócio devem entrar em módulos próprios, sem quebrar os serviços estruturais.
3. Integrações externas devem ser encapsuladas em serviços e jobs específicos.
4. O backend deve continuar usando ACL como porta de acesso administrativa.
5. A API mobile deve preservar ownership por usuário e paridade com o front quando aplicável.
