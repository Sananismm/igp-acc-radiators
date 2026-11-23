# Catalogue and enquiry migration

The import is bundled in the IGP Unified Core ZIP. Use Tools → Catalogue import from WordPress. No shell, SQL import script or production build is required for the supplied catalogues.

## Inventory and dry run

1. Restore a recent copy of the old installation on staging. Record counts by product, status, category and enquiry. Save a sample of post IDs, permalinks, titles, private attachment downloads and settings. Preserve wp-content/uploads and any private storage folder.
2. Install the compatible theme and plugin and save permalinks. Check for existing pages under acc-radiators and for other plugins registering products, igp_product or igp_category. Resolve namespace conflicts on the copy. Existing pages keep their IDs and content. The unified theme supplies the brand-specific layouts.
3. Choose Dry run. It checks the packaged images and matches the source records against the database. It returns the number of products and pages to create or preserve. An unchanged foundation catalogue produced 12 creates and 80 preserved in the upgrade rehearsal. A fresh database produced 92 creates. The import requires InnoDB and connection-scoped database locks.
4. Investigate conflicting or ambiguous matches before importing. Compare the source ID, original slug, historical slug, source image, part number, existing record and brand. Never infer identity from display name alone. Resolve any conflict with the catalogue owner and retain the backup.

## Writes and recovery

- IGP products stay in igp_product. Existing permalinks and WordPress post IDs stay in use. Existing products without _igp_brand resolve to IGP. New ACC records carry acc. Existing enquiries without a division appear under IGP. The importer leaves the customer records and files in place.
- The original seed ID is stored in _igp_source_id. Each ACC identity uses acc: plus the original ACC slug. Repeated runs check that key across all statuses. The importer can adopt a foundation record with its matching slug/history and original source-image identifier. Staff changes to existing products, including publication status, are preserved.
- igp_category holds generator systems. acc_category holds radiator category terms. Source ACC records also store make, model, years, material, construction, transmission and thickness. The material filter uses the product's specification. Featured media overrides the packaged reference image.
- A named lock serializes importer runs. Catalogue records are committed individually. After interruption, rerun Dry run and then Import. Completed records retain their source IDs, and a failed product transaction rolls back. Pages created before an interruption are reused on the next run. If a legacy category or page needs correction, fix the reported item on staging and retry.
- A fresh import creates privacy and terms as drafts. Existing legal pages, including old placeholder text, remain available for review.
- Archive or unpublish a source product to keep it excluded. A physically deleted source product can be recreated on the next import. Check historical exclusions and deliberately removed records in the dry-run count. A heavily modified pre-upgrade product without a recognizable source identity requires manual reconciliation.
- Verify counts, representative IDs, titles, slugs and attachments after import. Archive or restore one test product, change its title and rerun the preview. Keep the results with the release record.

## Data outside the three repositories

A WordPress database clone preserves private enquiries, passwords, settings and media associations. The bundles contain public seed data only. If the former Next deployment holds additional customer submissions, custom products, images, redirects or private Blob attachments, obtain an authorized export and map its IDs, statuses and media references on staging before cutover. Do not copy Next session secrets or import PostgreSQL tables directly into WordPress. No private production database or export was supplied.
