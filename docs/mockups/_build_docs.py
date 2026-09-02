# -*- coding: utf-8 -*-
"""Gera docs/telas/*.md — uma documentação por tela.
Spec estruturada (de _mapa.py) + inventário literal do protótipo (de _notas.md)."""
import importlib.util as il, os, re

s = il.spec_from_file_location("m", "_mapa.py"); m = il.module_from_spec(s); s.loader.exec_module(m)

OUT = "../telas"
os.makedirs(OUT, exist_ok=True)

# ── inventário literal do protótipo, indexado por número de tela ──
notas = open("_notas.md").read()
BLOCOS = {}
for mt in re.finditer(r'^## (\d+\.\d+) (.+?)$', notas, re.M):
    num = mt.group(1); ini = mt.end()
    nxt = re.search(r'^(## |# )', notas[ini:], re.M)
    BLOCOS[num] = notas[ini:ini + (nxt.start() if nxt else len(notas))].strip()

ORIG = {"core":"core (já existe no scaffold)","extensao":"extensão do core","novo":"novo (módulo Velaro)",
        "core/novo":"core + novo","—":"—"}
ENVSLUG = {"Site público":"site","Portal do Lojista":"portal","Vitrine white label":"vitrine",
           "Painel Interno Velaro":"master","Transversal":"core"}

idx = []
for t in m.T:
    num = t["n"]; slug = t["slug"]
    nome = f'{num.replace(".","-")}-{slug}.md'
    arq = t.get("arquivo")
    esc = lambda x: x.replace("|", "\\|")
    tabs = "\n".join(f'| `{esc(n)}` | {ORIG.get(o,o)} | {esc(c)} |' for n, o, c in t["tabelas"])
    perms = "\n".join(f'- {p}' for p in t["permissoes"])
    regras = "\n".join(f'{i}. {r}' for i, r in enumerate(t["regras"], 1))
    proto = BLOCOS.get(num)
    bloco_proto = (f"\n---\n\n## 5. Inventário literal do protótipo\n\n"
                   f"> Transcrição do que a tela do PDF mostra, campo a campo. É a régua de aceite:\n"
                   f"> ausência de campo aqui descrito caracteriza **pendência de escopo**, não melhoria (Anexo I §9).\n\n"
                   f"{proto}\n") if proto else ""
    mock = (f"[`docs/mockups/{arq}`](../mockups/{arq})" if arq else "—")

    doc = f"""# {num} · {t['titulo']}

| | |
|---|---|
| **Ambiente** | {t['env']} |
| **Rota** | `{t['rota']}` |
| **Acesso** | {t['acesso']} |
| **Referência contratual** | Anexo I {t['anexo']} |
| **Mockup** | {mock} |
| **Mapa** | [mapa.html#{slug}](../mockups/mapa.html#{slug}) |

## 1. Tabelas e campos

| Tabela | Origem | Campos |
|--------|--------|--------|
{tabs}

> `core` não é alterado. O domínio Velaro entra em tabelas próprias e em tabelas 1:1
> de extensão, conforme a regra de módulo isolado do scaffold.

## 2. Permissões

{perms}

## 3. Regras críticas

{regras}

## 4. Critérios de aceite

- [ ] Todos os campos da seção 5 existem na tela e persistem no banco.
- [ ] As permissões da seção 2 bloqueiam o acesso indevido (teste automatizado).
- [ ] As regras da seção 3 têm teste cobrindo o caminho feliz e a violação.
- [ ] Paridade dark/light e comportamento mobile-first.
- [ ] Escrita no backend gera registro em `audit_logs`.
{bloco_proto}"""
    open(os.path.join(OUT, nome), "w").write(doc)
    idx.append((num, t["titulo"], t["env"], nome, arq, slug))

# ── índice ──
por_env = {}
for n, ti, env, nome, arq, slug in idx:
    por_env.setdefault(env, []).append((n, ti, nome, arq))
sec = ""
for env in ["Transversal","Site público","Portal do Lojista","Vitrine white label","Painel Interno Velaro"]:
    if env not in por_env: continue
    linhas = "\n".join(
      f'| {n} | [{ti}]({nome}) | ' + (f'[mockup](../mockups/{a})' if a else '—') + ' |'
      for n, ti, nome, a in por_env[env])
    sec += f"\n### {env}\n\n| # | Tela | Mockup |\n|---|------|--------|\n{linhas}\n"

open(os.path.join(OUT, "README.md"), "w").write(f"""# Documentação das telas — Velaro B2B

Uma página por tela: rota, perfil de acesso, tabelas e campos, permissões, regras críticas,
critérios de aceite e o inventário literal do protótipo aprovado.

Fontes: Anexo I (escopo funcional e critérios de aceite), Plano de Negócio VELARO B2B
e a apresentação "PLATAFORMA B2B VELARO ALIANÇAS".

- Mockups navegáveis: [`docs/mockups/`](../mockups/index.html)
- Mapa consolidado das 31 telas: [`docs/mockups/mapa.html`](../mockups/mapa.html)

**Como ler.** A seção 5 de cada documento é a régua de aceite: ela transcreve o que o
protótipo mostra. Ausência de campo, regra, permissão, automação ou relatório ali descrito
caracteriza **pendência de escopo**, e não melhoria opcional (Anexo I §9).
{sec}
---

## Resumo do impacto no banco

{len({tb[0] for t in m.T for tb in t['tabelas'] if tb[1] == 'novo'})} tabelas novas no módulo Velaro ·
{len({tb[0] for t in m.T for tb in t['tabelas'] if tb[1] == 'extensao'})} extensões do core ·
nenhuma tabela do núcleo alterada.
""")
print(f"{len(idx)} documentos + índice gerados em docs/telas/")
