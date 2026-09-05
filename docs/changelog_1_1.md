# Changelog 1.1

## Principais implementações

- Identidade visual Velaro consolidada e aprovada para os quatro ambientes da plataforma B2B.
- Todas as telas do escopo contratado desenhadas, navegáveis e validadas em desktop, tablet e mobile.
- Documentação por tela com campos, permissões, regras de negócio e critérios de aceite.
- Sistema de design registrado como referência única e já aplicado à aplicação em funcionamento.

### 2026-09-05 · FEAT · Um login, um painel: a jornada do lojista comeca dentro do sistema

**Resumo:** O lojista que se cadastrava escolhia uma senha e saia com um login que nao levava a
lugar nenhum: ao entrar, era mandado para uma pagina de acompanhamento fora do painel. O
pre-cadastro deixou de ser um estado de excecao do lado de fora e virou o primeiro passo da jornada
dentro do produto. O painel e um so, do primeiro dia ate a operacao — o que muda e o conteudo.

**O que foi feito:** Quem entra com o cadastro em analise cai no proprio painel e ve ali o
andamento da solicitacao: as etapas da habilitacao, o resultado da verificacao automatica, a linha
do tempo e os dados do cadastro. Quem teve o cadastro recusado ou inativado ve o motivo registrado
por quem decidiu e o caminho para regularizar. Quem foi aprovado continua vendo o painel de sempre,
sem nenhuma mudanca.

Quando a equipe Velaro pede um documento adicional, o lojista passa a ter como responder: o painel
abre o reenvio com a justificativa que a equipe escreveu, aceita os mesmos tres documentos do
cadastro e devolve a solicitacao para a fila de analise, deixando o reenvio registrado no historico.
Ate aqui a equipe pedia informacao e o lojista nao tinha por onde envia-la. O mesmo bloco aparece na
pagina publica de acompanhamento, para quem chega pelo link do e-mail ou do WhatsApp.

O menu lateral passou a contar a jornada inteira: os itens que a aprovacao libera continuam a
vista, desabilitados e com a explicacao de quando abrem, em vez de sumirem. O lojista ve o que o
espera.

A abertura do painel foi deliberadamente estreita. So a tela inicial aceita lojista ainda nao
aprovado; todo o resto do ambiente — catalogo com o custo de compra, pedidos, clientes, financeiro
e margens — continua exigindo a aprovacao, exatamente como antes. E isso que a aprovacao concede, e
afrouxar o ambiente inteiro entregaria a tabela de custo a quem ainda nao passou pela analise. Cada
uma das dezoito telas de negocio tem teste automatizado provando que continua fechada, e a lista
que o teste percorre e lida do proprio roteamento, para que uma tela nova nasca coberta.

O link publico de acompanhamento continua existindo e funcionando sem sessao, porque e ele que o
e-mail e o WhatsApp abrem. Deixou apenas de ser o destino de quem faz login.

### 2026-09-05 · FEAT · Jornada do lojista unificada e permissoes do painel interno

**Resumo:** O lojista deixou de ficar do lado de fora enquanto espera aprovacao. Ele se cadastra,
recebe as boas-vindas por e-mail, entra com a senha que escolheu e acompanha o proprio processo
dentro do painel — o mesmo painel que, depois de aprovado, vira a operacao completa dele.

**O que foi feito:** O cadastro passou a criar o vinculo entre a pessoa e a empresa na hora, e nao
so na aprovacao. Antes o lojista saia do cadastro com um acesso que nao levava a lugar nenhum, e o
sistema o reconhecia comparando enderecos de e-mail — se qualquer um dos dois mudasse, ele perdia
o proprio acompanhamento.

Um e-mail de boas-vindas passou a sair no ato do cadastro, com o numero de protocolo, dizendo que
a analise ainda vai acontecer, que ele ja pode entrar e que documentos adicionais podem ser
pedidos.

O painel virou um so, e muda conforme o estagio: quem espera analise ve as etapas, a triagem
automatica e a linha do tempo; quem precisa complementar documentacao ve o reenvio; quem foi
aprovado ve a operacao inteira; quem foi recusado ve o motivo e como regularizar. Os itens ainda
indisponiveis aparecem no menu desabilitados, com a explicacao — a jornada fica visivel desde o
primeiro dia. A abertura e so do painel: catalogo, pedidos, financeiro e precos continuam
exigindo aprovacao.

A tela de entrada deixou de explicar o roteamento interno do sistema e passou a falar com o
lojista, que e quem usa o site.

Tambem entrou o catalogo de permissoes do painel interno: trinta e seis permissoes em doze
modulos, um por tela, com cinco perfis de equipe. As acoes sensiveis — aprovar, recusar, dar baixa
financeira, ajustar estoque e acessar como revendedor — estao marcadas como exigindo registro.

### 2026-09-05 · FIX · Deploy apontava para o produto errado

**Resumo:** O roteiro de publicacao ainda era o do sistema base do qual a Velaro nasceu. Rodado
como estava, publicaria no diretorio de outro produto do mesmo servidor e reiniciaria a fila dele.
Tambem sobravam mencoes ao sistema base em textos que o usuario le.

**O que foi feito:** O roteiro passou a apontar para o diretorio e a fila da Velaro. A etapa que
gera os arquivos visuais virou opcional, porque o servidor de producao nao tem a ferramenta que a
executa — antes ela interrompia a publicacao no pior momento, com o codigo e o banco ja atualizados
e a aparencia ainda antiga. Entrou uma conferencia que interrompe a publicacao ANTES de qualquer
troca, caso os arquivos visuais nao tenham sido enviados.

