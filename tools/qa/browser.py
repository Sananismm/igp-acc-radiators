"""Run on a disposable loopback WordPress installation seeded with both catalogues."""
import os
import re
import uuid
from pathlib import Path
from urllib.parse import urlparse
from playwright.sync_api import sync_playwright, expect

BASE = os.environ.get("IGP_QA_URL", "http://127.0.0.1:18756").rstrip("/")
assert urlparse(BASE).hostname == "127.0.0.1"
ROOT = Path(__file__).resolve().parents[2]
run = uuid.uuid4().hex[:10]
checks = []


def passed(name):
    checks.append(name)
    print(f"PASS {name}", flush=True)


def visit(p, path, status=200):
    response = p.goto(BASE + path, wait_until="domcontentloaded")
    assert response.status == status, (path, response.status)
    return response


def login(p, user):
    visit(p, "/wp-login.php")
    p.locator("#user_login").fill(user)
    p.locator("#user_pass").fill(os.environ["IGP_QA_PASSWORD"])
    p.locator("#wp-submit").click()
    assert "/wp-admin/" in p.url, p.url


def viewport_tests(browser, width, height):
    p = browser.new_page(viewport={"width": width, "height": height})
    p.set_default_timeout(8000)
    errors = []
    p.on("pageerror", lambda error: errors.append(str(error)))
    visit(p, "/")
    expect(p.locator("h1")).to_contain_text("Dependable")
    bar = p.get_by_role("navigation", name="Choose a brand")
    bar.get_by_text("ACC Radiators").click()
    expect(bar.locator("[aria-current]")).to_have_text("ACC Radiators")
    expect(p.locator("h1")).to_contain_text("radiator.")
    bar.get_by_text("Innovative Generator Parts").click()
    expect(p.locator("h1")).to_contain_text("Dependable")
    visit(p, "/acc-radiators/about/")
    if width <= 780:
        p.locator(".mobile-nav summary").click()
        p.locator(".mobile-nav").get_by_text("Radiators", exact=True).click()
    else:
        p.locator(".desktop-nav").get_by_text("Radiators", exact=True).click()
    expect(p.locator(".radiator-card")).to_have_count(12)
    f = p.locator(".vehicle-filters")
    f.locator("[name=make]").select_option("Toyota")
    f.locator("[name=model]").select_option("Hilux")
    f.locator("[name=mat]").select_option("All-Aluminum")
    f.locator("[name=q]").fill("1635")
    f.get_by_role("button").click()
    expect(p.locator(".radiator-card")).to_have_count(1)
    filtered = p.url
    p.reload()
    expect(f.locator("[name=model]")).to_have_value("Hilux")
    p.get_by_text("Reset filters").click()
    expect(p.locator(".radiator-card")).to_have_count(12)
    p.go_back()
    expect(p.locator(".radiator-card")).to_have_count(1)
    assert p.url == filtered
    p.go_forward()
    expect(p.locator(".radiator-card")).to_have_count(12)
    p.locator(".radiator-card h3 a").first.click()
    expect(p.locator(".product-facts dt")).to_have_count(7)
    p.get_by_text("Enquire about this radiator").click()
    expect(p.locator("[name=part_number]")).not_to_have_value("")
    assert p.evaluate("document.documentElement.scrollWidth <= innerWidth")
    visit(p, "/")
    p.keyboard.press("Tab")
    expect(p.locator(".skip-link")).to_be_focused()
    p.keyboard.press("Enter")
    expect(p.locator("#main")).to_be_focused()
    if width <= 780:
        p.locator(".mobile-nav summary").focus()
        p.keyboard.press("Enter")
        expect(p.locator(".mobile-nav")).to_have_attribute("open", "")
        p.keyboard.press("Escape")
        expect(p.locator(".mobile-nav summary")).to_be_focused()
    assert not errors, errors
    passed(f"{width}×{height} brand switching, direct navigation, filters, history, detail, contact, keyboard and overflow")
    p.close()


