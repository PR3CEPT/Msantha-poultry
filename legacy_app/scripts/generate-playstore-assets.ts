import fs from 'fs';
import path from 'path';
import sharp from 'sharp';

async function generatePlayStoreAssets() {
  const publicDir = path.resolve('public');

  // Google Play Store Feature Graphic: 1024 x 500
  const featureGraphicSvg = `
  <svg xmlns="http://www.w3.org/2000/svg" width="1024" height="500" viewBox="0 0 1024 500">
    <defs>
      <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
        <stop offset="0%" stop-color="#081c15" />
        <stop offset="40%" stop-color="#1b4332" />
        <stop offset="100%" stop-color="#2d6a4f" />
      </linearGradient>
      <linearGradient id="gold" x1="0%" y1="0%" x2="100%" y2="0%">
        <stop offset="0%" stop-color="#fbbf24" />
        <stop offset="100%" stop-color="#f59e0b" />
      </linearGradient>
      <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
        <path d="M 40 0 L 0 0 0 40" fill="none" stroke="#52b788" stroke-width="1" opacity="0.08" />
      </pattern>
    </defs>

    <!-- Background -->
    <rect width="1024" height="500" fill="url(#bg)" />
    <rect width="1024" height="500" fill="url(#grid)" />

    <!-- Ambient Glow Circles -->
    <circle cx="180" cy="250" r="180" fill="#40916c" opacity="0.25" />
    <circle cx="850" cy="120" r="150" fill="#fbbf24" opacity="0.1" />

    <!-- Left Badge / Icon Plate -->
    <g transform="translate(100, 100)">
      <rect width="180" height="180" rx="40" fill="#1b4332" stroke="#52b788" stroke-width="3" opacity="0.9" />
      <circle cx="90" cy="90" r="65" fill="#ffffff" fill-opacity="0.95" />
      <!-- Stylized Hen/Egg -->
      <path d="M 85 45 C 75 45 60 65 60 95 C 60 120 75 135 90 135 C 105 135 120 120 120 95 C 120 65 105 45 85 45 Z" fill="#1b4332" />
      <polygon points="102,68 116,74 102,80" fill="#f59e0b" />
      <circle cx="92" cy="70" r="3" fill="#fbbf24" />
      <path d="M 75 52 C 75 46 82 44 86 48 C 90 43 97 44 98 50 Z" fill="#ef4444" />
    </g>

    <!-- Main Branding & Typography -->
    <text x="320" y="150" font-family="system-ui, -apple-system, sans-serif" font-weight="900" font-size="44" fill="#ffffff" letter-spacing="1">MSANTHA POULTRY FARM</text>
    <text x="320" y="195" font-family="system-ui, -apple-system, sans-serif" font-weight="700" font-size="22" fill="#fbbf24" letter-spacing="2">OFFICIAL MOBILE APP • LIWONDE, MALAWI</text>

    <!-- Subtitle / Value Proposition Badges -->
    <g transform="translate(320, 230)">
      <rect width="210" height="36" rx="10" fill="#081c15" stroke="#40916c" stroke-width="1.5" />
      <text x="105" y="23" font-family="system-ui, -apple-system, sans-serif" font-weight="700" font-size="13" fill="#d8f3dc" text-anchor="middle">⚡ 100% Offline Database</text>
    </g>

    <g transform="translate(545, 230)">
      <rect width="200" height="36" rx="10" fill="#081c15" stroke="#40916c" stroke-width="1.5" />
      <text x="100" y="23" font-family="system-ui, -apple-system, sans-serif" font-weight="700" font-size="13" fill="#d8f3dc" text-anchor="middle">🐣 Digital Incubators</text>
    </g>

    <g transform="translate(760, 230)">
      <rect width="180" height="36" rx="10" fill="#081c15" stroke="#40916c" stroke-width="1.5" />
      <text x="90" y="23" font-family="system-ui, -apple-system, sans-serif" font-weight="700" font-size="13" fill="#d8f3dc" text-anchor="middle">💰 MWK Cashbook</text>
    </g>

    <!-- Feature Description -->
    <text x="320" y="320" font-family="system-ui, -apple-system, sans-serif" font-weight="500" font-size="16" fill="#b7e4c7" opacity="0.95">
      Comprehensive poultry management system for Mikolongwe, Sasso, Ducks, Quails &amp; Turkeys.
    </text>
    <text x="320" y="348" font-family="system-ui, -apple-system, sans-serif" font-weight="500" font-size="16" fill="#b7e4c7" opacity="0.95">
      Includes commercial egg incubation, customer WhatsApp alerts, feed inventory &amp; exact age pricing.
    </text>

    <!-- Bottom Bar with Google Play Store Verified Info -->
    <line x1="320" y1="390" x2="940" y2="390" stroke="#40916c" stroke-width="1" opacity="0.4" />
    <text x="320" y="425" font-family="system-ui, -apple-system, sans-serif" font-weight="700" font-size="13" fill="#74c69d">
      PACKAGE: com.msanthafarm.app  •  MANAGING DIRECTOR &amp; SALES MANAGER RBAC  •  LIWONDE
    </text>
  </svg>
  `;

  const featureGraphicPath = path.join(publicDir, 'playstore-feature-graphic.png');
  await sharp(Buffer.from(featureGraphicSvg))
    .png()
    .toFile(featureGraphicPath);
  console.log('Generated Google Play feature graphic: 1024x500 at', featureGraphicPath);

  // High-Res 512x512 Google Play Icon
  const iconSource = path.join(publicDir, 'icon.svg');
  if (fs.existsSync(iconSource)) {
    const iconBuffer = fs.readFileSync(iconSource);
    const playStoreIconPath = path.join(publicDir, 'playstore-icon-512.png');
    await sharp(iconBuffer)
      .resize(512, 512)
      .png()
      .toFile(playStoreIconPath);
    console.log('Generated Google Play 512x512 store icon at', playStoreIconPath);
  }
}

generatePlayStoreAssets().catch((err) => {
  console.error('Asset generation failed:', err);
  process.exit(1);
});
