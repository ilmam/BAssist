#!/usr/bin/env python3
"""Trace check: BAssist scenarios <-> local .feature files <-> tests, plus a review bundle.

Inputs
  --export    JSON saved from BAssist get-gherkin (project_id form: {"project":..,"features":[..]})
  --features  folder holding the pulled .feature files (default: features)
  --tests     folder(s) scanned for the scenario code, e.g. SC-7, in test sources (repeatable)
  --gaps      gap log (default: docs/requirements-gaps.md)
  --out       folder for report.json and report.md (default: spine-verify-report)
  --run-summary  optional text file with the test run output, embedded as evidence

Exit code 1 when any blocking finding exists. A scenario "traced" means a test source
mentions its code. It does NOT mean the test passes or is any good; the report says so.
"""
import argparse, datetime, hashlib, json, re, sys
from pathlib import Path

TAG_SC = re.compile(r"@(SC-\d+)\b")
TAG_REV = re.compile(r"@rev:(\S+)")
SCEN = re.compile(r"^\s*Scenario(?: Outline)?:\s*(.+)$")


def sha(text: str) -> str:
    return hashlib.sha256(text.encode("utf-8")).hexdigest()[:16]


def local_scenarios(features_dir: Path):
    """code -> {rev, file, title, edge}. Reads tag lines directly above each Scenario line."""
    found, dupes = {}, []
    for f in sorted(features_dir.rglob("*.feature")):
        tags = []
        for line in f.read_text(encoding="utf-8").splitlines():
            s = line.strip()
            if s.startswith("@"):
                tags.append(s)
                continue
            m = SCEN.match(line)
            if m:
                joined = " ".join(tags)
                code = TAG_SC.search(joined)
                if code:
                    rev = TAG_REV.search(joined)
                    entry = {"rev": rev.group(1) if rev else None, "file": f.name,
                             "title": m.group(1).strip(), "edge": "@edge-case" in joined}
                    if code.group(1) in found:
                        dupes.append(code.group(1))
                    found[code.group(1)] = entry
                else:
                    found.setdefault("_untagged", []).append(
                        {"file": f.name, "title": m.group(1).strip()})
            if s and not s.startswith("#"):
                tags = []
    return found, dupes


def test_mentions(test_dirs):
    """code -> list of files mentioning it."""
    hits = {}
    pat = re.compile(r"\bSC-\d+\b")
    for d in test_dirs:
        for p in Path(d).rglob("*"):
            if not p.is_file() or p.suffix in {".feature", ".png", ".jpg", ".dll", ".exe", ".sqlite"}:
                continue
            try:
                text = p.read_text(encoding="utf-8")
            except (UnicodeDecodeError, OSError):
                continue
            for code in set(pat.findall(text)):
                hits.setdefault(code, []).append(str(p))
    return hits


