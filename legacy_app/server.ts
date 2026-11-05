import express from 'express';
import path from 'path';
import fs from 'fs';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const PORT = Number(process.env.PORT) || 3000;
const rootDir = process.cwd();
const distDir = path.resolve(rootDir, 'dist');

// Middleware to serve static downloads with exact headers
app.get(['/msantha-poultry-farm.apk', '/downloads/msantha-poultry-farm.apk'], (req, res) => {
  const filePath = path.resolve(distDir, 'msantha-poultry-farm.apk');
  const fallbackPath = path.resolve(rootDir, 'public/msantha-poultry-farm.apk');
  const target = fs.existsSync(filePath) ? filePath : fallbackPath;
  if (fs.existsSync(target)) {
    const stat = fs.statSync(target);
    res.writeHead(200, {
      'Content-Type': 'application/vnd.android.package-archive',
      'Content-Length': stat.size,
      'Content-Disposition': 'attachment; filename="msantha-poultry-farm.apk"',
      'Cache-Control': 'no-cache',
    });
    fs.createReadStream(target).pipe(res);
    return;
  }
  res.status(404).send('APK file not found');
});

// Serve static assets from dist
app.use(express.static(distDir, {
  maxAge: '1d',
  etag: true,
}));

// SPA Fallback: for any other URL, serve index.html
app.get('*', (req, res) => {
  const indexPath = path.resolve(distDir, 'index.html');
  if (fs.existsSync(indexPath)) {
    res.sendFile(indexPath);
  } else {
    res.status(200).send(`<!DOCTYPE html><html><head><title>Loading Msantha Farm...</title><meta http-equiv="refresh" content="2"></head><body style="font-family:sans-serif;padding:2rem;text-align:center;"><h2>Starting Msantha Farm...</h2><p>Please wait a moment while the application initializes.</p></body></html>`);
  }
});

app.listen(PORT, '0.0.0.0', () => {
  console.log(`Msantha Poultry Farm server running on http://0.0.0.0:${PORT}`);
});
