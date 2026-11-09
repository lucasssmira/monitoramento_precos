---
name: "Nocturne Emerald & Warm Amber"
description: "Dark-mode-exclusive design system for Brazilian e-commerce price monitoring and real-time price tracking with tabular currency fidelity."
---

## Overview

A dark-mode-first, minimalist design system tailored for high-frequency price tracking across Brazilian retail giants (Amazon, KaBuM!, Mercado Livre, Magalu, Pichau). The interface prioritizes calm visual clarity, rapid scanning of cash/PIX discounts vs. installment prices, and immediate comprehension of target thresholds. The aesthetic unites deep obsidian night surfaces with vivid emerald mint for target hits, warm amber gold for alerts, and crisp monospaced tabular numerals.

## Colors

The palette employs deep obsidian slate foundations layered with midnight slate card surfaces and elevated slate drawers. Accents are strictly semantic: Soft Emerald Mint for savings, PIX prices, and target hits; Warm Amber Gold for target references, radar tracking, and stock return notifications; Soft Indigo for installment credit calculations; and Soft Coral for out-of-stock items.

- **Primary (Emerald Mint):** Represents price drops, PIX cash savings, and target met states.
- **Secondary (Warm Amber):** Represents radar alerts, target price thresholds, and back-in-stock indicators.
- **Tertiary (Soft Indigo):** Represents installment terms and analytics charts.
- **Danger (Soft Coral):** Represents out of stock and price volatility warnings.

```yaml
colors:
  canvas: "#0B0F17"
  surface: "#131B2E"
  surface-elevated: "#1E293B"
  surface-border: "#26334D"
  primary: "#34D399"
  on-primary: "#064E3B"
  primary-glow: "rgba(52, 211, 153, 0.12)"
  amber: "#FBBF24"
  on-amber: "#78350F"
  amber-glow: "rgba(251, 191, 36, 0.12)"
  indigo: "#818CF8"
  on-indigo: "#1E1B4B"
  coral: "#F87171"
  on-coral: "#450A0A"
  coral-glow: "rgba(248, 113, 113, 0.12)"
  text-primary: "#F8FAFC"
  text-secondary: "#94A3B8"
  text-muted: "#64748B"
  light:
    primary: "#059669"
    on-primary: "#1C1C1C"
    surface: "#131B2E"
    on-surface: "#F8FAFC"
  dark:
    primary: "#34D399"
    on-primary: "#064E3B"
    surface: "#131B2E"
    on-surface: "#F8FAFC"
```

## Typography

Typography pairs **Plus Jakarta Sans** for UI hierarchy, navigation labels, and clean metadata with **JetBrains Mono** for all Brazilian Real currency (`R$`), percentages, and timestamps. Tabular numerals ensure clean scanning across price tables and metric comparisons.

```yaml
typography:
  display-lg:
    fontFamily: "Plus Jakarta Sans"
    fontSize: 36px
    fontWeight: 700
    lineHeight: 44px
    letterSpacing: -0.5px
  display-lg-mobile:
    fontFamily: "Plus Jakarta Sans"
    fontSize: 28px
    fontWeight: 700
    lineHeight: 36px
    letterSpacing: -0.25px
  headline-lg:
    fontFamily: "Plus Jakarta Sans"
    fontSize: 28px
    fontWeight: 700
    lineHeight: 36px
    letterSpacing: -0.25px
  headline-lg-mobile:
    fontFamily: "Plus Jakarta Sans"
    fontSize: 22px
    fontWeight: 700
    lineHeight: 28px
  headline-md:
    fontFamily: "Plus Jakarta Sans"
    fontSize: 20px
    fontWeight: 600
    lineHeight: 28px
  title-lg:
    fontFamily: "Plus Jakarta Sans"
    fontSize: 18px
    fontWeight: 600
    lineHeight: 26px
  title-md:
    fontFamily: "Plus Jakarta Sans"
    fontSize: 16px
    fontWeight: 600
    lineHeight: 24px
  body-lg:
    fontFamily: "Plus Jakarta Sans"
    fontSize: 16px
    fontWeight: 400
    lineHeight: 24px
  body-md:
    fontFamily: "Plus Jakarta Sans"
    fontSize: 14px
    fontWeight: 400
    lineHeight: 20px
  body-sm:
    fontFamily: "Plus Jakarta Sans"
    fontSize: 12px
    fontWeight: 400
    lineHeight: 16px
  label-lg:
    fontFamily: "Plus Jakarta Sans"
    fontSize: 14px
    fontWeight: 600
    lineHeight: 20px
  label-md:
    fontFamily: "Plus Jakarta Sans"
    fontSize: 12px
    fontWeight: 600
    lineHeight: 16px
  label-sm:
    fontFamily: "Plus Jakarta Sans"
    fontSize: 11px
    fontWeight: 600
    lineHeight: 14px
  price-xl:
    fontFamily: "JetBrains Mono"
    fontSize: 26px
    fontWeight: 700
    lineHeight: 32px
  price-lg:
    fontFamily: "JetBrains Mono"
    fontSize: 20px
    fontWeight: 700
    lineHeight: 28px
  price-md:
    fontFamily: "JetBrains Mono"
    fontSize: 15px
    fontWeight: 600
    lineHeight: 20px
  price-sm:
    fontFamily: "JetBrains Mono"
    fontSize: 13px
    fontWeight: 500
    lineHeight: 18px
```

