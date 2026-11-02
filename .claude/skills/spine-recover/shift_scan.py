#!/usr/bin/env python3
"""Read a Shift Framework solution and list what it contains (no .NET parsing, line scanning only).

Output (all JSON-serialisable):
  items         [{kind, symbol, title, behaviour, parent_symbol, refs:[{file,line}], documented?}]
  roles         names of the action trees found
  api_actions   {"Controller/segment": {auth, file, line}}   what the API requires
  ui_actions    {"Controller/segment": {auth, file, line, page_symbol}}   what the UI guards it with
  action_refs   {"Tree.Node": {"razor": [files], "cs": [files]}}   where each action is referenced

Items by kind: feature (pages, timers/queue functions), role (action trees), fr (DTO constraints),
rule (constants, refusals, data-level access, attention), scenario (existing .feature files).
Files are read as UTF-8 (BOM tolerated) with universal newlines, so CRLF and LF scan the same.
"""
import re
from pathlib import Path

IGNORED_DIRS = {"bin", "obj", "node_modules", ".git", ".vs", "packages", "__pycache__"}
DATA_LEVEL = {"Company", "CompanyBranch", "Brand", "Region", "City", "Country", "Team"}

CLASS = re.compile(r"\bclass\s+(\w+)")
TREE = re.compile(r"\bclass\s+(\w+)\s*:\s*ActionTree\b")
TREE_ACTION = re.compile(r"static\s+readonly\s+ActionTreeAction\s+(\w+)")
CONST = re.compile(r"\bconst\s+(?:decimal|int|long|double|float|TimeSpan)\s+(\w+)\s*=\s*([^;]+);")
METHOD = re.compile(r"^\s*(?:public|private|protected|internal)\b[^=;]*?\b(\w+)\s*\(")
THROW = re.compile(r'throw\s+new\s+ShiftEntityException\(\s*new\s+Message\(\s*"([^"]*)"\s*,\s*"([^"]*)"')
DATA_ACCESS = re.compile(r"\bIEntityHas(\w+)<")
HTTP = re.compile(r'\[Http(?:Get|Post|Put|Delete|Patch)(?:\("([^"]*)"\))?\]')
NAMEOF = re.compile(r"nameof\(\s*\w+\.(\w+)\s*\)")
BASE_CALL = re.compile(r"base\(\s*(?:\w+\.)*(\w+)\s*\)")
RAZOR_TAG = re.compile(r"<(ShiftList|ShiftEntityForm|ActionButton)\b(.*?)>", re.S)
ATTR = re.compile(r'([\w:@-]+)="([^"]*)"')
FUNCTION = re.compile(
    r'\[Function\("([^"]+)"\)\][^{]*?(TimerTrigger|ServiceBusTrigger|QueueTrigger)\("([^"]+)"', re.S)
DTO_ATTR = re.compile(r"^\s*\[(Required|StringLength|Range)\b(.*)\]\s*$")
PROPERTY = re.compile(r"\bpublic\s+[\w<>?,\[\]]+\s+(\w+)\s*\{")
STEP = re.compile(r"^\s*(Given|When|Then|And|But)\b")


def _files(root: Path):
    for f in sorted(root.rglob("*")):
        if f.is_file() and not (set(f.relative_to(root).parts[:-1]) & IGNORED_DIRS):
            yield f


def _read(path: Path) -> str:
    return path.read_text(encoding="utf-8-sig")


def _rel(path: Path, root: Path) -> str:
    return path.relative_to(root).as_posix()


def _line_of(text: str, index: int) -> int:
    return text.count("\n", 0, index) + 1


def _item(kind, symbol, title, behaviour, file, line, **extra):
    item = {"kind": kind, "symbol": symbol, "title": title, "behaviour": behaviour,
            "parent_symbol": None, "refs": [{"file": file, "line": line}]}
    item.update(extra)
    return item


def _scan_cs(text, rel, out):
    cls, method, prev_comment = None, None, False
    lines = text.split("\n")
    for n, line in enumerate(lines, 1):
        stripped = line.strip()
        if "class " in line and (m := CLASS.search(line)):
            cls = m.group(1)
            for d in DATA_ACCESS.finditer(line):
                if d.group(1) in DATA_LEVEL:
                    sym = f"{cls}.DataLevelAccess.{d.group(1)}"
                    out["items"].append(_item(
                        "rule", sym, sym, f"Rows are filtered by the user's {d.group(1).lower()} grants",
                        rel, n))
            if re.search(r"\bIHasAttention\b", line):
                out["items"].append(_item(
                    "rule", f"{cls}.Attention", f"{cls}.Attention",
                    "Records can carry an attention flag", rel, n))
        elif (m := METHOD.match(line)) and "throw" not in line:
            method = m.group(1)
        if cls and (m := CONST.search(line)):
            out["items"].append(_item(
                "rule", f"{cls}.{m.group(1)}", f"{cls}.{m.group(1)}",
                f"{m.group(1)} = {m.group(2).strip()}", rel, n, documented=prev_comment))
        if (m := THROW.search(line)):
            cond = next((lines[i].strip() for i in range(n - 2, max(n - 5, -1), -1)
                         if lines[i].strip().startswith("if")), "")
            sym = f"{cls}.{method}#{m.group(1)}"
            out["items"].append(_item(
                "rule", sym, sym, f"{cond} -> refuses: {m.group(2)}".strip(), rel, n))
        if stripped:
            prev_comment = stripped.startswith("//")