Ficou registrado no proprio roteiro que o servidor hospeda mais de um produto e que a reinicializacao
do interpretador afeta todos por alguns segundos.

O nome do sistema base saiu do texto que o assistente responde ao usuario, da tela de envio de
arquivos e do banco de dados sugerido para instalacoes novas — que apontava para a base do outro
produto.

### 2026-09-05 · FEAT · Portal do Lojista e telas de acesso

**Resumo:** O lojista aprovado passou a ter o proprio ambiente. Ele entra, ve o que comprou, o que
deve, quem sao seus clientes, monta a propria vitrine e fala com a Velaro — tudo dentro de uma
fronteira que garante que um lojista jamais alcance o dado de outro.

**O que foi feito:** As dezenove telas do portal foram construidas sobre dados reais: painel com a
situacao dos pedidos, catalogo com o custo de compra, carteira de clientes, acompanhamento de
pedidos com a linha do tempo, financeiro com lotes, notas e pagamento, personalizacao da loja,
tabela de margens e o atendimento.

O isolamento entre lojistas virou estrutura, nao cuidado de quem escreve a consulta: existe uma
peca unica por onde toda busca do portal passa ja restrita ao dono. Pedir o registro de outro
lojista responde exatamente como pedir um registro inexistente — a diferenca entre as duas
respostas permitiria descobrir o tamanho e os codigos da operacao do concorrente. A observacao
interna da equipe Velaro e cortada na consulta, antes de chegar a tela.

As telas de acesso — entrar, recuperar senha, redefinir, segunda etapa, verificacao, confirmacao e
cadastro — ganharam a identidade Velaro, que ate aqui era a do sistema base.

O login passou a levar cada perfil ao seu lugar, como o escopo promete: a equipe Velaro ao painel
interno, o lojista aprovado ao portal, e quem esta em analise a propria solicitacao — antes todos
caiam na mesma pagina generica. Quem esta aguardando informacao adicional tambem, e o estado ganhou
nome e traducao nos tres idiomas.

### 2026-09-05 · FEAT · Site publico no ar

**Resumo:** O primeiro dos quatro ambientes saiu do protótipo e virou sistema. Quem chega pelo
endereço da Velaro agora navega o site de verdade — conhece a empresa, percorre o catálogo e pede
para virar lojista — e o pedido de cadastro entra na base com a trilha que a lei exige.

**O que foi feito:** As dez páginas públicas foram construídas sobre os dados reais do sistema, e
não mais sobre texto fixo: catálogo com busca e filtros por coleção, material, acabamento e
largura, ficha de produto com a especificação técnica, páginas institucionais e a tela de contato.
O preço não aparece em lugar nenhum do site — é condição comercial, liberada só depois da
aprovação do lojista.

O cadastro de lojista funciona de ponta a ponta: valida os documentos da empresa, recebe os três
anexos obrigatórios, gera o protocolo de acompanhamento e registra os aceites com data, endereço
de origem e a versão do texto vigente. O interessado acompanha o andamento pelo protocolo, com a
linha do tempo da análise.

As páginas institucionais que vinham da base reutilizável foram substituídas pelas da Velaro, e o
material antigo saiu do projeto. O conteúdo que alimenta o site — dados da empresa, canais de
atendimento, coleções e catálogo de demonstração — passou a ter carga inicial própria.

Também foi corrigido um defeito de estilo que afetava o site inteiro: as regras da vitrine do
lojista estavam vazando para fora dela e apagando o fundo dos cartões de coleção, além de deixar
um texto com contraste abaixo do mínimo de acessibilidade.

### 2026-09-05 · FEAT · Base do sistema em ingles, tres idiomas e a tela de contato

**Resumo:** O sistema passou a ser escrito em ingles por dentro e a falar tres idiomas por fora.
O lojista, o cliente final e a equipe continuam vendo portugues; o codigo deixou de misturar
idiomas, o que abre a porta para atender fora do Brasil sem refazer nada.

**O que foi feito:** Os nomes tecnicos e os estados de pedido, cadastro, estoque, promocao e
atendimento foram padronizados em ingles, preservando apenas os documentos brasileiros que nao tem
equivalente. Sobre isso entrou a camada de idiomas, com portugues, ingles e espanhol, usando os
mesmos rotulos que os prototipos aprovados ja mostravam.

A conversao do que ja estava gravado foi feita de forma reversivel e com trava de seguranca: se
houver dado que a conversao nao consiga representar, ela para antes de tocar na estrutura, em vez
de falhar no meio. A mesma protecao foi aplicada a dois pontos que podiam quebrar uma atualizacao
em base ja existente — codigo de produto repetido entre contas e registro sem responsavel.

O painel interno passou a tratar o pedido cuja conta responsavel foi excluida: o historico
continua consultavel, a edicao e recusada com aviso claro, e o cadastro de produto passou a avisar
quando o codigo ja pertence a outro item, em vez de deixar o erro estourar no banco.

Foi desenhada tambem a tela de contato do site publico, que faltava: ate agora o convite para
falar com a Velaro nao tinha para onde levar.

### 2026-09-05 · FEAT · Fundação de dados da plataforma B2B

