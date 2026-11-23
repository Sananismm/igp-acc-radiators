# Work implemented on the feature branch

- Preserved an enquiry when attachment metadata persistence cannot be confirmed.
- Added preliminary `igp-core` plugin and theme fallback. Product/enquiry post type identifiers remain unchanged. Install the compatible updated theme before activating the plugin. Older theme versions unconditionally load the same functions; the plugin avoids loading under that configuration.
- Added product metadata fields, a scoped catalogue metadata search, initial business settings and optional notification mail. Delivery is not guaranteed by `wp_mail` returning true.
- Added a unique submission reservation and non-cached quote-page headers. Pending/uncertain reservations require manual investigation; concurrency, cache and database-failure behavior still require testing.
- Added optional private directory storage with database fallback and compatibility with existing base64 attachments. No automatic deletion or retention cleanup is performed.

These changes are preliminary and do not represent all requirements in the task. Tests and limitations are in TEST_RESULTS.md.
