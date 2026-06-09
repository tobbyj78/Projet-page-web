---
name: L'Éclipse Design System
colors:
  surface: "#161412"
  surface-overlay: "#161412f2"
  surface-dim: "#161412"
  surface-bright: "#2a2824"
  surface-container: "#1e1c1a"
  surface-container-high: "#24221f"
  on-surface: "#f4ece0"
  on-surface-secondary: "#c7bdac"
  on-surface-muted: "#8a8175"
  accent: "#d4a574"
  accent-dark: "#b8863f"
  accent-line: "#e8d4a32e"
  border-subtle: "#ffffff0f"
  on-accent: "#161412"
  accent-gradient-1: "#e8d4a3"
  accent-gradient-2: "#d4a574"
  accent-gradient-3: "#b8863f"
  success: "#8bbf8b"
  error: "#c08070"
  error-strong: "#c97a7a"
  delivery-badge: "#82a0c8"
  restaurant-badge: "#82b482"
  light-surface: "#f7f2eb"
  light-on-surface: "#1a100a"
  light-on-surface-secondary: "#3d2b1f"
  light-on-surface-muted: "#7a6553"
  light-accent: "#b06a28"
  light-accent-dark: "#8a4e18"
  light-accent-line: "#b06a2822"
  light-border-subtle: "#00000014"
  light-surface-overlay: "#f7f2ebb3"
typography:
  headline-xl:
    fontFamily: Cormorant Garamond
    fontSize: 4rem
    fontWeight: "300"
    fontStyle: italic
    lineHeight: 1.08
    letterSpacing: -0.01em
  headline-lg:
    fontFamily: Cormorant Garamond
    fontSize: clamp(2.5rem, 4vw, 3.5rem)
    fontWeight: "300"
    fontStyle: italic
    lineHeight: 1.1
    letterSpacing: 0em
  headline-md:
    fontFamily: Cormorant Garamond
    fontSize: clamp(2rem, 5vw, 3rem)
    fontWeight: "300"
    fontStyle: italic
    lineHeight: 1.1
    letterSpacing: 0.04em
  headline-sm:
    fontFamily: Cormorant Garamond
    fontSize: clamp(2rem, 2.6vw, 2.75rem)
    fontWeight: "300"
    lineHeight: 1
  headline-xs:
    fontFamily: Cormorant Garamond
    fontSize: 1.65rem
    fontStyle: italic
    letterSpacing: 0.02em
  title-lg:
    fontFamily: Cormorant Garamond
    fontSize: 1.5rem
    fontWeight: "400"
    fontStyle: italic
    lineHeight: 1.2
  title-md:
    fontFamily: Cormorant Garamond
    fontSize: 1.15rem
    fontWeight: "400"
    fontStyle: italic
    lineHeight: 1.25
  title-sm:
    fontFamily: Cormorant Garamond
    fontSize: 1.05rem
    fontWeight: "400"
    lineHeight: 1.25
  body-lg:
    fontFamily: EB Garamond
    fontSize: 1.16rem
    fontWeight: "400"
    lineHeight: 1.5
  body-md:
    fontFamily: EB Garamond
    fontSize: 1rem
    fontWeight: "400"
    lineHeight: 1.7
  body-sm:
    fontFamily: EB Garamond
    fontSize: 0.9rem
    fontWeight: "400"
    lineHeight: 1.3
  body-xs:
    fontFamily: EB Garamond
    fontSize: 0.85rem
    fontWeight: "400"
    lineHeight: 1.4
  label-caps-xl:
    fontFamily: Cormorant SC
    fontSize: 0.8rem
    fontWeight: "600"
    letterSpacing: 0.38em
    textTransform: uppercase
  label-caps-lg:
    fontFamily: Cormorant SC
    fontSize: 0.75rem
    letterSpacing: 0.32em
    textTransform: uppercase
  label-caps-md:
    fontFamily: Cormorant SC
    fontSize: 0.7rem
    letterSpacing: 0.28em
    textTransform: uppercase
  label-caps-sm:
    fontFamily: Cormorant SC
    fontSize: 0.65rem
    letterSpacing: 0.28em
    textTransform: uppercase
  label-caps-xs:
    fontFamily: Cormorant SC
    fontSize: 0.6rem
    letterSpacing: 0.3em
    textTransform: uppercase
  ui-sm:
    fontFamily: Jost
    fontSize: 0.75rem
    fontWeight: "300"
    letterSpacing: 0.22em
    textTransform: uppercase
  ui-xs:
    fontFamily: Jost
    fontSize: 0.7rem
    fontWeight: "300"
    letterSpacing: 0.24em
    textTransform: uppercase
  ui-2xs:
    fontFamily: Jost
    fontSize: 0.65rem
    fontWeight: "300"
    letterSpacing: 0.2em
    textTransform: uppercase
  ui-3xs:
    fontFamily: Jost
    fontSize: 0.6rem
    fontWeight: "300"
    letterSpacing: 0.15em
    textTransform: uppercase
  ui-body:
    fontFamily: Jost
    fontSize: 0.68rem
    fontWeight: "300"
    letterSpacing: 0.12em
    textTransform: uppercase
  price:
    fontFamily: Cormorant Garamond
    fontSize: 1.5rem
    fontStyle: italic
    fontWeight: "400"
