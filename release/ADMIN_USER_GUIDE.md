# Administration

Sign in to /wp-admin/ with your own WordPress account. An administrator manages the two packages, settings, users and imports. Assign Catalogue editor to product staff and Enquiry staff to people handling customer information. Review inherited roles when upgrading.

## Products

1. Open Products. Use All brands to select IGP or ACC and then Filter. Each row shows its brand. Product categories organizes generator systems. Radiator categories organizes ACC material families. Part tags supports custom catalogue labels.
2. Choose Add product. Enter the display name, description and excerpt. Select one product brand. For IGP, choose a generator category and complete any available working principle, daily use, manufacturer, part number, OEM reference and compatibility fields. Featured selects homepage candidates.
3. For ACC, choose a radiator category and enter make, model, model years, material, construction, transmission and core thickness in mm. Use the material spelling already present in the catalogue. The part number is in Product details. Enter only confirmed information. Changing the brand changes its detail URL and clears category assignments for the other division.
4. Set featured image to use actual product photography. Enter an appropriate alt description in the Media Library. Additional images can be inserted with Add Media → Create gallery in the description. Imported records keep their supplied image when no featured image is set.
5. Save Draft, Preview and Publish. Check the detail page and search by part number. For ACC, confirm the make/model filter. The product's SEO title and description fields override automatic product copy.
6. Hover over a product row or focus its actions with the keyboard. Archive removes the product from public browsing and preserves its ID and metadata. Open Archived → Restore as draft to review and republish. Duplicate as draft creates a separate draft without the import identity. Review its part number before publication. Permanent deletion is an administrator operation.

## Enquiries and settings

1. Open Enquiries or its IGP/ACC submenu. Filter by brand. Existing unbranded enquiries belong to IGP. Open a record to see the submitted customer and part/vehicle details.
2. Use Download attachment within the record. Handle private files according to the company's approved retention policy. Change Enquiry status to New, Reviewing, Responded or Closed and click Update.
3. Review Email notification. “Accepted by the mailer” records WordPress's mail result. A failed or unconfigured notification leaves the private enquiry available for follow-up.
4. Settings → IGP Settings controls the published IGP contacts, service area and notification recipient. The ACC email is the address published in its source site. Menu links and gallery content use standard WordPress controls. Supply approved legal copy under Pages before publishing those pages.
5. Tools → Catalogue import provides preview and execution. Keep the import report and counts with the change record. See DATABASE_MIGRATION_GUIDE.md for conflict handling. Run a backup before a bulk operation.
