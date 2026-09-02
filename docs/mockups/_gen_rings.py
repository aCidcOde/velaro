# -*- coding: utf-8 -*-
"""Gera placeholders SVG de par de alianças. Sem dependência externa,
sem imagem binária: o desenho é o próprio placeholder."""

# hi = brilho, mid = corpo, lo = sombra, hi2 = reflexo secundário
METALS = {
    "ouro":    ("#f8ead0", "#d8ae57", "#8a6224", "#f0d59b"),
    "rose":    ("#f9e0d1", "#dda07f", "#96583e", "#f2c8ae"),
    "branco":  ("#ffffff", "#dbe0e4", "#8b959c", "#f0f3f5"),
    "black":   ("#7c8288", "#33383d", "#101215", "#565c62"),
    "grafite": ("#c9ced3", "#8e959b", "#4a5056", "#adb4ba"),
    "fosco":   ("#e6d3ae", "#c9ae7d", "#a08a5f", "#dbc69c"),
}

def _ellipse_path(cx, cy, rx, ry):
    return (f"M {cx-rx},{cy} a {rx},{ry} 0 1,0 {2*rx},0 "
            f"a {rx},{ry} 0 1,0 {-2*rx},0 Z")

def _annulus(cx, cy, rx, ry, t):
    return _ellipse_path(cx, cy, rx, ry) + " " + _ellipse_path(cx, cy, rx-t, ry-t)

def _rrect_path(cx, cy, rx, ry, k=0.40):
    """Retângulo de cantos suaves — usado no aro quadrado."""
    x, y, w, h = cx-rx, cy-ry, 2*rx, 2*ry
    r = min(rx, ry) * k
    return (f"M {x+r:.1f},{y:.1f} H {x+w-r:.1f} A {r:.1f},{r:.1f} 0 0 1 {x+w:.1f},{y+r:.1f} "
            f"V {y+h-r:.1f} A {r:.1f},{r:.1f} 0 0 1 {x+w-r:.1f},{y+h:.1f} "
            f"H {x+r:.1f} A {r:.1f},{r:.1f} 0 0 1 {x:.1f},{y+h-r:.1f} "
            f"V {y+r:.1f} A {r:.1f},{r:.1f} 0 0 1 {x+r:.1f},{y:.1f} Z")

def _annulus_sq(cx, cy, rx, ry, t):
    return _rrect_path(cx, cy, rx, ry) + " " + _rrect_path(cx, cy, rx-t, ry-t)

def _stones(cx, cy, rx, ry, t, n, uid, color="#ffffff"):
    """Pedras distribuídas no arco superior da banda."""
    import math
    r = (rx + (rx - t)) / 2, (ry + (ry - t)) / 2
    out = []
    for i in range(n):
        a = math.radians(200 + (140 / max(n - 1, 1)) * i)
        x = cx + r[0] * math.cos(a)
        y = cy + r[1] * math.sin(a)
        out.append(
            f'<g transform="translate({x:.1f},{y:.1f}) rotate(45)">'
            f'<rect x="-1.9" y="-1.9" width="3.8" height="3.8" rx=".5" fill="{color}" opacity=".95"/>'
            f'<rect x="-1.0" y="-1.0" width="2.0" height="2.0" fill="#ffffff"/></g>'
        )
    return "".join(out)

def ring(uid, cx, cy, rx, ry, t, metal, rot=0, stones=0, inlay=None, inlay_w=4, square=False):
    hi, mid, lo, hi2 = METALS[metal]
    gid = f"g{uid}"
    svg = [
      f'<linearGradient id="{gid}" x1="0%" y1="0%" x2="82%" y2="100%">'
      f'<stop offset="0%" stop-color="{hi}"/>'
      f'<stop offset="22%" stop-color="{mid}"/>'
      f'<stop offset="46%" stop-color="{lo}"/>'
      f'<stop offset="70%" stop-color="{mid}"/>'
      f'<stop offset="100%" stop-color="{hi2}"/></linearGradient>',
      f'<g transform="rotate({rot} {cx} {cy})">',
      # corpo da aliança
      f'<path d="{(_annulus_sq if square else _annulus)(cx,cy,rx,ry,t)}" fill="url(#{gid})" fill-rule="evenodd"/>',
      # aresta interna (sombra) e externa (luz)
      (f'<path d="{_rrect_path(cx,cy,rx-t,ry-t)}" fill="none" stroke="{lo}" stroke-opacity=".55" stroke-width="1"/>'
       f'<path d="{_rrect_path(cx,cy,rx,ry)}" fill="none" stroke="{hi}" stroke-opacity=".45" stroke-width=".8"/>'
       if square else
       f'<ellipse cx="{cx}" cy="{cy}" rx="{rx-t}" ry="{ry-t}" fill="none" stroke="{lo}" stroke-opacity=".55" stroke-width="1"/>'
       f'<ellipse cx="{cx}" cy="{cy}" rx="{rx}" ry="{ry}" fill="none" stroke="{hi}" stroke-opacity=".45" stroke-width=".8"/>'),
      # reflexo no quadrante superior esquerdo
      f'<path d="M {cx-rx+t/2},{cy} a {rx-t/2},{ry-t/2} 0 0,1 {rx-t/2},{-(ry-t/2)}" '
      f'fill="none" stroke="{hi}" stroke-opacity=".7" stroke-width="{t*0.34:.1f}" stroke-linecap="round"/>',
    ]
    if inlay:
        ihi, imid, ilo, _ = METALS[inlay]
        mrx = (rx + rx - t) / 2
        mry = (ry + ry - t) / 2
        svg.append(
          f'<ellipse cx="{cx}" cy="{cy}" rx="{mrx:.1f}" ry="{mry:.1f}" fill="none" '
          f'stroke="{imid}" stroke-width="{inlay_w}"/>'
          f'<path d="M {cx-mrx:.1f},{cy} a {mrx:.1f},{mry:.1f} 0 0,1 {mrx:.1f},{-mry:.1f}" '
          f'fill="none" stroke="{ihi}" stroke-opacity=".5" stroke-width="1"/>')
    if stones:
        svg.append(_stones(cx, cy, rx, ry, t, stones, uid))
    svg.append('</g>')
    return "".join(svg)

