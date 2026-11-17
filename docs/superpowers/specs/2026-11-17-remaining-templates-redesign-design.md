# Editorial, boutique and bold redesign — design

Date: 2026-11-17 · Status: owner asked to apply the industrial approach to the other three templates
Builds on: `2026-11-16-industrial-redesign-design.md` (same rules, same data, same JS hooks)

## Goal

Each template must read as a finished company website, photo-led, with no "AI look", and the four
templates must differ in **layout and structure**, not only colour and font.

## Shared ground (all three)

- Same content contract as industrial, including the slots added for it: `about.image2`,
  `stats/cta/faq/booking.image`, service `icon`.
- Same behaviour hooks from `preview.js`: `data-reveal`, `data-count`, `data-lightbox` in
  `data-gallery`, `data-filter-scope`, `data-wa-form`, `data-carousel` (slider), masthead `is-scrolled`.
- The WhatsApp enquiry form becomes one shared partial (`preview.partials.contact-form`) used by all
  four templates; each template styles it.
- The generic slider CSS (`.section-slider`) moves to `preview-base.css`; templates restyle buttons.
- No section ever renders an empty grey box; every section has a designed no-photo state.
- Same tracking labels as industrial (`service`, `product_order`, `contact_form`, `faq`, `cta`, `hero`…).

## Editorial — real estate, consulting, offices, premium brands

Magazine feature. Serif display (Amiri), lots of white, thin rules used as table/list separators only,
asymmetric 5/7 splits, captions under photos, numbered entries (01, 02…).

| Section | Layout |
|---|---|
| Masthead | Name centred, language + CTA at the sides, links on a row underneath (kept). |
| Hero | Huge serif headline over two columns; a tall captioned photo; a facts line under the headline (since, area, phone). No photo: headline alone at full width. |
| About | Large drop-in first paragraph beside a tall photo; second photo offset below; points as a numbered list. |
| Services | Index: numbered rows with title and description; on hover/focus the row's photo appears beside the list (desktop). Phones: rows with a thumbnail. |
| Stats | One row of large serif numerals separated by vertical rules, on paper. Photo slot: a wide photo above the row. |
| Projects | Home: alternating feature spreads (photo 7 / text 5, then swapped). Projects page: two-column grid with captions + category filter. |
| Gallery | Asymmetric spread: one tall photo and a staggered column of smaller ones; lightbox. |
| Testimonials | One large pull quote in serif with the author; others below as two smaller quotes. |
| Clients / certifications | Typeset lists in columns (names in serif small caps style, issuer in muted text). |
| FAQ | Two columns: questions as a numbered accordion, side photo with the WhatsApp card. |
| CTA | Full-width photo with a paper card holding the headline and buttons. No photo: centred serif headline on surface colour. |
| Contact | Address set like a letterhead beside the form. |
| Products / menu / booking | Catalogue cards with serif names; menu as a two-column price list; booking as a quiet panel beside a photo. |
| Footer | Very large company name set as a wordmark, then columns. |

## Boutique — clinics, beauty, perfume, restaurants

Warm salon. Arched photo frames (the signature), soft tinted bands, rounded panels, generous spacing,
sliders for services and reviews, booking given real prominence.

| Section | Layout |
|---|---|
| Masthead | Links on one side, name in the middle, booking pill on the other (kept). |
| Hero | Text beside a large arched photo with a small round second photo overlapping it and a floating "book now" card with phone + hours. No photo: soft tinted panel with the headline centred. |
| About | Arched photo + round inset photo, years badge, text with a signature-style company name. |
| Services | Slider of cards: arched photo top, icon, title, short text, "book this" link. |
| Stats | Soft rounded tiles in a row on the tinted band. Photo slot: rounded photo strip behind the tiles. |
| Booking | Prominent split panel: options as selectable chips, WhatsApp button, side photo in an arch. |
| Projects / gallery | Rounded masonry with lightbox; projects page grid with filter. |
| Testimonials | Slider of review cards with five stars and initial avatars. |
| FAQ | Rounded accordion beside a soft help card with photo. |
| Menu | Two-column menu card with category tabs as headings; dish prices aligned. |
| Products | Rounded product cards, price, "order" pill. |
| CTA | Rounded photo band with overlay inside the page width (not full-bleed). |
| Contact | Rounded info cards + form inside one soft panel. |
| Footer | Soft tinted footer with the name centred and columns below. |

## Bold modern — retail, tech, sports, young brands

High contrast, compact, square corners, brand-colour blocks, heavy Kufi display face, dense grids.

| Section | Layout |
|---|---|
| Masthead | Compact bar, logo + links + block CTA (kept); turns solid dark on scroll. |
| Hero | Split: brand-colour block with the headline (heavy type) beside an edge-to-edge photo; a scrolling strip of service names under it. No photo: full brand block with an oversized company initial. |
| About | Photo block and text block side by side, points as solid check rows, years as a big block number. |
| Services | Bento: first service large with photo, others in a tight grid of solid cards with icons. |
| Stats | Solid blocks in alternating brand colours, large numbers. |
| Products | Dense grid with price tag badges and full-width "order" buttons; slider on phones. |
| Projects | Edge-to-edge tiles with hover overlay; projects page filter. |
| Gallery | Tight square grid, no gaps radius; lightbox. |
| Testimonials | Dark section, cards with large quote marks, horizontal slider. |
| FAQ | Square accordion with numbered items + solid help block. |
| CTA | Full-width brand block with oversized headline; photo slot as a side panel. |
| Contact | Dark info block + form block side by side. |
| Footer | Dark, big name, compact columns. |

## Testing

- Existing `TemplatesTest` stays green for all four templates.
- New per-template checks (data-provider over editorial/boutique/bold): every image slot renders its
  photo; with all photos stripped no `<img>` renders inside `<main>` and every section still renders;
  contact form + product order + service links are tracked; no WhatsApp number → no form, no wa.me.
- Visual: screenshots of a realistic generated site per template, desktop and mobile, ar and en.
