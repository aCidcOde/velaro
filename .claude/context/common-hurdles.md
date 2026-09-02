# Common Hurdles — Problemas Frequentes

> Escopo: apenas o core do CodaFácil Scaffold.

## 1. Jobs da fila não processando
**Sintoma:** Mensagens do agente não respondem, uploads ficam em `processing`.
**Solução:** Garantir que o queue worker esteja rodando. Em dev, `composer dev` já inclui.
```bash
php artisan queue:listen
php artisan queue:failed
php artisan queue:retry all
```

## 2. Transição de status do pedido rejeitada
**Sintoma:** Erro ao tentar mudar status de um `Order`.
**Solução:** `OrderWorkflowStatusService` enforça transições válidas. Não atualizar `status` diretamente no model — passar por `assertTransition()`.

## 3. `AgentUpload` travado em `processing`
**Sintoma:** Upload nunca avança de status.
**Solução:** Queue worker parado ou job falhou. Checar `php artisan queue:failed` e rodar a reconciliação:
```bash
php artisan agent:sync-uploads
```
O limite de "processing" velho é `AGENT_UPLOAD_STALE_PROCESSING_SECONDS` (padrão 900s).

## 4. Gate `can:access-backend` negando acesso ao admin
**Sintoma:** Admin redirecionado ou 403.
**Solução:** Usuário precisa de **ambos**: `is_admin = true` no banco E passar o gate. ACL é adicional, não substitui `is_admin`. Após adicionar permissão, rodar `php artisan acl:sync-backend`.

## 5. Confusão entre Volt e Full Livewire Components
- **Full** (`app/Livewire/`): wizard multi-step, lógica complexa com estado
- **Volt** (`resources/views/livewire/`): CRUD simples, formulários, listas

## 6. `public_number` vs `id` interno nas URLs
**Solução:** Rotas usam `public_number`. O model `Order` tem route binding customizado. Nunca expor `id` numérico ao usuário. Pedido legado sem número: `php artisan orders:backfill-public-numbers`.

## 7. `unit_price` "desatualizado" após mudança no catálogo
**Solução:** Comportamento correto por design. `unit_price` é snapshot imutável. Novos pedidos usam o preço novo.

## 8. Diferença entre o banco de teste e o de desenvolvimento
**Sintoma:** Teste passa mas o comportamento difere rodando local.
**Solução:** Os testes rodam em **SQLite em memória** (`phpunit.xml`), enquanto dev/prod usam **MySQL**. Evitar sintaxe específica de um dialeto; colunas `json` se comportam diferente. Ao mexer em migration, validar sem destruir a base local:
```bash
php artisan migrate --pretend --no-interaction
```

## 9. Dependências desatualizadas após período sem manutenção
**Sintoma:** `composer audit` / `npm audit` acusando advisories; `node_modules` ausente.
**Solução:** Atualizar dentro dos constraints e revalidar os gates:
```bash
composer update && npm install && npm update && npm run build
composer qa:gates
```
Bump de **major** exige autorização explícita — ver `docs/guidelines/112-agente-deploy-gates.md`.