rounded:
  none: 0
  sm: 4px
  md: 12px
  lg: 18px
  xl: 20px
  xxl: 24px
  full: 9999px
spacing:
  unit: 8px
  container-max: 1280px
  container-wide: 1400px
  gutter: clamp(1.5rem, 5vw, 4rem)
  section-gap: 48px
  card-padding: 24px
  page-padding-top: 7rem
  page-padding-top-mobile: 5.5rem
components:
  button-primary:
    backgroundColor: "{colors.accent}"
    textColor: "{colors.on-accent}"
    fontFamily: Jost
    fontSize: 0.72rem
    fontWeight: "400"
    letterSpacing: 0.28em
    textTransform: uppercase
    rounded: "{rounded.full}"
    paddingTop: 16px
    paddingBottom: 16px
    paddingLeft: 24px
    paddingRight: 24px
    boxShadow: "0 4px 20px rgba(212, 165, 116, 0.12)"
  button-primary-hover:
    opacity: 0.88
    transform: "translateY(-1px)"
    boxShadow: "0 8px 28px rgba(212, 165, 116, 0.18)"
  button-outline:
    backgroundColor: transparent
    textColor: "{colors.accent}"
    fontFamily: Jost
    fontSize: 0.7rem
    fontWeight: "400"
    letterSpacing: 0.26em
    textTransform: uppercase
    rounded: "{rounded.full}"
    border: "1px solid {colors.accent}"
    paddingTop: 14px
    paddingBottom: 14px
    paddingLeft: 22px
    paddingRight: 22px
  button-outline-hover:
    backgroundColor: "{colors.accent}"
    textColor: "{colors.on-accent}"
  button-ghost:
    backgroundColor: transparent
    textColor: "{colors.on-surface-muted}"
    fontFamily: Jost
    fontSize: 0.68rem
    fontWeight: "300"
    letterSpacing: 0.12em
    textTransform: uppercase
    rounded: "{rounded.xl}"
    border: "1px solid {colors.accent-line}"
  button-ghost-hover:
    textColor: "{colors.on-surface-secondary}"
    borderColor: "rgba(192, 128, 112, 0.35)"
  button-danger:
    backgroundColor: transparent
    textColor: "{colors.error-strong}"
    rounded: "{rounded.sm}"
    border: "1px solid rgba(201, 122, 122, 0.25)"
  button-danger-hover:
    backgroundColor: "rgba(201, 122, 122, 0.1)"
    borderColor: "{colors.error-strong}"
  stepper:
    border: "1px solid {colors.accent-line}"
    rounded: "{rounded.full}"
    overflow: hidden
    buttonWidth: 36px
    buttonHeight: 36px
    buttonColor: "{colors.on-surface-secondary}"
    buttonHoverColor: "{colors.accent}"
    valueMinWidth: 40px
    valueFontSize: 0.82rem
  card-glass:
    backgroundColor: "rgba(255, 255, 255, 0.012)"
    rounded: "{rounded.xl}"
    border: "1px solid {colors.accent-line}"
    backdropFilter: "blur(10px)"
  card-menu:
    backgroundColor: "{colors.surface}"
    rounded: "{rounded.sm}"
    border: "1px solid {colors.accent-line}"
    imageHeight: 210px
    bodyPadding: 18px 20px 20px
  card-menu-hover:
    borderColor: "{colors.accent}"
    transform: "translateY(-3px)"
    boxShadow: "0 4px 24px rgba(212, 165, 116, 0.15), 0 0 0 1px rgba(212, 165, 116, 0.25)"
  card-dish:
    backgroundColor: "{colors.surface}"
    rounded: "{rounded.sm}"
    border: "1px solid {colors.accent-line}"
    imageHeight: 170px
    bodyPadding: 12px 14px 14px
  card-dish-hover:
    borderColor: "{colors.accent}"
    transform: "translateY(-2px)"
    boxShadow: "0 4px 20px rgba(212, 165, 116, 0.12), 0 0 0 1px rgba(212, 165, 116, 0.22)"
  nav-link:
    fontFamily: Jost
    fontSize: 0.75rem
    fontWeight: "300"
    letterSpacing: 0.22em
    textTransform: uppercase
    textColor: "{colors.on-surface-secondary}"
  nav-link-hover:
    textColor: "{colors.accent}"
  nav-link-underline:
    height: 1px
    backgroundColor: "{colors.accent}"
    transform: "scaleX(0)"
    transformOrigin: left
  nav-link-underline-hover:
    transform: "scaleX(1)"
  input-underline:
    backgroundColor: transparent
    border: none
    borderBottom: "1px solid {colors.on-surface-muted}"
    textColor: "{colors.on-surface}"
    fontFamily: EB Garamond
    fontSize: 1rem
    padding: "0.6rem 0"
  input-underline-focus:
    borderBottomColor: "{colors.accent}"
    outline: none
  input-outlined:
    backgroundColor: "rgba(255, 255, 255, 0.03)"
    border: "1px solid {colors.accent-line}"
    rounded: "{rounded.md}"
    textColor: "{colors.on-surface}"
    fontFamily: EB Garamond
    fontSize: 0.95rem
    padding: "0.75rem 1rem"
  input-outlined-focus:
    borderColor: "{colors.accent}"
    boxShadow: "0 0 0 3px rgba(212, 165, 116, 0.08)"
  fieldset:
    border: "1px solid {colors.accent-line}"
    rounded: "{rounded.lg}"
    padding: 1.5rem
  radio-card:
    border: "1px solid {colors.accent-line}"
    rounded: "{rounded.md}"
  radio-card-checked:
    borderColor: "{colors.accent}"
    backgroundColor: "rgba(212, 165, 116, 0.06)"
    boxShadow: "0 0 0 1px {colors.accent}"
  profile-box:
    backgroundColor: "{colors.accent}"
    textColor: "{colors.on-accent}"
    rounded: "{rounded.full}"
    fontFamily: Jost
    fontSize: 0.78rem
    padding: "7px 10px 7px 16px"
  profile-box-hover:
    boxShadow: "0 0 14px rgba(212, 165, 116, 0.35)"
    opacity: 0.92
  cart-badge:
    backgroundColor: "{colors.accent}"
    textColor: "{colors.on-accent}"
    rounded: "{rounded.full}"
    fontSize: 0.65rem
    fontWeight: "600"
    minWidth: 20px
    height: 20px
  table-header:
    fontFamily: Cormorant SC
    fontSize: 0.65rem
    letterSpacing: 0.28em
    textTransform: uppercase
    textColor: "{colors.on-surface-muted}"
    padding: "14px 20px"
  table-cell:
    textColor: "{colors.on-surface-secondary}"
    fontSize: 0.875rem
    padding: "14px 20px"
    borderBottom: "1px solid {colors.border-subtle}"
  table-header-hover:
    backgroundColor: "rgba(255, 255, 255, 0.018)"
  staff-badge-admin:
    textColor: "{colors.accent}"
    borderColor: "{colors.accent}"
  staff-badge-restaurateur:
    textColor: "{colors.restaurant-badge}"
    borderColor: "{colors.restaurant-badge}"
  staff-badge-livreur:
    textColor: "{colors.delivery-badge}"
    borderColor: "{colors.delivery-badge}"
  staff-badge-client:
    textColor: "{colors.on-surface-muted}"
    borderColor: "{colors.on-surface-muted}"
  staff-topbar:
    height: 64px
    backgroundColor: "{colors.surface-overlay}"
    backdropFilter: "blur(14px)"
    borderBottom: "1px solid {colors.border-subtle}"
  toast-success:
    border: "1px solid rgba(139, 191, 139, 0.35)"
    backgroundColor: "rgba(22, 20, 18, 0.95)"
    textColor: "{colors.success}"
    rounded: "{rounded.full}"
    boxShadow: "0 8px 32px rgba(0, 0, 0, 0.4)"
  toast-error:
    border: "1px solid rgba(192, 128, 112, 0.35)"
    backgroundColor: "rgba(22, 20, 18, 0.95)"
    textColor: "{colors.error}"
    rounded: "{rounded.full}"
    boxShadow: "0 8px 32px rgba(0, 0, 0, 0.4)"
