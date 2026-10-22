#!/usr/bin/env python3
"""
CampusLend Static Demo Exporter
Exports a static, design-only preview of the local PHP application
for publishing to GitHub Pages from the 'docs/' directory.

Uses Python 3 standard library only.
"""

import os
import sys
import re
import shutil
import urllib.request
import urllib.error
import urllib.parse
from html.parser import HTMLParser

BASE_URL = "http://127.0.0.1:8000"
PROJECT_ROOT = os.path.dirname(os.path.abspath(__file__))
DOCS_DIR = os.path.join(PROJECT_ROOT, "docs")
PUBLIC_DIR = os.path.join(PROJECT_ROOT, "public")
SUBPATH = "/campuslend"
MIN_PAGE_SIZE = 2048  # 2 KB

PAGES = [
    ("/", "index.html"),
    ("/how-it-works", os.path.join("how-it-works", "index.html")),
    ("/login", os.path.join("login", "index.html")),
    ("/register", os.path.join("register", "index.html")),
]

BANNER_HTML = f"""
<!-- Static Preview Announcement Banner (Injected by export_static.py) -->
<div class="static-preview-banner" style="background: linear-gradient(90deg, #1e1b4b 0%, #312e81 50%, #1e1b4b 100%); color: #e0e7ff; padding: 10px 16px; font-size: 0.85rem; font-weight: 500; text-align: center; border-bottom: 1px solid rgba(99, 102, 241, 0.4); display: flex; align-items: center; justify-content: center; gap: 8px; z-index: 10001; position: relative;">
    <span style="font-size: 1rem;">ℹ️</span>
    <span>Static design preview – login, feed and chat need the PHP backend. Run locally to use the full app.</span>
</div>
"""

FAVICON_TAG = f'<link rel="icon" type="image/png" href="{SUBPATH}/assets/img/icon-192.png">\n'


def fetch_url(url: str) -> bytes:
    """Fetch content from the local PHP server."""
    req = urllib.request.Request(
        url,
        headers={
            "User-Agent": "CampusLend-Static-Exporter/1.0",
            "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
        },
    )
    with urllib.request.urlopen(req, timeout=10) as response:
        return response.read()


def is_local_url(url: str) -> bool:
    """Determine if a URL belongs to the local site rather than an external CDN."""
    if not url or url.startswith("#") or url.startswith("mailto:") or url.startswith("tel:") or url.startswith("javascript:"):
        return False
    if url.startswith("http://127.0.0.1:8000") or url.startswith("http://localhost:8000"):
        return True
    if url.startswith("http://") or url.startswith("https://") or url.startswith("//"):
        return False
    return True


def normalize_local_path(url: str) -> str:
    """Strip protocol and local host from local URLs."""
    for host in ["http://127.0.0.1:8000", "http://localhost:8000"]:
        if url.startswith(host):
            url = url[len(host):]
            break
    if not url.startswith("/"):
        url = "/" + url
    return url


def rewrite_link(url: str) -> str:
    """
    Rewrite internal links to match GitHub Pages subpath /campuslend/.
    Leaves external CDNs and Google Fonts untouched.
    """
    if not url or url.startswith("#") or url.startswith("mailto:") or url.startswith("tel:") or url.startswith("javascript:"):
        return url

    # Leave external links untouched
    if (url.startswith("http://") or url.startswith("https://") or url.startswith("//")) and \
       not url.startswith("http://127.0.0.1:8000") and not url.startswith("http://localhost:8000"):
        return url

    norm = normalize_local_path(url)
    path_part, _, query = norm.partition("?")
    q_str = f"?{query}" if query else ""

    # Map root and internal page routes
    if path_part in ["", "/"]:
        return f"{SUBPATH}/"
    elif path_part == "/how-it-works":
        return f"{SUBPATH}/how-it-works/"
    elif path_part == "/login":
        return f"{SUBPATH}/login/"
    elif path_part == "/register":
        return f"{SUBPATH}/register/"
    elif path_part.startswith("/assets/") or path_part.startswith("/uploads/"):
        return f"{SUBPATH}{path_part}{q_str}"
    elif path_part == "/manifest.json":
        return f"{SUBPATH}/manifest.json"
    elif path_part == "/sw.js":
        return f"{SUBPATH}/sw.js"
    elif path_part in ["/feed", "/items", "/leaderboard"]:
        # Pages requiring backend: point back to root or preview with category if any
        return f"{SUBPATH}/{q_str}"
    elif path_part.startswith("/requests/") or path_part.startswith("/items/"):
        return f"{SUBPATH}/login/"
    else:
        # Default subpath rewrite
        return f"{SUBPATH}{path_part}{q_str}"


