# Local release verification

Tests ran on 23 November 2026 against real WordPress 7.1.5, PHP 8.4.28, MariaDB 11.4.8 with InnoDB, and Chrome 158 on Windows. WordPress installed the release ZIPs using its installer. The browser connected over HTTP to a loopback PHP web server. All customer records and accounts used in the tests were synthetic.

| Executed check | Observed result |
|---|---|
| Source comparison | Deep equality for all 80 IGP records and eight categories against the Next.js JavaScript source. All original fields of the 12 ACC records matched its JavaScript source. |
| Upstream suites | IGP Vitest passed 30 tests across nine files. ACC Node tests passed 10 of 10. ACC's historical comparison used the referenced Git history. |
| Current packages | Both archives passed CRC checks and installed and activated together. All 37 packaged PHP files passed PHP lint. The theme JavaScript passed node --check. The archives exclude test fixtures, development runtimes, credentials and databases. |
| Fresh import | Dry run predicted 92 creates and 10 pages. Import created them. The next import reported 0 creates, 92 existing records and 0 new pages. |
| Upgrade rehearsal | Installed the original core in a second WordPress database and ran its importer to create 80 records. Changed an existing title and moved it to draft. The upgrade preview and import each reported 12 creates and 80 preserved. The repeat preserved 92. Reading the original stored post ID returned the edited title, draft status and newly attached source ID. |
| Import conflict and concurrency | A synthetic draft with a conflicting source part number blocked import. Holding the importer lock on another DB connection blocked a simultaneous import. After removing the fixture and releasing the lock, the preview again preserved all 92. |
| Catalogue coverage | Checked all 92 detail URLs and primary image HTTP responses and matched the sitemap to the full source slug set. All eight IGP category pages contained products. IGP returned 80 results, both AVRs for the full-name synonym, and zero for a nonexistent keyword. Legacy page 2 and pretty page 2 returned the same first item, and page 4 returned eight cards. |
| Responsive visitor journeys | Chrome passed at 1440×900, 768×1024 and 390×844. Each run opened IGP, switched to ACC and back, entered ACC directly, navigated to the catalogue, combined four filters, refreshed, reset, went Back/Forward, opened a product and followed its prefilled contact link. Keyboard skip-link, mobile menu dismissal and form width were checked. |
| ACC behaviour and SEO | All 12 detail pages showed seven recorded specification fields and matching canonical URLs. Invalid make/model, material, unknown and repeated parameters, array inputs and long/empty-result searches showed no results. A missing product returned 404. Historical product and static catalogue paths reached the correct destination. The product sitemap contained 92 entries, including 12 nested ACC URLs. |
| Visitor failure paths | A blocked material image showed the text fallback. The lookup rendered under simulated 180 ms latency and constrained bandwidth. The suite interrupted an enquiry request at the browser transport and submitted the form again. |
| Quote persistence | Each division accepted a genuine browser multipart submission with a WebP attachment. Replaying its UUID returned the same receipt. Rapid repeat submission with a different ID returned 429. Fake success flags did not display a receipt. Mandatory fields, invalid email, invalid product ID, quantity, year, missing nonce, honeypot and mismatched upload were rejected. |
| Storage and mail failures | A QA-only database fault caused a real insert error and HTTP 503. A SQL check found zero enquiry rows for that failed insert. Submission could be retried. The local mail fixture rejected notifications, and staff saw the failed status on the retained record. |
| Administration | Admin login, dry run and two repeat imports passed in Chrome. Created an ACC product, assigned category and brand, edited vehicle fields, uploaded a featured image through the native Media Library, published, searched by SKU, edited, archived and restored it as a draft. The archived/restored draft URL returned 404 to a guest. |
| Staff access | Staff found an ACC enquiry by its brand, read the submitted details, downloaded byte-identical private media, updated its status and saw the persisted value. Staff were denied products/settings. The catalogue editor could list ACC products and was denied enquiries, imports and users. Guests received no downloadable private attachment. |
| Recovery | Exported the disposable database with mariadb-dump and restored into a separate database. Post counts by type and status, all source IDs, and stored private attachment hashes matched. |
| Visual review | Inspected rendered desktop and mobile screenshots of the IGP homepage, ACC homepage and ACC product grid. Adjusted the IGP hero text width. ACC uses the original vehicle photo and separate material imagery, with mobile controls stacking at narrower widths. |

## Reproduce the browser checks

1. Set up a disposable single-site WordPress installation on 127.0.0.1, using the stack above or equivalent. Use a separate database and the Post name permalink structure. Install both ZIPs, run the catalogue importer and leave all 92 source products published. Disable external email for this installation.
2. Install Chrome and Python 3 with the playwright package. Use a test administrator named qa_owner and two test users named igp_catalogue_editor and igp_enquiry_staff with their matching custom roles. Give them the local-only test password supplied through IGP_QA_PASSWORD. IGP_QA_ADMIN can override the administrator name. Set IGP_QA_URL to the site's loopback origin if it differs from http://127.0.0.1:18756.
3. On the disposable site, define IGP_LOCAL_QA as true in wp-config.php and copy tools/qa/fixtures.php into wp-content/mu-plugins/. Use a synthetic notification recipient ending in example.invalid in IGP Settings. The fixture simulates mail rejection and one named database failure. It is excluded from both packages.
4. Run the following from the source checkout. The browser suites create synthetic enquiries, media and a draft product. The third script reads all public detail pages and images. The fourth command uses a local WP-CLI installation pointed at the disposable site.

```sh
python tools/qa/browser.py
python tools/qa/admin.py
python tools/qa/catalogue.py
wp --path=/path/to/disposable-wordpress eval-file /path/to/checkout/tools/qa/import-integrity.php
```

## Corrections verified during QA

Testing exposed a syntax error during initial activation and a WordPress menu permission issue that prevented enquiry staff from opening their inbox. Both were corrected and retested through the installer or staff browser session. Additional checks caught the legacy tag parameter colliding with WordPress's blog tag routing. The corrected unknown-tag request returns an empty product catalogue with HTTP 200. The browser harness was also corrected for Chrome's file-button naming, form-field naming and synthetic drafts retained between runs.

Hosted TLS, Websouls account limits, actual SMTP delivery and the production site's end-to-end checks remain pending. KNOWN_LIMITATIONS.md records the outstanding work.
