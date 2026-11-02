#!/usr/bin/env python3
"""Read a Shift Framework solution and list what it contains (no .NET parsing, line scanning only).

Output (all JSON-serialisable):
  items         [{kind, symbol, title, behaviour, parent_symbol, refs:[{file,line}], origin, documented?}]
  roles         names of the action trees defined or used in the solution
  api_actions   {"Controller/segment": {auth, file, line}}   what the API requires ("Controller/*" = default)
  ui_actions    {"Controller/segment": {auth, file, line, page_symbol}}   what the UI guards it with
  action_refs   {"Tree.Node": {"razor": [files], "cs": [files]}}   where each defined action is referenced
  warnings      files read with a fallback encoding
  files_scanned number of .cs, .razor and .feature files read

Items by kind: feature (pages, timers/queue functions), role (action trees), fr (DTO constraints),
rule (constants, refusals, data-level access, attention), scenario (existing .feature files).
Symbols are namespace-qualified for C# members and never contain line numbers; when two things still
share a symbol the later one gets a `~2` (`~3`, ...) suffix so ids stay unique.
Files are read as UTF-8 (BOM tolerated), UTF-16 (BOM) or Windows-1256, with universal newlines.
"""
import re
from collections import Counter
from pathlib import Path

IGNORED_DIRS = {"bin", "obj", "node_modules", ".git", ".vs", "packages", "__pycache__"}
DATA_LEVEL = {"Company", "CompanyBranch", "Brand", "Region", "City", "Country", "Team"}
NOT_A_DECLARATION = {"if", "for", "foreach", "while", "switch", "return", "using", "lock", "catch",
                     "else", "new", "await", "throw", "var", "do", "try", "fixed"}

NAMESPACE = re.compile(r"^\s*namespace\s+([\w.]+)")
CLASS = re.compile(r"^\s*(?:\[[^\]]*\]\s*)*(?:(?:public|internal|private|protected|static|sealed|abstract|"
                   r"partial|unsafe|readonly)\s+)*(?:class|record|struct)\s+(\w+)")
TREE_LEGACY = re.compile(r"\bclass\s+\w+\s*:\s*ActionTree\b")
TREE_ACTION = re.compile(r"\bstatic\s+(?:readonly\s+)?\w*Action\s+(\w+)")
CONST = re.compile(r"\bconst\s+(?:decimal|int|long|double|float|TimeSpan)\s+(\w+)\s*=\s*([^;]+);")
DECL = re.compile(r"^\s*(?:\[[^\]]*\]\s*)*(?:(?:public|private|protected|internal|static|override|virtual|"
                  r"async|sealed|abstract|new|partial|extern|unsafe)\s+)*[\w<>\[\],.?]+\s+(\w+)\s*\(")
THROW = re.compile(r'throw\s+new\s+ShiftEntityException\(\s*new\s+Message\(\s*"([^"]*)"\s*,\s*"([^"]*)"')
SAME_LINE_IF = re.compile(r"^\s*(?:else\s+)?(if\s*\(.*\))\s*throw\b")
DATA_ACCESS = re.compile(r"\bIEntityHas(\w+)<")
HTTP = re.compile(r'\[Http(?:Get|Post|Put|Delete|Patch)(?:\("([^"]*)"\))?\]')
NAMEOF = re.compile(r"nameof\(\s*(\w+)\.(\w+)\s*\)")
BASE_PATH = re.compile(r"base\(\s*((?:\w+\.)+\w+)\s*\)")
ENDPOINT_ATTR = re.compile(
    r'ShiftEntity(Secure)?Endpoint(?:WithMapper)?<[^"]*?>\(\s*"([^"]+)"(?:\s*,\s*nameof\(\s*(\w+)\.(\w+)\s*\))?')
RAZOR_TAG = re.compile(r"""<(ShiftList|ShiftEntityForm|ActionButton)\b((?:"[^"]*"|'[^']*'|[^>"'])*)>""", re.S)
ATTR = re.compile(r'([\w:@-]+)="([^"]*)"')
FUNCTION = re.compile(
    r'\[Function\("([^"]+)"\)\][^{]*?(TimerTrigger|ServiceBusTrigger|QueueTrigger)\("([^"]+)"', re.S)
DTO_ATTR = re.compile(r"^\s*\[(Required|StringLength|Range)\b(.*)\]\s*$")
PROPERTY = re.compile(r"\bpublic\s+[\w<>?,\[\]]+\s+(\w+)\s*\{")
STEP = re.compile(r"^\s*(Given|When|Then|And|But)\b")
STRING = re.compile(r'"(?:[^"\\]|\\.)*"')


def _files(root: Path):
    for f in sorted(root.rglob("*")):
        if f.is_file() and not (set(f.relative_to(root).parts[:-1]) & IGNORED_DIRS):
            yield f


