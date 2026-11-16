# MI Preview — Interactive preview-site generator

Platform for **MI Web Development** to generate realistic, interactive preview websites for
prospective clients on subdomains (`{company}.mius.it.com`), send the link on WhatsApp, and get
a Telegram alert the moment the prospect opens it.

When a client approves, a **separate** real site with its own dashboard is built outside this
platform. The preview only needs an **export** (JSON + media zip) to seed that project.

---

## 1. Infrastructure (already done — do not redo)

| Item | Value |
|---|---|
| Hosting | Namecheap Stellar Plus (shared, cPanel, CloudLinux), SSH available |
| Server IP | `66.29.141.215` |
| Base domain | `mius.it.com` (DNS on Cloudflare Free) |
| Admin panel | `https://app.mius.it.com` |
| Preview sites | `https://{slug}.mius.it.com` (wildcard `*` record, proxied) |
| Document root | `/home/miusofrv/preview/public` (serves both `app.` and `*.`) |
| SSL | Cloudflare Universal SSL at edge + Cloudflare Origin Cert on server, mode **Full (strict)** |
| PHP | 8.3 (CLI and web), ext: bcmath curl exif fileinfo gd imagick intl mbstring openssl pdo_mysql tokenizer xml zip |
| Tools | Composer 2.11, Git 2.56 |
| Database | MySQL `miusofrv_preview`, user `miusofrv_preview` |
| Telegram | Bot `@mi_preview_alert_bot`, chat id `901743307` (token only in `.env`) |

**Other projects live on the same domain** as explicit DNS-only subdomains (clinic, lacoste,
funnycouples, …). Explicit cPanel subdomains win over the wildcard vhost, so this app only
receives hosts that are *not* existing subdomains. Never touch their records.

### Shared-hosting constraints (design around these)
- **No Supervisor / no long-running processes.** Queues run from cron:
  `schedule:run` every minute → `queue:work --stop-when-empty --max-time=50`.
- CPU/RAM limits per account (LVE): keep requests light, no heavy image/ML work on the server.
- Behind Cloudflare: real visitor IP = `CF-Connecting-IP`, country = `CF-IPCountry`.
  Configure trusted proxies for Cloudflare ranges.
- Outbound HTTPS works (Telegram verified).

---

## 2. Stack & conventions
- Latest stable **Laravel** that supports PHP 8.3, **Filament** (latest stable) for the admin.
- Public preview sites: **Blade** + minimal vanilla JS (no SPA). Vite for assets.
- MySQL in production; SQLite acceptable locally.
- Code, identifiers, comments in English. All user-facing preview content bilingual **ar / en**,
  Arabic default, full **RTL/LTR** support.
- Filament admin UI in Arabic (RTL).

### Environment variables (add to `.env.example`)
```
APP_URL=https://app.mius.it.com
PREVIEW_BASE_DOMAIN=mius.it.com        # locally: localhost
PREVIEW_ADMIN_SUBDOMAIN=app
PREVIEW_BRAND_NAME="MI Web Development"
PREVIEW_BRAND_WHATSAPP=                # my own number, used in the demo bar
TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=901743307
PEXELS_API_KEY=                        # phase 2
AI_API_KEY=                            # phase 3
```

### Local development
Use `*.localhost` (Chrome/Edge resolve any `*.localhost` to 127.0.0.1, no hosts file needed):
- Admin: `http://app.localhost:8000`
- Preview: `http://acme.localhost:8000`

Session/cookie domain must be configurable so it works for both `.localhost` and `.mius.it.com`.

---

## 3. Routing

```
{admin}.{base}   → Filament panel (+ admin-only helper routes)
{slug}.{base}    → Preview site renderer
anything else    → 404
```

- Middleware `ResolvePreviewSite`: extract slug from host, load `Site` by slug
  (status not archived), share it with views, 404 with a neutral branded page otherwise.
- **Reserved slugs** (cannot be used for sites, validate on create):
  `www app admin api mail webmail cpanel whm webdisk autodiscover autoconfig cpcalendars
  cpcontacts ftp smtp imap pop static cdn assets preview demo test`
  plus every existing project subdomain: `clinic funnycouples mi-solutions maya-portfolio
  imposter-game lacoste nabet-khalifa first-trade mdz-quote-system ostaz-platform mechanic archit
  elle-store islam-web-studio bulk-sender arkan abouda-elashmawy topcorner alfanar rk-fire riwaq`
  (keep this list in config so new ones can be added).
- Slug rules: lowercase `a-z0-9-`, 3–40 chars, no leading/trailing hyphen.
- Preview pages: `/` = home page, `/{page-slug}` = other pages. Locale via `?lang=en` or
  `/en/...` — pick one and be consistent; Arabic default.
- Every preview response: `<meta name="robots" content="noindex,nofollow">` **and**
  `X-Robots-Tag: noindex, nofollow` header. Previews must never be indexed.

