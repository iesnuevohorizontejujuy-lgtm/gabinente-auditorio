---
name: Institutional Campus Space Booking
colors:
  surface: '#f8f9ff'
  surface-dim: '#cbdbf5'
  surface-bright: '#f8f9ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#eff4ff'
  surface-container: '#e5eeff'
  surface-container-high: '#dce9ff'
  surface-container-highest: '#d3e4fe'
  on-surface: '#0b1c30'
  on-surface-variant: '#444651'
  inverse-surface: '#213145'
  inverse-on-surface: '#eaf1ff'
  outline: '#757682'
  outline-variant: '#c5c5d3'
  surface-tint: '#4059aa'
  primary: '#00236f'
  on-primary: '#ffffff'
  primary-container: '#1e3a8a'
  on-primary-container: '#90a8ff'
  inverse-primary: '#b6c4ff'
  secondary: '#0051d5'
  on-secondary: '#ffffff'
  secondary-container: '#316bf3'
  on-secondary-container: '#fefcff'
  tertiary: '#002e41'
  on-tertiary: '#ffffff'
  tertiary-container: '#00455f'
  on-tertiary-container: '#2eb7f2'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#dce1ff'
  primary-fixed-dim: '#b6c4ff'
  on-primary-fixed: '#00164e'
  on-primary-fixed-variant: '#264191'
  secondary-fixed: '#dbe1ff'
  secondary-fixed-dim: '#b4c5ff'
  on-secondary-fixed: '#00174b'
  on-secondary-fixed-variant: '#003ea8'
  tertiary-fixed: '#c4e7ff'
  tertiary-fixed-dim: '#7bd0ff'
  on-tertiary-fixed: '#001e2c'
  on-tertiary-fixed-variant: '#004c69'
  background: '#f8f9ff'
  on-background: '#0b1c30'
  surface-variant: '#d3e4fe'
typography:
  display-lg:
    fontFamily: Inter
    fontSize: 2.25rem
    fontWeight: '700'
    lineHeight: 2.75rem
    letterSpacing: -0.025em
  headline-lg:
    fontFamily: Inter
    fontSize: 1.75rem
    fontWeight: '600'
    lineHeight: 2.25rem
    letterSpacing: -0.02em
  headline-lg-mobile:
    fontFamily: Inter
    fontSize: 1.375rem
    fontWeight: '600'
    lineHeight: 1.75rem
    letterSpacing: -0.015em
  headline-md:
    fontFamily: Inter
    fontSize: 1.25rem
    fontWeight: '600'
    lineHeight: 1.75rem
    letterSpacing: -0.015em
  headline-sm:
    fontFamily: Inter
    fontSize: 1.125rem
    fontWeight: '600'
    lineHeight: 1.5rem
    letterSpacing: -0.01em
  body-lg:
    fontFamily: Inter
    fontSize: 1rem
    fontWeight: '400'
    lineHeight: 1.5rem
  body-md:
    fontFamily: Inter
    fontSize: 0.875rem
    fontWeight: '400'
    lineHeight: 1.375rem
  body-sm:
    fontFamily: Inter
    fontSize: 0.75rem
    fontWeight: '400'
    lineHeight: 1.125rem
  label-lg:
    fontFamily: Inter
    fontSize: 0.875rem
    fontWeight: '600'
    lineHeight: 1.25rem
  label-md:
    fontFamily: Inter
    fontSize: 0.8125rem
    fontWeight: '500'
    lineHeight: 1.125rem
  label-sm:
    fontFamily: Inter
    fontSize: 0.6875rem
    fontWeight: '600'
    lineHeight: 1rem
    letterSpacing: 0.025em
  code-mono:
    fontFamily: Inter
    fontSize: 0.8125rem
    fontWeight: '500'
    lineHeight: 1.25rem
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  space-2xs: 0.25rem
  space-xs: 0.5rem
  space-sm: 0.75rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2rem
  space-2xl: 3rem
  container-max: 80rem
  gutter-mobile: 1rem
  gutter-desktop: 1.5rem
---

## Brand & Style

This design system establishes an institutional, accessible, and structured environment engineered specifically for educational campus facilities management. It serves academic faculty, laboratory coordinators, administrative heads, and student leadership booking critical communal assets such as Computer Laboratories (Gabinetes de Informática), Lecture Auditoriums, and Media/Streaming Suites.

The design movement balances Modern Institutional Governance with refined Contemporary SaaS utility—inspired by cleanly defined utility components (shadcn/ui aesthetic). The emotional core communicates:
- **Operational Reliability:** Predictable interactions, structured hierarchies, and zero ambiguity in asset scheduling.
- **Academic Serenity:** Crisp white surfaces, calming slate-neutral foundations, and authoritative navy anchors that prioritize legibility over decoration.
- **Effortless Clarity:** Fast scannability of reservation states (Pending, Approved, Rejected, Cancelled) via high-legibility badges and semantic iconography.
- **Accessible Rigor:** Standards-compliant contrast ratios, strict 24-hour timeline visuals, and localized date presentation (DD/MM/YYYY) tailored to Latin American and European academic administrations.

