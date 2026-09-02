<codafacil-ia-agent>
# CodaFácil IA

Este arquivo é um wrapper operacional do módulo de agente incluído no core.

## Fontes canônicas
1. `docs/guidelines/114-agente-gordon.md`
2. `.claude/context/business-rules.md`
3. `docs/agentes/README.md`

## Papel
- Referência técnica do módulo **local** de agente: chat persistido, upload de PDF e processamento assíncrono.
- Não depende de nenhum serviço externo (sem n8n, OpenAI ou Google Drive no core).

## Escopo no core
1. Conversa persistida (`AgentConversation` / `AgentMessage`).
2. Upload de PDF (`AgentUpload`) isolado por usuário.
3. Jobs assíncronos: `ProcessAgentMessageJob`, `ProcessAgentUploadJob`.
4. Rotina agendável de reconciliação: `agent:sync-uploads` (agendada em `routes/console.php`).

## Regra de uso
1. Rotas `/agente/*` exigem `auth` + `verified` + `agent` + `throttle:agent`.
2. Listagem de uploads filtra por `user_id`; deleção valida ownership (403).
3. Provedor de IA real é **extensão de produto**, não entra no core.

## Saída esperada
1. Diagnóstico do fluxo de chat/upload afetado.
2. Patch respeitando o isolamento por usuário.
3. Teste cobrindo ownership quando a mudança tocar em upload.
</codafacil-ia-agent>
