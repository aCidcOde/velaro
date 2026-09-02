---
name: tailwind
description: Provide Tailwind CSS v4 guidance for this project using CSS-first config, @theme tokens, dark mode, and utility spacing.
---

Use this skill when you need Tailwind CSS v4 details or examples aligned with this repository. Prefer CSS-first configuration and token-driven utilities.

## Core Rules

- Use CSS-first configuration with `@import "tailwindcss";` in the main CSS entry file.
- Define design tokens in a top-level `@theme` block so Tailwind generates utilities from them.
- Use `dark:` variants for dark mode; customize the dark variant only when a data-attribute toggle is required.
- Prefer `gap-*`, `gap-x-*`, and `gap-y-*` utilities for spacing in lists and grids.
- Nunca hardcodar cor sem variante `dark:` — ver `docs/guidelines/111-agente-darkmode.md`.

## Examples

### CSS-first config and tokens

```css
@import "tailwindcss";

@theme {
  --font-display: "Satoshi", "sans-serif";
  --breakpoint-3xl: 1920px;
  --color-avocado-500: oklch(0.84 0.18 117.33);
}
```

### Data-attribute dark mode

```css
@import "tailwindcss";

@custom-variant dark (&:where([data-theme=dark], [data-theme=dark] *));
```

> Este projeto persiste a preferência em `users.theme_preference`.

### Utility usage

```html
<div class="grid grid-cols-2 gap-4">
  <div class="bg-avocado-500 text-white rounded-lg p-4">Card</div>
  <div class="bg-avocado-500 text-white rounded-lg p-4">Card</div>
</div>
```
