#!/usr/bin/env python3
"""Compare the items found in code with what BAssist already holds.

BAssist is the only record of what has been done: records and comments are matched by their
[recover:...] markers. Nothing here writes; it only decides what to create, skip or raise.
"""
import re

from recover_core import behaviour_hash, item_id, parse_markers

# The scanner's kinds against BAssist's entity names (matched ignoring case, spaces and punctuation).
ENTITY_NAMES = {"fr": "functionalrequirement", "rule": "businessrule", "role": "stakeholderneed"}


def _same(a: str, b: str) -> bool:
    return " ".join((a or "").split()).casefold() == " ".join((b or "").split()).casefold()


def _kind_key(kind: str) -> str:
    return re.sub(r"[^a-z0-9]", "", (kind or "").casefold())


def _same_kind(record_kind: str, item_kind: str) -> bool:
    return _kind_key(record_kind) in {_kind_key(item_kind), ENTITY_NAMES.get(item_kind, _kind_key(item_kind))}


def reconcile(items: list, records: list, comments: list) -> dict:
    recovered = {}  # item id -> (record, stored hash)
    hand_written = []
    for rcd in records:
        marks = [m for m in parse_markers(rcd.get("description", "")) if m["type"] == "record"]
        if marks:
            recovered[marks[0]["id"]] = (rcd, marks[0]["hash"])
        else:
            hand_written.append(rcd)

    raised_drift = {(m["id"], m["hash"]) for c in comments
                    for m in parse_markers(c.get("body", "")) if m["type"] == "drift"}

    out = {"create": [], "skip": [], "drift": [], "possibly_removed": [], "overlap": []}
    seen_ids = set()
    for it in items:
        iid = item_id(it["kind"], it["symbol"])
        new_hash = behaviour_hash(it["behaviour"])
        seen_ids.add(iid)
        if iid in recovered:
            rcd, old_hash = recovered[iid]
            # Items the model wrote (scenarios traced in the judgement pass) are re-worded freely
            # between runs; only what the scanner reads from code can drift.
            if it.get("origin") != "model" and old_hash != new_hash and (iid, new_hash) not in raised_drift:
                out["drift"].append({"item": it, "record_id": rcd["id"],
                                     "old_hash": old_hash, "new_hash": new_hash})
            else:
                out["skip"].append(it)
            continue
        twin = next((h for h in hand_written
                     if _same_kind(h.get("kind"), it["kind"]) and _same(h.get("title"), it["title"])), None)
        if twin:
            out["overlap"].append({"item": it, "record_id": twin["id"]})
        else:
            out["create"].append(it)

    for iid, (rcd, _) in recovered.items():
        if iid not in seen_ids:
            out["possibly_removed"].append({"record_id": rcd["id"], "title": rcd.get("title")})
    return out
