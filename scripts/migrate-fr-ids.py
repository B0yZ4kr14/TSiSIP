#!/usr/bin/env python3
"""Migrate flat FR-XXX IDs to feature-scoped FR-NNN-XXX across all specs."""

import os
import re

SPECS_DIR = "specs"
FLAT_ID_RE = re.compile(r'\bFR-(\d+)\b(?!-\d{3})')

def migrate_feature(feature_dir):
    feature_path = os.path.join(SPECS_DIR, feature_dir)
    if not os.path.isdir(feature_path):
        return 0
    m = re.match(r'(\d{3})-', feature_dir)
    if not m:
        return 0
    feature_num = m.group(1)
    
    changes = 0
    # Include all markdown and documentation files in the feature directory
    for root, dirs, files in os.walk(feature_path):
        for fname in files:
            if not fname.endswith(('.md', '.txt', '.py', '.sh', '.sql')):
                continue
            doc_path = os.path.join(root, fname)
            with open(doc_path) as f:
                content = f.read()
            
            def replacer(match):
                req_num = match.group(1)
                return f"FR-{feature_num}-{req_num.zfill(3)}"
            
            new_content, count = FLAT_ID_RE.subn(replacer, content)
            if count > 0:
                with open(doc_path, 'w') as f:
                    f.write(new_content)
                rel_path = os.path.relpath(doc_path, SPECS_DIR)
                print(f"  {rel_path}: {count} replacement(s)")
                changes += count
    return changes

def main():
    total = 0
    for feature_dir in sorted(os.listdir(SPECS_DIR)):
        # Skip 018 (already uses new scheme) and non-feature dirs
        if feature_dir.startswith('018-') or not re.match(r'\d{3}-', feature_dir):
            continue
        count = migrate_feature(feature_dir)
        total += count
    print(f"\nTotal replacements: {total}")

if __name__ == '__main__':
    main()
