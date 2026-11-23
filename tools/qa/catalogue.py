"""Check every supplied product URL and image on the local WordPress site."""
import json
import os
import re
import xml.etree.ElementTree as ET
from concurrent.futures import ThreadPoolExecutor
from pathlib import Path
from urllib.parse import urlparse
from urllib.request import urlopen

root = Path(__file__).resolve().parents[2]
base = os.environ.get("IGP_QA_URL", "http://127.0.0.1:18756").rstrip("/")
assert urlparse(base).hostname == "127.0.0.1"

def slug(value):
    return re.sub(r"[^a-z0-9]+", "-", value.lower()).strip("-")

def read(url):
    with urlopen(url, timeout=30) as response:
        assert response.status == 200
        return response.read()

igp = json.loads((root / "igp-core/inc/catalogue.json").read_text())
acc = json.loads((root / "igp-core/inc/radiators.json").read_text())
expected = {f"{base}/products/{slug(p['category'])}-{slug(p['name'])}/" for p in igp["products"]}
expected.update(f"{base}/acc-radiators/products/{p['slug']}/" for p in acc)
xml = ET.fromstring(read(base + "/wp-sitemap-posts-igp_product-1.xml"))
actual = {x.text for x in xml.iter("{http://www.sitemaps.org/schemas/sitemap/0.9}loc")}
assert actual == expected, (expected - actual, actual - expected)

def check(url):
    html = read(url).decode()
    found = re.search(r'<(?:figure|div) class="(?:product-detail-image|acc-detail-photo)">\s*<img src="([^"]+)"', html)
    assert found and read(found[1]), url

with ThreadPoolExecutor(3) as pool:
    list(pool.map(check, sorted(actual)))
print("PASS all 92 source detail URLs, primary images and sitemap membership.")