def pair(uid, metal_a, metal_b, stones_a=0, stones_b=0, inlay=None, inlay_w=4, w=240, h=150, thumb=False, square=False, bulk=1.0):
    """Par de alianças: a de trás menor e mais inclinada, a da frente destacada."""
    if thumb:
        shadow = f'<ellipse cx="{w/2:.0f}" cy="{h-13}" rx="{w*0.34:.0f}" ry="6" fill="#000" opacity=".14"/>'
        back  = ring(f"{uid}a", w*0.38, h*0.46, w*0.27, h*0.29, w*0.062*bulk, metal_a, rot=-14, stones=stones_a, inlay=inlay, inlay_w=inlay_w*0.7, square=square)
        front = ring(f"{uid}b", w*0.62, h*0.52, w*0.29, h*0.31, w*0.068*bulk, metal_b, rot=8,  stones=stones_b, inlay=inlay, inlay_w=inlay_w*0.7, square=square)
    else:
        shadow = f'<ellipse cx="120" cy="{h-16}" rx="86" ry="9" fill="#000" opacity=".16"/>'
        back  = ring(f"{uid}a", 92, 70, 44, 48, 10*bulk, metal_a, rot=-14, stones=stones_a, inlay=inlay, inlay_w=inlay_w, square=square)
        front = ring(f"{uid}b", 152, 78, 48, 52, 11*bulk, metal_b, rot=8,  stones=stones_b, inlay=inlay, inlay_w=inlay_w, square=square)
    return (f'<svg viewBox="0 0 {w} {h}" xmlns="http://www.w3.org/2000/svg" '
            f'role="img" aria-label="Par de alianças" preserveAspectRatio="xMidYMid meet">'
            f'<defs></defs>{shadow}{back}{front}</svg>')

VARIANTS = {
  "classica":    dict(metal_a="ouro",    metal_b="ouro"),
  "diamond":     dict(metal_a="branco",  metal_b="branco", stones_b=7),
  "premium":     dict(metal_a="rose",    metal_b="rose",   stones_b=5),
  "urbana":      dict(metal_a="black",   metal_b="black",   inlay="ouro", inlay_w=3),
  "personaliz":  dict(metal_a="ouro",    metal_b="branco"),
  "cravejada":   dict(metal_a="ouro",    metal_b="ouro",   stones_b=9),
  "bicolor":     dict(metal_a="branco",  metal_b="ouro"),
  "rose":        dict(metal_a="rose",    metal_b="rose"),
  "branco":      dict(metal_a="branco",  metal_b="branco"),
  "ouro":        dict(metal_a="ouro",    metal_b="ouro"),
  "black":       dict(metal_a="black",   metal_b="black"),
  "diamantada":  dict(metal_a="ouro",    metal_b="ouro",   stones_b=6),
  "fosca":       dict(metal_a="fosco",   metal_b="fosco"),
  "trabalhada":  dict(metal_a="ouro",    metal_b="ouro",   inlay="fosco", inlay_w=3.5),
  "conforto":    dict(metal_a="ouro",    metal_b="ouro",   bulk=1.45),
  "quadrado":    dict(metal_a="ouro",    metal_b="ouro",   square=True),
  "tricolor":    dict(metal_a="rose",    metal_b="branco"),
}

def svg(variant, uid, w=240, h=150):
    return pair(uid, w=w, h=h, **VARIANTS[variant])

def thumb(variant, uid, size=132):
    """Miniatura quadrada — para linha de carrinho e listagem densa."""
    return pair(uid, w=size, h=size, thumb=True, **VARIANTS[variant])

if __name__ == "__main__":
    cards = "".join(
      f'<figure><div class="ph">{svg(v, i)}</div><figcaption>{v}</figcaption></figure>'
      for i, v in enumerate(VARIANTS))
    open("_teste-alianças.html", "w").write(f"""<!doctype html><meta charset="utf-8">
<title>Teste · placeholders de aliança</title>
<style>
 body{{margin:0;padding:28px;background:#f3f2ef;font:14px/1.4 system-ui}}
 .grid{{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;max-width:1100px;margin:0 auto}}
 figure{{margin:0}}
 .ph{{border-radius:12px;overflow:hidden;background:linear-gradient(160deg,#fbf7ef,#ece3d4)}}
 .ph.dk{{background:linear-gradient(160deg,#12211f,#04292c)}}
 figcaption{{margin-top:6px;font-size:12px;color:#666}}
 svg{{display:block;width:100%;height:auto}}
 h2{{max-width:1100px;margin:0 auto 14px;font-weight:600}}
</style>
<h2>Sobre superfície clara</h2><div class="grid">{cards}</div>
<h2 style="margin-top:32px">Sobre superfície esmeralda</h2>
<div class="grid">{cards.replace('class="ph"', 'class="ph dk"')}</div>""")
    print("teste gerado")
