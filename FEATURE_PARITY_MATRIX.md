# Feature parity — preliminary assessment

| Feature | Next.js reference | WordPress implementation | Status |
|---|---|---|---|
| Initial catalogue | 80 products, eight categories | Explicit import into empty catalogue | PARTIAL: repeat stops, interrupted import needs recovery |
| Catalogue CRUD | Product/category administration | WordPress post type and taxonomy | PARTIAL: archive, bulk image and metadata parity incomplete |
| Product fields | OEM, fitment, images, attributes and SEO | Core plugin adds basic text fields and featured image | PARTIAL: multiple images, attributes and SEO output not complete |
| Search | Product descriptions, part numbers, manufacturers | WP search plus product metadata filter | PARTIAL: needs integration testing and synonyms |
| Quote enquiries | Neon records, status and private Blob attachments | Private WordPress records, status, optional private directory or DB fallback | PARTIAL: concurrent failures, guest access, retention and real MySQL unverified |
| Notifications | Not implemented | Optional `wp_mail`, outcome stored on enquiry | PARTIAL: SMTP and delivery not tested |
| Authentication | Custom sessions and roles | WordPress sessions and native role capabilities | PARTIAL: dedicated least-privilege roles not implemented |
| Business information | Static constants | Plugin settings, footer and WhatsApp use settings | PARTIAL: contact and other template text need consistency |
| Product redirects | Database-backed redirects | WordPress defaults only | MISSING: tested slug-history policy |
| SEO | Next metadata, robots, sitemap and structured data | WordPress core and theme titles | PARTIAL: metadata and SEO plugin compatibility unverified |
| Content migration | Prisma/Neon | Bundled initial source data | MISSING: secure live-data migration process |

No production data was accessed during this assessment.
