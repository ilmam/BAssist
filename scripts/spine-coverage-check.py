#!/usr/bin/env python3
"""Scenario-coverage check (guardrail A4, see docs/delivery-guardrails.md).

Portable: Python 3 standard library only, no knowledge of the project's language or framework.

Reads the feature files pulled from the spine (each scenario is tagged `@SC-n @status:... @rev:...`)
and the project's tests, and fails when the two disagree:

  - a feature file with no scenario                      error
  - a scenario with no SC code (written by hand)         error
  - the same SC code twice                               error
  - an agreed / need_revision scenario with no test      error
  - a draft scenario with no test                        warning (error with --strict)
  - a test naming an SC code that no feature file has    error
  - a test naming a deprecated scenario                  warning
  - deprecated scenarios need no test

A test covers a scenario when its file mentions the SC code anywhere (a tag, an annotation, a
comment, a test name). Exit status is 1 when there is any error, otherwise 0.

    python scripts/spine-coverage-check.py --features features --tests tests
"""

import argparse
import os
import re
import sys
from collections import defaultdict

SKIP_DIRS = {'.git', 'vendor', 'node_modules', '__pycache__', '.venv', 'storage', 'bootstrap'}
CODE = re.compile(r'\bSC-\d+\b')
SCENARIO = re.compile(r'^\s*Scenario(?:\s+Outline)?\s*:\s*(.*)$', re.IGNORECASE)
FEATURE = re.compile(r'^\s*Feature\s*:\s*(.*)$', re.IGNORECASE)
STATUS = re.compile(r'@status:(\w+)')


def walk(root, suffixes=None):
    for base, dirs, files in os.walk(root):
        dirs[:] = [d for d in dirs if d not in SKIP_DIRS]
        for name in files:
            if suffixes is None or name.endswith(suffixes):
                yield os.path.join(base, name)


def read(path):
    with open(path, encoding='utf-8', errors='replace') as handle:
        return handle.read()


def parse_features(root):
    """Return (scenarios, problems). A scenario is a dict: code, title, status, file, line."""
    scenarios, problems = [], []

    for path in sorted(walk(root, '.feature')):
        feature_title, feature_line, count = None, 0, 0
        tags = []

        for number, line in enumerate(read(path).splitlines(), start=1):
            stripped = line.strip()

            if stripped.startswith('@'):
                tags.append(stripped)
                continue

            feature = FEATURE.match(line)
            if feature:
                feature_title, feature_line, tags = feature.group(1).strip(), number, []
                continue

            scenario = SCENARIO.match(line)
            if scenario:
                count += 1
                tag_text = ' '.join(tags)
                codes = CODE.findall(tag_text)
                status = STATUS.search(tag_text)
                scenarios.append({
                    'code': codes[0] if codes else None,
                    'title': scenario.group(1).strip(),
                    'status': status.group(1) if status else 'draft',
                    'file': path,
                    'line': number,
                })
                tags = []
                continue

            if stripped and not stripped.startswith('#'):
                tags = []

        if feature_title is not None and count == 0:
            problems.append(f'{path}:{feature_line}: feature "{feature_title}" has no scenario')

    return scenarios, problems


def collect_references(test_roots, feature_root):
    """Map each SC code to the test files that mention it."""
    references = defaultdict(set)
    feature_root = os.path.abspath(feature_root)

    for root in test_roots:
        for path in walk(root):
            if path.endswith('.feature') or os.path.abspath(path).startswith(feature_root + os.sep):
                continue
            try:
                text = read(path)
            except OSError:
                continue
            for code in set(CODE.findall(text)):
                references[code].add(path)

    return references


def check(features, tests, strict):
    scenarios, errors = parse_features(features)
    warnings = []

    if not scenarios and not errors:
        errors.append(f'no scenarios found under {features}: pull the feature files from the spine first')

    seen = {}
    for scenario in scenarios:
        where = f"{scenario['file']}:{scenario['line']}"
        code = scenario['code']

        if code is None:
            errors.append(f'{where}: scenario "{scenario["title"]}" has no SC code; pull it from the spine instead of writing it by hand')
            continue
        if code in seen:
            errors.append(f'{where}: {code} is also defined at {seen[code]}')
            continue
        seen[code] = where

    references = collect_references(tests, features)

    for scenario in scenarios:
        code = scenario['code']
        if code is None or code in references or scenario['status'] == 'deprecated':
            continue
        message = f'{scenario["file"]}:{scenario["line"]}: {code} "{scenario["title"]}" ({scenario["status"]}) has no test'
        (errors if scenario['status'] != 'draft' or strict else warnings).append(message)

    by_code = {s['code']: s for s in scenarios if s['code']}
    for code, files in sorted(references.items()):
        listed = ', '.join(sorted(files))
        if code not in by_code:
            errors.append(f'{code} is named in {listed} but no feature file defines it')
        elif by_code[code]['status'] == 'deprecated':
            warnings.append(f'{code} is deprecated but still named in {listed}')

    covered = sum(1 for s in scenarios if s['code'] and s['code'] in references)
    return errors, warnings, len(scenarios), covered


def main():
    parser = argparse.ArgumentParser(description='Check that every spine scenario has a test and every test names a real scenario.')
    parser.add_argument('--features', default='features', help='folder holding the pulled .feature files')
    parser.add_argument('--tests', action='append', help='test folder (repeatable; default: tests)')
    parser.add_argument('--strict', action='store_true', help='treat draft scenarios without a test as errors')
    args = parser.parse_args()

    tests = args.tests or ['tests']
    for folder in [args.features] + tests:
        if not os.path.isdir(folder):
            print(f'error: {folder} is not a folder', file=sys.stderr)
            return 2

    errors, warnings, total, covered = check(args.features, tests, args.strict)

    for message in warnings:
        print(f'warning: {message}')
    for message in errors:
        print(f'error: {message}')
    print(f'{covered} of {total} scenarios have a test; {len(errors)} error(s), {len(warnings)} warning(s)')

    return 1 if errors else 0


if __name__ == '__main__':
    sys.exit(main())