def visitor_tests(p):
    visit(p, "/categories/")
    urls = p.locator(".category-index a").evaluate_all("links=>links.map(a=>a.pathname)")
    assert len(urls) == 8
    for path in urls:
        visit(p, path)
        assert p.locator(".product-card").count() > 0
    for query, count in [("", 80), ("?query=Automatic+Voltage+Regulator", 2), ("?query=unlisted_xyz", 0)]:
        visit(p, "/products/" + query)
        expect(p.locator(".results-count")).to_contain_text(str(count) + " components")
    passed("Eight IGP system archives, 80 generator records, AVR synonym and empty search")
    visit(p, "/acc-radiators/products/")
    urls = p.locator(".radiator-card h3 a").evaluate_all("links=>links.map(a=>a.pathname)")
    for path in urls:
        visit(p, path)
        expect(p.locator(".product-facts dt")).to_have_count(7)
        expect(p.locator('link[rel="canonical"]')).to_have_attribute("href", BASE + path)
    for params in ["?make=Honda&model=Alto", "?model=Xyz", "?mat=Other", "?q=no-such-radiator", "?make=Honda&make=KIA", "?make[]=Honda", "?unrecognized=1", "?q="+"x"*201]:
        visit(p, "/acc-radiators/products/"+params)
        expect(p.locator(".radiator-card")).to_have_count(0)
        expect(p.locator(".empty-state")).to_be_visible()
    visit(p, "/acc-radiators/products/xyz-missing/", 404)
    visit(p, "/products/suzuki-alto/")
    assert p.url.endswith("/acc-radiators/products/suzuki-alto/")
    visit(p, "/acc-radiators/products.html?make=Honda")
    expect(p.locator(".radiator-card")).to_have_count(2)
    sitemap = p.request.get(BASE+"/wp-sitemap-posts-igp_product-1.xml").text()
    assert sitemap.count("<loc>") == 92 and sitemap.count("/acc-radiators/products/") == 12
    passed("All 12 detail pages, canonical URLs, eight empty or malformed searches, historical URLs, 404 and sitemap")
    p.route("**/acc-plastic-aluminium.webp", lambda route: route.abort())
    visit(p, "/acc-radiators/products/?model=Alto")
    expect(p.get_by_text("Image unavailable")).to_be_visible()
    p.unroute("**/acc-plastic-aluminium.webp")
    session = p.context.new_cdp_session(p)
    session.send("Network.enable")
    session.send("Network.emulateNetworkConditions", {"offline":False,"latency":180,"downloadThroughput":125000,"uploadThroughput":60000})
    visit(p,"/acc-radiators/?")
    expect(p.get_by_role("button",name="Find a radiator")).to_be_visible()
    session.detach()
    passed("Missing-image fallback and vehicle lookup on a throttled connection")


