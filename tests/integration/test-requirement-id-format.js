/**
 * Requirement ID Format Validation — Test ID: REQ-FMT-001
 * @req FR-018-001 Feature-scoped IDs must use FR-NNN-XXX format
 * @req FR-018-002 Retroactive migration of all specs to new scheme
 * @req FR-018-003 CI gate rejects flat FR-XXX IDs
 * @req FR-018-004 Validation script checks for duplicate FR-NNN-XXX across specs
 *
 * Validates that all spec.md files in specs/ use feature-scoped requirement IDs.
 */

const fs = require('fs');
const path = require('path');

const specsDir = path.join(__dirname, '..', '..', 'specs');
const specDirs = fs.readdirSync(specsDir).filter(d => d.match(/^\d{3}-/));

let violations = 0;
const seenIds = new Map();

for (const dir of specDirs) {
  const specPath = path.join(specsDir, dir, 'spec.md');
  if (!fs.existsSync(specPath)) continue;

  const content = fs.readFileSync(specPath, 'utf8');
  const featureNum = dir.split('-')[0];
  const isMigrationSpec = featureNum === '018';

  // Find all feature-scoped requirement IDs
  const reqMatches = content.match(/FR-\d{3}-\d{3}/g) || [];
  const uniqueInFile = [...new Set(reqMatches)];

  for (const id of uniqueInFile) {
    // Migration spec (018) references other specs' IDs as examples — skip prefix check
    if (!isMigrationSpec) {
      const expectedPrefix = `FR-${featureNum}`;
      if (!id.startsWith(expectedPrefix)) {
        console.error(`VIOLATION: ${dir}/spec.md has requirement ID ${id} but feature folder is ${dir}`);
        violations++;
      }
    }

    // For migration spec, only track its own IDs (FR-018-XXX) for duplicate detection
    // References to other specs' IDs are intentional examples
    if (isMigrationSpec && !id.startsWith('FR-018')) {
      continue;
    }

    // Check for duplicates ACROSS different spec directories only
    if (seenIds.has(id)) {
      if (seenIds.get(id) !== dir) {
        console.error(`VIOLATION: Duplicate requirement ID ${id} in ${dir} and ${seenIds.get(id)}`);
        violations++;
      }
    } else {
      seenIds.set(id, dir);
    }
  }

  // Flag flat IDs (e.g., FR-001 without -XXX suffix) that are NOT part of a scoped ID
  const flatPattern = /(?<![A-Z])FR-(\d{3})(?!-\d{3})/g;
  let flatMatch;
  const flatSet = new Set();
  while ((flatMatch = flatPattern.exec(content)) !== null) {
    flatSet.add(flatMatch[0]);
  }
  for (const flat of flatSet) {
    const scopedVersion = `${flat}-001`;
    if (!reqMatches.includes(scopedVersion)) {
      console.error(`VIOLATION: ${dir}/spec.md contains flat requirement ID ${flat} without matching scoped form`);
      violations++;
    }
  }
}

if (violations === 0) {
  console.log('REQ-FMT-001: Requirement ID format validation PASSED');
  console.log(`  - ${specDirs.length} spec directories scanned`);
  console.log(`  - ${seenIds.size} unique feature-scoped requirement IDs found`);
  console.log('  - No cross-feature ID collisions');
  console.log('  - No orphaned flat IDs');
} else {
  console.error(`REQ-FMT-001 FAILED: ${violations} violation(s) found`);
  process.exit(1);
}
