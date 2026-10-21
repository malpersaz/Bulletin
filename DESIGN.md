---
# Amed Framework Design Tokens
colors:
  primary:
    base: "#6750A4"
    on: "#FFFFFF"
    container: "#EADDFF"
    onContainer: "#21005D"
  secondary:
    base: "#625B71"
    on: "#FFFFFF"
    container: "#E8DEF8"
    onContainer: "#1D192B"
  surface:
    base: "#FFFBFE"
    on: "#1C1B1F"
    variant: "#E7E0EC"
    onVariant: "#49454F"
    container: "#F3EDF7"
    containerHigh: "#E8DEF8"
  outline:
    base: "#79747E"
    variant: "#CAC4D0"
  error:
    base: "#B3261E"
    on: "#FFFFFF"
    container: "#F9DEDC"
    onContainer: "#410E0B"
  blue:
    base: "#0B57D0"
    on: "#FFFFFF"
    container: "#DCE8FF"
    onContainer: "#001D3A"


typography:
  families:
    brand: "'Marcellus', serif" # Site title / Hero
    headline: "'Manrope', 'Roboto', sans-serif" # Page headers
    body: "'Roboto', 'Inter', sans-serif" # Standard UI
  scales:
    display: "32px"
    headline: "22px"
    title: "18px"
    body: "16px"
    label: "12px"

spacing:
  unit: 4px
  scales:
    xs: 4px
    sm: 8px
    md: 16px
    lg: 24px
    xl: 32px

radii:
  none: 0px
  xs: 4px
  sm: 8px
  md: 12px
  lg: 20px
  full: 999px

elevation:
  level1: "0px 1px 2px rgba(0, 0, 0, 0.3), 0px 1px 3px 1px rgba(0, 0, 0, 0.15)"
  level2: "0px 2px 6px 2px rgba(0, 0, 0, 0.15), 0px 1px 2px rgba(0, 0, 0, 0.3)"

breakpoints:
  mobile: 0px
  tablet: 768px
  desktop: 1024px
  admin_fixed_phone: 412px # Standard Admin Simulator Width
---

# Amed Framework Design Rationale

## Core Philosophy
The Amed Framework balances **Material Design 3 (MD3)** utility for internal tools with **Scandinavian Restraint** for public themes.

## Admin Panel Strategy (Phone-First)
The entire administrative interface is built as a **phone-only product**.
- **Fixed Viewport**: Admin UI is optimized for a 412px wide shell (simulated on desktop).
- **Touch-First**: All interactive elements (buttons, chips, inputs) must have a minimum touch target height of **48px**.
- **MD3 Compliance**: Use standardized color roles and elevations. Prefer cards (`md` or `lg` radii) for grouping related content.
- **Micro-Animations**: Use smooth transitions (200ms ease) for state changes (hover, active, sheet opening).

### Reference Implementations (Admin)
- **Primary List Template**: [`mod_news/list.twig`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/admin/mod_news/list.twig)
  - Uses `app-scroll-frame` for smooth scrolling.
  - Implements Pull-to-Refresh and Infinite Scroll patterns.
- **Standard Toolbar**: [`.standard-toolbar`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/admin/assets/css/style.css)
  - Layout: Symmetric distribution of control/action items (such as edit, delete, gallery, etc.) with a central start menu button.
  - Behavior: Actions are disabled (`opacity: 0.3` and `pointer-events: none`) until an item/card is selected.
- **Primary Floating Action Button (FAB)**: [`.main-fab`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/admin/assets/css/style.css)
  - Position: Fixed at `bottom: 80px` (or `calc(80px + env(safe-area-inset-bottom))`) and `right: 18px` relative to the mobile wrapper / viewport.
  - Color: Background is `var(--md-sys-color-blue, #0B57D0)` (blue), icon/foreground is `#FFFFFF` (white), with a shadow of `rgba(11, 87, 208, 0.3)` in default state and `rgba(11, 87, 208, 0.4)` on hover.
  - Size: Width and height of `58x58px` with a border radius of `20px` (`radii.lg`) to ensure touch target compliance (min 48px).
  - Role: Dedicated exclusively to primary creative/addition actions (e.g., adding a new news post, page, product). This separation prevents crowding and overlap on narrow mobile viewports.
- **Content Cards**: [`.m3-card.content-card`](file:///home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/templates/admin/assets/js/mod_news.js)
  - Layout: `80x80px` fixed thumbnail on the left with 8px border-radius, `flex-shrink: 0`. Title, action menu button, and high-contrast MD3 solid chips (Active green `#2E7D32`, Draft blue `#0B57D0` - 18px height) on the right side.
  - Metadata & Divider: Bottom metadata row containing language code, compact date, xwedî/nivîskar name, and category pushed to the right (`margin-left: auto`) with a `bi-diagram-2` icon. The row is placed directly under the `.card-content-layout` column spanning the full width, separated by a top border divider (`border-top: 1px solid var(--md-sys-color-outline-variant)`) with 8px top padding and 12px top margin.
  - Tokens: Use `var(--md-sys-color-surface)` for background, `var(--md-sys-elevation-1)` for shadow, and `var(--md-sys-color-outline-variant)` for dividers.

## Public Theme Strategy (Scandinavian)
Public themes (like `templates/furniture/`) should prioritize:
- **Whitespace**: Architectural spacing using the `lg` and `xl` scales.
- **Typography**: Viewport-clamped scaling for fluid headers.
- **Elegance**: High-quality photography combined with minimal UI furniture.
- **Color**: Neutral bases with subtle brand accents.