def submit_tests(p):
    for brand, path in [("acc","/acc-radiators/contact/?product=suzuki-alto"),("igp","/request-quote/?product=engine-parts-engine-block")]:
        visit(p, path)
        form = p.locator(".quote-form")
        form.locator("[name=name]").fill(f"QA {brand} {run}")
        form.locator("[name=email]").fill(f"{run}-{brand}@example.invalid")
        form.locator("[name=message]").fill("Please review this catalogue application.")
        form.locator("[name=quantity]").fill("2")
        if brand == "acc":
            form.locator("[name=vehicle_year]").fill("2022")
        else:
            form.locator("[name=generator_model]").fill("QA generator")
        values = form.evaluate("f=>Object.fromEntries([...new FormData(f)].filter(([k])=>k!=='attachment'))")
        endpoint = BASE + "/wp-admin/admin-post.php"
        if brand == "acc":
            for overrides, status in [({"name":""},422),({"quantity":"0"},422),({"email":"invalid"},422),({"_wpnonce":""},403),({"product_id":"99999999"},422),({"vehicle_year":"0"},422),({"website":"filled"},422)]:
                r = p.request.post(endpoint,form={**values,**overrides})
                assert r.status == status, (overrides,r.status)
            failed = p.request.post(endpoint,form={**values,"name":"QA storage failure"})
            assert failed.status == 503 and "could not confirm" in failed.text()
            p.route("**/admin-post.php",lambda r:r.abort(),times=1)
            interrupted = form.evaluate("""async f => {
                try { await fetch(f.getAttribute('action'), {method:'POST', body:new FormData(f)}); return false; }
                catch { return true; }
            }""")
            assert interrupted
            bad_upload = p.request.post(endpoint,multipart={**values,"attachment":{
                "name":"reference.webp","mimeType":"image/webp","buffer":b"plain test document"}})
            assert bad_upload.status == 422
        form.locator("[name=attachment]").set_input_files(str(ROOT/"assets/acc/logo.webp"))
        form.get_by_role("button",name="Send enquiry").click()
        expect(p.get_by_role("status")).to_contain_text("received")
        assert "receipt=" in p.url
        replay = p.request.post(endpoint,form=values,max_redirects=0)
        assert replay.status == 303 and replay.headers["location"] == p.url
        again = p.request.post(endpoint,form={**values,"submission_id":str(uuid.uuid4())})
        assert again.status == 429
    visit(p,"/request-quote/?submitted=1")
    assert not p.get_by_role("status").count()
    visit(p,"/acc-radiators/contact/?product=missing-record")
    expect(p.get_by_role("status")).to_contain_text("unavailable")
    passed("Two stored form submissions, attachment upload, identical retry receipts, rate limits, invalid inputs, failed write and interrupted submission")


def staff_tests(browser):
    p = browser.new_page(accept_downloads=True)
    login(p,"igp_enquiry_staff")
    visit(p,"/wp-admin/edit.php?post_type=igp_enquiry&division=acc")
    row = p.locator("#the-list tr").filter(has_text="QA acc "+run)
    expect(row).to_have_count(1)
    row.locator(".row-title").click()
    expect(p.locator("#igp_notification")).to_contain_text("Sending failed")
    expect(p.locator("#content")).to_have_value(re.compile("Suzuki Alto"))
    p.locator("#igp_status").select_option("responded")
    p.locator("#publish").click()
    expect(p.locator("#igp_status")).to_have_value("responded")
    url = p.get_by_text("Download attachment").get_attribute("href")
    attachment=p.request.get(url)
    assert attachment.status==200 and attachment.body()==(ROOT/"assets/acc/logo.webp").read_bytes()
    guest=browser.new_page()
    assert "attachment;" not in guest.request.get(url).headers.get("content-disposition","")
    guest.close()
    visit(p,"/wp-admin/edit.php?post_type=igp_product",403)
    visit(p,"/wp-admin/options-general.php",403)
    p.close()
    p=browser.new_page()
    login(p,"igp_catalogue_editor")
    visit(p,"/wp-admin/edit.php?post_type=igp_product&division=acc&post_status=publish")
    expect(p.locator("#the-list .row-title")).to_have_count(12)
    visit(p,"/wp-admin/edit.php?post_type=igp_enquiry",403)
    visit(p,"/wp-admin/tools.php?page=igp-import",403)
    visit(p,"/wp-admin/users.php",403)
    p.close()
    passed("Staff retrieval, brand filtering, attachment bytes, failed-mail status, status update, guest and restricted-role boundaries")


if __name__ == "__main__":
    with sync_playwright() as pw:
        browser = pw.chromium.launch(channel=os.environ.get("IGP_QA_BROWSER","chrome"))
        for width,height in [(1440,900),(768,1024),(390,844)]:
            viewport_tests(browser,width,height)
        p=browser.new_page()
        visitor_tests(p)
        submit_tests(p)
        staff_tests(browser)
        p.close()
        browser.close()
    print(f"Completed {len(checks)} scenario groups.")
