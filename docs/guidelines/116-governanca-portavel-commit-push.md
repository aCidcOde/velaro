# 116 - Governança Portável de Commit/Push

## 1. Objetivo

Consolidar uma política reutilizável de `commit/push`, aplicável em qualquer produto derivado deste scaffold sem depender do contexto de negócio de um repositório específico.

Fontes que esta guideline generaliza:

1. `docs/guidelines/112-agente-deploy-gates.md`
2. `docs/guidelines/00-master-guideline.md`
3. `docs/agentes/developer-agent.md`
4. `docs/agentes/deploy-agent.md`
5. `docs/agentes/README.md`

## 2. Política base inegociável

1. Nunca executar `git commit` automaticamente.
2. Nunca executar `git push` automaticamente.
3. `commit` e `push` só podem acontecer com autorização **explícita, atual e contextual** do usuário.
4. Toda mensagem de commit deve seguir um prefixo obrigatório.
5. A descrição do commit, após o prefixo, deve estar em `pt-BR`.
6. Se surgir um fix adicional depois do primeiro commit, ele **não** gera novo commit por iniciativa própria; a correção fica apenas local até nova autorização explícita.
7. O fechamento técnico de `commit/push` só acontece depois da execução completa dos gates oficiais.
8. O changelog da branch/release ativa deve ser atualizado antes do fechamento técnico.

## 3. Gatilho operacional

1. Gatilho textual: `commit/push`
2. Ação obrigatória: executar a rotina completa desta guideline antes de pedir ou usar `git commit` e `git push`.
3. Status final permitido:
   - `APROVADO`
   - `BLOQUEADO`

## 4. Prefixo obrigatório de commit

```regex
^\[(FEAT|FIX|CHORE|DOCS|REFACTOR|TEST|BUILD|CI|PERF|STYLE|HOTFIX)\]
```

Exemplos válidos:

1. `[FEAT] adiciona gate portavel de commit e push`
2. `[FIX] corrige validacao da rotina de changelog`
3. `[DOCS] documenta politica de readiness tecnica`

## 5. Fluxo operacional portável

### 5.1 Antes de pensar em commit/push

1. Confirmar que existe autorização explícita e atual do usuário.
2. Atualizar o changelog da branch/release ativa.
3. Executar os gates técnicos oficiais.
4. Registrar evidências resumidas dos gates.
5. Só então solicitar ou executar `commit/push`, se a autorização continuar válida.

### 5.2 Se aparecer um ajuste após um commit

1. Corrigir localmente.
2. Não abrir novo commit por iniciativa própria.
3. Reexecutar os gates afetados.
4. Aguardar nova autorização explícita.

## 6. Gates oficiais portáveis

Adapte os comandos à stack do outro projeto, mas preserve os mesmos controles.

### 6.1 Sequência mínima recomendada

1. Validação do gerenciador de dependências — `composer validate --no-check-publish`
2. Auditoria de segurança de dependências — `composer audit --locked`
3. Formatação sem diff residual — `vendor/bin/pint` + `git diff --exit-code`
4. Análise estática — `vendor/bin/phpstan analyse --memory-limit=1G`
5. Testes automatizados — `php artisan test --compact`
6. Scan de secrets — `gitleaks detect --source . --no-banner --redact --config .gitleaks.toml`
7. Guard de prefixo do commit — regex da seção 4
8. Guard semântico de idioma — descrição após o prefixo em `pt-BR`
9. Gate anti-debug — bloquear `dd(`, `dump(`, `ray(`, `var_dump(`, `die(`
10. Changelog da branch/release atualizado no formato oficial

### 6.2 Gate opcional por contexto

1. Se o projeto tiver imagem ou pipeline Docker, incluir scan de imagem/container.
2. Se existir suíte agregadora única (`composer qa:gates`), usá-la como fechamento obrigatório.

## 7. Padrão portável de changelog

### 7.1 Estrutura

1. Definir um arquivo oficial por branch/release.
2. Mapeamento: `v_X_Y` ou `X.Y` → `docs/changelog_X_Y.md`.
3. Manter no topo uma seção `## Principais implementações` com poucos temas estratégicos em linguagem de negócio.

