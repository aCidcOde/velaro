# 112 - Agente Deploy Gates

> **Fonte canônica dos gates de `commit`/`push`.**
> `commit` e `push` acontecem **somente com autorização explícita do usuário**.
> A implementação executável destes gates vive em `composer.json` (`qa:gates` e `qa:gates:ci`).

## Sequência oficial (10 gates, em ordem)

| # | Gate | Comando | Bloqueante |
|---|------|---------|------------|
| 1 | Validação do composer | `composer qa:validate` | sim |
| 2 | Auditoria de segurança | `composer qa:security` | sim |
| 3 | Formatação | `composer qa:style` (local) / `composer qa:style:check` (CI) | sim |
| 4 | Análise estática | `composer qa:static` | sim |
| 5 | Testes | `composer qa:test` | sim |
| 6 | Scan de secrets | `composer qa:secrets` | sim |
| 7 | Prefixo de commit | `[TIPO] resumo curto` | sim |
| 8 | Anti-debug | `composer qa:anti-debug` | sim |
| 9 | Imagem container | `trivy image --severity HIGH,CRITICAL --exit-code 1 <imagem>` | só se houver `Dockerfile` |
| 10 | Changelog da branch | atualizar `docs/changelog_X_Y.md` | sim |

Atalho para os gates 1–6 e 8 de uma vez:

```bash
composer qa:gates        # local  (gate 3 = pint --dirty, corrige)
composer qa:gates:ci     # CI     (gate 3 = pint + git diff --exit-code, não corrige)
```

> O gate 9 não se aplica enquanto o repositório não tiver `Dockerfile`.

## Gate 7 — formato de commit

- toda mensagem deve usar o formato `[TIPO] resumo curto`
- tipos aceitos: `FEAT`, `FIX`, `CHORE`, `DOCS`, `REFACTOR`, `TEST`, `BUILD`, `CI`, `PERF`, `STYLE`, `HOTFIX`
- quando a tarefa misturar escopos, usar o tipo dominante da entrega
- sem esse prefixo, o commit não atende o gate

## Readiness técnica mínima

Além dos 10 gates, antes de `commit/push`:

- validar `php artisan route:list --except-vendor` — sem rotas quebradas
- sem migração órfã
- se a tarefa tocar em migrations, validar o caminho **sem destruir dados locais**:
  `php artisan migrate --pretend --no-interaction`
- sem inconsistência entre documentação, rotas e bootstrap atual

## Banco local em desenvolvimento

- `php artisan migrate:fresh`, `php artisan migrate:fresh --seed` e `php artisan db:seed` **não são gates padrão**
- esses comandos só podem ser usados com solicitação explícita do usuário ou em bootstrap local descartável
- no fluxo normal, preferir validação não destrutiva e manter a base local intacta

## Manutenção de dependências

Atualização de dependências é entrega como qualquer outra e passa pelos mesmos 10 gates.

```bash
composer outdated --direct     # o que saiu do topo
composer update                # dentro dos constraints do composer.json
npm outdated
npm update                     # dentro dos ranges do package.json
npm run build                  # o build precisa continuar passando
```

- `composer audit --locked` e `npm audit` devem terminar em **zero advisories**
- bump de **major** (linha `~` no `composer outdated`, coluna `Latest` divergente no `npm outdated`)
  exige autorização explícita: sai do constraint e pode quebrar contrato

## Gate 8 (idioma) — descrição em pt-BR

Além do prefixo, a descrição após `[TIPO]` deve estar em português do Brasil.

## Padrão obrigatório do changelog (gate 10)

1. Atualizar o arquivo da branch ativa: `X.Y` → `docs/changelog_X_Y.md`.
2. Manter no topo `## Principais implementações`, com poucos itens estratégicos em linguagem de negócio.
3. Cada nova entrada de tarefa contém **somente**:
   - título (`### data · tipo · título`);
   - `**Resumo:**`;
   - `**O que foi feito:**`.
4. `**Resumo:**` e `**O que foi feito:**` são curtos, comerciais e **não citam arquivos, classes, funções ou rotas**.
5. Não usar por tarefa `**Arquivos impactados:**` nem `**Status:**`.
6. Ajuste que faz parte de uma `FEAT` já registrada é consolidado no mesmo bloco — não abre entrada `FIX` nova.
7. Manter um único bloco final `## Fechamento Técnico` com:
   - `**🧪 Testes executados**`;
   - `**📊 Total de testes**`;
   - `**🛡️ Validação das demais gates**`;
   - `**📈 Métricas do sistema**`.
8. Marcadores: `🟢` OK · `⚪` N/A · `🔵` métrica numérica.
9. Atualizar o fechamento técnico com o resultado mais recente **antes** de solicitar `commit/push`.
10. Em `**📈 Métricas do sistema**`, registrar obrigatoriamente:
    `Arquivos rastreados`, `Linhas rastreadas`, `Release anterior de referência`,
    `Arquivos da release anterior`, `Linhas da release anterior`,
    `Aumento de arquivos vs release anterior` (% e absoluto),
    `Aumento de linhas vs release anterior` (% e absoluto),
    `Novos commits da release`.
11. Regra comparativa: `1.1` é a baseline da série e usa `⚪ N/A`; cada release seguinte compara apenas com a imediatamente anterior.
12. Contadores oficiais:

```bash
git ls-files | wc -l
git ls-files -z | xargs -0 wc -l | tail -n 1
git rev-list --count <release_anterior>..HEAD
```

> Detalhe completo e versão portável para outros projetos: `docs/guidelines/116-governanca-portavel-commit-push.md`.

## Política de bloqueio

1. Um único gate falhando já define status `BLOQUEADO`.
2. Não existe bypass sem aprovação explícita do usuário.
3. A correção deve ser aplicada e os gates reexecutados.

## Saída esperada

1. Status final: `APROVADO` ou `BLOQUEADO`.
2. Tabela de gates com resultado.
3. Para cada falha: causa objetiva, evidência e ação corretiva.
4. Confirmação de que `commit/push` permanece bloqueado até autorização explícita.

## Prompt base recomendado

```text
Valide esta release com o deploy-agent.
Execute os gates oficiais de qualidade e seguranca na ordem padrao.
Nao execute deploy automatico.
Retorne APROVADO apenas se todos os gates estiverem verdes; caso contrario,
BLOQUEADO com a causa e as correcoes necessarias.
```