def download_asset(asset_rel_path: str):
    """Download a referenced asset from local PHP server and save under docs/."""
    clean_path = asset_rel_path.split("?")[0].lstrip("/")
    target_file = os.path.join(DOCS_DIR, clean_path)

    # First check if the file exists directly in public/ directory
    local_source = os.path.join(PUBLIC_DIR, clean_path)
    if os.path.exists(local_source) and os.path.isfile(local_source):
        os.makedirs(os.path.dirname(target_file), exist_ok=True)
        shutil.copy2(local_source, target_file)
        return

    # Otherwise fetch from the local server
    asset_url = f"{BASE_URL}/{clean_path}"
    try:
        data = fetch_url(asset_url)
        os.makedirs(os.path.dirname(target_file), exist_ok=True)
        with open(target_file, "wb") as f:
            f.write(data)
    except Exception as e:
        print(f"  [WARN] Could not fetch asset {asset_url}: {e}")


def process_css_file(css_path: str):
    """Find all url(...) references in a CSS file, download assets, and rewrite paths."""
    if not os.path.exists(css_path):
        return

    with open(css_path, "r", encoding="utf-8", errors="ignore") as f:
        content = f.read()

    url_pattern = re.compile(r'url\(\s*[\'"]?([^\'")]+)[\'"]?\s*\)')
    matches = url_pattern.findall(content)

    for m in matches:
        if is_local_url(m):
            norm = normalize_local_path(m)
            download_asset(norm)

    def css_url_repl(match):
        raw = match.group(1).strip("'\"")
        if is_local_url(raw):
            rewritten = rewrite_link(raw)
            return f'url("{rewritten}")'
        return match.group(0)

    updated = url_pattern.sub(css_url_repl, content)
    with open(css_path, "w", encoding="utf-8") as f:
        f.write(updated)


def neutralize_main_js(js_path: str):
    """
    Disable service worker registration (sw.js) and AJAX notification polling
    in docs/assets/js/main.js to ensure the console stays clean without touching PHP source.
    """
    if not os.path.exists(js_path):
        return

    with open(js_path, "r", encoding="utf-8") as f:
        js = f.read()

    # Neutralize initNotifications (stop setInterval and /api/... fetch)
    js = re.sub(
        r'initNotifications\s*:\s*function\s*\(\)\s*\{[\s\S]*?\},',
        '''initNotifications: function () {
      // Neutralized for static preview: bell polling disabled
      const bellBadge = document.getElementById('notification-badge');
      if (bellBadge) bellBadge.style.display = 'none';
    },''',
        js,
    )

    # Neutralize initPWA (stop navigator.serviceWorker.register)
    js = re.sub(
        r'initPWA\s*:\s*function\s*\(\)\s*\{[\s\S]*?\},',
        '''initPWA: function () {
      // Neutralized for static preview: service worker registration disabled
      if ('serviceWorker' in navigator) {
        navigator.serviceWorker.getRegistrations().then(regs => {
          for (let reg of regs) reg.unregister();
        }).catch(() => {});
      }
    },''',
        js,
    )

    with open(js_path, "w", encoding="utf-8") as f:
        f.write(js)


