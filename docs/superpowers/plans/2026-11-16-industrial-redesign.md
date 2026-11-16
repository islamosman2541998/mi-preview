# Industrial Template Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the industrial preview template look like a real company website: photo-led sections, icons, light motion, and an image slot in every section.

**Architecture:** No schema changes — new keys live inside `sections.content.shared`. `SectionFields::definition()` stays the single source for admin forms and AI schema; a new `App\Support\ServiceIcons` holds the fixed icon set. The industrial Blade views and CSS are rewritten; shared behaviour goes into `resources/js/preview.js` behind `data-*` hooks so the other templates can adopt it later.

**Tech Stack:** Laravel + Blade, vanilla JS, Vite, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-11-16-industrial-redesign-design.md`

## Global Constraints

- Colours only from `--c-*` custom properties; fonts only from `--f-*`.
- No grid/dot patterns, no decorative lines beside headings, no full-viewport stacked sections, no gradient blobs, no glassmorphism, no emoji icons.
- Hooks the existing tests assert on must survive: `class="nav__sub"`, `class="crumbs"`, `class="page-head`, `<h1 class="page-head__title">`, `booking_option`, `data-masthead`, `data-nav-toggle`, `data-sub-toggle`, `class="wa-float"`, `data-section="{type}"` on every rendered section.
- A section with no content renders nothing; a section with no photo never renders an empty visual box.
- Everything works without JS and under `prefers-reduced-motion`.
- The editorial, boutique and bold templates must keep rendering unchanged.

## Review Focus

- A site with zero photos: every industrial section still looks designed (covered by Task 5 test `industrial_renders_without_any_photos`).
- Long Arabic or English titles in the hero facts strip and project tiles must wrap, not overflow (checked in Task 7 screenshots at 390px).
- A service whose `icon` is missing or not in the set falls back to the default icon (Task 1 test).
- Regenerating a section with AI must not wipe photos in the new slots (Task 2 test).
- A site with a phone but no WhatsApp: contact form and product buttons fall back to a call link or disappear, never a dead `wa.me` link (Task 5 test).

---

### Task 1: Section fields and icon set

**Files:**
- Create: `app/Support/ServiceIcons.php`
- Modify: `app/Filament/Support/SectionFields.php`, `resources/views/components/preview/icon.blade.php`
- Test: `tests/Feature/SectionFieldsTest.php`

**Interfaces:**
- Produces: `ServiceIcons::KEYS` (list of 30 strings from the spec), `ServiceIcons::DEFAULT = 'sparkle'`, `ServiceIcons::resolve(?string $key): string`, `ServiceIcons::options(): array<string,string>` (key → Arabic label).
- Produces: field kind `SectionFields::ICON = 'icon'` (a Select in the admin); new definition entries: about `shared.image2`, stats/cta/faq/booking `shared.image`, services items `icon`.

- [ ] Test: `new_image_slots_and_service_icon_round_trip` — `fromForm(toForm(x)) == x` for about (`image2`), stats/cta/faq/booking (`image`) and a services item with `icon => 'crane'`.
- [ ] Test: `service_icons_resolve_unknown_keys_to_default` — `resolve('nope') === 'sparkle'`, `resolve(null) === 'sparkle'`, `resolve('crane') === 'crane'`.
- [ ] Implement; add the 30 icons as `@case` blocks in `x-preview.icon`.
- [ ] Run `php artisan test --filter=SectionFieldsTest`; commit.

### Task 2: AI schema and image fill for the new slots

**Files:**
- Modify: `app/Ai/SectionSchema.php`, `app/Ai/ContentGenerator.php`, `app/Ai/Prompt.php`
- Test: `tests/Feature/AiContentTest.php`

**Interfaces:**
- Consumes: `ServiceIcons::KEYS`, `SectionFields::ICON`.
- Produces: `SectionSchema::imageFields()` returns, per type, a list drawn from `image`, `image2`, `images`, `items.image`: hero `[image]`, about `[image, image2]`, services `[items.image]`, stats/cta/faq/booking `[image]`, gallery `[images]`, projects/products `[items.image]`.

