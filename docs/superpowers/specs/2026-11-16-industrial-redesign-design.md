# Industrial template redesign (sub-project A) — design

Date: 2026-11-16 · Status: approved in chat by the owner

## Why

The owner rejects the current previews: they read like a document, not a company website.
Seen on the demo site (`acme`, industrial template):

- Services are text rows, "about" is text only, certifications are a table.
- Projects without photos render as empty grey boxes.
- The hero without a photo is a flat colour block.
- AI image fill covers only hero, about, gallery, projects and products.
- No icons, hover states or motion; large empty areas.

Goal: a prospect opening the preview on their phone believes it is a real, finished site for
their company.

## Scope

Work is split into three sub-projects, each with its own spec and plan:

- **A (this spec)** — redesign the templates, industrial first, and give every section an
  image slot. The other three templates follow once the owner approves industrial from
  screenshots.
- **B** — distribute a site's media library across sections automatically, real company
  photos first.
- **C** — import content, logo and photos from a Facebook / Instagram page
  (Apify API, with a browser bookmarklet as fallback).

Out of scope for A: smart image-to-section matching (B), social import (C), the editorial,
boutique and bold templates.

## Design rules (from CLAUDE.md, unchanged)

No grid/dot pattern backgrounds, no decorative lines or bars beside headings, no stacked
full-viewport sections, no gradient blobs, no glassmorphism, no emoji icons, no identical
3-card row repeated in every section. Colours only from the `--c-*` custom properties.
Pages stay light on mobile: lazy images, constrained sizes, vanilla JS only.

## Industrial template, section by section

| Section | New layout |
|---|---|
| Masthead | Utility strip + navbar kept; navbar gains a shadow once the page is scrolled. |
| Hero | Full-width photo under a brand-colour overlay, limited height (never full viewport). Company facts become a strip of cards overlapping the hero's bottom edge. Without a photo: solid brand colour, same layout. |
| About | Two overlapping photos with a "since {year}" badge, beside the text and checklist. Without photos: text and checklist in two columns. |
| Services | Photo cards with a line icon per service. Without photos: icon cards. `variant = list` keeps a compact row layout with icons. |
| Stats | Background photo under a dark overlay; numbers count up when scrolled into view. Without photo: solid dark band. |
| Projects | Mosaic: one large project plus smaller tiles, title and location over the photo. On the projects page: uniform grid with a category filter. Without a photo: a brand-tinted tile carrying the category and title — never an empty box. |
| Certifications | Cards with a seal icon, certificate name and issuer. |
| Clients | Logo grid; an entry without a logo is a monogram tile. |
| Testimonials | Large quotes in a swipeable row, author shown with an initial (no fake portraits). |
| FAQ | Accordion beside a "still have a question?" card with a WhatsApp button. |
| CTA | Background photo under a brand-colour overlay. Without photo: solid secondary colour. |
| Contact | Detail cards plus a form (name, service, message) that opens WhatsApp with the message prefilled; counted as a WhatsApp click. |
| Map | Unchanged embed, rounded inside the wrap. |
| Gallery | Even grid with a lightbox. |
| Products | Card with photo, price and an "order on WhatsApp" button naming the product. |
| Menu | Kept, restyled. |
| Booking | Panel with a service select + WhatsApp button; option chips kept (tests rely on `booking_option`). |
| Inner page header | Banner using the page's first available photo, with breadcrumb. Without photo: solid band. |
| Footer | Four columns: brand + short about text, pages, services, contact + hours. |

Motion (all disabled under `prefers-reduced-motion`): reveal on scroll, image zoom on hover,
stat count-up, masthead shadow.

## Data changes

No new tables or columns. New keys inside `sections.content`:

| Section | New shared key | Meaning |
|---|---|---|
| about | `image2` | second, smaller photo |
| stats | `image` | background photo |
| cta | `image` | background photo |
| faq | `image` | photo above the side card |
| booking | `image` | side photo |
| services items | `icon` | key from a fixed icon set (field already exists, unused) |

- `SectionFields::definition()` gains these fields so they are editable in the admin.
- `SectionSchema`: the AI picks `icon` per service from the fixed set and writes an
  `image_query` for every section that now has an image slot, and per service.
- `ContentGenerator::fillImages()` fills the new slots from stock photos (only when empty).
- The other three templates ignore the new keys until their own redesign.

Icon set (inline SVG, added to `x-preview.icon`): building, crane, helmet, road, tools,
blueprint, shield, flame, truck, box, stethoscope, tooth, heart, scissors, sparkle, utensils,
coffee, cart, tag, home, key, chart, users, clock, wrench, bolt, drop, leaf, phone-support,
star. Unknown or missing key falls back to a neutral icon.

## Behaviour (resources/js/preview.js, shared by all templates)

- `[data-reveal]` → class `is-in` when intersecting (once).
- `[data-count]` → count up from 0 to the value when intersecting.
- `[data-lightbox]` gallery links → overlay with next/previous/escape.
- `[data-filter]` chips → show/hide `[data-category]` items.
- `[data-wa-form]` → builds a wa.me link from the fields and opens it; sends a tracked
  WhatsApp click through the existing `data-track` mechanism.
- Masthead gets `is-scrolled` after 8px of scroll.

Everything degrades without JS: content is visible, links work.

## Testing

- Existing `TemplatesTest` and `IndustrialTemplateTest` stay green (hooks they assert on are kept:
  `nav__sub`, `crumbs`, `page-head`, `page-head__title`, `booking_option`, `data-masthead`,
  `data-nav-toggle`, `data-sub-toggle`, `wa-float`).
- New tests: new fields round-trip through `SectionFields`; AI schema exposes `icon` and the new
  `image_query` fields; `fillImages` fills new slots only when empty; industrial renders with and
  without photos and never emits an empty visual box; contact form and product order buttons carry
  tracking attributes.
- Visual check: full-page screenshots of the demo site, desktop and mobile, Arabic and English,
  with stock photos loaded locally.