## Colors

The color palette is built upon a commanding deep academic navy, supported by functional blue highlights, clean slate-neutral scales, and unmistakable status indicators.

### Key Roles
- **Primary Navy (`#1E3A8A`):** Core institutional anchor used for top-level navigation, primary visual identity, table headers, and commanding actions.
- **Secondary Interactive Blue (`#2563EB`):** High-efficiency action color used for standard interactive elements, focal buttons, active tab indicators, and keyboard focus rings.
- **Tertiary Accent / Sky (`#38BDF8`):** Restrained contextual accents, timeline guides, and subtle highlight borders in data visualizations or time slots.
- **Neutral Slate (`#64748B` / Range `#0F172A` to `#F8FAFC`):** Balanced cool greys providing high-contrast textual clarity and crisp structural borders without harsh black tones.

### Surface Hierarchy
- **Canvas Base:** `#F8FAFC` (Slate-50) – ultra-light grey backdrop reducing eye strain during prolonged administrative shifts.
- **Sub-surface / Wells:** `#F1F5F9` (Slate-100) – used for muted panels, filter toolbars, and inactive track backgrounds.
- **Component Elevated Surface:** `#FFFFFF` (Pure White) – high-clarity cards, modular dialogs, and elevated table containers.
- **Structural Dividers:** `#E2E8F0` (Slate-200) – structural, razor-sharp 1px border lines.

### Semantic Reservation States
- **Approved (`#10B981`):** Deep Emerald base (`#047857` text on `#ECFDF5` background) denoting confirmed reservations.
- **Pending (`#F59E0B`):** Warm Amber (`#B45309` text on `#FFFBEB` background) indicating requests awaiting review.
- **Rejected (`#EF4444`):** Soft Crimson (`#B91C1C` text on `#FEF2F2` background) signaling declined resource requests.
- **Cancelled (`#6B7280`):** Muted Neutral Grey (`#374151` text on `#F3F4F6` background) for expired or withdrawn bookings.

## Typography

The design system relies on `Inter` across all headings, body narrative, labels, and numerical metrics to ensure uncompromising clarity, high x-height legibility, and tabular number alignment.

### Formatting & Localization Rules
- **Calendar Formats:** Strictly formatted as `DD/MM/YYYY` (e.g., `24/10/2025`). Avoid ambiguous slash notation where day and month could invert.
- **Clock Convention:** Strict 24-hour presentation (e.g., `08:00`, `14:30`, `21:15`) to eliminate academic ambiguity between morning and evening shifts.
- **Tabular Figures:** All calendar day pickers, time logs, room numbers, and occupancy metrics (`45/50 puestos`) must enable font feature `'tnum' 1` (`font-variant-numeric: tabular-nums`) to prevent horizontal jitter during filtering or live updates.

## Layout & Spacing

The layout is built on a 12-column fluid grid system pinned to a maximum container boundary of `80rem` (1280px) on wide displays, ensuring wide monitors retain comfortable reading distances for dense reservation data.

### Breakpoints & Adaptations
- **Mobile (< 768px):** 4-column layout with `1rem` margins and `0.75rem` gutters. Data tables gracefully reflow into stacked modular appointment cards with status indicators pinned to the top-right corner.
- **Tablet (768px - 1024px):** 8-column layout with `1.25rem` margins. The primary navigation shifts into a collapsible side-sheet.
- **Desktop (> 1024px):** 12-column layout with a fixed `16rem` (256px) left navigation rail and `1.5rem` gutters across the main operational viewport.

### Rhythmic Padding Standards
- Card internal padding: `1.25rem` (20px) on mobile, `1.5rem` (24px) on desktop.
- Form field stacks: uniform `1rem` separation between input groups with `0.375rem` gap between label and control.
- Table cell padding: `0.75rem` vertical and `1rem` horizontal, preserving compact spatial efficiency for busy administrative rosters.

## Elevation & Depth

Visual depth is achieved through crisp 1px borders combined with low-diffusion, tinted ambient drop shadows. This prevents heavy, dated drop shadows and maintains an authentic modern workspace feel.

