# Outstanding release conditions

| Area | Verified state and remaining action |
|---|---|
| Hosted staging | A DNS lookup for staging.igp.com.pk returned NXDOMAIN. No Websouls cPanel or staging WordPress session was supplied. Provision the separate staging root and certificate, then complete the installation and verification checklist. |
| Production | The release was built and exercised locally. Live DNS, files, database and WordPress configuration require separate release authorization. Record the production backup, restore point and post-release verification at that time. |
| SMTP | Local tests exercised the failed-mail and durable-storage paths. Real mailbox credentials, sender authentication and inbox receipt require staging verification. Staff can retrieve enquiries when delivery fails. |
| Business approval | ACC photos represent material types. The year ranges, transmission details, dimensions, construction and published contact channels came from the sources. Confirm fitment and availability with the business. Approved legal and retention text, hours and any additional commercial claims remain owner decisions. |
| Additional live content | The archives include the two public seed catalogues. A separate live Next.js database, Blob store or customized WordPress installation could contain more products, redirects, users or enquiries. Inventory these before release. |
| Hosting variations | Validation used a single-site WordPress installation, PHP 8.4 and MariaDB on Windows. Test Websouls' actual PHP/MySQL versions, Apache rewrites, filesystem permissions, proxy settings, object/page caches, private directory mapping and database packet size. |
| Devices and load | Browser coverage used Chrome at desktop, tablet and mobile viewport sizes. Physical iOS/Android devices, Safari/Firefox, assistive-technology review and production load were outside the executed test matrix. |
| Historical domains | Previous external hosts need redirects at those origins. The package handles local canonical routes and old ACC section page paths. No legacy-domain DNS or server access was supplied. |