---

## 4. Data model

```
sites
  id, slug (unique), status (draft|sent|viewed|won|lost|archived)
  company_name_ar, company_name_en, industry_id, template (key)
  short_description (text, input for AI)
  logo_path, logo_nobg_path
  palette (json: primary, secondary, accent, text, bg, + generated shades)
  font_key (nullable, template default otherwise)
  phone, whatsapp, email, address_ar, address_en, map_url, socials (json)
  default_locale (ar|en), preloader_enabled (bool, default true)
  notify_enabled (bool, default true)
  sent_at, first_viewed_at, last_viewed_at, views_count
  timestamps, softDeletes

pages
  id, site_id, slug, title_ar, title_en, is_home, sort, is_published, timestamps

sections
  id, page_id, type, variant, content (json, keyed by locale), sort, is_visible, timestamps

nav_items
  id, site_id, parent_id (nullable), label_ar, label_en
  type (page|anchor|external|whatsapp|phone), target, sort, is_cta, timestamps

media
  id, site_id, path, source (upload|pexels), credit (nullable), alt_ar, alt_en, timestamps

industries
  id, key, name_ar, name_en
  sections_blueprint (json: default pages → sections list)
  stock_keywords (json), ai_hints (text), default_palette (json)

visits
  id, site_id, visitor_uuid, ip_hash, user_agent, device (mobile|tablet|desktop)
  country, city (nullable), referrer, landing_path
  started_at, last_ping_at, ended_at, duration_seconds
  is_return (bool), summary_sent_at (nullable), timestamps

visit_events
  id, visit_id, type (page_view|section_view|whatsapp_click|phone_click|cta_click|scroll_depth)
  payload (json: section key, seconds, path, depth…), occurred_at
```

Section `type` values (shared by all templates): `hero, about, services, projects, gallery,
stats, testimonials, clients, faq, cta, contact, map, menu (restaurants), booking (clinics),
products (retail), certifications (contracting/fire)`.

---

## 5. Preview rendering

- Templates live in `resources/views/templates/{key}/`:
  `layout.blade.php`, `partials/nav.blade.php`, `partials/footer.blade.php`,
  `sections/{type}.blade.php` (each may support `variant`s).
- Palette injected as CSS custom properties on `:root`; templates use only those variables.
- **Logo preloader**: centered `logo_nobg`, subtle animation in primary color; hides on
  `load` or after max 2.5s; shown **once per browser session** (`sessionStorage`);
  toggle per site.
- **Navbar** fully driven by `nav_items` (auto-generated from pages on site creation, then
  editable): page links, anchors to sections (`#services`), external links, WhatsApp/phone CTA
  button, one level of dropdown.
- **Demo bar** (small, unobtrusive, dismissible for the session): "نموذج تجريبي لموقع
  {company} — مقدم من MI Web Development" + button "أريد هذا الموقع" opening WhatsApp to
  `PREVIEW_BRAND_WHATSAPP` with a prefilled message containing the site slug.
- Floating WhatsApp button for the company's own number (part of the demo experience).
- Images lazy-loaded, sizes constrained; pages must stay light on mobile.

### Design rules (important — the owner rejects "AI-looking" design)
- **Never** use grid/dot pattern backgrounds, decorative lines/bars beside headings, or
  oversized full-viewport sections stacked one after another.
- Avoid generic gradient blobs, glassmorphism cards everywhere, emoji icons, identical
  3-card rows on every section.
- Templates must differ in **layout and structure**, not just colors.
- Arabic fonts: don't default to Cairo/Tajawal; use e.g. IBM Plex Sans Arabic, Readex Pro,
  Alexandria, Noto Kufi Arabic (self-host or Google Fonts with `display=swap`).
- Planned template directions (phase 4):
  1. **Editorial** — magazine-like, strong typography, asymmetric, photo-led.
  2. **Industrial / corporate** — contracting, fire, supply: dense info, stats, project cards, dark sections.
  3. **Boutique** — clinics, beauty, perfume, restaurants: warm, soft, large imagery.
  4. **Bold modern** — high contrast, brand-color blocks, compact spacing.

---

## 6. View tracking + Telegram alerts (core feature — phase 1)

### Client side (`public/js/t.js`, ~3 KB, no dependencies)
- Runs only on preview sites. **All tracking is JS-initiated** — WhatsApp/Facebook link-preview
  bots fetch the URL but don't run JS, so they never create visits.
- `visitor_uuid` in `localStorage` (fallback cookie).
- `POST /_t/start` on load → returns `visit_id` + short signed token.
- Heartbeat `POST /_t/ping` every 15s while the tab is visible (pause when hidden).
- `IntersectionObserver` on `[data-section]` → accumulate visible seconds per section,
  flush with pings.
- Clicks on `[data-track="whatsapp|phone|cta"]` → `POST /_t/event`.
- `navigator.sendBeacon('/_t/end')` on `pagehide` / `visibilitychange=hidden`.

