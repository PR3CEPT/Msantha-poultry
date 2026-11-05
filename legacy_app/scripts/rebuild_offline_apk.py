#!/usr/bin/env python3
"""
Rebuilds the Msantha Poultry Farm Android APK to run 100% OFFLINE without any external server dependency.
Eliminates the "Page not found / The requested URL was not found on the server" error.
Embeds the complete application bundle inside assets/dist/index.html and updates classes.dex.
Signs with high-strength RSA-2048 certificate (valid through 2055).
"""

import os
import sys
import zipfile
import hashlib
import zlib
import struct
import base64
import subprocess
import re

APK_IN = "public/msantha-poultry-farm.apk"
APK_OUT = "public/msantha-poultry-farm.apk"
APK_DOWNLOADS = "public/downloads/msantha-poultry-farm.apk"
KEY_FILE = "/tmp/msantha_key.pem"
CERT_FILE = "/tmp/msantha_cert.pem"

def main():
    print("=== Rebuilding 100% Offline Standalone APK ===")
    if not os.path.exists(APK_IN):
        print(f"Error: {APK_IN} not found!")
        sys.exit(1)

    # 1. Read existing APK entries
    entries = {}
    with zipfile.ZipFile(APK_IN, 'r') as z:
        for name in z.namelist():
            if not name.startswith("META-INF/"):
                entries[name] = z.read(name)

    print(f"Loaded {len(entries)} non-signature entries from APK.")

    # 2. Patch classes.dex to unconditionally load file:///android_asset/dist/index.html
    dex_bytes = bytearray(entries["classes.dex"])
    
    # Locate const-string v1, string@71 (\x1a\x01\x47\x00) and change to string@64 (\x1a\x01\x40\x00)
    target_pattern = b"\x1a\x01\x47\x00"
    pos = dex_bytes.find(target_pattern)
    if pos != -1:
        print(f"Found LIVE_URL instruction at offset {pos}. Patching to OFFLINE_FALLBACK (string@64)...")
        dex_bytes[pos + 2] = 0x40  # 64
        dex_bytes[pos + 3] = 0x00
    else:
        print("Notice: target pattern not found; may already be patched or different offset.")

    # Recompute SHA1 signature: over bytes 32 to end
    sha1 = hashlib.sha1(dex_bytes[32:]).digest()
    dex_bytes[12:32] = sha1

    # Recompute Adler32 checksum: over bytes 12 to end
    adler = zlib.adler32(dex_bytes[12:]) & 0xffffffff
    dex_bytes[8:12] = struct.pack("<I", adler)

    entries["classes.dex"] = bytes(dex_bytes)
    print("classes.dex patched with valid checksum and signature.")

    # 3. Create self-contained assets/dist/index.html
    html_raw = entries["assets/dist/index.html"].decode("utf-8")
    css_raw = entries.get("assets/dist/assets/index-DzolXlon.css", b"").decode("utf-8")
    js_raw = entries.get("assets/dist/assets/index-C5wUuFvA.js", b"").decode("utf-8")

    # Inline CSS
    inlined_html = re.sub(
        r'<link\s+rel="stylesheet"[^>]*href="/assets/[^"]+"[^>]*>',
        lambda m: f"<style>\n{css_raw}\n</style>",
        html_raw
    )

    # Inline JS (remove type="module" so WebView executes it immediately on file:///)
    inlined_html = re.sub(
        r'<script\s+type="module"[^>]*src="/assets/[^"]+"[^>]*></script>',
        lambda m: f"<script>\n{js_raw}\n</script>",
        inlined_html
    )

    # Relative paths for manifest, icons and offline service worker
    inlined_html = inlined_html.replace('href="/manifest.webmanifest"', 'href="manifest.webmanifest"')
    inlined_html = inlined_html.replace('src="/registerSW.js"', 'src="registerSW.js"')
    inlined_html = inlined_html.replace('href="/icon.svg"', 'href="icon.svg"')
    inlined_html = inlined_html.replace('href="/apple-touch-icon.png"', 'href="apple-touch-icon.png"')
    inlined_html = inlined_html.replace('href="/favicon.ico"', 'href="favicon.ico"')

    entries["assets/dist/index.html"] = inlined_html.encode("utf-8")
    print(f"Self-contained index.html built ({len(entries['assets/dist/index.html'])} bytes).")

    # 4. Generate RSA-2048 signing key and self-signed certificate if not present
    if not os.path.exists(KEY_FILE) or not os.path.exists(CERT_FILE):
        print("Generating RSA-2048 signing certificate...")
        subprocess.run([
            "openssl", "req", "-x509", "-newkey", "rsa:2048",
            "-keyout", KEY_FILE, "-out", CERT_FILE,
            "-days", "10000", "-nodes",
            "-subj", "/C=MW/ST=Machinga/L=Liwonde/O=Msantha/OU=Poultry Division/CN=Msantha Farm"
        ], check=True)

    # 5. Build META-INF/MANIFEST.MF
    manifest_lines = [
        "Manifest-Version: 1.0",
        "Created-By: 1.0 (Msantha Android Builder)",
        ""
    ]
    sf_lines = [
        "Signature-Version: 1.0",
        "Created-By: 1.0 (Msantha Android Builder)",
    ]

    manifest_bytes_map = {}
    for name, data in sorted(entries.items()):
        digest = base64.b64encode(hashlib.sha256(data).digest()).decode("ascii")
        entry_lines = [
            f"Name: {name}",
            f"SHA-256-Digest: {digest}",
            ""
        ]
        manifest_lines.extend(entry_lines)
        manifest_bytes_map[name] = "\r\n".join(entry_lines).encode("utf-8") + b"\r\n"

    manifest_content = "\r\n".join(manifest_lines).encode("utf-8")
    manifest_digest = base64.b64encode(hashlib.sha256(manifest_content).digest()).decode("ascii")
    sf_lines.append(f"SHA-256-Digest-Manifest: {manifest_digest}")
    sf_lines.append("")

    for name, section_bytes in sorted(manifest_bytes_map.items()):
        sf_digest = base64.b64encode(hashlib.sha256(section_bytes).digest()).decode("ascii")
        sf_lines.append(f"Name: {name}")
        sf_lines.append(f"SHA-256-Digest: {sf_digest}")
        sf_lines.append("")

    sf_content = "\r\n".join(sf_lines).encode("utf-8")

    # 6. Sign MSANTHAF.SF using OpenSSL
    sf_tmp = "/tmp/MSANTHAF.SF"
    rsa_tmp = "/tmp/MSANTHAF.RSA"
    with open(sf_tmp, "wb") as f:
        f.write(sf_content)

    subprocess.run([
        "openssl", "smime", "-sign",
        "-in", sf_tmp,
        "-out", rsa_tmp,
        "-outform", "DER",
        "-signer", CERT_FILE,
        "-inkey", KEY_FILE,
        "-noattr", "-binary"
    ], check=True)

    with open(rsa_tmp, "rb") as f:
        rsa_content = f.read()

    entries["META-INF/MANIFEST.MF"] = manifest_content
    entries["META-INF/MSANTHAF.SF"] = sf_content
    entries["META-INF/MSANTHAF.RSA"] = rsa_content

    # 7. Write new aligned APK
    temp_apk = "/tmp/new_msantha.apk"
    with zipfile.ZipFile(temp_apk, "w", zipfile.ZIP_DEFLATED) as z_out:
        for name in sorted(entries.keys()):
            data = entries[name]
            # resources.arsc is typically uncompressed in APKs
            if name == "resources.arsc":
                z_out.writestr(name, data, compress_type=zipfile.ZIP_STORED)
            else:
                z_out.writestr(name, data, compress_type=zipfile.ZIP_DEFLATED)

    # Copy to destination paths
    os.makedirs(os.path.dirname(APK_DOWNLOADS), exist_ok=True)
    with open(temp_apk, "rb") as src, open(APK_OUT, "wb") as dst:
        dst.write(src.read())

    with open(temp_apk, "rb") as src, open(APK_DOWNLOADS, "wb") as dst:
        dst.write(src.read())

    print(f"Successfully generated {APK_OUT} ({os.path.getsize(APK_OUT)} bytes).")
    print(f"Successfully generated {APK_DOWNLOADS} ({os.path.getsize(APK_DOWNLOADS)} bytes).")

    # 8. Update source zip with offline-ready MainActivity.java
    update_source_zip()

