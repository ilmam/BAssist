#!/usr/bin/env python3
"""Stable ids and markers for /spine-recover.

An item id is derived from its kind and a stable symbol (never a line number), so the same
thing found on a later run gets the same id. The behaviour is hashed separately so a change
in behaviour shows up as drift on the same item, not as a new item.
"""
import hashlib
import re
import unicodedata

_MARKER = re.compile(r"\[recover:(?:(q|drift):)?([0-9a-f]{12})(?::([0-9a-f]{8}))?\]")


def _norm(text: str) -> str:
    return " ".join(unicodedata.normalize("NFC", text).split())


def _sha(text: str, length: int) -> str:
    return hashlib.sha256(text.encode("utf-8")).hexdigest()[:length]


def item_id(kind: str, symbol: str) -> str:
    return _sha(_norm(kind).casefold() + "|" + _norm(symbol), 12)


def behaviour_hash(behaviour: str) -> str:
    return _sha(_norm(behaviour), 8)


def build_marker(id_: str, hash_: str) -> str:
    return f"[recover:{id_}:{hash_}]"


def build_question_marker(id_: str) -> str:
    return f"[recover:q:{id_}]"


def build_drift_marker(id_: str, new_hash: str) -> str:
    return f"[recover:drift:{id_}:{new_hash}]"


def parse_markers(text: str) -> list:
    found = []
    for m in _MARKER.finditer(text or ""):
        prefix, id_, hash_ = m.groups()
        kind = {"q": "question", "drift": "drift", None: "record"}[prefix]
        if kind == "question" and hash_:
            continue
        if kind in ("record", "drift") and not hash_:
            continue
        found.append({"type": kind, "id": id_, "hash": hash_})
    return found
