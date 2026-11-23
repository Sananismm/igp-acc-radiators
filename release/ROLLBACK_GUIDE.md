# Backup and rollback

## Take a restorable snapshot

1. Record the target hostname, document root, active theme, active plugins, WordPress/PHP/database versions, permalink setting and current release hash.
2. In cPanel Backup/Backup Wizard or phpMyAdmin, export the complete WordPress database with its actual table prefix. Include options, users, usermeta, posts, postmeta, terms and relationships. Store the export off the public web root with restricted access.
3. In File Manager enable hidden files. Back up the entire WordPress document root, including wp-config.php, .htaccess, uploads, plugins and themes. Include the separately configured private attachment directory, if used. Store an off-host copy. Database fallback attachments travel with the SQL backup.
4. Restore files and SQL into an isolated staging or recovery root and separate database. Set that clone's home and siteurl appropriately. Use a serialized-data-aware WordPress migration tool for embedded URLs if the hostname changes. Protect the recovery site and prevent outbound customer mail.
5. Log in, open an existing product, check its image, view a private test enquiry and download its attachment. Record restore time and verify counts. Keep the named snapshot until the release is accepted.

## Revert a release

1. If pages, imports or records fail verification, record the time and failed checks. Put the affected environment in maintenance mode and capture a fresh copy of its current database and private files. Identify enquiries, edits and media created after the pre-release backup.
2. For a presentation-only failure, restore the previous compatible theme/plugin pair and previous active theme first. Save permalinks, purge caches and verify the saved customer records. Revert both code and data only if necessary.
3. For a database rollback, restore the verified pre-release SQL, matching WordPress files and matching private storage together. Retain the post-release snapshot. Reconcile newer enquiries and attachments under controlled access before reopening the site. Replacing a live database can erase those records.
4. Restore original site URLs, menu settings, cache policy and SMTP configuration. Test the home page, product routes, admin login, a private attachment and a stored test enquiry. Confirm actual email receipt separately.
5. Resume normal traffic only after the owner accepts the restored state and the outstanding enquiries have an assigned handler.

Account-specific production backups and an off-host restore drill require the hosting operator. The local catalogue-upgrade rehearsal is described in QA_TEST_REPORT.md.