def _read(path: Path, warnings: list, rel: str) -> str:
    raw = path.read_bytes()
    if raw[:2] in (b"\xff\xfe", b"\xfe\xff"):
        text = raw.decode("utf-16")
    else:
        try:
            text = raw.decode("utf-8-sig")
        except UnicodeDecodeError:
            warnings.append(rel)
            try:
                text = raw.decode("cp1256")
            except UnicodeDecodeError:
                text = raw.decode("utf-8", errors="replace")
    return text.replace("\r\n", "\n").replace("\r", "\n")


def _rel(path: Path, root: Path) -> str:
    return path.relative_to(root).as_posix()


def _line_of(text: str, index: int) -> int:
    return text.count("\n", 0, index) + 1


def _item(kind, symbol, title, behaviour, file, line, **extra):
    item = {"kind": kind, "symbol": symbol, "title": title, "behaviour": behaviour,
            "parent_symbol": None, "refs": [{"file": file, "line": line}], "origin": "scan"}
    item.update(extra)
    return item


def _ref(path_text: str):
    """'Tree.Node' or 'Pkg.ActionTrees.Tree.Node' -> (tree, node)."""
    parts = path_text.split(".")
    return (parts[-2], parts[-1]) if len(parts) >= 2 else (None, parts[-1])


def _use(out, tree, node, file, line):
    if tree:
        u = out["_used"].setdefault(tree, {"nodes": set(), "file": file, "line": line})
        u["nodes"].add(node)


def _code(line: str) -> str:
    return STRING.sub('""', line).split("//")[0]


def _scan_cs(text, rel, out):
    lines = text.split("\n")
    ns, depth, stack, pending_class = None, 0, [], None
    pending_tree, method, prev_comment, pending_attrs = False, None, False, []

    def qual(cls, member):
        return f"{ns}.{cls}.{member}" if ns else f"{cls}.{member}"

    for n, line in enumerate(lines, 1):
        stripped = line.strip()
        is_comment = stripped.startswith(("//", "/*", "*"))
        code = _code(line)
        if not is_comment:
            if (m := NAMESPACE.match(line)):
                ns = m.group(1)
            if "[ActionTree" in line:
                pending_tree = True
            if (m := CLASS.match(line)):
                pending_class, cls_name = m.group(1), m.group(1)
                method = None
                if pending_tree or TREE_LEGACY.search(line):
                    out["_trees"][cls_name] = {"file": rel, "line": n, "nodes": []}
                pending_tree = False
                for d in DATA_ACCESS.finditer(line):
                    if d.group(1) in DATA_LEVEL:
                        sym = qual(cls_name, f"DataLevelAccess.{d.group(1)}")
                        out["items"].append(_item("rule", sym, sym,
                                                  f"Rows are filtered by the user's {d.group(1).lower()} grants",
                                                  rel, n))
                if re.search(r"\bIHasAttention\b", line):
                    out["items"].append(_item("rule", qual(cls_name, "Attention"), qual(cls_name, "Attention"),
                                              "Records can carry an attention flag", rel, n))
            cls = stack[-1][0] if stack else None
            at_class_level = bool(stack) and depth == stack[-1][1]

            if at_class_level and not pending_class and (m := DECL.match(line)) \
                    and m.group(1) not in NOT_A_DECLARATION and "=" not in line.split("(")[0]:
                method = m.group(1)
            if cls and cls in out["_trees"] and at_class_level and (m := TREE_ACTION.search(line)):
                out["_trees"][cls]["nodes"].append(m.group(1))
            if cls and (m := CONST.search(line)):
                out["items"].append(_item("rule", qual(cls, m.group(1)), qual(cls, m.group(1)),
                                          f"{m.group(1)} = {m.group(2).strip()}", rel, n, documented=prev_comment))
            if cls and (m := THROW.search(line)):
                if (si := SAME_LINE_IF.match(line)):
                    cond = si.group(1)
                else:
                    cond = ""
                    for back in range(n - 2, max(n - 5, -1), -1):
                        prior = lines[back].strip()
                        if "throw" in prior:
                            break
                        if prior.startswith(("if", "else if", "case", "while")):
                            cond = prior
                            break
                sym = f"{qual(cls, method or '(class)')}#{m.group(1)}"
                out["items"].append(_item("rule", sym, sym, f"{cond} -> refuses: {m.group(2)}".strip(), rel, n))

            if (m := DTO_ATTR.match(line)):
                pending_attrs.append(m.group(1) + m.group(2))
            elif pending_attrs:
                if cls and (p := PROPERTY.search(line)):
                    for attr in pending_attrs:
                        what = f"{p.group(1)} is required" if attr == "Required" else f"{p.group(1)}: {attr}"
                        out["items"].append(_item("fr", qual(cls, p.group(1)), qual(cls, p.group(1)),
                                                  what, rel, n))
                    pending_attrs = []
                elif stripped and not stripped.startswith("["):
                    pending_attrs = []

        for ch in code:
            if ch == "{":
                depth += 1
                if pending_class:
                    stack.append((pending_class, depth))
                    pending_class = None
            elif ch == "}":
                if stack and stack[-1][1] == depth:
                    stack.pop()
                    method = None
                elif stack and depth == stack[-1][1] + 1:
                    method = None
                depth -= 1
        if stripped:
            prev_comment = stripped.startswith("//")


