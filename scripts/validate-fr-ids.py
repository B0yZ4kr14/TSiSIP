#!/usr/bin/env python3
# @req FR-002
# @req FR-018
"""FR-ID Validation Script for Feature 018.

Validates that all specs use feature-scoped FR-NNN-XXX IDs.
Detects duplicates and flat FR-XXX IDs.
Produces JSON report on failure.
"""

import json
import os
import re
import sys

SPECS_DIR = "specs"
REPORTS_DIR = "reports"
# Match flat IDs: FR- followed by digits, NOT followed by -DDD
FLAT_ID_RE = re.compile(r'\bFR-(\d+)\b(?!-\d{3})')
SCOPED_ID_RE = re.compile(r'\bFR-(\d{3})-(\d{3})\b')

def main():
    errors = []
    warnings = []
    scoped_ids = {}  # id -> [(file, line)]
    
    for feature_dir in sorted(os.listdir(SPECS_DIR)):
        feature_path = os.path.join(SPECS_DIR, feature_dir)
        if not os.path.isdir(feature_path):
            continue
        # Extract feature number
        m = re.match(r'(\d{3})-', feature_dir)
        if not m:
            continue
        feature_num = m.group(1)
        
        for root, dirs, files in os.walk(feature_path):
            for fname in files:
                if not fname.endswith(('.md', '.txt', '.py', '.sh', '.sql')):
                    continue
                doc_path = os.path.join(root, fname)
                rel_path = os.path.relpath(doc_path, SPECS_DIR)
                with open(doc_path) as f:
                    lines = f.readlines()
                for i, line in enumerate(lines, 1):
                    # Check for flat IDs
                    for match in FLAT_ID_RE.finditer(line):
                        errors.append({
                            'type': 'flat_id',
                            'feature': feature_dir,
                            'file': rel_path,
                            'line': i,
                            'id': match.group(0),
                            'context': line.strip()
                        })
                    # Check for scoped IDs and track duplicates
                    for match in SCOPED_ID_RE.finditer(line):
                        scoped_id = match.group(0)
                        if scoped_id not in scoped_ids:
                            scoped_ids[scoped_id] = []
                        scoped_ids[scoped_id].append((rel_path, i))
    
    # Detect duplicates across different specs
    # Allow references from meta-feature 018
    for sid, occurrences in scoped_ids.items():
        features = set()
        for path, line in occurrences:
            feat = path.split('/')[0]
            features.add(feat)
        # Remove meta-feature from consideration
        non_meta_features = features - {'018-global-requirement-id-migration'}
        if len(non_meta_features) > 1:
            errors.append({
                'type': 'duplicate_across_specs',
                'id': sid,
                'occurrences': occurrences
            })
    
    # Write report
    os.makedirs(REPORTS_DIR, exist_ok=True)
    report_path = os.path.join(REPORTS_DIR, 'fr-id-duplicates.json')
    report = {
        'valid': len(errors) == 0,
        'error_count': len(errors),
        'warning_count': len(warnings),
        'errors': errors,
        'warnings': warnings
    }
    with open(report_path, 'w') as f:
        json.dump(report, f, indent=2)
    
    if errors:
        print(f"FR-ID VALIDATION FAILED: {len(errors)} error(s)", file=sys.stderr)
        for e in errors[:10]:
            if e['type'] == 'flat_id':
                print(f"  {e['feature']}/{e['file']}:{e['line']} — flat ID {e['id']}", file=sys.stderr)
            else:
                print(f"  Duplicate {e['id']} across {len(e['occurrences'])} locations", file=sys.stderr)
        print(f"Full report: {report_path}", file=sys.stderr)
        sys.exit(1)
    else:
        print("FR-ID VALIDATION PASSED: All IDs are feature-scoped and unique.")
        sys.exit(0)

if __name__ == '__main__':
    main()