def neutralize_forms(html: str) -> str:
    """Neutralize form submissions in static previews with friendly toasts."""
    form_handler = """
<script>
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(function(f) {
        f.addEventListener('submit', function(e) {
            e.preventDefault();
            if (window.CampusLend && window.CampusLend.toast) {
                window.CampusLend.toast('Static design preview – login & registration require the local PHP backend.', 'warning');
            } else {
                alert('Static design preview – login & registration require the local PHP backend.');
            }
            return false;
        });
    });
});
</script>
"""
    if "</body>" in html:
        return html.replace("</body>", f"{form_handler}\n</body>")
    return html + form_handler


def process_html(html: str) -> str:
    """
    Rewrite local attributes (href, src, action), inject banner,
    favicon link, and neutralize forms for static preview.
    """
    # 1. Rewrite tag attributes: href, src, action
    attr_pattern = re.compile(r'(\b(?:href|src|action)\s*=\s*["\'])([^"\']+)(["\'])', re.IGNORECASE)

    def attr_repl(match):
        prefix = match.group(1)
        val = match.group(2)
        suffix = match.group(3)
        return f"{prefix}{rewrite_link(val)}{suffix}"

    rewritten_html = attr_pattern.sub(attr_repl, html)

    # 2. Also replace any remaining absolute localhost/127.0.0.1 references
    rewritten_html = rewritten_html.replace("http://127.0.0.1:8000", SUBPATH)
    rewritten_html = rewritten_html.replace("http://localhost:8000", SUBPATH)

    # 3. Inject favicon link in <head>
    if "<head>" in rewritten_html:
        rewritten_html = rewritten_html.replace("<head>", f"<head>\n    {FAVICON_TAG}", 1)

    # 4. Inject preview banner right after <body>
    body_match = re.search(r'<body[^>]*>', rewritten_html, re.IGNORECASE)
    if body_match:
        end_pos = body_match.end()
        rewritten_html = rewritten_html[:end_pos] + "\n" + BANNER_HTML + rewritten_html[end_pos:]
    else:
        rewritten_html = BANNER_HTML + "\n" + rewritten_html

    # 5. Neutralize interactive forms
    rewritten_html = neutralize_forms(rewritten_html)

    return rewritten_html