### Elevation Hierarchy
- **Level 0 (Flat Canvas):** `#F8FAFC` background without shadow or outline.
- **Level 1 (Card & Content Surface):** `#FFFFFF` surface with a crisp `1px solid #E2E8F0` border and subtle shadow: `0 1px 3px 0 rgba(15, 23, 42, 0.05), 0 1px 2px -1px rgba(15, 23, 42, 0.05)` (`shadow-sm`).
- **Level 2 (Hovered Cards & Interactive Panels):** Elevates via border color shift to `#CBD5E1` and ambient shadow: `0 4px 6px -1px rgba(15, 23, 42, 0.07), 0 2px 4px -2px rgba(15, 23, 42, 0.07)` (`shadow-md`).
- **Level 3 (Modals, Popovers & Time Dropdowns):** Floating overlays backed by `1px solid #E2E8F0` with prominent ambient blur: `0 10px 15px -3px rgba(15, 23, 42, 0.08), 0 4px 6px -4px rgba(15, 23, 42, 0.04)` (`shadow-lg`). Modal backdrops utilize `rgba(15, 23, 42, 0.45)` with a backdrop blur of `4px`.

## Shapes

The design system maintains a balanced, disciplined architectural aesthetic using Level 1 soft curvature (`rounded-md` standard). This conveys structural seriousness while remaining approachable and clean.

### Corner Radii
- **Standard Controls (Buttons, Inputs, Selects, Dropdowns):** `0.375rem` (6px) for an accurate shadcn/ui tactile feel.
- **Cards, Panels, Modals, and Tables:** `0.5rem` (8px) base curvature (`rounded-lg`).
- **Status Badges, Filter Pills, and Avatar Containers:** Full pill curvature (`9999px` / `rounded-full`) to immediately contrast status markers against rectangular layout blocks.
- **Focus Indicators:** Matched radius with a clean `2px` offset outline in `#2563EB`.

## Components

### Buttons
- **Primary:** Background `#1E3A8A`, text `#FFFFFF`, hover `#172554`. Height `2.5rem` (40px) with `px-4`, font-weight `500`. Active click shifts scale to `0.99`.
- **Secondary / Action:** Background `#2563EB`, text `#FFFFFF`, hover `#1D4ED8`.
- **Outline / Neutral:** Background `#FFFFFF`, border `1px solid #E2E8F0`, text `#1E293B`, hover `#F8FAFC`.
- **Ghost:** Transparent background, text `#475569`, hover `#F1F5F9`.
- **Destructive:** Background `#EF4444`, text `#FFFFFF`, hover `#DC2626`.

### Badges & Status Chips
Badges use pill contours (`rounded-full`), `px-2.5`, `py-0.5`, with an embedded 12px status icon:
- **Aprobada:** Background `#ECFDF5`, text `#065F46`, border `1px solid #A7F3D0`, icon: checkmark.
- **Pendiente:** Background `#FFFBEB`, text `#92400E`, border `1px solid #FDE68A`, icon: clock.
- **Rechazada:** Background `#FEF2F2`, text `#991B1B`, border `1px solid #FECACA`, icon: circle-slash.
- **Cancelada:** Background `#F3F4F6`, text `#374151`, border `1px solid #E5E7EB`, icon: x-circle.

### Input Fields & Selects
- Base: `#FFFFFF` surface, border `1px solid #CBD5E1`, text `#0F172A`, placeholder `#94A3B8`. Height `2.5rem` (40px), radius `0.375rem`.
- Focus State: Border color `#2563EB` with `box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15)`.
- Error State: Border color `#EF4444`, focus ring `#EF4444` at 15% opacity, helper message in `#DC2626`.

### Space Availability & Reservation Cards
- Container: White surface with `1px solid #E2E8F0`, `shadow-sm`, and inner padding of `1.25rem`.
- Header: Room badge (e.g., "Gabinete A1", "Auditorio Principal") in bold headline typography alongside capacity indicator chips (`40 PCs`, `Proyector 4K`, `Micrófonos`).
- Content Area: Includes resource tags, current status badge, and reserved time slots formatted in 24-hour style (e.g., `10:00 - 12:00`).

### Data Tables (Desktop) & Responsive Cards (Mobile)
- **Desktop Table:** Crisp `#FFFFFF` container with rounded corners and border. Header row in `#F8FAFC` with uppercase `#64748B` labels (`0.75rem`, font-weight `600`). Table borders utilize `#E2E8F0`. Hover row state: `#F8FAFC`.
- **Mobile Cards:** When viewport drops below 768px, rows transform into discrete white cards displaying space name, applicant, formatted date (`DD/MM/YYYY`), 24h schedule (`14:00 - 16:30`), and floating state badge.

### Time Slot Picker & Schedule Grid
- Calendar timeline displaying vertical or horizontal 30-minute block divisions.
- Occupied slots render as light-tinted blocks styled according to status (e.g., green-tinted for Approved reservations), showing booking title and owner.
- Free slots render with dashed borders in `#E2E8F0` and subtle `#F8FAFC` hover fill to prompt selection.