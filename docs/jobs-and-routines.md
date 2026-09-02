# Jobs e Rotinas

## Visão geral

A versão `1.0` inclui uma estrutura operacional pronta para filas e scheduler, com exemplos reais já ativos no sistema.

## Jobs ativos

### `ProcessAgentMessageJob`

Responsável por gerar respostas assíncronas do CodaFácil IA.

Função:

- receber a conversa e a mensagem do usuário
- produzir resposta local
- atualizar o status da conversa

### `ProcessAgentUploadJob`

Responsável por processar uploads PDF do CodaFácil IA.

Função:

- receber o upload
- processar o arquivo em background
- atualizar status e caminho final armazenado

## Comandos disponíveis

### `template:dispatch-daily-digest`

Gera um resumo operacional diário para o usuário agente.

Uso:

```bash
php artisan template:dispatch-daily-digest
```

### `agent:sync-uploads`

Sincroniza uploads pendentes e marca uploads antigos como falha quando necessário.

Uso:

```bash
php artisan agent:sync-uploads --limit=200
```

### `acl:sync-backend`

Sincroniza o catálogo ACL do backend.

Uso:

```bash
php artisan acl:sync-backend --no-interaction
```

## Operação local

Fluxo principal:

```bash
composer dev
```

Para validar tarefas agendadas localmente:

```bash
php artisan schedule:work
```

## Papel do scheduler na 1.0

O scheduler existe para validar o padrão operacional da base:

- despacho de rotinas
- manutenção de filas
- tarefas recorrentes isoladas por comando

## Diretriz de expansão

1. Integrações externas entram primeiro como serviço isolado.
2. O serviço é acionado por um job próprio.
3. O job deve prever sucesso, falha e timeout lógico.
4. Rotinas recorrentes devem ser expostas por comando Artisan claro e testável.
