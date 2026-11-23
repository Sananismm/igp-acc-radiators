# IGP + ACC Radiators

This repository contains the unified WordPress theme and IGP Unified Core plugin. IGP serves the site root, and ACC uses /acc-radiators/. The local release contains 80 generator parts and 12 radiator listings.

Install the two production archives in [release](release/). Follow the [Websouls installation guide](release/WEBSOULS_INSTALLATION_GUIDE.md), [database migration guide](release/DATABASE_MIGRATION_GUIDE.md) and [verification checklist](release/PRODUCTION_VERIFICATION_CHECKLIST.md). Review [QA results](release/QA_TEST_REPORT.md) and [remaining hosting checks](release/KNOWN_LIMITATIONS.md) before release.

The theme source is at the repository root. The plugin source is in igp-core/. Build both ZIPs with Python 3 using the command below.

```sh
python tools/package.py
```

The browser regression tests live in tools/qa/. The QA report describes fixture setup and execution. The root-level reports inherited from the foundation describe earlier revisions. Current release documentation is in release/.
