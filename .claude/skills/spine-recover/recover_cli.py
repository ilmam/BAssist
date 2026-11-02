#!/usr/bin/env python3
"""Command line for /spine-recover. It decides; the skill (the model) only carries it out.

  scan <root> --out inventory.json
      Read a Shift solution; write {items, roles, questions}.

  plan --inventory inventory.json --records records.json --comments comments.json
       --sensitivity N [--out plan.json]
      Compare the inventory with what BAssist already holds (records.json from list-records,
      comments.json from list-comments) and write the action plan:
      {create, skip, drift, possibly_removed, overlap, file_questions, summary}.

Exit code 2 on an invalid sensitivity. Nothing here talks to BAssist.
"""
import argparse
import json
import sys
from pathlib import Path

from questions import parse_sensitivity, question_id, select
from recover_core import behaviour_hash, build_marker, build_question_marker, item_id, parse_markers
from reconcile import reconcile
from shift_scan import detect_questions, scan


def _write(path, data):
    Path(path).write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")


def _read(path):
    return json.loads(Path(path).read_text(encoding="utf-8"))


def _annotate(item: dict) -> dict:
    """Add the id, hash, marker and a ready description. The marker comes first in the description
    because list-records cuts long text at 300 characters and the marker must survive that."""
    iid, h = item_id(item["kind"], item["symbol"]), behaviour_hash(item["behaviour"])
    marker = build_marker(iid, h)
    refs = ", ".join(f"{r['file']}:{r['line']}" for r in item.get("refs", [])[:3])
    description = f"{marker} Inferred from code: {item['behaviour']}" + (f". Code: {refs}" if refs else "")
    return {**item, "id": iid, "hash": h, "marker": marker, "description": description}


def _targets(question: dict, items: list, record_by_item: dict) -> dict:
    """Which item (and existing BAssist record, if any) a question is about."""
    match = next((i for i in items if i["symbol"] == question["symbol"]), None)
    if not match:
        return {"target_item_id": None, "target_record_id": None}
    iid = item_id(match["kind"], match["symbol"])
    return {"target_item_id": iid, "target_record_id": record_by_item.get(iid)}


def _notes(inv: dict, records: list, comments: list) -> list:
    """Say once, on 'Main stakeholder', that real roles are now visible in the code."""
    marker = build_question_marker(item_id("note", "roles-visible"))
    posted = any(marker in c.get("body", "") for c in comments)
    main = next((r for r in records if " ".join(r.get("title", "").split()).casefold() == "main stakeholder"), None)
    if not inv.get("roles") or not main or posted:
        return []
    return [{"record_id": main["id"], "marker": marker,
             "text": "Roles are now visible in the code (" + ", ".join(inv["roles"]) + "). "
                     "Stakeholder Needs were created for them beside this one; nothing was re-linked. " + marker}]


def _summary(n_questions, selection, plan) -> str:
    parts = [f"{n_questions} questions found, {len(selection['file'])} filed, "
             f"{selection['unfiled_left']} below threshold; "
             f"{len(plan['create'])} records to create, {len(plan['skip'])} already existed"]
    for key, label in (("drift", "with changed behaviour"), ("possibly_removed", "possibly removed or renamed"),
                       ("overlap", "overlapping hand-written records")):
        if plan[key]:
            parts.append(f"{len(plan[key])} {label}")
    if selection["closed"]:
        parts.append(f"{selection['closed']} questions already closed")
    return "; ".join(parts)


def cmd_scan(args) -> int:
    if not Path(args.root).is_dir():
        print(f"{args.root} is not a folder", file=sys.stderr)
        return 1
    result = scan(args.root)
    if result["files_scanned"] == 0:
        print(f"Warning: no .cs, .razor or .feature files found under {args.root}. Is this the right path? "
              f"Planning against an empty inventory would report every recovered record as possibly removed.",
              file=sys.stderr)
    for rel in result["warnings"]:
        print(f"Warning: {rel} is not UTF-8; read with a fallback encoding", file=sys.stderr)
    _write(args.out, {"items": result["items"], "roles": result["roles"],
                      "questions": detect_questions(result)})
    return 0


def cmd_plan(args) -> int:
    try:
        sensitivity = parse_sensitivity(args.sensitivity)
    except ValueError as e:
        print(str(e), file=sys.stderr)
        return 2
    inv, records, comments = _read(args.inventory), _read(args.records), _read(args.comments)
    rec = reconcile(inv["items"], records, comments)
    selection = select(inv["questions"], comments, sensitivity)
    record_by_item = {m["id"]: r["id"] for r in records
                      for m in parse_markers(r.get("description", "")) if m["type"] == "record"}
    plan = {
        "create": [_annotate(i) for i in rec["create"]],
        "skip": [_annotate(i) for i in rec["skip"]],
        "drift": [{**d, "id": item_id(d["item"]["kind"], d["item"]["symbol"])} for d in rec["drift"]],
        "possibly_removed": rec["possibly_removed"],
        "overlap": rec["overlap"],
        "file_questions": [{**q, "id": question_id(q), "marker": build_question_marker(question_id(q)),
                            **_targets(q, inv["items"], record_by_item)} for q in selection["file"]],
        "notes": _notes(inv, records, comments),
    }
    plan["summary"] = _summary(len(inv["questions"]), selection, plan)
    if args.out:
        _write(args.out, plan)
    print(plan["summary"])
    return 0


def main(argv=None) -> int:
    p = argparse.ArgumentParser(prog="recover_cli")
    sub = p.add_subparsers(dest="cmd", required=True)
    s = sub.add_parser("scan")
    s.add_argument("root")
    s.add_argument("--out", required=True)
    s.set_defaults(func=cmd_scan)
    q = sub.add_parser("plan")
    q.add_argument("--inventory", required=True)
    q.add_argument("--records", required=True)
    q.add_argument("--comments", required=True)
    q.add_argument("--sensitivity", default="30")
    q.add_argument("--out")
    q.set_defaults(func=cmd_plan)
    args = p.parse_args(argv)
    return args.func(args)


if __name__ == "__main__":
    sys.exit(main())
