#!/bin/bash
set -e

echo "=== Building Msantha Farm Android APK ==="
WORK_DIR="/tmp/android_apk_build"
rm -rf "$WORK_DIR"
mkdir -p "$WORK_DIR/src/com/msanthafarm/app"
mkdir -p "$WORK_DIR/res/values"
mkdir -p "$WORK_DIR/res/mipmap-mdpi"
mkdir -p "$WORK_DIR/res/mipmap-hdpi"
mkdir -p "$WORK_DIR/res/mipmap-xhdpi"
mkdir -p "$WORK_DIR/res/mipmap-xxhdpi"
mkdir -p "$WORK_DIR/assets"
mkdir -p "$WORK_DIR/bin"
mkdir -p "$WORK_DIR/classes"

ANDROID_JAR="/usr/lib/android-sdk/platforms/android-23/android.jar"
DX="/usr/lib/android-sdk/build-tools/debian/dx"

# 1. Copy icons
cp public/pwa-192x192.png "$WORK_DIR/res/mipmap-mdpi/ic_launcher.png"
cp public/pwa-192x192.png "$WORK_DIR/res/mipmap-hdpi/ic_launcher.png"
cp public/pwa-192x192.png "$WORK_DIR/res/mipmap-xhdpi/ic_launcher.png"
cp public/pwa-512x512.png "$WORK_DIR/res/mipmap-xxhdpi/ic_launcher.png"

# 2. Strings
cat << 'EOF' > "$WORK_DIR/res/values/strings.xml"
<?xml version="1.0" encoding="utf-8"?>
<resources>
    <string name="app_name">Msantha Farm</string>
</resources>
EOF

# 3. AndroidManifest.xml
cat << 'EOF' > "$WORK_DIR/AndroidManifest.xml"
<?xml version="1.0" encoding="utf-8"?>
<manifest xmlns:android="http://schemas.android.com/apk/res/android"
    package="com.msanthafarm.app"
    android:versionCode="100"
    android:versionName="1.0.0">

    <uses-sdk
        android:minSdkVersion="21"
        android:targetSdkVersion="34" />

    <!-- Minimal, zero-intrusive permissions for maximum privacy on Android 13, 14 & 15 -->
    <uses-permission android:name="android.permission.INTERNET" />
    <uses-permission android:name="android.permission.ACCESS_NETWORK_STATE" />

    <application
        android:allowBackup="true"
        android:fullBackupOnly="true"
        android:usesCleartextTraffic="false"
        android:icon="@mipmap/ic_launcher"
        android:label="@string/app_name"
        android:supportsRtl="true"
        android:theme="@android:style/Theme.NoTitleBar">
        <activity
            android:name=".MainActivity"
            android:configChanges="orientation|screenSize|keyboardHidden"
            android:exported="true"
            android:hardwareAccelerated="true"
            android:launchMode="singleTask"
            android:windowSoftInputMode="adjustResize">
            <intent-filter>
                <action android:name="android.intent.action.MAIN" />
                <category android:name="android.intent.category.LAUNCHER" />
            </intent-filter>
        </activity>
    </application>
</manifest>
EOF

# 4. Java MainActivity
cat << 'EOF' > "$WORK_DIR/src/com/msanthafarm/app/MainActivity.java"
package com.msanthafarm.app;

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
import android.net.ConnectivityManager;
import android.net.NetworkInfo;
import android.content.Context;

public class MainActivity extends Activity {
    private WebView webView;
    private ProgressBar progressBar;
    // Live Cloud Run URL for Msantha Farm
    private static final String LIVE_URL = "https://ais-pre-fiedxz3qvhv7vhxov76col-150248851167.europe-west2.run.app/";
    private static final String OFFLINE_FALLBACK = "file:///android_asset/dist/index.html";

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

        // Offline and cache optimization
        if (isNetworkAvailable()) {
            settings.setCacheMode(WebSettings.LOAD_DEFAULT);
        } else {
            settings.setCacheMode(WebSettings.LOAD_CACHE_ELSE_NETWORK);
        }

