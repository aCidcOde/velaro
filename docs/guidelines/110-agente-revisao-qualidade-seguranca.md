# 110 - Agente de Revisão de Qualidade e Segurança (manual)

## 1. Objetivo

Padronizar uma revisão técnica final, executada manualmente **após a funcionalidade estar pronta**, para reduzir risco de regressão, fragilidade estrutural e vulnerabilidades.

## 1.1 Arquivo do agente

- `docs/agentes/review-agent.md`

## 2. Regra de operação

1. Este agente **não roda sozinho**.
2. Deve ser executado **somente após** a entrega funcional estar implementada.
3. A revisão **não altera comportamento** do produto sem aprovação explícita.
4. Todo achado deve vir com:
   - severidade (`alta`, `média`, `baixa`);
   - risco objetivo;
   - sugestão de correção.

## 3. Escopo obrigatório da revisão

### 3.1 Qualidade de código

1. Duplicação desnecessária e oportunidade de reutilização.
2. Trechos fragilizados (workarounds, gambiarras, acoplamento excessivo).
3. Complexidade desnecessária em funções/componentes.
4. Nomes, coesão e clareza de responsabilidades.
5. Cobertura de testes para cenários críticos.

### 3.2 Segurança

1. Validação e sanitização de entrada (Form Request em todo input).
2. Controle de acesso e autorização em rotas/ações.
3. Risco de XSS / SQL injection / exposição de dados sensíveis.
4. Upload e download de arquivos (tipo, tamanho, permissão e caminho).
5. Vazamento de informação em mensagens de erro ou logs.
6. Integridade de fluxo (bloqueios de etapa e pré-condições).

### 3.3 Específico deste scaffold

1. **Ownership**: toda query de recurso do usuário filtra por `user_id`? Toda deleção valida dono?
2. **Status de pedido**: nenhuma escrita direta em `status` fora do `OrderWorkflowStatusService`.
3. **ACL**: rota nova sob `/backend` está coberta por permissão e por `acl:sync-backend`?
4. **Auditoria**: escrita nova no backend gera registro em `AuditLog`?
5. **Exposição**: nenhuma rota/response expõe `orders.id` interno em vez de `public_number`.
6. **Async**: nenhuma chamada a API externa síncrona em controller.

## 4. Entrada mínima

1. Branch ou diff alvo da revisão.
2. Lista curta de mudanças funcionais esperadas.
3. Escopo de arquivos sensíveis (ex.: pagamentos, uploads, auth, admin).

## 5. Saída esperada do agente

1. Lista de achados ordenados por severidade.
2. Riscos residuais (o que não foi coberto por teste automatizado).
3. Sugestões de melhoria sem alterar regra de negócio.
4. Checklist final:
   - `vendor/bin/pint --test`;
   - testes focados;
   - recomendação de suíte completa quando aplicável.

## 6. Prompt base recomendado

```text
Revise este diff com foco em qualidade e seguranca, sem alterar comportamento funcional.
Priorize bugs, regressao potencial, duplicacao, acoplamento, validacao/autorizacao,
XSS/SQLi/upload/download e vazamento de dados.
Retorne os achados por severidade com arquivo, risco e correcao sugerida.
```
