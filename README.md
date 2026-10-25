# Sammav IT Services website

WordPress website for Sammav IT Services, built from `Sammav_IT_Services_Website_SRS.docx`.
Four menu pages (Home, Projects, About Us, Contact Us), project detail pages, a contact form
that emails and stores enquiries, Privacy Policy and 404 pages.

- **Theme** `wp-content/themes/sammav`: lightweight block theme (templates, header/footer, patterns, CSS/JS). No page builder.
- **Plugin** `wp-content/plugins/sammav-core`: content types, custom fields, dynamic blocks, contact form, enquiries, settings.
- **Docker** stack: WordPress 7 (PHP 8.3), MariaDB, WP-CLI and Mailpit (catches outgoing email locally).

## Run it locally

Requirements: Docker Desktop.

```bash
cp .env.example .env        # change the passwords
./scripts/setup.sh          # starts containers, installs WordPress, seeds content
```

| What | URL |
| --- | --- |
| Website | http://localhost:8088 |
| Dashboard | http://localhost:8088/wp-admin (Administrator and Editor logins are in `.env`) |
| Mailpit (local inbox) | http://localhost:8025 |

`setup.sh` is safe to run again; it skips anything that already exists.

Other commands:

```bash
docker compose stop                        # stop
docker compose up -d                       # start again
docker compose run --rm wpcli <command>    # any WP-CLI command, e.g. "plugin list"
docker compose down -v                     # delete everything (database included) and start over
php scripts/generate-images.php            # regenerate placeholder images (needs PHP + GD)
```

## Editing content (for Sammav staff)

| To change… | Go to |
| --- | --- |
| Page text, images and sections | **Pages** → Home / About Us / Contact Us |
| Projects (add, edit, feature on Home, gallery) | **Projects** → Add New. Fields are in the **Project details** panel at the bottom of the editor ("Meta Boxes"). Tick **Feature on Home page** to show it on Home. |
| Project categories (filter buttons) | **Projects** → Project Categories |
| Service cards | **Services** |
| Testimonials | **Testimonials** |
| Team members | **Team** (featured image = photo) |
| Phone, email, address, hours, WhatsApp, map, social links, Google Analytics ID, spam protection keys | **Settings** → **Sammav** |
| Menu | **Appearance** → **Editor** → Navigation |
| Footer "Quick links" (includes Terms & Conditions) | **Appearance** → **Editor** → Patterns → Footer |
| Terms & Conditions, Privacy Policy | **Pages** |
| Enquiries from the contact form | **Enquiries** (view, reply, **Export CSV**) |

The footer, Contact page and structured data all read contact details from **Settings → Sammav**, so each detail is entered once.

The portfolio (TACMS, eMemo, TAC ERP and the other projects) is defined in `scripts/seed/projects.php`.
Edit it and run `docker compose run --rm wpcli eval-file /seed/seed.php` to update the site, or edit projects
directly in the dashboard. Re-running the seed overwrites dashboard edits to those projects.

## How the SRS is covered

| SRS | Where |
| --- | --- |
| FR-G1–G4 sticky header, active item, hamburger, quote CTA | `parts/header.html`, `functions.php` (active item), `site.css` |
| FR-G5–G6 footer, privacy link | `parts/footer.html`, `[sammav]` shortcodes |
| FR-G7 WhatsApp button, FR-G9 cookie consent | `functions.php`, `assets/js/site.js` |
| FR-G8 404 page | `templates/404.html` |
| FR-H1–H8 Home sections | `patterns/hero-home.php` … `patterns/cta.php` |
| FR-P1–P8 Projects | `sv_project` type, `blocks/projects-grid`, `blocks/project-details`, `templates/single-sv_project.html` |
| FR-A1–A7 About | `patterns/about-*.php`, `mission-values`, `process`, `team-section`, `certifications`, `history` |
| FR-C1–C9 Contact form | `blocks/contact-form`, `includes/contact.php`, `includes/enquiries-admin.php` |
| FR-M1–M5 Administration | Block editor, `includes/roles.php`, WebP image sizes in `functions.php` |
| NFR-9 retention | Enquiries are deleted automatically after 12 months (configurable) |
| NFR-10 accessibility | Labels, focus styles, skip link, keyboard lightbox, contrast-checked palette |
| NFR-11 SEO | Meta description fallback, LocalBusiness schema, clean URLs |

Contact form spam protection: honeypot, a signed 3-second time-trap, a limit of 5 submissions per IP every 10 minutes,
and optional Cloudflare Turnstile (add keys under Settings → Sammav). There is no nonce, so the form keeps working
behind page caching.

## Before going live

These depend on the production host and are not part of the local stack:

- [ ] Replace placeholder content: logo, copy, projects, team, testimonials, client names, statistics, contact details
- [ ] Have the Privacy Policy and Terms & Conditions reviewed
- [ ] Hosting with PHP 8.2+, MySQL 8 / MariaDB 10.6+, HTTPS and HTTP→HTTPS redirect (NFR-5)
- [ ] Real email delivery: install **WP Mail SMTP** (or define `SAMMAV_SMTP_HOST/PORT/USER/PASS/SECURE` in `wp-config.php`) and set SPF, DKIM, DMARC
- [ ] Security: **Wordfence** (firewall, login limits, malware scan) and 2FA for administrators (NFR-6, NFR-7)
- [ ] SEO: **Rank Math** or **Yoast**, submit the sitemap to Google Search Console (NFR-11)
- [ ] Analytics: add the GA4 ID under Settings → Sammav; the cookie banner then appears automatically (NFR-13)
- [ ] Turnstile keys under Settings → Sammav (FR-C3)
- [ ] Caching/CDN, e.g. LiteSpeed Cache or Cloudflare (NFR-3)
- [ ] Backups: **UpdraftPlus**, daily database and weekly full backups off-site, 30-day retention; test a restore (NFR-15)
- [ ] Uptime monitoring (NFR-14)

To deploy, install WordPress on the host, copy the `sammav` theme and `sammav-core` plugin into `wp-content`,
activate both, then either migrate the local database (e.g. with All-in-One WP Migration) or run
`wp eval-file scripts/seed/seed.php` to create the pages, menu and placeholder content.