### Server side
- Endpoints on preview hosts only, throttled per IP, validate the signed visit token.
- Filter known bot user-agents as a second line of defense.
- **Owner exclusion**: "Open as owner" action in admin hits an admin route that sets a signed
  cookie on `.{base}` domain → visits from that browser are not recorded and never notify.
- IP stored hashed (`sha256(ip + app key)`), country from `CF-IPCountry`.

### Notifications (Telegram, Arabic, queued; send sync if queue unavailable)
1. **Instant — first open of a visit:**
   `🟢 {company} فتح الموقع الآن` + device, country, return/first visit, link to visit in admin.
2. **Summary — visit ended** (job `CloseStaleVisits`, every minute, closes visits with no ping
   for 60s, sends once using `summary_sent_at`):
   duration, pages viewed, sections ranked by seconds, clicked WhatsApp/phone or not.
3. **High intent — immediately:** WhatsApp/phone/CTA click, or a return visit
   (`🔥`). De-duplicate: max one high-intent alert per visit per type.
- Update `sites.status` → `viewed` on first real visit; maintain counters.

---

## 7. Admin (Filament)

- **Sites** resource: list with status badges, views count, last viewed.
  Create as a wizard: basic info → logo & colors → template → pages/sections → review.
- Actions on a site: **Copy link**, **Copy WhatsApp pitch message** (Arabic template with the
  link), **Open as owner**, **Duplicate**, **Export** (phase 5), mark won/lost.
- Relation managers: Pages (sortable) → Sections (sortable, per-type form schema, ar/en tabs),
  Nav items (sortable, nested one level), Media.
- **Visits** view per site: timeline of visits with events and section dwell bars.
- Dashboard widgets: sites sent / viewed / won, latest visits.
- Single admin user is enough (seeded); protect with standard Filament auth.

---

## 8. Roadmap

**Phase 1 — foundation (build first)**
- Project setup, config, env, trusted proxies, reserved slugs.
- Domain routing + `ResolvePreviewSite`.
- All migrations, models, factories, seeders (industries with blueprints, one demo site).
- One complete template (start with *Industrial/corporate*) covering all section types,
  preloader, nav, demo bar, RTL/LTR.
- Tracking (JS + endpoints + jobs) + Telegram notifications.
- Filament: Sites, Pages, Sections, Nav, Visits; owner cookie; copy link/message.
- Feature tests: routing by host, reserved slugs, tracking flow, stale-visit closing,
  notification dedupe, owner exclusion.

**Phase 2 — assets**
- Logo upload; background removal **in the browser** (`@imgly/background-removal`, WASM —
  AGPL license, admin-only use) with optional remove.bg API fallback.
- Palette extraction from logo (`league/color-extractor`), contrast check (WCAG AA for text on
  primary), shade generation, fallback to industry palette for monochrome logos, manual override.
- Media library + Pexels search per section with credits.

**Phase 3 — AI content**
- One structured-JSON generation call per site: all sections ar/en, services list, FAQ, SEO meta,
  Pexels keywords. Section list comes from the industry **blueprint** (rules), not from the AI.
- "Regenerate" per section. Queued job, progress shown in admin.

**Phase 4 — remaining 3 templates.**

**Phase 5 — export** (JSON + media zip) for seeding the real client site; later: expiry,
client comments, design switcher, bulk generation from leads CSV.

---

## 9. Deployment (Namecheap)

First deploy:
```bash
rm -rf ~/preview                       # removes the temporary test index.php
git clone <repo> ~/preview && cd ~/preview
composer install --no-dev --optimize-autoloader
cp .env.example .env && php artisan key:generate   # then fill .env
php artisan migrate --force
php artisan storage:link
npm run build locally and commit public/build (no Node build on the server)
php artisan config:cache route:cache view:cache
```
Cron (cPanel → Cron Jobs, every minute):
```
* * * * * cd ~/preview && php artisan schedule:run >> /dev/null 2>&1
```
Scheduler must contain:
```php
Schedule::command('queue:work --stop-when-empty --max-time=50')->everyMinute()->withoutOverlapping();
Schedule::job(new CloseStaleVisits)->everyMinute();
```
Updates: `git pull && composer install --no-dev -o && php artisan migrate --force && php artisan optimize`.

---

## 10. Phase 1 acceptance checklist
- [ ] `app.localhost` opens Filament login; `acme.localhost` renders the demo site; unknown slug → 404; reserved slug rejected.
- [ ] Preloader shows once per session; nav fully editable from admin; ar/en switch works with correct direction.
- [ ] Opening a preview in a normal browser sends instant Telegram alert; closing the tab produces a summary within ~1–2 min; WhatsApp click sends one 🔥 alert.
- [ ] "Open as owner" visits produce no visits and no alerts.
- [ ] `noindex` meta + header present on every preview response.
- [ ] Tests pass.
