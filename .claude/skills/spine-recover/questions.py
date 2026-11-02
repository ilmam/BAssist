#!/usr/bin/env python3
"""Impact scoring and the sensitivity budget for BA questions.

Sensitivity N sets a budget of open questions: ceil(N% of the questions whose threads are not
closed). Open, answered and implemented threads occupy the budget; closed threads free it and
are never raised again.
"""
import math

from recover_core import item_id, parse_markers

WEIGHTS = {"money": 5, "security": 5, "irreversible": 4, "cross_layer": 4,
           "hardcoded": 3, "inconsistent": 2}
DEPENDENTS_CAP = 5
OCCUPYING = {"open", "answered", "implemented"}


def question_id(q: dict) -> str:
    return item_id("question", f"{q['symbol']}#{q['reason']}")


def score(q: dict) -> int:
    base = sum(WEIGHTS.get(s, 0) for s in q.get("signals", []))
    return base + min(q.get("dependents", 0), DEPENDENTS_CAP)


def parse_sensitivity(raw) -> int:
    message = "Sensitivity must be a whole number from 0 to 100"
    try:
        value = int(str(raw).strip())
    except ValueError:
        raise ValueError(message) from None
    if not 0 <= value <= 100:
        raise ValueError(message)
    return value


def select(questions: list, threads: list, sensitivity: int) -> dict:
    status_by_id = {}
    for t in threads:
        for m in parse_markers(t.get("body", "")):
            if m["type"] == "question":
                status_by_id[m["id"]] = t.get("status")

    occupied, closed, unfiled = 0, 0, []
    for q in questions:
        status = status_by_id.get(question_id(q))
        if status == "closed":
            closed += 1
        elif status in OCCUPYING:
            occupied += 1
        else:
            unfiled.append(q)

    total = occupied + len(unfiled)
    budget = math.ceil(total * sensitivity / 100)
    slots = max(0, budget - occupied)
    ranked = sorted(unfiled, key=lambda q: (-score(q), question_id(q)))
    return {"file": ranked[:slots], "occupied": occupied, "closed": closed,
            "unfiled_left": len(ranked) - min(slots, len(ranked)), "budget": budget}