**Resumo:** A plataforma saiu do desenho e ganhou a estrutura que sustenta os quatro
ambientes. Tudo que as telas aprovadas prometem — do cadastro do lojista até a retirada do
pedido no balcão da loja — passou a ter onde ser guardado, com as regras de negócio
expressas na própria estrutura.

**O que foi feito:** O modelo de dados foi construído a partir das telas aprovadas e das
regras do contrato, cobrindo catálogo e estoque por tamanho, cadastro e habilitação de
lojistas, carteira de clientes finais, pedidos com os dois acompanhamentos independentes,
faturamento semanal com nota fiscal e remessa, vitrine com identidade e margem próprias de
cada loja, atendimento e as configurações do sistema.

As dezoito pendências que o levantamento anterior havia registrado como decisões em aberto
foram todas fechadas — entre elas o consentimento do lojista exigido pela lei de proteção de
dados, o transporte da remessa, o frete e o desconto do pedido, o local de armazenamento e a
solicitação de produção. Uma delas era risco concreto de perda de dados: excluir um usuário
apagaria em cascata a carteira de clientes e o histórico de pedidos de um lojista inteiro.
O vínculo foi refeito para que isso não seja possível, e o histórico de pedidos passou a ser
protegido contra exclusão por ser registro fiscal.

Sobre essa estrutura foi escrita a camada de acesso aos dados e os geradores de massa de
teste, que permitem montar um cenário completo de ponta a ponta — do lojista aprovado ao
chamado de atendimento — para validar o comportamento antes de existir tela. A documentação
das telas foi realinhada com o que existe de fato, e a cadeia de geração passou a avisar em
destaque quais arquivos são produzidos automaticamente e não devem ser editados à mão.

A atualização de instalações existentes passou a preservar cadastros, vínculos e históricos
durante a padronização dos dados. Códigos de produto conflitantes são recusados de forma
consistente, pedidos de contas excluídas continuam consultáveis e reversões incompatíveis
são interrompidas antes de remover informações. Esses cenários ganharam testes de regressão.

### 2026-09-03 · CHORE · Higiene do repositório

**Resumo:** Arquivos gerados automaticamente pela execução das ferramentas internas
deixaram de ser versionados, evitando conflito recorrente entre quem trabalha no projeto.

**O que foi feito:** Os artefatos temporários criados ao rodar os geradores de
documentação foram removidos do controle de versão e passaram a ser ignorados
permanentemente.

### 2026-09-02 · FEAT · Telas internas, medição no mobile e diagrama do banco

**Resumo:** O detalhamento das telas avançou dos fluxos principais para os níveis
internos, cobrindo o que o usuário encontra depois do primeiro clique.

**O que foi feito:** Foram desenhadas as telas de segundo nível de cada ambiente —
detalhes de pedido, de chamado e de produto, as áreas de configuração e as páginas
institucionais. O comportamento no celular foi medido e um diagrama do banco de dados
foi produzido para apoiar a etapa de implementação.

### 2026-09-02 · FIX · Tipografia da marca bloqueada pela política de segurança

**Resumo:** A tipografia aprovada não estava chegando ao usuário: a política de segurança
da plataforma bloqueava o provedor externo em silêncio, e o texto saía numa fonte genérica.

**O que foi feito:** As fontes passaram a ser servidas pelo próprio domínio, em vez de
abrir exceção na política de segurança para um terceiro. Foram embarcados apenas os
recortes de caracteres usados pelo português, mantendo o peso do carregamento baixo.

### 2026-09-02 · FEAT · Identidade visual aplicada à aplicação

**Resumo:** A identidade aprovada saiu da prancheta e passou a valer no produto em
funcionamento. Login, painéis, site e comunicações transacionais agora falam a mesma
língua visual da marca.

**O que foi feito:** A paleta e a tipografia aprovadas foram aplicadas ao sistema, de
forma que todos os componentes já existentes passassem a exibir a marca sem precisar
ser refeitos. As cores que estavam escritas diretamente nas telas, e por isso escapavam
do sistema, foram localizadas e substituídas — incluindo a lateral do acesso, a área dos
gráficos, o site institucional e os e-mails automáticos. O contraste foi medido par a par
nos dois temas e atende ao critério de acessibilidade adotado. O nome da marca deixou de
estar fixo nos títulos das telas de acesso e passou a vir da configuração do sistema.

### 2026-09-02 · FEAT · Design system e telas da plataforma B2B

**Resumo:** A plataforma B2B da Velaro ganhou uma referência visual e funcional completa,
cobrindo todo o escopo contratado, para validação antes da implementação. A marca e a
operação passaram a conviver na mesma experiência sem que uma prejudique a outra.

**O que foi feito:** A identidade visual foi consolidada e aprovada, com paleta,
tipografia e regras de uso definidas a partir da arte oficial da marca. Todas as telas
do escopo contratado foram desenhadas e conectadas entre si, permitindo percorrer os
quatro ambientes de ponta a ponta: a captação e aprovação de lojistas, o portal do
parceiro, a vitrine com a marca do revendedor e o painel interno da fábrica. Cada tela
recebeu documentação própria com campos, permissões, regras de negócio e critérios de
aceite, apoiada na transcrição do material aprovado pelas partes. Campos e etapas
exigidos em contrato que não apareciam no material original foram identificados e
incorporados. O comportamento foi validado em desktop, tablet e mobile.

## Auditoria Técnica — Segurança, Arquitetura e Workflow de Pedidos

A versão `1.1` aplica as correções identificadas em auditoria técnica completa do scaffold (4 agentes em paralelo: segurança, arquitetura, formatação e conformidade com guidelines).