        webView.setWebViewClient(new WebViewClient() {
            @Override
            public boolean shouldOverrideUrlLoading(WebView view, String url) {
                if (url.startsWith("http://") || url.startsWith("https://") || url.startsWith("file://")) {
                    view.loadUrl(url);
                    return true;
                }
                return false;
            }

            @Override
            public void onReceivedError(WebView view, int errorCode, String description, String failingUrl) {
                // If network fails, load local offline bundled application
                if (!failingUrl.startsWith("file:///")) {
                    view.loadUrl(OFFLINE_FALLBACK);
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

        // Load Live Server or Fallback
        if (isNetworkAvailable()) {
            webView.loadUrl(LIVE_URL);
        } else {
            webView.loadUrl(OFFLINE_FALLBACK);
        }
    }

    private boolean isNetworkAvailable() {
        try {
            ConnectivityManager cm = (ConnectivityManager) getSystemService(Context.CONNECTIVITY_SERVICE);
            NetworkInfo activeNetwork = cm.getActiveNetworkInfo();
            return activeNetwork != null && activeNetwork.isConnectedOrConnecting();
        } catch (Exception e) {
            return false;
        }
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
EOF

# 5. Copy built web app to assets for 100% offline fallback in rural areas
mkdir -p "$WORK_DIR/assets/dist"
cp -r dist/* "$WORK_DIR/assets/dist/"

# 6. Generate R.java using aapt
aapt package -f -m \
    -J "$WORK_DIR/src" \
    -M "$WORK_DIR/AndroidManifest.xml" \
    -S "$WORK_DIR/res" \
    -I "$ANDROID_JAR"

# 7. Compile Java code
javac -source 1.8 -target 1.8 \
    -cp "$ANDROID_JAR:$WORK_DIR/src" \
    -d "$WORK_DIR/classes" \
    "$WORK_DIR/src/com/msanthafarm/app/R.java" \
    "$WORK_DIR/src/com/msanthafarm/app/MainActivity.java"

# 8. Convert bytecode to Dalvik dex
"$DX" --dex --output="$WORK_DIR/bin/classes.dex" "$WORK_DIR/classes"

# 9. Package APK base resources
aapt package -f \
    -M "$WORK_DIR/AndroidManifest.xml" \
    -S "$WORK_DIR/res" \
    -A "$WORK_DIR/assets" \
    -I "$ANDROID_JAR" \
    -F "$WORK_DIR/bin/base.apk"

# 10. Add classes.dex to APK
cd "$WORK_DIR/bin"
/usr/bin/python3 -c "
import zipfile
with zipfile.ZipFile('base.apk', 'a') as z:
    z.write('classes.dex', 'classes.dex')
"
cd - > /dev/null

# 11. Generate release signing key if needed
KEYSTORE="$WORK_DIR/msantha-release.keystore"
keytool -genkeypair -v \
    -keystore "$KEYSTORE" \
    -alias msanthafarm \
    -keyalg RSA \
    -keysize 2048 \
    -validity 10000 \
    -storepass msantha2026 \
    -keypass msantha2026 \
    -dname "CN=Msantha Farm, OU=Poultry Division, O=Msantha, L=Liwonde, ST=Machinga, C=MW"

# 12. Zipalign the APK
zipalign -v -p 4 "$WORK_DIR/bin/base.apk" "$WORK_DIR/bin/aligned.apk"

# 13. Sign the APK with apksigner (v1 + v2 + v3 signing)
apksigner sign \
    --ks "$KEYSTORE" \
    --ks-key-alias msanthafarm \
    --ks-pass pass:msantha2026 \
    --key-pass pass:msantha2026 \
    --out "$WORK_DIR/msantha-poultry-farm.apk" \
    "$WORK_DIR/bin/aligned.apk"

# 14. Verify signature
apksigner verify -v "$WORK_DIR/msantha-poultry-farm.apk"

# 15. Publish APK to public directories for download
mkdir -p public/downloads
cp "$WORK_DIR/msantha-poultry-farm.apk" "public/downloads/msantha-poultry-farm.apk"
cp "$WORK_DIR/msantha-poultry-farm.apk" "public/msantha-poultry-farm.apk"

# Also package the source project into a clean zip
python3 -c "
import zipfile, os

zip_path = 'public/downloads/msantha-farm-android-source.zip'
with zipfile.ZipFile(zip_path, 'w', zipfile.ZIP_DEFLATED) as z:
    for root, dirs, files in os.walk('$WORK_DIR/src'):
        for file in files:
            full_path = os.path.join(root, file)
            rel_path = os.path.relpath(full_path, '$WORK_DIR')
            z.write(full_path, rel_path)
    for root, dirs, files in os.walk('$WORK_DIR/res'):
        for file in files:
            full_path = os.path.join(root, file)
            rel_path = os.path.relpath(full_path, '$WORK_DIR')
            z.write(full_path, rel_path)
    z.write('$WORK_DIR/AndroidManifest.xml', 'AndroidManifest.xml')
"

echo "=== APK Successfully Created & Verified! ==="
ls -lh public/msantha-poultry-farm.apk public/downloads/msantha-farm-android-source.zip