---

## Brand Overview

L'Éclipse is a fine-dining restaurant in Paris — an intimate, luxurious experience where sommelier-level service meets contemporary French haute cuisine. The website evokes **a velvet-lined jewel box**: dark, warm, intimate, with amber-gold light catching polished wood and crystal glass.

The design references **1930s Parisian brasserie typography meets modern editorial luxury** — think the restrained elegance of *Casa Batlló* at dusk, the letterpress menus of Le Grand Véfour, and the typographic craft of a Pierre Chareau interior.

The audience is discerning diners seeking an exceptional evening — the site must feel like opening the door to a hushed, candlelit dining room.

## Design Principles

- **Warm darkness as the default.** The interface lives in deep brown-black (`#161412`), not pure black — like a room lit only by candlelight. Light mode exists but is secondary, like opening curtains at dawn.
- **Gold as the sole accent.** A single warm-amber gradient (`#e8d4a3 → #d4a574 → #b8863f`) drives all interaction — buttons, links, hover states, active elements. No secondary accent color. No blue links.
- **Typography as hierarchy.** Four distinct typefaces create a clear editorial hierarchy: Cormorant Garamond for narrative titles, Cormorant SC for metadata labels, EB Garamond for reading text, Jost for functional UI.
- **Subtle luxe, not flash.** Border-radius is minimal (4px cards, 20px containers, 999px pills). Shadows are warm-amber glows. No harsh transitions — everything fades at 250–300ms.
- **Intimacy through darkness.** The vast majority of the UI is content on dark. The nav is transparent until scroll — it feels like the page breathes. Backdrop filters add depth without weight.