---

## Correções de Segurança

### S1 — AgentUploadListController: isolamento por usuário
- Adicionado filtro `where('user_id', auth()->id())` na query de uploads
- **Antes:** qualquer usuário com acesso ao agente via via uploads de todos os usuários do sistema
- **Depois:** cada usuário vê apenas seus próprios uploads

### S2 — AgentUploadDeleteController: verificação de ownership
- Adicionado `abort_unless($upload->user_id === auth()->id(), 403)` antes da deleção
- **Antes:** usuário autenticado podia deletar uploads de outros via enumeração de ID
- **Depois:** tentativa de deletar upload alheio retorna 403

### S5 — Session encryption habilitada por padrão
- `config/session.php`: `SESSION_ENCRYPT` default alterado de `false` para `true`
- Sessões armazenadas em banco de dados passam a ser cifradas em repouso

### F1 — Import não utilizado removido
- `config/database.php`: removido `use Pdo\Mysql;` que não era referenciado

### S8 — Content-Security-Policy header
- `SecurityHeaders.php`: adicionado header CSP como defesa em profundidade contra XSS
- Política: `default-src 'self'` com permissões explícitas para script, style, img, font e connect

---

## Arquitetura — Novos Services

### OrderWorkflowStatusService
- **Arquivo:** `app/Services/OrderWorkflowStatusService.php`
- Define os 7 statuses válidos do domínio: `draft`, `awaiting_payment`, `paid`, `in_progress`, `completed`, `canceled`, `error`
- Implementa matriz de transições permitidas conforme spec
- Métodos: `isValid()`, `canTransition()`, `assertTransition()`, `transition()`
- `assertTransition()` enforçado no `update` de pedidos (web e mobile)

### OrderService
- **Arquivo:** `app/Services/OrderService.php`
- Elimina duplicação entre `OrdersController` e `Api/Mobile/OrderController`
- Centraliza: `resolveOwnedCustomer()`, `resolveOwnedProducts()`, `syncItems()`

---

## Arquitetura — Audit Logging Expandido

- `Backend/CustomerController`: audit log em `update` e `destroy`
- `Backend/ProductController`: audit log em `update` e `destroy`
- `Backend/OrderController`: audit log em `update` e `destroy`
- **Antes:** apenas `UserController` registrava eventos no `AuditLog`
- **Depois:** todas as operações de escrita do backend geram trilha de auditoria

---

## Conformidade com Spec

### Statuses de pedido corrigidos
- `OrderStoreRequest`: status restrito a `['draft']` (novos pedidos sempre iniciam em draft)
- `OrderUpdateRequest`: statuses alinhados com a spec (`awaiting_payment`, `paid`, `in_progress`, etc.)
- `OrderFactory`: substituídos `pending`/`processing` pelos valores canônicos da spec
- `CustomerProductOrderFlowTest`: corrigido de `submitted` → `draft` e progressão válida `draft → awaiting_payment`

---

## Governança Operacional de Commit e Banco Local

- `commit/push` passa a exigir o formato obrigatório `[TIPO] resumo curto`
- Tipos aceitos: `FEAT`, `FIX`, `CHORE`, `DOCS`, `REFACTOR`, `TEST`, `BUILD`, `CI`, `PERF`, `STYLE`, `HOTFIX`
- `php artisan migrate:fresh`, `php artisan migrate:fresh --seed` e `php artisan db:seed` deixam de ser rotina padrão de desenvolvimento
- O bootstrap seguro da base passa a priorizar `composer setup` ou `php artisan migrate --force --no-interaction`
- O reset com seed fica restrito a banco local descartável e execução explícita

---

## Gates Executados (Guideline 112)

| Gate | Comando | Resultado |
|------|---------|-----------|
| 1 | `vendor/bin/pint --dirty` | ✅ pass |
| 2 | `php artisan test --compact` | ✅ 66 passed, 281 assertions |
| 3 | `php artisan route:list --except-vendor` | ✅ 85 rotas sem erros |
| 4 | `php artisan migrate:fresh --seed --no-interaction` | ✅ seed completo |

---

## Referência operacional

```bash
php artisan migrate:fresh --seed --no-interaction
php artisan acl:sync-backend --no-interaction
composer dev
```

---

## Manutenção — 2026-09-02 — Atualização de dependências e sincronização de documentação

Retomada do projeto após ~5 meses sem manutenção (último commit em 2026-03-30).

### Branch

- Criada a branch `main` a partir da `1.1` (que já continha toda a `master`)
- `main` passa a ser o tronco do scaffold; `master` fica como histórico legado

### Dependências — backend

`composer update` dentro dos constraints do `composer.json` (nenhum bump de major):

| Pacote | De | Para |
|--------|----|------|
| laravel/framework | 13.2.0 | 13.30.1 |
| livewire/flux | 2.13.0 | 2.18.0 |
| livewire/volt | 1.10.4 | 1.11.2 |
| laravel/fortify | 1.36.1 | 1.39.0 |
| laravel/socialite | 5.25.0 | 5.31.0 |
| laravel/boost | 2.4.1 | 2.7.0 |
| laravel/sail | 1.54.0 | 1.67.0 |
| laravel/pint | 1.29.0 | 1.30.5 |
| laravel/pail | 1.2.6 | 1.2.7 |
| laravel/tinker | 3.0.0 | 3.0.2 |
| larastan/larastan | 3.9.3 | 3.11.0 |
| phpunit/phpunit | 12.5.14 | 12.5.34 |
| mockery/mockery | 1.6.12 | 1.6.15 |
| nunomaduro/collision | 8.9.1 | 8.9.5 |
| mercadopago/dx-php | 3.8.0 | 3.16.0 |

