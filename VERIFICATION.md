# Local verification — 10 November 2026

Test environment: isolated WordPress Playground 7.1.4 with PHP 8.3 and its SQLite compatibility layer. This was not the existing website or production database.

## Verified

- The theme activated and the homepage rendered in a browser with the existing hero, navigation, typography, imagery and WhatsApp link.
- The administrator ran Tools → IGP catalogue import; the WordPress Products screen displayed 80 published products and the homepage showed eight categories with ten products each.
- The public catalogue rendered and reported 80 components. One product detail page and related products rendered with the expected image, content and WhatsApp URL.
- An administrator changed a test product's manufacturer and the updated value appeared on its public product page.
- The quote form submitted a test enquiry without an attachment, displayed confirmation and showed it as a private enquiry in the admin list.
- A second test enquiry with a sample PDF appeared as private. An authorized admin downloaded the attachment; its SHA-256 hash matched the original sample file.
- The enquiry editor and its status control were visible to the administrator after permissions were tightened.
- Source catalogue data contains eight categories, 80 products and 80 corresponding image files.

## Not yet verified

- The customer tests above were performed while signed in as the test administrator. Anonymous submissions and unauthorized attachment access still need a test on a conventional WordPress host with separate accounts. Playground's automatic login/redirect behavior made an unauthenticated HTTP check inconclusive.
- The screenshot.png is a logo placeholder for WordPress's theme selector, not a capture of the finished theme.
- Full visual comparison with the Next.js site, mobile breakpoints, all 80 product routes, cross-browser behavior, menu management, widgets, taxonomy editing, all product CRUD operations, the enquiry status save, security limits and failure/retry paths remain to be tested.
- PHP-FPM/Apache/nginx, MySQL/MariaDB behavior, 5 MB upload/server/database limits, backup/restore and real deployment were not tested. The local attachment test used a tiny sample file.
- Email notifications, production email delivery, migration of existing client accounts/enquiries/catalogue edits, exact functional parity with the Next.js admin and every catalogue filter, and final legal content are not implemented. See README.md.

Do not use this preliminary test as production sign-off. No live site, production database or DNS was changed.