### 7.2 Formato de cada entrada

Cada tarefa deve conter somente:

1. título da entrada (`### data · tipo · título`)
2. `**Resumo:**`
3. `**O que foi feito:**`

### 7.3 Regras editoriais

1. `**Resumo:**` e `**O que foi feito:**` devem ser curtos, comerciais e **sem citar arquivos, classes, funções ou rotas**.
2. Não usar por tarefa: `**Arquivos impactados:**` nem `**Status:**`.
3. Se um ajuste fizer parte de uma `FEAT` já registrada, consolidar no mesmo bloco e não abrir nova entrada `FIX`.

### 7.4 Fechamento técnico único

O arquivo mantém um único bloco final `## Fechamento Técnico` com:

1. `**🧪 Testes executados**`
2. `**📊 Total de testes**`
3. `**🛡️ Validação das demais gates**`
4. `**📈 Métricas do sistema**`

Marcadores visuais:

1. `🟢` para `OK`
2. `⚪` para `N/A`
3. `🔵` para métricas numéricas

### 7.5 Métricas obrigatórias

Registrar no mínimo:

1. `Arquivos rastreados`
2. `Linhas rastreadas`
3. `Release anterior de referência`
4. `Arquivos da release anterior`
5. `Linhas da release anterior`
6. `Aumento de arquivos vs release anterior` (percentual e absoluto)
7. `Aumento de linhas vs release anterior` (percentual e absoluto)
8. `Novos commits da release`

Contadores oficiais:

```bash
git ls-files | wc -l                              # arquivos rastreados
git ls-files -z | xargs -0 wc -l | tail -n 1      # linhas rastreadas
git rev-list --count <release_anterior>..HEAD     # novos commits da release
```

### 7.6 Regra comparativa

1. Definir uma release baseline inicial da série.
2. Na baseline, os campos comparativos ficam como `⚪ N/A`.
3. Toda release seguinte compara apenas com a imediatamente anterior.

> Neste scaffold, `1.1` é a baseline da série e usa `⚪ N/A` nos campos comparativos.

## 8. Checklist de adaptação para outro projeto

Antes de aplicar esta política em outro repositório, definir:

1. o prefixo oficial de commit
2. o idioma oficial da mensagem de commit
3. o comando oficial de testes
4. o comando oficial de análise estática
5. o comando oficial de formatação
6. o comando oficial de auditoria de dependências
7. o comando oficial de secret scanning
8. os diretórios cobertos pelo gate anti-debug
9. o padrão do arquivo de changelog por release/branch
10. a baseline inicial de comparação
11. se existirá uma suíte agregadora única, como `qa:gates`

## 9. Bloco pronto para copiar em outro projeto

```md
## Governança Git

1. Nunca executar `git commit` automaticamente.
2. Nunca executar `git push` automaticamente.
3. `commit` e `push` só podem ocorrer com autorização explícita, atual e contextual do usuário.
4. Toda mensagem de commit deve usar o prefixo `^\[(FEAT|FIX|CHORE|DOCS|REFACTOR|TEST|BUILD|CI|PERF|STYLE|HOTFIX)\]`.
5. A descrição do commit, após o prefixo, deve estar em `pt-BR`.
6. Se surgir um fix adicional após um commit, não criar novo commit por iniciativa própria; manter a correção local até nova autorização explícita.
7. Antes do fechamento técnico de `commit/push`, atualizar o changelog da branch/release ativa.
8. O gatilho textual `commit/push` deve acionar a rotina oficial de gates pré-deploy/pré-release.
```

## 10. Prompt base recomendado

```text
Valide esta entrega no fluxo de commit/push.
Execute todos os gates oficiais na ordem padrao.
Nao execute git commit nem git push sem autorizacao explicita e atual.
Retorne APROVADO apenas se todos os gates estiverem verdes; caso contrario, BLOQUEADO
com causa objetiva, evidencias e correcao necessaria.
```

## 11. Resultado esperado

1. Status final: `APROVADO` ou `BLOQUEADO`
2. Resultado de cada gate
3. Evidências resumidas das falhas
4. Ação corretiva recomendada
5. Confirmação de que `commit/push` permanece bloqueado até autorização explícita