def _scan_dto(text, rel, out):
    cls, pending = None, []
    for n, line in enumerate(text.split("\n"), 1):
        if (m := CLASS.search(line)) and "class " in line:
            cls = m.group(1)
        if (m := DTO_ATTR.match(line)):
            pending.append(m.group(1) + m.group(2))
            continue
        if pending and (m := PROPERTY.search(line)) and cls:
            for attr in pending:
                text_ = f"{m.group(1)} is required" if attr == "Required" else f"{m.group(1)}: {attr}"
                out["items"].append(_item("fr", f"{cls}.{m.group(1)}", f"{cls}.{m.group(1)}",
                                          text_, rel, n))
            pending = []
        elif line.strip() and not line.strip().startswith("["):
            pending = []


def _scan_controller(text, rel, out):
    m = re.search(r"\bclass\s+(\w+?)Controller\b", text)
    if not m:
        return
    ctrl, default_auth, route, auth = m.group(1), None, None, None
    for n, line in enumerate(text.split("\n"), 1):
        if default_auth is None and ": base(" in line and (b := BASE_CALL.search(line)):
            default_auth = b.group(1)
            out["api_actions"][f"{ctrl}/*"] = {"auth": default_auth, "file": rel, "line": n}
        if (h := HTTP.search(line)):
            segs = [s for s in (h.group(1) or "").split("/") if s and not s.startswith("{")]
            route = segs[-1] if segs else None
        if "[TypeAuth" in line and (a := NAMEOF.search(line)):
            auth = a.group(1)
        if METHOD.match(line) and route:
            out["api_actions"][f"{ctrl}/{route}"] = {"auth": auth or default_auth, "file": rel, "line": n}
            route, auth = None, None


def _scan_razor(text, rel, symbol, out):
    list_auth, feature = None, None
    for tag in RAZOR_TAG.finditer(text):
        name, attrs = tag.group(1), dict(ATTR.findall(tag.group(2)))
        line = _line_of(text, tag.start())
        node = attrs.get("TypeAuthAction", "").rsplit(".", 1)[-1] or None
        if name == "ActionButton":
            endpoint = attrs.get("Endpoint", "").lstrip("/")
            key = endpoint[4:] if endpoint.startswith("api/") else endpoint
            if key:
                out["ui_actions"][key] = {"auth": node or list_auth, "file": rel, "line": line,
                                          "page_symbol": symbol}
            continue
        list_auth = node
        if feature is None or (name == "ShiftList" and feature["tag"] != "ShiftList"):
            entity = attrs.get("EntitySet") or attrs.get("Endpoint") or "?"
            guard = node or "any signed-in user"
            feature = {"tag": name, "line": line, "title": attrs.get("Title") or Path(rel).stem,
                       "behaviour": f"{name} for {entity} guarded by {guard}"}
    if feature:
        out["items"].append(_item("feature", symbol, feature["title"], feature["behaviour"],
                                  rel, feature["line"]))


def _scan_functions(text, rel, out):
    for m in FUNCTION.finditer(text):
        trigger = {"TimerTrigger": "Runs on schedule", "ServiceBusTrigger": "Processes messages from queue",
                   "QueueTrigger": "Processes messages from queue"}[m.group(2)]
        out["items"].append(_item("feature", f"Functions/{m.group(1)}", m.group(1),
                                  f"{trigger} {m.group(3)}", rel, _line_of(text, m.start())))


def _scan_feature_file(text, rel, out):
    title, steps, line = None, [], 0

    def flush():
        if title:
            out["items"].append(_item("scenario", f"{rel}#{title}", title, " / ".join(steps), rel, line))

    for n, raw in enumerate(text.split("\n"), 1):
        s = raw.strip()
        if s.startswith(("Scenario:", "Scenario Outline:")):
            flush()
            title, steps, line = s.split(":", 1)[1].strip(), [], n
        elif title and STEP.match(raw):
            steps.append(s)
    flush()


