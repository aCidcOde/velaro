# Mapa visual de inputs - Velaro

Escopo desta etapa: somente aparência, hierarquia e comportamento responsivo.
Campos, validações, permissões, banco de dados e regras de negócio ficam para uma
etapa posterior.

Prancha navegável: <http://localhost:8010/06-mapa-inputs.html>

## Direção visual

- Campos são discretos para não competir com produto, conteúdo ou ação primária.
- A altura padrão é `44px`; a versão compacta de `36px` aparece apenas em filtros
  de desktop e volta a `44px` no mobile.
- O raio dos inputs é `8px`, menor que o raio dos cards, para reforçar a hierarquia.
- Labels ficam sempre visíveis. Placeholder é exemplo de conteúdo, nunca substituto
  do rótulo.
- Esmeralda comunica foco em superfícies claras.
- Dourado comunica foco em superfícies escuras; não é borda permanente de campo.
- Erro e sucesso alteram borda e mensagem, sem preencher toda a superfície com cor.
- Sombra não é usada em campos. O foco recebe apenas um halo leve.

## Anatomia

1. Label de `12px`, peso 600.
2. Intervalo de `6px` entre label e controle.
3. Controle de `44px`, texto de `14px` no desktop e `16px` no mobile.
4. Intervalo de `6px` entre controle e ajuda.
5. Ajuda ou mensagem de `12px`, com uma linha sempre que possível.

## Estados da família

| Estado | Tratamento visual |
|---|---|
| Default | Borda neutra fina e fundo branco |
| Hover | Borda neutra mais escura, sem sombra |
| Focus | Borda esmeralda e halo externo suave |
| Preenchido | Conteúdo em alto contraste, sem ornamento adicional |
| Desabilitado | Fundo rebaixado, texto atenuado e cursor bloqueado |
| Somente leitura | Aparência normal para preservar legibilidade |
| Erro | Borda vermelha e mensagem textual abaixo |
| Sucesso | Borda verde e mensagem textual abaixo |

## Composição

- Usar no máximo três controles por linha.
- Largura deve indicar a expectativa visual do conteúdo: campos longos ocupam mais
  espaço; seletores curtos ocupam menos.
- Campos relacionados compartilham a linha e o intervalo de `16px`.
- Grupos diferentes usam `24px` de respiro.
- Em telas estreitas, todos os grupos passam para uma coluna sem alterar a ordem.
- Filtros podem ser compactos no desktop, mas nunca menores que `44px` no mobile.

## Escolhas visuais

- Checkbox e radio simples para listas curtas.
- Opção com explicação usa contorno plano, sem sombra ou efeito de card promocional.
- Switch representa uma aparência binária imediata.
- Controle segmentado serve apenas para poucas opções paralelas.
- Pills ficam reservadas a status e filtros curtos; não devem dominar formulários.

## Superfícies

Em superfície clara, a ação visual é esmeralda. Em superfície ônix ou esmeralda,
o controle usa fundo branco translúcido e o dourado aparece somente no foco. Esse
tratamento preserva a regra `Gold On Dark` sem transformar cada input em ornamento.

## Decisões propostas para validação

1. Manter `44px` como altura universal da família.
2. Aprovar label acima do campo como padrão de todos os ambientes.
3. Usar `8px` nos controles e reservar raios maiores para cards e seções.
4. Manter o dourado fora dos inputs claros.
5. Limitar formulários a três colunas e transformar tudo em uma coluna no mobile.