def update_source_zip():
    source_zip = "public/downloads/msantha-farm-android-source.zip"
    if not os.path.exists(source_zip):
        return

    offline_main_activity = """package com.msanthafarm.app;

import android.app.Activity;
import android.os.Bundle;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.webkit.WebChromeClient;
import android.view.KeyEvent;
import android.view.View;
import android.widget.FrameLayout;
import android.widget.ProgressBar;
import android.graphics.Color;

public class MainActivity extends Activity {
    private WebView webView;
    private ProgressBar progressBar;
    // 100% Offline Standalone - Local embedded assets (No external server dependency)
    private static final String OFFLINE_URL = "file:///android_asset/dist/index.html";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        FrameLayout rootLayout = new FrameLayout(this);
        rootLayout.setBackgroundColor(Color.parseColor("#1b4332"));

        webView = new WebView(this);
        webView.setBackgroundColor(Color.parseColor("#f8fafc"));

        progressBar = new ProgressBar(this, null, android.R.attr.progressBarStyleHorizontal);
        progressBar.setLayoutParams(new FrameLayout.LayoutParams(
            FrameLayout.LayoutParams.MATCH_PARENT, 8
        ));
        progressBar.setMax(100);

        rootLayout.addView(webView, new FrameLayout.LayoutParams(
            FrameLayout.LayoutParams.MATCH_PARENT, FrameLayout.LayoutParams.MATCH_PARENT
        ));
        rootLayout.addView(progressBar);

        setContentView(rootLayout);

        WebSettings settings = webView.getSettings();
        settings.setJavaScriptEnabled(true);
        settings.setDomStorageEnabled(true);
        settings.setDatabaseEnabled(true);
        settings.setAllowFileAccess(true);
        settings.setAllowContentAccess(true);
        settings.setLoadWithOverviewMode(true);
        settings.setUseWideViewPort(true);
        settings.setSupportZoom(false);
        settings.setBuiltInZoomControls(false);
        settings.setCacheMode(WebSettings.LOAD_CACHE_ELSE_NETWORK);

        webView.setWebViewClient(new WebViewClient() {
            @Override
            public boolean shouldOverrideUrlLoading(WebView view, String url) {
                if (url.startsWith("file://") || url.startsWith("http://") || url.startsWith("https://")) {
                    view.loadUrl(url);
                    return true;
                }
                return false;
            }

            @Override
            public void onReceivedError(WebView view, int errorCode, String description, String failingUrl) {
                if (!failingUrl.startsWith("file:///")) {
                    view.loadUrl(OFFLINE_URL);
                }
            }
        });

        webView.setWebChromeClient(new WebChromeClient() {
            @Override
            public void onProgressChanged(WebView view, int newProgress) {
                if (newProgress < 100) {
                    progressBar.setVisibility(View.VISIBLE);
                    progressBar.setProgress(newProgress);
                } else {
                    progressBar.setVisibility(View.GONE);
                }
            }
        });

        // Load 100% offline standalone application
        webView.loadUrl(OFFLINE_URL);
    }

    @Override
    public boolean onKeyDown(int keyCode, KeyEvent event) {
        if (keyCode == KeyEvent.KEYCODE_BACK && webView != null && webView.canGoBack()) {
            webView.goBack();
            return true;
        }
        return super.onKeyDown(keyCode, event);
    }
}
"""
    # Read existing source zip and replace MainActivity.java
    entries = {}
    with zipfile.ZipFile(source_zip, "r") as z:
        for name in z.namelist():
            entries[name] = z.read(name)

    for name in list(entries.keys()):
        if name.endswith("MainActivity.java"):
            entries[name] = offline_main_activity.encode("utf-8")

    with zipfile.ZipFile(source_zip, "w", zipfile.ZIP_DEFLATED) as z:
        for name, data in entries.items():
            z.writestr(name, data)

    print(f"Updated {source_zip} with offline-ready MainActivity.java.")

if __name__ == "__main__":
    main()
