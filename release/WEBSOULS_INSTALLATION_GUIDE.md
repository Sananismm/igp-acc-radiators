# Websouls cPanel installation

## Prepare staging

1. In cPanel → Domains, locate staging.igp.com.pk and record its document root. Configure its own root and a separate database through the hosting account. Check the DNS A/AAAA or CNAME with the host and confirm a valid HTTPS certificate in SSL/TLS Status. At release preparation the staging hostname returned NXDOMAIN.
2. Install WordPress into that root using the host's installer, or clone the current WordPress site into the staging root and database. Verify the WordPress Address and Site Address both point to the staging HTTPS origin. Restrict the staging directory through cPanel Directory Privacy. In Settings → Reading, enable “Discourage search engines”.
3. Check Tools → Site Health → Info and cPanel's PHP selector. The code needs PHP 8.1+, mysqli, mbstring and fileinfo. Enable GD or Imagick with WebP support for staff uploads. The tested stack is listed in QA_TEST_REPORT.md. WordPress recommends PHP 8.3+, MariaDB 10.11+ or MySQL 8.4+, HTTPS and working pretty permalinks. [WordPress requirements](https://wordpress.org/about/requirements/)
4. Check InnoDB for the WordPress options, posts, postmeta and taxonomy tables. The database account needs normal database read/write privileges, InnoDB transactions, table-engine visibility and named locks. Allow PHP uploads of at least 5 MB and a larger POST body. For database-backed attachments, allow a database packet above 7 MB plus overhead. Confirm disk quota for media, packages, database growth and backups.
5. For an existing installation, complete the backup and restore rehearsal in ROLLBACK_GUIDE.md. Record active theme slug, menu locations, custom logo and contact settings.

## Install the two archives

1. Match both ZIP hashes against SHA256SUMS.txt. Open cPanel → File Manager and select the staging domain's document root. A domain installed at the account web root uses public_html/wp-content. For staging, use the domain-specific root recorded above.
2. Upload igp-unified-theme.zip inside wp-content/themes and select Extract into that directory. Confirm wp-content/themes/igp-unified/style.css. Upload igp-unified-core.zip inside wp-content/plugins and extract there. Confirm wp-content/plugins/igp-core/igp-core.php. Each archive already contains its top-level folder. [cPanel File Manager](https://docs.cpanel.net/cpanel/files/file-manager/)
3. On an upgrade, use a maintenance window. Deactivate the old IGP Core if installed. After backup, move its existing directory outside the web root before extracting the replacement. Keep a single registered copy. Activate the new IGP Unified theme first on older theme-only installations, then activate IGP Unified Core under Plugins. On a fresh installation either activation order works. The unified theme shows a setup message until its compatible plugin is active.
4. Alternatively, upload the two ZIPs through Appearance → Themes → Add Theme → Upload Theme and Plugins → Add Plugin → Upload Plugin. Check the host upload limit against the actual ZIP sizes. Remove uploaded ZIPs from web-accessible directories after extraction.
5. Under Settings → Permalinks select Post name and save. If WordPress cannot write .htaccess, apply the rewrite rules it displays through cPanel File Manager with hidden files visible. Verify direct entry to a nested product URL.
6. Open Tools → Catalogue import → Dry run. Review the counts and resolve any conflict. Select Import catalogues and pages. Run Dry run again. A fresh installation should now show 0 products to create, 92 preserved and 0 pages to create. Follow DATABASE_MIGRATION_GUIDE.md for existing data.
7. Keep the site root assigned to IGP. On a fresh install use Settings → Reading → Your latest posts. The theme's front-page.php renders the IGP homepage. Use the generated nested pages at /acc-radiators/. Check any existing custom home, page slug and menu settings before switching an existing site.
8. Review Settings → IGP Settings, the two brand homepages and all catalogue links. If the former theme used a custom menu or logo, select it for this theme in Appearance. Review privacy and terms pages. Publish only approved copy.

## Mail, caching and private attachments

- Create or use the approved sending mailbox. Obtain the host's SMTP server, port, encryption mode and authentication details from cPanel Email Accounts → Connect Devices or Websouls support. Configure an authenticated WordPress mail transport with matching From address. Keep credentials in the hosting secret store or protected configuration. Enter the approved notification address in Settings → IGP Settings.
- Send the transport's test message, then a real staging enquiry from each division. Confirm the correct private dashboard record, notification status and actual inbox receipt. Review spam, SMTP logs and domain authentication if delivery fails. Staff can retrieve saved records in Enquiries.
- Exclude /request-quote/, /acc-radiators/contact/, all receipt URLs, wp-admin and admin-post.php from shared page caches. Vary catalogue cache entries by their filter query strings. Purge cached listings after changes. Per-form submission identifiers and nonces must be generated for each visitor.
- Attachments default to private post metadata. For filesystem storage, create a private writable directory outside every hosted document root, including sibling domains. Set IGP_PRIVATE_STORAGE_DIR to its actual absolute path in wp-config.php before the stop-editing line. Back it up with the database. See SECURITY_AUDIT.md.

## Production release

Use PRODUCTION_VERIFICATION_CHECKLIST.md on staging. Obtain explicit approval for the release, a current backup and a verified restore point. Repeat the package installation and dry run on the actual igp.com.pk WordPress database. Preserve enquiries created after staging was cloned. Verify https://igp.com.pk and /acc-radiators/ with the production configuration. Keep staging restrictions on staging and enable indexing on production after approval. The staging DNS entry and account-specific cPanel steps require the hosting operator.