- **Antes:** 40 advisories de segurança abertos (`composer audit --locked`), incluindo severidade *high* em `laravel/framework`, `guzzlehttp/guzzle`, `league/commonmark`, `symfony/mime` e `symfony/http-kernel`
- **Depois:** zero advisories

### Dependências — frontend

- `node_modules` estava ausente; reinstalado
- `npm update` dentro dos ranges do `package.json`
- **Antes:** 11 vulnerabilidades (2 critical, 8 high, 1 moderate) — `shell-quote`, `axios`, `nanoid`, `postcss`, `rollup`, `vite`, `form-data`, `picomatch`, `browserslist`, `follow-redirects`
- **Depois:** zero vulnerabilidades
- `npm run build` validado

### Documentação sincronizada com o código

`CLAUDE.md` documentava um stack que não existe mais:

| Item | Documentado | Real |
|------|-------------|------|
| Laravel | 12 | 13 |
| Livewire | 3 | 4 |
| PHP | 8.4+ | 8.3 (plataforma fixada em 8.3.30) |
| PHPUnit | 11 | 12 |
| Banco padrão | SQLite | MySQL (SQLite só nos testes) |
| Agente | n8n + OpenAI + Google Drive | CodaFácil IA local, sem serviço externo |

- Removidos da doc os services inexistentes `N8nAgentService`, `GoogleDriveAgentService`, `OpenAiAgentService`, `AgentToolService`
- Removidos os jobs inexistentes `SendN8nAgentMessageJob`, `UploadAgentFileToGoogleDriveJob`
- Documentados os services reais `OrderService` e `OrderWorkflowStatusService`
- Documentados os commands `BackfillOrderPublicNumbers` e `SyncAgentDriveUploads`
- Bloco de env: removidas as vars mortas `N8N_*`, `OPENAI_*`, `GOOGLE_DRIVE_AGENT_*`; documentadas as vars reais `AGENT_*`
- Diagrama de status do pedido corrigido para refletir a matriz real (`draft → canceled` e `paid|in_progress → error` faltavam)
- Gate 8 alinhado ao que o `composer.json` executa de fato (`rg`, via `composer qa:anti-debug`)

### Guidelines

- **112** reescrita como fonte canônica de fato: a guideline se declarava dona dos "10 gates" mas continha uma lista de 4 itens divergente do `composer.json`. Agora os 10 gates estão mapeados 1:1 nos scripts `qa:*`, com seção nova de manutenção de dependências
- **00-master-guideline**: banco padrão corrigido, ponto de partida do clone passa a ser a branch `main` (v1.1), regra nova de zero advisories
- **114**: removida a amarração à versão `1.0`; reforçado que o agente é local e sem dependência externa

### Contexto (`.claude/context/`)

Os arquivos descreviam um **produto anterior** (carteira com ledger, emissão de certidões InfoSimples, agente "Gordon", webhook MercadoPago) — nada disso existe no scaffold:

- `design-patterns.md`: removidos os padrões 5 (Ledger Wallet), 8 (`InfoSimplesJobStatus`), 9 (Dynamic Required Fields) e 12 (session fallback do Gordon); adicionados "Service compartilhado web/mobile" e "Audit log em toda escrita do backend"
- `common-hurdles.md`: removidos os problemas de n8n, Google Drive, MercadoPago e InfoSimples; adicionados os de reconciliação de upload e de dependências desatualizadas
- `business-rules.md`: **criado** — o `CLAUDE.md` referenciava o arquivo, mas ele não existia. Documenta a matriz de transição real, ownership, ACL, auditoria e segurança transversal

### Gates executados

| Gate | Comando | Resultado |
|------|---------|-----------|
| 1 | `composer validate --no-check-publish` | ✅ valid |
| 2 | `composer audit --locked` | ✅ zero advisories (era 40) |
| 3 | `vendor/bin/pint --test` | ✅ passed |
| 4 | `vendor/bin/phpstan analyse` | ✅ no errors |
| 5 | `php artisan test --compact` | ✅ 66 passed, 281 assertions |
| 8 | `composer qa:anti-debug` | ✅ sem debug calls |
| — | `npm audit` | ✅ zero vulnerabilidades (era 11) |
| — | `npm run build` | ✅ build ok |

### Pendências identificadas

> Os itens 1 a 4 foram resolvidos na manutenção de remoção de código morto, mais abaixo neste changelog. O item 5 segue em aberto.

1. ✅ **`mercadopago/dx-php` é dependência não utilizada** — zero referências em `app/`, `routes/`, `config/`, `resources/`. Está só no `composer.json`/`lock`. Remover ou implementar.
2. ✅ **Factory órfã** — `database/factories/OrderItemRequiredDataFactory.php` referencia `App\Models\OrderItemRequiredData` e `App\Models\RequiredDataField`, que não existem. Quebra se for usada. (Não é pega pelo PHPStan: a análise cobre só `app/`.)
3. ✅ **Views órfãs** — `resources/views/backend/required-data-fields/{index,create,edit}.blade.php` apontam para um componente Livewire inexistente e não têm rota.
4. ✅ **`.env` local com vars mortas** — `N8N_*`, `OPENAI_*`, `GOOGLE_DRIVE_AGENT_*` continuam preenchidas, incluindo segredos, sem nenhum código que as leia.
5. **Majors disponíveis** (fora dos constraints, exigem autorização): `vite` 7→8, `laravel-vite-plugin` 2→3, `jquery` 3→4, `concurrently` 9→10, `@tabler/icons-webfont` 2→3.

