# Preliminary audit — not a release assessment

Repository `igp_wp`, base commit 82ee0eb. Reference: local `innovative-generator-parts` checkout and Prisma schema. This is a partial audit; further inspection and real WordPress/MySQL staging tests are required.

- **P0: unverified enquiry reliability.** `inc/enquiries.php` has no retry-safe submission identifier and can create duplicate entries on uncertain outcomes. Its attachment metadata failure previously deleted the enquiry; this branch retains it but the uncertain state still needs reconciliation and staff visibility. Notification emails are absent.
- **P0: legal and hosting verification pending.** Approved privacy/terms, permissions, backups, secure storage and real cPanel staging validation have not been established.
- **P1: theme-bound data model — partially addressed.** A preliminary `igp-core` plugin and compatible theme fallback have been added. Plugin activation was tested locally; switching themes and migration of existing data still require testing.
- **P1: missing catalogue parity.** Compared with the reference Prisma schema, the WordPress editor omits OEM reference, compatibility fields, multiple images, tags and SEO fields. Archived status and slug-redirect management require design and tests. `archive-igp_product.php` searches WordPress post text and does not search product metadata.
- **P1: incomplete importer.** `inc/catalogue.php` stops when any product or category exists; interrupted imports leave partial records and cannot resume automatically.
- **P1: storage and authorization.** An optional private filesystem implementation and base64 database fallback are present, but neither the new storage path nor guest/staff authorization and hosting/database size limits have been validated on MySQL/cPanel. Signature-only checks do not prove a file is safe. The transient throttle is not atomic and can be bypassed across identifiers.
- **P1: settings and email — partially addressed.** Initial settings and optional `wp_mail` notifications exist, but templates still have duplicated values and real SMTP and delivery remain unverified.

Previous Playground results in `VERIFICATION.md` describe an earlier test environment and have not been independently rerun on this branch. No cPanel deployment, production database access or DNS changes were made.
