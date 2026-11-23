# Security review

The review covered the merged PHP handlers, public templates, importer, queries, WordPress capabilities and attachment paths. Runtime checks used synthetic data in a loopback WordPress installation. Account permissions, other installed plugins, host controls and production mail handling require staging verification.

| Control | Implementation and verification |
|---|---|
| Product administration | Custom product capabilities separate the catalogue editor from general site administration. Product saves check post-level authorization and nonces. Catalogue editors can publish and archive. Permanent deletion is reserved for administrators. The editor was denied access to import, user management and enquiries. |
| Enquiry privacy | igp_enquiry is private, excluded from public queries and REST, and forced to private on ordinary saves. Private file downloads require the dedicated enquiry capability, access to the specific record and a matching nonce. The guest file request returned no attachment. Staff downloaded the exact uploaded bytes. |
| Input and display | Brand allowlist, form-specific nonces, maximum field lengths, email, quantity, year and published same-brand product validation run server-side. Malformed arrays are rejected at submission. Templates escape text, attributes, URLs and JSON by context. Search uses WP_Query and prepared LIKE patterns. Filter values never become SQL identifiers. |
| Uploads | Check upload error, origin, size, WordPress filetype/extension and independently detected MIME. Limit attachments to 5 MB of JPEG, PNG, WebP or PDF. Serve as attachment with nosniff, a sanitized name and no-cache headers. Store privately in postmeta or a configured non-web directory with random binary filenames. Attachments are not public Media Library objects. |
| Abuse and retries | A honeypot, database-serialized UUID submissions, hashed IP/email throttles and a per-IP lock protect the write path. Replay of a completed token for the same brand/email returns its prior receipt. A fresh submission in the throttle window received HTTP 429. |
| Failures | Transactional post, metadata and receipt writes complete before success. A forced database insert failure returned HTTP 503 and the form could retry. Email runs after commit. Simulated mail rejection appeared as failed in the staff interface and left the enquiry retrievable. |
| Import safety | Administrator-only POST form with nonce, source identity checks, named lock and per-product transactions. A legacy migration preserved the existing draft, edited title and ID. Repeated imports created zero records. |
| Links and secrets | Public links derive from WordPress URL functions, and redirects target known local routes. Dedicated test fixtures and credentials are excluded from the ZIPs. Browser/API tests use synthetic accounts on loopback only. |

## Hosting-dependent controls

Enable HTTPS, protect backups and private media, and restrict error-log access. Database errors can contain submitted values. Keep public debug display off. Validate every domain alias before choosing a filesystem storage location. If a disk-backed write is interrupted after its file is written, the private directory can contain an unreferenced file. Reconcile references before purging private files under the company's retention policy. Basic file-type checks do not provide malware scanning.

The throttles are application-level controls and use the server's REMOTE_ADDR. A reverse proxy must supply a trustworthy client address through host configuration. Review traffic limits and caches with Websouls. A current host backup and monitored mail transport are operational prerequisites for release.
