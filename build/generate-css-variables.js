const fs = require('fs');
const path = require('path');

const themePath = path.join(__dirname, 'theme.json');
const outputPath = path.join(__dirname, '..', 'web', 'tsisip', 'css', 'tsisip-variables.css');

const theme = JSON.parse(fs.readFileSync(themePath, 'utf8'));

let css = '/* TSiSIP Theme Variables - Generated from theme.json */\n';
css += ':root {\n';

// Colors
for (const [key, value] of Object.entries(theme.colors)) {
  const varName = '--tsisip-' + key.replace(/_/g, '-');
  css += `  ${varName}: ${value};\n`;
}

// Typography
for (const [key, value] of Object.entries(theme.typography)) {
  if (key === 'scale') continue;
  const varName = '--tsisip-' + key.replace(/_/g, '-');
  css += `  ${varName}: ${value};\n`;
}

// Scale
for (const [key, value] of Object.entries(theme.typography.scale)) {
  css += `  --tsisip-text-${key}: ${value};\n`;
}

// Breakpoints
for (const [key, value] of Object.entries(theme.breakpoints)) {
  css += `  --tsisip-breakpoint-${key}: ${value};\n`;
}

css += `  --tsisip-asset-version: "${theme.asset_version}";\n`;
css += '}\n';

fs.writeFileSync(outputPath, css);
console.log('Generated', outputPath);