- [ ] Test: `schema_lets_the_model_pick_a_service_icon` — services item schema has `icon` with `enum === ServiceIcons::KEYS` and an `image_query`.
- [ ] Test: `regenerating_keeps_photos_in_new_slots` — `ContentGenerator::merge()` on a cta with `shared.image` set keeps it; on services keeps each item's `image` and takes `icon` from the AI.
- [ ] Test: `image_fill_covers_new_slots_only_when_empty` — with a faked stock provider, an empty cta `image` and services item images get filled; a pre-set stats `image` is untouched.
- [ ] Implement. `about.image2` uses the section `image_query` (second result). Prompt: one line telling the model to choose the closest icon.
- [ ] Run `php artisan test --filter=AiContentTest`; commit.

### Task 3: Shared preview behaviour

**Files:**
- Modify: `resources/js/preview.js`, `resources/css/preview-base.css`

**Interfaces:**
- Produces DOM hooks: `[data-reveal]` (+ class `is-in`), `[data-count="1400"]`, `[data-lightbox]` on `<a href="full.jpg">` inside `[data-gallery]`, `[data-filter="category"]` buttons controlling `[data-category]` items inside `[data-filter-scope]`, `<form data-wa-form data-wa="2010…" data-wa-intro="…">` with named fields, masthead class `is-scrolled`.
- Reveal styles apply only under `.js` and are neutralised by the existing reduced-motion block and by the 3s `is-ready` safety net.

- [ ] Implement each behaviour as a small block in the existing IIFE; lightbox markup is created on first use.
- [ ] `data-wa-form` submit: build `https://wa.me/{digits}?text=…` from intro + labelled field values, `window.open`; the submit button carries `data-track="whatsapp" data-track-label="contact_form"` so `t.js` records the click.
- [ ] Verified in Task 7 (browser), commit with Task 5.

### Task 4: Industrial layout chrome (nav, page header, footer)

**Files:**
- Modify: `resources/views/templates/industrial/partials/{nav,page-header,footer,section-head}.blade.php`, `resources/views/templates/industrial/layout.blade.php`
- Create: `app/Support/PageVisuals.php`

**Interfaces:**
- Produces: `PageVisuals::banner(Site $site, Page $page, Collection $sections): ?string` — URL of the first photo on the page (section `image`, first gallery image, first item image), else the home hero image, else null.
- Produces: `PageVisuals::services(Site $site, Locale $locale, int $limit = 5): array` — service titles from the first services section, for the footer.

- [ ] Test (in `IndustrialTemplateTest`): `inner_page_header_uses_a_page_photo_when_available` and existing breadcrumb test still green.
- [ ] Implement; commit with Task 5.

### Task 5: Industrial sections and stylesheet

**Files:**
- Modify: all 16 files in `resources/views/templates/industrial/sections/`, `resources/css/templates/industrial.css`, `lang/ar/preview.php`, `lang/en/preview.php`
- Test: `tests/Feature/IndustrialTemplateTest.php`

- [ ] Test: `industrial_renders_without_any_photos` — kitchen-sink site with every image key null: 200, every `data-section` present, no `<img` with empty `src`, no element with class `is-empty-visual`.
- [ ] Test: `industrial_uses_photos_in_every_slot` — with images set: hero, about (2), services cards, stats, projects, cta, page header each output the image URL.
- [ ] Test: `contact_form_and_product_orders_are_tracked` — `data-wa-form`, `data-track-label="contact_form"`, `data-track-label="product_order"` present; with no WhatsApp number neither appears and no `wa.me` link is emitted.
- [ ] Rewrite sections per the spec table; rewrite the stylesheet.
- [ ] Run `php artisan test`; commit.

### Task 6: Demo content

**Files:**
- Modify: `database/seeders/DemoSiteSeeder.php`

- [ ] Give each demo service an `icon`; keep images null (photos are loaded locally in Task 7, not committed).
- [ ] Run `php artisan test`; commit.

### Task 7: Build and visual verification

- [ ] `npm ci && npm run build` (the repo ships `public/build`; commit the rebuilt assets).
- [ ] Load stock photos into the local demo site through the existing Pixabay provider (local DB only).
- [ ] Full-page screenshots: home + projects + contact, 1366px and 390px, Arabic and English; fix what looks wrong; no console errors.
- [ ] `php artisan test` green; commit.