def _scan_endpoint_attributes(text, rel, out):
    for m in ENDPOINT_ATTR.finditer(text):
        ctrl = m.group(2).rstrip("/").split("/")[-1]
        line = _line_of(text, m.start())
        auth = m.group(4) if m.group(1) else None
        out["api_actions"][f"{ctrl}/*"] = {"auth": auth, "file": rel, "line": line}
        if m.group(1) and m.group(3):
            _use(out, m.group(3), m.group(4), rel, line)


def _scan_controller(text, rel, out):
    m = re.search(r"\bclass\s+(\w+?)Controller\b", text)
    if not m:
        return
    ctrl, default_auth, route, auth = m.group(1), None, None, None
    for n, line in enumerate(text.split("\n"), 1):
        if default_auth is None and ": base(" in line and (b := BASE_PATH.search(line)):
            tree, default_auth = _ref(b.group(1))
            _use(out, tree, default_auth, rel, n)
            out["api_actions"][f"{ctrl}/*"] = {"auth": default_auth, "file": rel, "line": n}
        if (h := HTTP.search(line)):
            segs = [s for s in (h.group(1) or "").split("/") if s and not s.startswith("{")]
            route = segs[-1] if segs else None
        if "[TypeAuth" in line and (a := NAMEOF.search(line)):
            auth = a.group(2)
            _use(out, a.group(1), auth, rel, n)
        if route and DECL.match(line):
            out["api_actions"][f"{ctrl}/{route}"] = {"auth": auth or default_auth, "file": rel, "line": n}
            route, auth = None, None


def _scan_razor(text, rel, symbol, out):
    list_auth, feature = None, None
    for tag in RAZOR_TAG.finditer(text):
        name, attrs = tag.group(1), dict(ATTR.findall(tag.group(2)))
        line = _line_of(text, tag.start())
        raw_action = attrs.get("TypeAuthAction", "")
        node = raw_action.rsplit(".", 1)[-1] or None
        if raw_action:
            t, nd = _ref(raw_action)
            _use(out, t, nd, rel, line)
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


def _unique_symbols(items):
    seen = Counter()
    for it in items:
        key = (it["kind"], it["symbol"])
        seen[key] += 1
        if seen[key] > 1:
            it["symbol"] = f"{it['symbol']}~{seen[key]}"
            it["title"] = it["symbol"]


def scan(root) -> dict:
    root = Path(root)
    out = {"items": [], "roles": [], "api_actions": {}, "ui_actions": {}, "action_refs": {},
           "warnings": [], "files_scanned": 0, "_trees": {}, "_used": {}}
    texts = {}
    for f in _files(root):
        if f.suffix in {".cs", ".razor", ".feature"}:
            rel = _rel(f, root)
            texts[rel] = _read(f, out["warnings"], rel)
    out["files_scanned"] = len(texts)

    for rel, text in texts.items():
        suffix = Path(rel).suffix
        if suffix == ".cs":
            _scan_cs(text, rel, out)
            _scan_functions(text, rel, out)
            _scan_endpoint_attributes(text, rel, out)
            _scan_controller(text, rel, out)
        elif suffix == ".razor":
            _scan_razor(text, rel, Path(rel).with_suffix("").as_posix(), out)
        else:
            _scan_feature_file(text, rel, out)

    trees, used = out.pop("_trees"), out.pop("_used")
    for name in sorted(set(trees) | set(used)):
        if name in trees:
            t = trees[name]
            out["items"].append(_item("role", name, name, "actions: " + ", ".join(t["nodes"]),
                                      t["file"], t["line"]))
        else:
            u = used[name]
            out["items"].append(_item("role", name, name, "actions used: " + ", ".join(sorted(u["nodes"])),
                                      u["file"], u["line"]))
        out["roles"].append(name)

    for tree, t in sorted(trees.items()):
        for node in t["nodes"]:
            hits = {"razor": [], "cs": []}
            for rel, text in texts.items():
                if rel != t["file"] and re.search(rf"\b{tree}\.{node}\b", text):
                    hits["razor" if rel.endswith(".razor") else "cs"].append(rel)
            out["action_refs"][f"{tree}.{node}"] = hits

    _unique_symbols(out["items"])
    out["items"].sort(key=lambda i: (i["kind"], i["symbol"]))
    out["warnings"].sort()
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
            if MONEY_WORDS.search(member) or MONEY_WORDS.search(item["behaviour"]):
                signals.append("money")
            dependents = sum(1 for s in scenarios if member in s["behaviour"] or member in s["title"])
            out.append(_question(
                item["symbol"], "hardcoded-value",
                f"{item['behaviour']} is hard-coded with no explanation nearby "
                f"({item['refs'][0]['file']}:{item['refs'][0]['line']}). Is it intended policy?",
                signals, item["refs"], dependents))

    out.sort(key=lambda q: (q["symbol"], q["reason"]))
    return out
