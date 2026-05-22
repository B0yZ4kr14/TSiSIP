const fs = require('fs');
const path = require('path');
const crypto = require('crypto');

const webDir = path.join(__dirname, '..', 'web', 'tsisip');
const manifestPath = path.join(webDir, 'asset-manifest.json');

// Matches files already containing a hash segment: name.abcdef12.ext
const alreadyHashed = /^(.+)\.([a-f0-9]{8})\.(css|js|svg)$/i;

function hashFile(filePath) {
  const content = fs.readFileSync(filePath);
  return crypto.createHash('md5').update(content).digest('hex').slice(0, 8);
}

function hashAndRename(dir, pattern) {
  const files = fs.readdirSync(dir).filter(f => {
    if (!pattern.test(f)) return false;
    // Skip files that already have a hash segment
    if (alreadyHashed.test(f)) return false;
    return true;
  });

  // Clean up previously hashed copies (keep only originals + one hashed copy)
  const allFiles = fs.readdirSync(dir);
  for (const f of allFiles) {
    const m = f.match(alreadyHashed);
    if (m) {
      const originalName = m[1] + '.' + m[3];
      if (!fs.existsSync(path.join(dir, originalName))) {
        // orphaned hashed file, remove
        fs.unlinkSync(path.join(dir, f));
      }
    }
  }

  const result = {};
  for (const file of files) {
    const filePath = path.join(dir, file);
    if (!fs.statSync(filePath).isFile()) continue;
    const ext = path.extname(file);
    const base = path.basename(file, ext);
    const hash = hashFile(filePath);
    const hashedName = `${base}.${hash}${ext}`;
    const hashedPath = path.join(dir, hashedName);
    fs.copyFileSync(filePath, hashedPath);
    result[file] = hashedName;
    result[`${base}${ext}`] = hashedName;
  }
  return result;
}

const manifest = {
  version: Date.now().toString(36),
  generated: new Date().toISOString(),
  assets: {}
};

// CSS files
const cssDir = path.join(webDir, 'css');
if (fs.existsSync(cssDir)) {
  manifest.assets.css = hashAndRename(cssDir, /\.css$/);
}

// JS files
const jsDir = path.join(webDir, 'js');
if (fs.existsSync(jsDir)) {
  manifest.assets.js = hashAndRename(jsDir, /\.js$/);
}

// SVG assets
const assetsDir = path.join(webDir, 'assets');
if (fs.existsSync(assetsDir)) {
  manifest.assets.svg = hashAndRename(assetsDir, /\.svg$/);
}

fs.writeFileSync(manifestPath, JSON.stringify(manifest, null, 2));
console.log('Generated manifest:', manifestPath);
