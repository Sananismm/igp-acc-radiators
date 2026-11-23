"""Exercise the native WordPress product editor and catalogue importer in Chrome."""
from browser import *

with sync_playwright() as pw:
    browser = pw.chromium.launch(channel=os.environ.get("IGP_QA_BROWSER","chrome"))
    p = browser.new_page()
    p.set_default_timeout(14000)
    login(p, os.environ.get("IGP_QA_ADMIN","qa_owner"))
    visit(p, "/wp-admin/tools.php?page=igp-import")
    for label in ["Dry run", "Import catalogues and pages", "Import catalogues and pages"]:
        p.get_by_role("button",name=label).click()
        expect(p.locator(".notice-success")).to_contain_text("Existing products preserved: 92")
        expect(p.locator(".notice-success")).to_contain_text("created: 0")
    passed("Admin login, browser dry run and two repeat imports preserving 92 source products")
    name = "QA radiator " + run
    sku = "QA-" + run
    visit(p,"/wp-admin/post-new.php?post_type=igp_product")
    p.locator("#title").fill(name)
    p.locator("[name=igp_brand]").select_option("acc")
    p.locator("[name=igp_part_number]").fill(sku)
    for key,value in {"make":"QA make","model":"QA model","years":"2020-2022","material":"All-Aluminum","thickness":"32"}.items():
        p.locator("[name=acc_"+key+"]").fill(value)
    p.locator("#acc_categorychecklist label").filter(has_text="All-Aluminum").locator("input").check()
    p.locator("#set-post-thumbnail").click()
    dialog=p.get_by_role("dialog")
    dialog.get_by_role("tab",name="Upload files").click()
    p.locator("input[type=file]").last.set_input_files(str(ROOT/"assets/acc/copper-brass.webp"))
    dialog.locator(".media-button-select").click()
    expect(p.locator("#set-post-thumbnail img")).to_be_visible()
    p.locator("#publish").click()
    expect(p.locator("#message")).to_contain_text("published")
    post_id=p.locator("#post_ID").input_value()
    public=p.locator("#sample-permalink a").get_attribute("href")
    guest=browser.new_page()
    guest.goto(public)
    expect(guest.locator("h1")).to_have_text(name)
    img=guest.locator(".acc-detail-photo img").get_attribute("src")
    assert "/uploads/" in img and guest.request.get(img).status==200
    visit(guest,"/acc-radiators/products/?q="+sku)
    expect(guest.locator(".radiator-card h3")).to_have_text(name)
    p.locator("[name=acc_thickness]").fill("34")
    p.locator("#publish").click()
    guest.goto(public)
    expect(guest.locator(".product-facts")).to_contain_text("34")
    visit(p,"/wp-admin/edit.php?post_type=igp_product&division=acc")
    row=p.locator("#post-"+post_id)
    row.hover()
    row.get_by_role("link",name="Archive",exact=True).click()
    assert guest.goto(public).status==404
    visit(p,"/wp-admin/edit.php?post_type=igp_product&post_status=igp_archived")
    row=p.locator("#post-"+post_id)
    row.hover()
    row.get_by_role("link",name="Restore as draft").click()
    assert guest.goto(public).status==404
    visit(p,"/wp-admin/post.php?post="+post_id+"&action=edit")
    expect(p.locator("[name=acc_thickness]")).to_have_value("34")
    expect(p.locator("#acc_categorychecklist input:checked")).to_have_count(1)
    passed("Native product creation, brand, category, metadata, media upload, publication, SKU search, edit, archive and draft restoration")
    browser.close()