---

## Manutenção — 2026-09-02 — Portagem de práticas do produto evoluído

Origem: `~/data/planetacertidoes-saas`, produto que evoluiu a partir deste scaffold. Foram lidas as 43 guidelines, os 6 agentes, as 2 skills, o `DESIGN.md` e o `pipeline.sh` de lá, separando o que é genérico do que é domínio de certidões.

> **Nota de versão:** o produto evoluído está em Laravel 12 / PHPUnit 11 — **atrás** deste scaffold (Laravel 13 / PHPUnit 12). Nada foi regredido; só práticas, estrutura documental e tooling foram portados. Os scripts `qa:*` já eram idênticos nos dois repositórios.

### Agentes (`docs/agentes/`) — de stubs a definições completas

De ~1,3 KB somados para ~9,8 KB. Cada agente ganhou papel, fontes canônicas, regra de uso e saída esperada:

- `README.md` — índice consolidado, mapa de agentes, **gatilhos operacionais** (`commit/push`, `criar landing page`) e regra anti-duplicação
- `business-agent.md` — processo de 6 passos + regra de separação core vs. produto
- `developer-agent.md` — add-ons do executor, gatilhos e regra de commit/push
- `review-agent.md`, `darkmode-agent.md`, `deploy-agent.md` — wrappers apontando para as guidelines `110`/`111`/`112`
- `gordon-agent.md` — wrapper do CodaFácil IA, reforçando que o módulo é local

### Guidelines

- **110** (243 B → 2,9 KB): escopo obrigatório de revisão em qualidade e segurança, achados com severidade/risco/correção, e uma seção nova **específica do scaffold** (ownership, status via service, ACL sincronizada, auditoria, não expor `id` interno, nada de I/O externo síncrono)
- **111** (243 B → 1,6 KB): escopo de verificação dark/light, regras de código (sem cor hardcoded sem `dark:`, WCAG AA, token antes de literal) e entrega esperada
- **112**: ganhou o padrão obrigatório de changelog, o gate 8 de idioma (`pt-BR`), a política de bloqueio e o formato de saída `APROVADO`/`BLOQUEADO`
- **116** (540 B → 7,5 KB): governança portável completa — política inegociável, fluxo operacional, 10 gates portáveis, padrão de changelog com métricas, checklist de adaptação para outro projeto e bloco pronto para copiar
- **00-master-guideline**: seções novas de convenções transversais (busca case-insensitive, `public_number`, auditoria, Form Request, paridade dark/light, mobile-first), **Governança Git** com 21 regras, mapa de agentes e skills

### Skills (`.claude/skills/`) — novo

- `tailwind/` — Tailwind v4 CSS-first, tokens `@theme`, dark mode por data-attribute
- `frontend-design/` — construção de interface com qualidade de design, mobile-first. **O bloco "Mobile App Visual System (Flutter)" foi removido**: era específico do domínio de certidões (saldo de carteira, etapa de seleção de cliente, paleta jurídica). Em seu lugar entrou uma seção de restrições do scaffold

### Design system (`DESIGN.md`) — novo

Portado como **template neutro**: mantém a arquitetura (tokens no frontmatter, "Named Rules", vocabulário de sombra, do's & don'ts) com paleta e marca genéricas para o clone substituir. Preserva as práticas de acessibilidade: WCAG 2.1 AA nos dois temas, alvo de toque de 44px e escala de espaçamento 4/8/12/16/24/32/48.

### Deploy (`pipeline.sh`) — reescrito

O script anterior tinha um **bug**: default para a branch `v_1_3`, que não existe neste repositório, e fazia `git checkout` cego. Além disso, faltavam etapas essenciais.

| | Antes | Depois |
|---|---|---|
| Branch | `git checkout v_1_3` (inexistente) | detecta a branch atual, `pull --ff-only` |
| Dependências PHP | não instalava | `composer install --optimize-autoloader --no-dev` |
| Dependências JS | não instalava | `npm ci` |
| ACL | não sincronizava | `php artisan acl:sync-backend` |
| Caches | não tratava | `optimize:clear` + config/route/view/event cache |
| Opcache | não zerava | restart do PHP-FPM (reload não limpa a SHM) |
| Ambientes | um só | `homologacao` / `producao` por variável de ambiente |

### Outros

- `.claude/launch.json` — **criado** (estava ausente): configs de Laravel serve, Vite, queue worker e Pail
- `.gitleaks.toml` — estava configurado para o **outro projeto**: título `planetacertidoes-saas`, allowlist com `INFOSIMPLES_TOKEN`/`MERCADOPAGO_*` e dois hashes de commit que não existem aqui. Reescrito para o scaffold, com allowlist genérica de placeholders e de fixtures de teste
- `CLAUDE.md` — passou a mapear agentes, skills, design system e gatilhos operacionais; checklist de commit atualizado com o gate de idioma e o status `APROVADO`/`BLOQUEADO`

---

---

## Manutenção — 2026-09-02 — Remoção de código morto e regeneração do baseline