## Colors

The palette is intentionally narrow — a single warm-accent system on a dark earth canvas.

- **Surface (`#161412`):** Deep obsidian-brown canvas. Never pure black — it has warmth. This is the entire page background.
- **On-surface (`#f4ece0`):** Warm ivory for all primary text, headlines, and navigation. Reads like hot-pressed paper under candlelight.
- **On-surface-secondary (`#c7bdac`):** Warm stone for body text, descriptions, and meta. The reading color.
- **On-surface-muted (`#8a8175`):** Faded earth for labels, placeholders, timestamps, and non-interactive metadata.
- **Accent (`#d4a574`):** Amber-gold — the single interactive color. Used for borders on hover, buttons (filled and outline), active states, links, icons, and badges.
- **Accent-line (`rgba(232, 212, 163, 0.18)`):** The ghost of gold for borders, dividers, and card outlines. Present everywhere, assertive nowhere.
- **Success (`#8bbf8b`):** Soft sage — appears only for "paid", "delivered", "saved" states. Never decorative.
- **Error (`#c08070` / `#c97a7a`):** Faded terracotta — for rejected orders, logout actions, and field errors.

### Light Mode

A single alternative: warm limestone (`#f7f2eb`) background with dark ink (`#1a100a`) text. The accent shifts toward rust (`#b06a28`). This is activated by user preference only — it is not the default, and it never overrides the intentional darkness of the experience.

## Typography

Four typefaces, carefully zoned by role.

| Role | Typeface | Usage |
|---|---|---|
| Display / Narrative | **Cormorant Garamond** (300–400, italic) | Page titles, hero text, section headings, prices, logo |
| Metadata / Labels | **Cormorant SC** (400–500, small-caps) | Table headers, section labels, badges, form labels, decorative ornaments |
| Reading / Body | **EB Garamond** (400) | Descriptions, menu items, paragraphs, tooltips, input values |
| Functional / UI | **Jost** (300–400, uppercase, wide tracking) | Navigation, buttons, tabs, filter pills, utility text, captions |

### Key Typographic Rules

- **All UI text is uppercase** (Jost / Cormorant SC). Body text is never uppercase (EB Garamond).
- **Headlines are italic** (Cormorant Garamond). Emphasized words within headlines use the accent gradient via `background-clip: text`.
- **Letter-spacing is wide**: 0.22em–0.38em for labels and UI, 0.04em for headlines.
- **Body text is set at reading size**: 1rem–1.16rem with generous line-height (1.5–1.7).
- **The logo** uses the accent gradient and italic Cormorant Garamond at 1.65rem.

