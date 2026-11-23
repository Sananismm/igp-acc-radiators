# Repository audit

The source comparison used the following revisions. The audit covered the tracked application and seed data. Production content, user accounts and customer records were unavailable.

| Source | Revision | What it contains |
|---|---|---|
| [IGP WordPress foundation](https://github.com/Sananismm/igp_wp/tree/204c6bc33d71f6a6aacb383edba9154d07521fd3) | 204c6bc | PHP theme, private and public data types, independent IGP Core plugin, duplicated theme fallbacks, eight category images and product photography |
| [IGP Next.js reference](https://github.com/Sananismm/innovative-generator-parts/tree/f2244cec7bbb53ad71664a4027bd41c55fa8f3c6) | f2244ce | Next App Router, Prisma schema and initial migration, legacy JavaScript catalogue, public product assets, administration and Vitest suites |
| [ACC reference](https://github.com/Sananismm/ACC-Radiators/tree/3206efb3340f7a7f7cb5da116d8f118f802d5dd8) | 3206efb | Three HTML pages, vanilla JS catalogue, URL/filter helpers, slider, original and optimized images, static and browser checks |

## Visitor routes and identity

- IGP supplies home, products, product detail, categories, category detail, about, contact, request-quote, privacy and terms. The home uses industrial engine photography, a navy/red palette, oversized sans-serif typography, system tiles and featured parts. Compact navigation, WhatsApp links, catalogue search, sorting and quotation prefill already existed in the WordPress theme.
- The Next app includes login, admin overview, product and category create/edit lists, enquiries, individual enquiry and private attachment routes, robots and sitemap. WordPress substitutes its login, password recovery, admin screens, media library and XML sitemaps. Old Next database IDs cannot identify independently created WordPress records.
- ACC supplies index.html, products.html and about.html. Contact was a mailto action. The vehicle lookup, material tiles, seven vehicle makes and 12 listings used JavaScript and bookmarked query parameters. Make, model, material and part/name search combined. Unsupported combinations and malformed parameters returned no matches. The unified section uses server-rendered forms, independently addressable products and a private contact form.
- The ACC logo, vehicle-and-radiator hero, make logos and material photography came from its optimized assets. The IGP logo, generator hero, eight categories and original part photographs came from the migration. No product-specific ACC photography or additional fitment data was inferred.

## Data and functional differences

| Area | Source finding | Integration decision |
|---|---|---|
| IGP seed | 80 records across eight systems. Names recur across categories. Next generated category-prefixed slugs. Core translated this data but discarded the seed ID on import. | Compare every seed field with the source. Preserve names, image references and slugs, retain WordPress IDs, and attach stable source IDs. |
| ACC seed | 12 objects supply make, model, name, slug, material, core, transmission, part and thickness. Inclusive year ranges are embedded in names. | Preserve the original fields. Extract the literal year range and map material to the same representative photograph. |
| WordPress structures | Public igp_product with igp_category, private igp_enquiry, post metadata, options, transients, core settings. Older theme-only path duplicated registration and enquiry handling. | One core plugin owns both brands. An absent brand on an existing record means IGP. ACC categories have their own taxonomy. |
| Import | WordPress importer refused any populated catalogue. A failure could leave a partially imported catalogue. Next seeded an empty database under an advisory lock. | Preflight existing identities, serialized imports, per-record transactions, separate dry run and repeatable adoption. |
| Management | Next stored product images, tags, attributes, role/status, redirect history and enquiries in PostgreSQL. The WordPress foundation already had featured media, OEM/compatibility and SEO fields, but the front end omitted several fields. Generic post capabilities mixed posts and customer enquiries. | WordPress media galleries, tags, compatibility details, SEO output, restricted roles, native editor, duplicate-to-draft and archive/restore actions. Files from private Next Blob storage require a separate authorized migration if they exist. |
| Search | Next supported metadata, categories, tags, manufacturer and an AVR synonym. WP core searched selected metadata but missed category text and the synonym. | Extend prepared search, category/tag and manufacturer filters. Keep brand scope on archives, featured items and related items. |
| Enquiries | Next validated requests and stored private attachments. WP offered optional mail, private file or DB storage, status and an idempotency option. The theme fallback lacked the newer safeguards. | Unified transactional write path, per-brand form, verified receipt, attachment checks and separately recorded notification outcome. |
| SEO | Next generated metadata and a sitemap. The foundation largely depended on WordPress defaults. ACC had a sitemap template pending domain approval. | Canonical links from WordPress URL APIs, Product/WebPage data, Open Graph, core sitemaps, noindex filtered results and redirects for legacy ACC section paths. |
| Responsive UX | IGP used a hide-on-scroll header and native mobile menu. ACC used its own responsive menu and static carousel. | Persistent division navigation, a native keyboard-operable mobile menu, responsive grids, model filtering and linked make cards. |

## Source issues and launch decisions

The foundation's audit and parity matrix documented incomplete import recovery, capability separation, attachment testing, SMTP, product details and SEO. ACC's report highlighted uncertain commercial claims and representative images. The original repositories' GitHub issue lists returned no open non-PR issues at inspection. Historical reports describe earlier versions. Current results are in QA_TEST_REPORT.md.

The source-approved IGP public details are +92 300 8877677, WhatsApp at the same number, usm_tra@yahoo.com and service across Pakistan. ACC publishes info@accradiators.pk. Confirm mailbox ownership and the notification recipient. Obtain approved legal text, record retention, any address or operating hours, inventory, current fitment, prices and warranty terms. ACC model years end where the supplied data ends. Testing methods, pressure, temperature, facilities and certification claims require separate approval.