def scan(root) -> dict:
    root = Path(root)
    out = {"items": [], "roles": [], "api_actions": {}, "ui_actions": {}, "action_refs": {}}
    texts = {}
    trees = {}  # tree -> (defining rel path, [nodes])
    for f in _files(root):
        if f.suffix not in {".cs", ".razor", ".feature"}:
            continue
        rel = _rel(f, root)
        texts[rel] = _read(f)
        if f.suffix == ".cs":
            for i, line in enumerate(texts[rel].split("\n"), 1):
                if (m := TREE.search(line)):
                    nodes = TREE_ACTION.findall(texts[rel])
                    trees[m.group(1)] = (rel, nodes)
                    out["roles"].append(m.group(1))
                    out["items"].append(_item("role", m.group(1), m.group(1),
                                              "actions: " + ", ".join(nodes), rel, i))

    for rel, text in texts.items():
        suffix = Path(rel).suffix
        if suffix == ".cs":
            _scan_cs(text, rel, out)
            _scan_functions(text, rel, out)
            if rel.endswith("DTO.cs") or "DTOs/" in rel:
                _scan_dto(text, rel, out)
            _scan_controller(text, rel, out)
        elif suffix == ".razor":
            _scan_razor(text, rel, str(Path(rel).with_suffix("").as_posix()), out)
        else:
            _scan_feature_file(text, rel, out)

    for tree, (defined_in, nodes) in sorted(trees.items()):
        for node in nodes:
            hits = {"razor": [], "cs": []}
            for rel, text in texts.items():
                if rel != defined_in and re.search(rf"\b{tree}\.{node}\b", text):
                    hits["razor" if rel.endswith(".razor") else "cs"].append(rel)
            out["action_refs"][f"{tree}.{node}"] = hits

    out["roles"].sort()
    out["items"].sort(key=lambda i: (i["kind"], i["symbol"]))
    return out


MONEY_WORDS = re.compile(r"price|discount|tax|amount|fee|cost|rate|total|balance", re.I)
DESTRUCTIVE_WORDS = re.compile(r"cancel|delete|remove|purge|archive|void|reverse|terminate", re.I)


def _question(symbol, reason, text, signals, refs, dependents=0):
    return {"symbol": symbol, "reason": reason, "text": text, "signals": sorted(set(signals)),
            "dependents": dependents, "refs": refs}


def detect_questions(scan_result: dict) -> list:
    """Things the code does that a person should confirm (see the design's scoring signals).

    Cross-layer mismatch: the UI guards an endpoint with a different action than the API requires.
    It carries `security` when the UI is unguarded or only uses the controller's default action
    while the API route demands a specific one (the API is stricter than the UI).
    """
    out = []
    api, ui = scan_result.get("api_actions", {}), scan_result.get("ui_actions", {})
    scenarios = [i for i in scan_result.get("items", []) if i["kind"] == "scenario"]

    for key, u in sorted(ui.items()):
        a = api.get(key)
        if not a or u["auth"] == a["auth"]:
            continue
        default = api.get(key.split("/")[0] + "/*", {}).get("auth")
        signals = ["cross_layer"]
        if u["auth"] is None or u["auth"] == default:
            signals.append("security")
        text = (f"The page '{u['page_symbol']}' guards '{key}' with {u['auth'] or 'no action'} "
                f"({u['file']}:{u['line']}), but the API requires {a['auth']} "
                f"({a['file']}:{a['line']}). Which one is intended?")
        out.append(_question(u["page_symbol"], f"ui-api-mismatch:{key}", text, signals,
                             [{"file": u["file"], "line": u["line"]},
                              {"file": a["file"], "line": a["line"]}]))

    for key, a in sorted(api.items()):
        segment = key.split("/", 1)[1]
        if segment != "*" and DESTRUCTIVE_WORDS.search(segment):
            u = ui.get(key)
            symbol = u["page_symbol"] if u else key
            out.append(_question(
                symbol, f"destructive-action:{key}",
                f"The API exposes '{key}' ({a['file']}:{a['line']}), which looks destructive or hard "
                f"to undo. Is it reversible, and who may use it?",
                ["irreversible"], [{"file": a["file"], "line": a["line"]}]))

    for ref, hits in sorted(scan_result.get("action_refs", {}).items()):
        if not hits["razor"]:
            tree, node = ref.split(".", 1)
            role = next((i for i in scan_result.get("items", [])
                         if i["kind"] == "role" and i["symbol"] == tree), None)
            out.append(_question(
                tree, f"unused-action:{node}",
                f"The action {ref} is never referenced by any page. Is it only enforced in the API, "
                f"or is a screen missing?",
                ["security"], role["refs"] if role else []))

    for item in scan_result.get("items", []):
        if item["kind"] == "rule" and item.get("documented") is False:
            member = item["symbol"].rsplit(".", 1)[-1]
            signals = ["hardcoded"]
            if MONEY_WORDS.search(item["symbol"]) or MONEY_WORDS.search(item["behaviour"]):
                signals.append("money")
            dependents = sum(1 for s in scenarios if member in s["behaviour"] or member in s["title"])
            out.append(_question(
                item["symbol"], "hardcoded-value",
                f"{item['behaviour']} is hard-coded with no explanation nearby "
                f"({item['refs'][0]['file']}:{item['refs'][0]['line']}). Is it intended policy?",
                signals, item["refs"], dependents))

    out.sort(key=lambda q: (q["symbol"], q["reason"]))
    return out