## Rounded Corners

Minimal geometry for a refined, tactile feel:

| Token | Value | Usage |
|---|---|---|
| `sm` | 4px | Menu/dish cards, filter pills |
| `md` | 12px | Input fields, scheduling cards |
| `lg` | 18px | Fieldsets, info cards |
| `xl` | 20px | Cart lists, summary cards, profile cards |
| `xxl` | 24px | Result cards, empty states, modals |
| `full` | 9999px | Buttons, badges, toggles, profile box, steppers |

Cards have sharp (4px) corners to feel like physical stock; containers and modals are rounder (20–24px) to feel like polished objects.

## Spacing

| Token | Value | Usage |
|---|---|---|
| Unit | 8px | Grid base |
| Container max | 1280px | Main content width |
| Container wide | 1400px | Catalogue layout |
| Gutter | `clamp(1.5rem, 5vw, 4rem)` | Page margins |
| Section gap | 48px | Between major sections |
| Card padding | 24px | Standard card interior |
| Page padding top | 7rem (desktop), 5.5rem (mobile) | Below fixed navbar |

## Layout Patterns

- **Navbar**: fixed (72px → 60px on scroll), transparent → `surface-overlay` with blur
- **Footer**: 4-column grid (1.6fr / 1fr / 1.1fr / 1fr), collapses to 2-col at 1024px, 1-col at 600px
- **Catalogue**: 25% sidebar / 75% content grid, collapses to single column at 1024px with floating FAB
- **Validation**: 2-column grid (summary + form), collapses to 1-col at 768px
- **Auth**: Centered card (max 480px), no sidebar
- **Staff**: Topbar (64px) + inner content (max 1400px)

## Component Patterns

### Buttons
All buttons use uppercase Jost with generous letter-spacing. The primary button is filled accent gradient (rounded full). Outline buttons invert on hover. Ghost buttons are subtle until hovered. All buttons have smooth 250–300ms transitions.

### Navigation
The mega-menu dropdown system uses a 5-column grid with image + identity, formulas, category list, dynamic dishes panel, and a CTA column. It activates on hover with a backdrop blur overlay. On mobile (≤1024px), the entire nav collapses to a bottom bar with a single CTA.

### Cards
Menu and dish cards are minimal — a border (`accent-line` at 4px), an image cropped at 210px or 170px, and body text. On hover, the border becomes gold and the card lifts slightly (2–3px) with a warm glow shadow.

### Forms
Two form styles coexist: underlined (connexion/inscription — minimalist, bottom-border only) and outlined (validation/paiement — bordered fields with 12px radius). Radio inputs are rendered as selectable cards with gold check state.

### Tables
Staff tables are dense but airy — generous padding (14px 20px), thin subtle borders, Cormorant SC headers, Jost body. Rows highlight subtly on hover. Status badges use colored borders matching their semantic meaning.

## Component Token Reference

Tokens above use `{reference}` syntax to reference other tokens in the YAML front matter. For example, `button-primary` references `{colors.accent}` and `{colors.on-accent}` — changing the accent color propagates to all buttons.

## Animations

All animations are subtle and purposeful:

| Timing | Context |
|---|---|
| 600–800ms | Page entrance fades (cart, validation, paiement, profil) |
| 450–500ms | List item slides (cart items, order list) — staggered |
| 250–300ms | Hover transitions (border, color, background, transform) |
| 200ms | Field error, toast fade |
| 400ms | Error shake, banner slide, flash messages |
| 350ms | Modal open/close (scale + opacity) |

All animations respect `prefers-reduced-motion: reduce` by collapsing to `0.01ms`.

## Accessibility

- Focus-visible outlines use the accent color with 4px offset
- Sufficient WCAG contrast ratios on all text/background pairs
- `aria-label`, `aria-expanded`, `role` attributes throughout
- Semantic heading hierarchy (h1 → h2 → h3)
- Keyboard-navigable mega-menu dropdowns

## Light Mode Overrides

When `data-theme="light"` is set on `<html>`:
- Background shifts to `#f7f2eb` (warm limestone)
- Hero image switches to `bg_light.webp`
- Text inverts: `#1a100a` primary, `#3d2b1f` secondary
- Accent shifts warmer: `#b06a28`
- Cards become white (`#fff`)
- All backdrop filters remain but backgrounds become lighter
- The glass effect uses `rgba(255, 250, 244, 0.7)` instead of dark overlays
- Wave divider images swap from dark to light variants