Fecha as pendências 1 a 4 registradas acima. Antes de apagar qualquer coisa, cada item passou por verificação adversarial independente (9 agentes: um par por item tentando **provar que o item estava em uso**, mais uma varredura de completude). Resultado: os 4 confirmados mortos, confiança alta, zero bloqueio de código — e vários achados novos.

### 1. Dependência não utilizada — removida

`mercadopago/dx-php` saiu do `composer.json`. Verificações que sustentaram a remoção:

- zero ocorrências do namespace `MercadoPago\` ou de classes do SDK (`PreferenceClient`, `PaymentClient`, `MercadoPagoConfig`) em todo o código
- `composer why` confirma que o único dependente era o pacote raiz — nenhum pacote transitivo órfão
- o pacote não declara `extra.laravel`: sem auto-discovery, sem service provider, sem alias
- `README.md` listava "Mercado Pago SDK" na stack — linha removida junto, senão a doc passaria a mentir

### 2. Factory órfã — removida

`database/factories/OrderItemRequiredDataFactory.php` referenciava `App\Models\OrderItemRequiredData` e `App\Models\RequiredDataField`, ambos inexistentes. O PHPStan não pegava porque a análise cobre apenas `app/`.

### 3. Views órfãs — removidas

As três views de `resources/views/backend/required-data-fields/` invocavam componentes Livewire que não existem, sem nenhuma rota apontando para elas.

### 4. Variáveis de ambiente mortas — removidas

O escopo inicial era de 12 variáveis; a verificação encontrou **36**. Nenhuma delas é lida por qualquer arquivo de `config/`, `app/`, `routes/`, `database/` ou `tests/`:

| Bloco | Vars | Observação |
|-------|------|------------|
| `OPENAI_*` | 3 | credencial real preenchida |
| `N8N_*` | 3 | token real, URL de outro sistema |
| `GOOGLE_DRIVE_AGENT_*` | 6 | client secret e refresh token reais |
| `MERCADOPAGO_*` | 10 | vazias ou URLs de outro domínio |
| `INFOSIMPLES_*` | 14 | **credenciais de terceiros preenchidas** |

Preservadas por serem vivas: `GOOGLE_CLIENT_*` (Socialite, coberta por teste), `AWS_*`, `REDIS_*`, `MEMCACHED_HOST`, `VITE_APP_NAME`.

### 5. Baseline do PHPStan — regenerado

O baseline descrevia majoritariamente o produto anterior: 23 dos 35 caminhos apontavam para arquivos inexistentes (`MercadoPagoController`, `InfoSimplesService`, `WalletController`, `CertificateType`, `NewOrderWizard`, `OpenAIAgentService`…).

| | Antes | Depois |
|---|---|---|
| Tamanho | 35.930 bytes | 1.533 bytes |
| Entradas | 135 | 7 |
| Caminhos inexistentes | 23 de 35 | 0 |

Os 7 erros remanescentes são reais e ficam honestamente suprimidos. O gate 4 continua `[OK] No errors`.

### Lacuna inversa — `.env.example` completado

A verificação encontrou o problema oposto: variáveis **vivas** que nenhum arquivo de exemplo documentava, o que quebra o clone do scaffold. Adicionadas: `APP_VERSION`, `DB_QUEUE_RETRY_AFTER`, `MAIL_SCHEME`, `GOOGLE_CLIENT_ID/SECRET/REDIRECT_URI`, as 7 `AGENT_*` e `ADMIN_SEED_EMAIL/PASSWORD`.

Não foram documentadas de propósito as chaves `mail.new_order_internal_to`, `mail.order_item_error_to` e `mail.order_completed_download_link_expire_minutes`: existem em `config/mail.php` mas não têm nenhum leitor.

### Correções de documentação

- `CLAUDE.md` descrevia `AgentUpload` com a coluna `drive_file_id`, que **não existe** na migration nem no `$fillable` — substituída pelas 12 colunas reais
- `CLAUDE.md`, `.claude/context/*` e `docs/agentes/gordon-agent.md` apontavam `agent:sync-drive-uploads` como rotina canônica. O que está no scheduler (`routes/console.php`) é `agent:sync-uploads`; `SyncAgentDriveUploads` é duplicata exata do outro comando e não é agendada. Referências corrigidas e a duplicata sinalizada

---

---

## Manutenção — 2026-09-02 — Erradicação do resíduo do produto anterior

Segunda rodada de limpeza, também precedida de verificação adversarial (13 agentes: um investigador por item, segunda opinião independente nos destrutivos e um agente de integração para achar conflito entre as mudanças). A verificação **corrigiu um erro do diagnóstico anterior** e achou resíduo mais grave do que o listado.

### Correção do diagnóstico

O relatório anterior dizia que `DatabaseSeeder` e `MobileApiTest` usavam status inválido. Errado: `OrderItem` tem **domínio de status próprio** (`pending`, `processing`, `completed`, validado por `Api/Mobile/OrderStoreRequest`), diferente do `Order`. Das três ocorrências apontadas, só uma era bug de fato — a linha do `Order` no seeder. Alterar o teste teria causado `422` e quebrado o gate 5.

### Marca do produto anterior em página pública

- `about.blade.php` exibia o wordmark do produto anterior como ilustração de card — trocado pelo logo do próprio scaffold, seguindo o padrão do card irmão
- `welcome.blade.php` exibia uma imagem de 1,6 MB com o codinome do agente do produto anterior — trocada por ilustração abstrata genérica já usada na mesma página
- `public/robots.txt` apontava crawlers para o sitemap de um domínio de terceiro — linha removida

### Vazamento entre projetos

- `public/storage` era um **symlink absoluto para o storage de outro repositório** — tudo servido em `/storage/*` vinha do produto anterior. Refeito com `storage:link` para o storage deste projeto
- `storage/users.json` estava **versionado**, com nome do produto anterior, e-mail real e hash bcrypt de senha, sem nenhum leitor no código — removido
- `.claude/settings.local.json` concedia leitura do outro repositório e continha buscas, um `curl` que gravava lá dentro e um domínio de API do produto anterior — 16 das 49 entradas removidas, preservando integralmente as permissões dos gates

### Código morto

- comando `agent:sync-drive-uploads` era duplicata exata de `agent:sync-uploads` e não estava no scheduler — removido, com as 4 referências em documentação corrigidas
- duas views órfãs: uma usava um model que nunca existiu neste repositório (falha em runtime se montada), a outra tinha a classe de apoio ausente
- três chaves de `config/mail.php` sem nenhum leitor, mais as variáveis correspondentes
- `MAIL_ENCRYPTION` no `.env.example`: morta desde o Laravel 11, substituída por `MAIL_SCHEME`
- 13 assets de marca de terceiros e a biblioteca `jquery-multi`, todos sem referência — `public/` caiu de 21 MB para 13 MB

### Correções de bug encontradas de passagem

- seeder criava pedido com status fora da máquina de estados — estado inalcançável em dado de demonstração
- `manifest.json` e `browserconfig.xml` apontavam para ícones na raiz web, mas os arquivos estão em `/images/icons/` — já estava quebrado, corrigido

### Preparação para o fork

- `composer.json` ainda se identificava como `laravel/livewire-starter-kit`, com a descrição do starter kit oficial — corrigido para a identidade do scaffold
- `.env.example` trazia e-mails pessoais como default, que se propagariam para todo fork — trocados por placeholders
- `docs/clone-checklist.md` reescrito: cobria 3 seções e citava a versão errada; agora cobre identidade, configuração, deploy, governança, conteúdo, validação e **higiene herdada** — a seção que impede o próximo fork de repetir estes mesmos problemas

---

## Fechamento Técnico

**🧪 Testes executados**

| Gate | Comando | Resultado |
|------|---------|-----------|
| 1 | `composer qa:validate` | 🟢 composer.json valid |
| 2 | `composer qa:security` | 🟢 zero advisories |
| 3 | `composer qa:style` | 🟢 passed |
| 4 | `composer qa:static` | 🟢 no errors |
| 5 | `composer qa:test` | 🟢 387 passed, 1.874 assertions |
| 6 | `composer qa:secrets` | 🟢 no leaks found (histórico) |
| 7 | prefixo de commit | ⚪ N/A — sem commit nesta correção |
| 8 | `composer qa:anti-debug` | 🟢 sem debug calls |
| 9 | Trivy | ⚪ N/A (sem Dockerfile) |
| 10 | changelog atualizado | 🟢 este bloco |
| 11 | `composer qa:gates` | 🟢 todos os gates passaram na correção do review |
| 12 | `npm run build` | 🟢 build concluído |
| — | `php artisan route:list --except-vendor` | 🟢 156 rotas (80 do scaffold + 76 do Velaro) |

**📊 Total de testes**

🔵 387 testes · 1.874 assertions, incluindo 22 testes novos de regressão

**🛡️ Validação das demais gates**

- 🟢 Atualização incremental do schema antigo e reversão com dados validadas em SQLite
  em memória, incluindo vínculos, índices, defaults e históricos de status
- 🟢 Instalações já traduzidas aceitam a migration incremental sem repetir renomeações
- 🟢 SKUs duplicados bloqueiam a atualização antes de alterar a tabela; códigos existentes
  não são renomeados automaticamente e exigem resolução explícita do conflito
- 🟢 Validação de SKU global coberta no web, mobile e painel administrativo
- 🟢 Pedidos sem usuário podem ser consultados com seus itens; tentativas de edição são
  recusadas sem modificar o pedido ou registrar uma atualização indevida
- 🟢 Rollback de produtos, clientes e pedidos sem responsável bloqueado antes de remover
  campos; a reversão continua funcionando quando os registros possuem responsável
- 🟢 Mensagem de histórico conferida visualmente em 1280 px e 390 px, light/dark, sem
  rolagem horizontal, usando uma prévia isolada com dados fictícios
- ⚪ MySQL e PostgreSQL não executados nesta correção; a validação anterior em MySQL não
  substitui o teste da nova migration nesses motores
- ⚪ Nova migration não aplicada ao banco local; nenhum reset, seed ou commit executado

**📈 Métricas do sistema**

- 🔵 Arquivos rastreados: 1.279
- 🔵 Linhas rastreadas: 177.404 (medição antes da atualização deste fechamento; exclui arquivos novos não rastreados)
- ⚪ Release anterior de referência: N/A (`1.1` é a baseline da série)
- ⚪ Arquivos da release anterior: N/A
- ⚪ Linhas da release anterior: N/A
- ⚪ Aumento de arquivos vs release anterior: N/A
- ⚪ Aumento de linhas vs release anterior: N/A
- 🔵 Novos commits da release: 15 (incluindo esta entrega)

**Status final: 🟢 Código e regressões validados · ⚪ Aplicação da nova migration ao banco local pendente**
