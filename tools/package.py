"""Build installable WordPress packages from explicit production paths."""
from hashlib import sha256
from pathlib import Path
from zipfile import ZipFile, ZipInfo, ZIP_DEFLATED

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "release"
OUT.mkdir(exist_ok=True)

def package(name, prefix, files, base):
    target = OUT / name
    with ZipFile(target, "w") as archive:
        for file in sorted(files):
            info = ZipInfo(f"{prefix}/{file.relative_to(base).as_posix()}", (2026, 1, 1, 0, 0, 0))
            info.external_attr = 0o100644 << 16
            data = file.read_bytes()
            if file.suffix in {".php", ".css", ".js", ".json", ".svg"}:
                data = data.replace(b"\r\n", b"\n")
            archive.writestr(info, data, compress_type=ZIP_DEFLATED)
    with ZipFile(target) as archive:
        assert archive.testzip() is None
    return f"{sha256(target.read_bytes()).hexdigest()}  {name}"

theme = [*ROOT.glob("*.php"), ROOT / "style.css", ROOT / "screenshot.png", ROOT / "inc/division-theme.php"]
for directory in ["template-parts", "assets/css", "assets/js", "assets/acc"]:
    theme.extend(p for p in (ROOT / directory).rglob("*") if p.is_file())
theme.extend(p for p in (ROOT / "assets/images").glob("*") if p.is_file() and p.suffix != ".png")
plugin = ROOT / "igp-core"
hashes = [package("igp-unified-theme.zip","igp-unified",theme,ROOT),
          package("igp-unified-core.zip","igp-core",[p for p in plugin.rglob("*") if p.is_file()],plugin)]
(OUT / "SHA256SUMS.txt").write_text("\n".join(hashes)+"\n",encoding="ascii")
print("\n".join(hashes))