## Layout & Spacing

A desktop-first web application layout centered around a 1280px container with generous breathing room. The layout uses a strict 8px base rhythm with 16px to 24px inner card paddings and 24px grid gutters.

```yaml
spacing:
  gutter-xs: 4px
  gutter-sm: 8px
  gutter-md: 16px
  gutter-lg: 24px
  gutter-xl: 32px
  section: 40px
  page-margin: 32px
```

## Elevation & Depth

Surfaces rest on deep `#0B0F17` obsidian slate. Containers and product cards use `#131B2E` Midnight Slate with crisp `1px solid #26334D` borders. Elevated overlays, modals, and slide-overs use `#1E293B` with soft deep shadow (`0 20px 25px -5px rgba(0, 0, 0, 0.5)`) and subtle border rims.

## Shapes

Containers, cards, and modal dialogs feature clean 16px rounded corners (`rounded-2xl` or `rounded-lg`). Action buttons, pills, and filter chips use 8px or pill-rounded 9999px geometry.

```yaml
rounded:
  sm: 6px
  DEFAULT: 8px
  md: 12px
  lg: 16px
  xl: 20px
  full: 9999px
```

## Motion & Micro-interaction

Micro-interactions use quick 150ms transitions for button hover, filter pill activation, and modal fade-in. Drawer slide-overs use 250ms decelerating cubic bezier curves.

```yaml
motion:
  state-change:
    duration: 150ms
    delay: 0ms
    timingFunction: ease-out
  surface-enter:
    duration: 250ms
    delay: 0ms
    timingFunction: cubic-bezier(0.16, 1, 0.3, 1)
  surface-exit:
    duration: 180ms
    delay: 0ms
    timingFunction: cubic-bezier(0.4, 0, 1, 1)
```

## Voice & Content Strategy

Fully localized in Brazilian Portuguese (pt-BR). Tone is precise, helpful, and financial-grade. All currency uses `R$ X.XXX,XX` format with comma for cents and period for thousands. Timestamps are formatted as relative Brazilian Portuguese (`há 14 min`, `há 2 horas`, `12 de Out, 2026`). Buttons use active verbs (`+ Monitorar Produto`, `Iniciar Monitoramento`, `Verificar Agora`, `Ir para a Loja`).

## Components

Component implementations live as individual HTML sticker sheet files in
`components/`. Each file contains the canonical, styled HTML components with
`<!-- COMPONENT: name -->` comment markers. Read each file before use.

- **Badge** → `@components/badge.html`: `badge-numeric`, `badge-dot`
- **Button** → `@components/button.html`: `button-primary`, `button-icon`, `button-with-icon`
- **Card** → `@components/card.html`: `card-elevated`, `card-action`, `card-interactive`, `card-interactive-2`
- **Input Field** → `@components/input-field.html`: `input-field-text`, `input-field-icon`
- **Modal** → `@components/modal.html`: `modal-standard`, `modal-alert`, `modal-drawer`
