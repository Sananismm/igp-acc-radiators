# IGP Unified 2.1.0

One WordPress installation serves Innovative Generator Parts at the site root and ACC Radiators at /acc-radiators/. Both divisions use the existing IGP product and private enquiry data types. The installable theme and core plugin are in this directory.

| Public area | Entry points |
|---|---|
| Generator parts | /, /products/, /products/{slug}/, /categories/, /categories/{slug}/ |
| IGP support | /about/, /contact/, /request-quote/ |
| Automotive radiators | /acc-radiators/, /acc-radiators/products/, /acc-radiators/products/{slug}/ |
| ACC support | /acc-radiators/about/, /acc-radiators/contact/ |
| Administration and sitemap | /wp-admin/ and /wp-sitemap.xml |

## Changes delivered

- Preserved the 80 IGP seed records, all eight systems and every ACC source record. ACC retains the literal year ranges, part numbers, construction, material, transmission and thickness. The material photos are visibly labeled as representative.
- Kept the two logos, primary photography and brand palettes. Added a persistent division switcher with active states, keyboard navigation and compact menus. ACC has its own hero, make lookup, material links, catalogue, detail pages and product-prefilled form.
- Extended IGP Core with source identities, brand-specific metadata, category separation, combined filters, shared enquiry storage, staff capabilities and administrator-controlled imports. Native WordPress media, taxonomy and editor tools manage both catalogues.
- Added dry-run and repeat import, duplicate detection, database locking, retry-safe quote receipts and private attachment handling. Failed email remains visible on the saved record.
- Added canonical URLs, structured data, social metadata, policy-aware indexing and sitemap integration. Package assets are local. Production requires WordPress, PHP and its MySQL-compatible database.

## Operations

Start with WEBSOULS_INSTALLATION_GUIDE.md and DATABASE_MIGRATION_GUIDE.md. ADMIN_USER_GUIDE.md covers daily work. REPOSITORY_AUDIT.md records the source comparison. QA_TEST_REPORT.md and SECURITY_AUDIT.md describe the checks performed. KNOWN_LIMITATIONS.md and PRODUCTION_VERIFICATION_CHECKLIST.md identify the remaining hosted checks. Use ROLLBACK_GUIDE.md for recovery.

The local feature branch is codex/unified-radiators, based directly on igp_wp commit 204c6bc. The source application repositories were used as references. Release archives exclude QA scripts, private data, WordPress itself and development tools.