def export():
    print("==============================================================")
    print("      CampusLend - Static Design Preview Exporter")
    print("==============================================================")

    # 1. Verify local server is reachable
    print(f"\n[1/7] Probing PHP server at {BASE_URL}...")
    try:
        data = fetch_url(f"{BASE_URL}/")
        print(f"  [OK] Server responded: 200 OK ({len(data):,} bytes)")
    except Exception as e:
        print(f"  [ERROR] Cannot connect to local PHP server at {BASE_URL}!")
        print(f"          Reason: {e}")
        print("          Please ensure 'php -S 127.0.0.1:8000 -t public' is running.")
        sys.exit(1)

    # 2. Clean and create docs/ directory
    print(f"\n[2/7] Preparing output directory: {DOCS_DIR}...")
    os.makedirs(DOCS_DIR, exist_ok=True)

    # 3. Copy all static assets from public/assets/ into docs/assets/
    print("\n[3/7] Copying assets from public/ to docs/...")
    src_assets = os.path.join(PUBLIC_DIR, "assets")
    dst_assets = os.path.join(DOCS_DIR, "assets")
    if os.path.exists(src_assets):
        shutil.copytree(src_assets, dst_assets, dirs_exist_ok=True)
        print("  [OK] Copied public/assets/ -> docs/assets/")

    # Also copy uploads if present
    src_uploads = os.path.join(PUBLIC_DIR, "uploads")
    dst_uploads = os.path.join(DOCS_DIR, "uploads")
    if os.path.exists(src_uploads):
        shutil.copytree(src_uploads, dst_uploads, dirs_exist_ok=True)
        print("  [OK] Copied public/uploads/ -> docs/uploads/")

    # 4. Export manifest.json
    print("\n[4/7] Exporting manifest.json...")
    manifest_src = os.path.join(PUBLIC_DIR, "manifest.json")
    manifest_dst = os.path.join(DOCS_DIR, "manifest.json")
    if os.path.exists(manifest_src):
        with open(manifest_src, "r", encoding="utf-8") as f:
            manifest_content = f.read()
        manifest_content = manifest_content.replace('"/assets/', f'"{SUBPATH}/assets/')
        manifest_content = manifest_content.replace('"start_url": "/"', f'"start_url": "{SUBPATH}/"')
        with open(manifest_dst, "w", encoding="utf-8") as f:
            f.write(manifest_content)
        print("  [OK] Saved rewritten manifest.json")

    # 5. Fetch and export HTML pages
    print("\n[5/7] Fetching and processing pages...")
    saved_pages = []

    for route, out_rel in PAGES:
        page_url = f"{BASE_URL}{route}"
        out_file = os.path.join(DOCS_DIR, out_rel)
        os.makedirs(os.path.dirname(out_file), exist_ok=True)

        print(f"  Fetching {page_url}...")
        raw_html = fetch_url(page_url).decode("utf-8")
        processed_html = process_html(raw_html)

        # Write to file
        with open(out_file, "w", encoding="utf-8") as f:
            f.write(processed_html)

        size = os.path.getsize(out_file)
        saved_pages.append((route, out_rel, size))
        print(f"    -> Saved {out_rel} ({size:,} bytes)")

        if size < MIN_PAGE_SIZE:
            print(f"  [ERROR] Page {out_rel} size {size} bytes is under {MIN_PAGE_SIZE} bytes (2 KB)!")
            sys.exit(1)

    # 6. Neutralize and process CSS and JS
    print("\n[6/7] Post-processing CSS and JS for static deployment...")
    css_file = os.path.join(DOCS_DIR, "assets", "css", "main.css")
    if os.path.exists(css_file):
        process_css_file(css_file)
        print("  [OK] Processed main.css url(...) assets")

    js_file = os.path.join(DOCS_DIR, "assets", "js", "main.js")
    if os.path.exists(js_file):
        neutralize_main_js(js_file)
        print("  [OK] Neutralized sw.js registration and notification polling in main.js")

    # 7. Create special files: .nojekyll, favicon.ico, sw.js stub, 404.html
    print("\n[7/7] Creating static deployment metadata files...")
    # .nojekyll
    nojekyll_path = os.path.join(DOCS_DIR, ".nojekyll")
    with open(nojekyll_path, "w", encoding="utf-8") as f:
        f.write("")
    print("  [OK] Created docs/.nojekyll")

    # favicon.ico (fallback)
    icon_src = os.path.join(DOCS_DIR, "assets", "img", "icon-192.png")
    favicon_dst = os.path.join(DOCS_DIR, "favicon.ico")
    if os.path.exists(icon_src):
        shutil.copy2(icon_src, favicon_dst)
        print("  [OK] Created docs/favicon.ico")

    # sw.js (clean stub that unregisters)
    sw_dst = os.path.join(DOCS_DIR, "sw.js")
    with open(sw_dst, "w", encoding="utf-8") as f:
        f.write("""// CampusLend PWA - Static preview stub
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', () => self.clients.claim());
""")
    print("  [OK] Created docs/sw.js stub")

    # 404.html (friendly fallback)
    index_file = os.path.join(DOCS_DIR, "index.html")
    notfound_file = os.path.join(DOCS_DIR, "404.html")
    if os.path.exists(index_file):
        with open(index_file, "r", encoding="utf-8") as f:
            idx_content = f.read()
        with open(notfound_file, "w", encoding="utf-8") as f:
            f.write(idx_content)
        print("  [OK] Created docs/404.html fallback")

    # Final summary report
    print("\n==============================================================")
    print("                 EXPORT COMPLETED SUCCESSFULLY                ")
    print("==============================================================")
    print(f"{'Route':<20} | {'Saved Path':<30} | {'Size':>10}")
    print("-" * 66)
    for route, rel, sz in saved_pages:
        print(f"{route:<20} | {rel:<30} | {sz:>8,} B")
    print("-" * 66)
    print("All pages exceed the 2 KB requirement. Static demo is ready for Pages.")


if __name__ == "__main__":
    export()
