import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import path from 'path';
import {defineConfig} from 'vite';
import {VitePWA} from 'vite-plugin-pwa';

import fs from 'fs';

export default defineConfig(() => {
  const rootDir = process.cwd();

  const apkDownloadPlugin = {
    name: 'serve-apk-downloads',
    configureServer(server: any) {
      server.middlewares.use((req: any, res: any, next: any) => {
        const urlPath = req.url ? req.url.split('?')[0] : '';
        if (urlPath === '/msantha-poultry-farm.apk' || urlPath === '/downloads/msantha-poultry-farm.apk') {
          const filePath = path.resolve(rootDir, 'public/msantha-poultry-farm.apk');
          if (fs.existsSync(filePath)) {
            const stat = fs.statSync(filePath);
            res.writeHead(200, {
              'Content-Type': 'application/vnd.android.package-archive',
              'Content-Length': stat.size,
              'Content-Disposition': 'attachment; filename="msantha-poultry-farm.apk"',
              'Cache-Control': 'no-cache',
            });
            fs.createReadStream(filePath).pipe(res);
            return;
          }
        }
        if (urlPath.endsWith('.zip') || urlPath.includes('/downloads/')) {
          const fileName = path.basename(urlPath);
          const filePath = path.resolve(rootDir, 'public/downloads', fileName);
          if (fs.existsSync(filePath)) {
            const stat = fs.statSync(filePath);
            res.writeHead(200, {
              'Content-Type': 'application/zip',
              'Content-Length': stat.size,
              'Content-Disposition': `attachment; filename="${fileName}"`,
              'Cache-Control': 'no-cache',
            });
            fs.createReadStream(filePath).pipe(res);
            return;
          }
        }
        next();
      });
    },
  };

  return {
    plugins: [
      apkDownloadPlugin,
      react(),
      tailwindcss(),
      VitePWA({
        registerType: 'autoUpdate',
        includeAssets: ['favicon.ico', 'apple-touch-icon.png', 'icon.svg'],
        manifest: {
          id: '/',
          name: 'Msantha Poultry Farm Manager',
          short_name: 'MsanthaFarm',
          description: 'Poultry Farm Operations & Commercial Incubation Management for Msantha Poultry Farm, Liwonde, Malawi.',
          theme_color: '#1b4332',
          background_color: '#f8fafc',
          display: 'standalone',
          start_url: '/',
          scope: '/',
          icons: [
            {
              src: '/pwa-192x192.png',
              sizes: '192x192',
              type: 'image/png',
              purpose: 'any',
            },
            {
              src: '/pwa-512x512.png',
              sizes: '512x512',
              type: 'image/png',
              purpose: 'any',
            },
            {
              src: '/pwa-maskable-512x512.png',
              sizes: '512x512',
              type: 'image/png',
              purpose: 'maskable',
            },
          ],
        },
        workbox: {
          globPatterns: ['**/*.{js,css,html,ico,png,svg,woff,woff2}'],
        },
        devOptions: {
          enabled: true,
        },
      }),
    ],
    resolve: {
      alias: {
        '@': path.resolve(__dirname, '.'),
      },
    },
    server: {
      // HMR is disabled in AI Studio via DISABLE_HMR env var.
      // Do not modify—file watching is disabled to prevent flickering during agent edits.
      hmr: process.env.DISABLE_HMR !== 'true',
      // Disable file watching when DISABLE_HMR is true to save CPU during agent edits.
      watch: process.env.DISABLE_HMR === 'true' ? null : {},
    },
  };
});