def gap_rows(path: Path):
    rows = []
    if not path.exists():
        return rows
    for line in path.read_text(encoding="utf-8").splitlines():
        cells = [c.strip() for c in line.strip().strip("|").split("|")]
        if len(cells) >= 7 and re.fullmatch(r"G-\d+", cells[0]):
            rows.append({"ref": cells[0], "area": cells[1], "gap": cells[2],
                         "status": cells[6].lower(), "filed": cells[7] if len(cells) > 7 else ""})
    return rows


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--export", required=True)
    ap.add_argument("--features", default="features")
    ap.add_argument("--tests", action="append", default=[])
    ap.add_argument("--gaps", default="docs/requirements-gaps.md")
    ap.add_argument("--out", default="spine-verify-report")
    ap.add_argument("--run-summary")
    a = ap.parse_args()

    export_text = Path(a.export).read_text(encoding="utf-8")
    export = json.loads(export_text)
    local, dupes = local_scenarios(Path(a.features))
    untagged = local.pop("_untagged", [])
    mentions = test_mentions(a.tests or ["tests"])
    gaps = gap_rows(Path(a.gaps))

    findings, scenarios, features = [], [], []

    def add(sev, code, ref, msg):
        findings.append({"severity": sev, "check": code, "ref": ref, "message": msg})

    spec_codes = set()
    for ft in export["features"]:
        n_edge = 0
        for sc in ft["scenarios"]:
            code = sc["code"]
            spec_codes.add(code)
            loc = local.get(code)
            tests = mentions.get(code, [])
            if loc and loc["edge"]:
                n_edge += 1
            if not loc:
                add("block", "not-pulled", code, f"{code} exists in BAssist but not in {a.features}. Run /spine-pull.")
            elif loc["rev"] != sc["rev"]:
                add("block", "stale-spec", code,
                    f"{code} local rev {loc['rev']} differs from BAssist rev {sc['rev']}. Spec changed; tests may be out of date.")
            if not tests:
                add("block", "no-test", code, f"{code} '{sc['title']}' has no test that mentions its code.")
            scenarios.append({"code": code, "feature": ft["code"], "title": sc["title"],
                              "bassist_rev": sc["rev"], "local_rev": loc["rev"] if loc else None,
                              "edge_case": bool(loc and loc["edge"]), "test_files": tests})
        if ft["scenarios"] and n_edge == 0:
            add("warn", "no-edge-case", ft["code"],
                f"{ft['code']} '{ft['title']}' has {len(ft['scenarios'])} scenario(s) and none tagged @edge-case. "
                "Walk the edge-case checklist and log gaps.")
        if not ft["scenarios"]:
            add("block", "no-scenarios", ft["code"], f"{ft['code']} has no scenarios.")
        features.append({"code": ft["code"], "title": ft["title"], "status": ft["status"],
                         "rev": ft["rev"], "scenario_count": len(ft["scenarios"]), "edge_case_count": n_edge})

    for code, loc in local.items():
        if code not in spec_codes:
            add("block", "orphan-tag", code, f"{code} in {loc['file']} is not in BAssist (deleted or typo).")
    for code in set(dupes):
        add("block", "duplicate-tag", code, f"{code} appears more than once in local feature files.")
    for u in untagged:
        add("block", "untagged-scenario", u["file"], f"Scenario '{u['title']}' has no @SC-n tag; it exists only in code.")
    for code in sorted(set(mentions) - spec_codes):
        add("warn", "unknown-code-in-tests", code, f"Tests mention {code}, which is not in BAssist.")

    unfiled = [g for g in gaps if g["status"] == "open" and not g["filed"]]
    for g in unfiled:
        add("block", "gap-unfiled", g["ref"], f"{g['ref']} is open and not filed in BAssist. Run /spine-feedback.")

    run_summary = Path(a.run_summary).read_text(encoding="utf-8") if a.run_summary else None
    blocking = [f for f in findings if f["severity"] == "block"]
    report = {
        "generated_utc": datetime.datetime.now(datetime.timezone.utc).isoformat(timespec="seconds"),
        "project": export.get("project"),
        "inputs": {"export_sha": sha(export_text), "features_dir": a.features,
                   "test_dirs": a.tests or ["tests"], "gap_log": a.gaps},
        "limits": ["'traced' means a test source mentions the scenario code. It does not mean the test passes or asserts the scenario correctly.",
                   "Edge-case completeness cannot be proven by this script; the reviewer judges it against the checklist."],
        "verdict": "FAIL" if blocking else "PASS",
        "summary": {"features": len(features), "scenarios": len(scenarios),
                    "traced": sum(1 for s in scenarios if s["test_files"]),
                    "blocking": len(blocking), "warnings": len(findings) - len(blocking),
                    "open_gaps": sum(1 for g in gaps if g["status"] == "open")},
        "findings": findings, "features": features, "scenarios": scenarios, "gaps": gaps,
        "test_run_summary": run_summary,
    }
    report["report_sha"] = sha(json.dumps({k: v for k, v in report.items() if k != "generated_utc"}, sort_keys=True))

    out = Path(a.out)
    out.mkdir(parents=True, exist_ok=True)
    (out / "report.json").write_text(json.dumps(report, indent=2), encoding="utf-8")
    (out / "report.md").write_text(render_md(report), encoding="utf-8")
    s = report["summary"]
    print(f"{report['verdict']}: {s['traced']}/{s['scenarios']} scenarios traced, "
          f"{s['blocking']} blocking, {s['warnings']} warnings, {s['open_gaps']} open gaps -> {out}")
    sys.exit(1 if blocking else 0)


REVIEW_BRIEF = """\
## Instructions for the reviewing agent (independent verification)

You are a second, independent reviewer. You did not write this code or these tests. Do not trust this report's PASS/FAIL; check it.

1. For every scenario below, open the listed test file(s) and confirm the test really exercises the Given/When/Then of the scenario with a meaningful assertion. A test that only mentions the code does not count.
2. For every feature, apply the edge-case checklist to each rule: boundaries, null/empty input, duplicates and idempotency, permissions and data-level access, illegal state transitions, concurrency, time and time zones, failure of dependencies. List cases the scenarios do not cover.
3. Look for behaviour in the code that no scenario or business rule describes (drift).
4. Cite evidence for every finding: file path and line, or scenario code. A finding without evidence is discarded.
5. Mark each finding `verified` (you checked it in the code) or `unverified` (inference).
6. Do not invent business facts. When the spec is silent, write the question; do not choose the answer.

Return findings as a table the author can paste into docs/requirements-gaps.md:

| # | Area | Gap / question | What the app does today | Suggested default | Decision | Status | Filed |
"""


def render_md(r):
    s = r["summary"]
    L = [f"# Verification bundle: {r['project']['code'] if r.get('project') else ''}",
         f"Verdict (automated, advisory): **{r['verdict']}**  |  generated {r['generated_utc']}  |  report id `{r['report_sha']}`", "",
         f"{s['traced']}/{s['scenarios']} scenarios traced to a test; {s['blocking']} blocking, {s['warnings']} warnings, {s['open_gaps']} open gaps.", "",
         "**Limits:** " + " ".join(r["limits"]), "", REVIEW_BRIEF, "## Findings"]
    L += ([f"- **{f['severity']}** `{f['check']}` {f['ref']}: {f['message']}" for f in r["findings"]] or ["- none"])
    L += ["", "## Scenario trace", "", "| Scenario | Feature | Edge | Spec rev | Local rev | Test files |", "|---|---|---|---|---|---|"]
    for x in r["scenarios"]:
        L.append(f"| {x['code']} {x['title']} | {x['feature']} | {'yes' if x['edge_case'] else ''} | {x['bassist_rev']} | "
                 f"{x['local_rev'] or ''} | {', '.join(x['test_files']) or '**none**'} |")
    L += ["", "## Gap log", ""] + ([f"- {g['ref']} [{g['status']}] {g['gap']} (filed: {g['filed'] or 'no'})" for g in r["gaps"]] or ["- none"])
    if r["test_run_summary"]:
        L += ["", "## Test run output", "```", r["test_run_summary"].strip(), "```"]
    return "\n".join(L) + "\n"


if __name__ == "__main__":
    main()
