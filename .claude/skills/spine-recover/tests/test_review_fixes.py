"""Regression tests for the whole-branch review findings (real-world Shift code, BAssist limits)."""
import contextlib, io, json, shutil, sys, tempfile, unittest
from pathlib import Path

HERE = Path(__file__).resolve().parent
sys.path.insert(0, str(HERE.parent))
import recover_cli as cli
import recover_core as rc
import reconcile as rec
import shift_scan as ss

FIXTURE = HERE / "fixture_shift"


class TmpSolution(unittest.TestCase):
    def setUp(self):
        self.tmp = Path(tempfile.mkdtemp())

    def tearDown(self):
        shutil.rmtree(self.tmp, ignore_errors=True)

    def sol(self, files):
        for name, content in files.items():
            f = self.tmp / name
            f.parent.mkdir(parents=True, exist_ok=True)
            f.write_bytes(content if isinstance(content, bytes) else content.encode("utf-8"))
        return self.tmp

    def symbols(self, files, kind=None):
        r = ss.scan(self.sol(files))
        return {i["symbol"] for i in r["items"] if kind is None or i["kind"] == kind}


class ActionTreeShapeTests(TmpSolution):
    def test_attribute_style_tree_with_typed_actions(self):
        r = ss.scan(self.sol({"T.cs": '[ActionTree("Services", "Services")]\npublic class ServicesTree\n{\n'
                              '    public static ReadWriteDeleteAction Technician = new("T");\n'
                              '    public static BooleanAction Flag = new("F");\n}\n'}))
        self.assertEqual(r["roles"], ["ServicesTree"])
        role = next(i for i in r["items"] if i["kind"] == "role")
        self.assertIn("Technician", role["behaviour"])
        self.assertIn("Flag", role["behaviour"])

    def test_external_tree_used_by_a_controller_becomes_a_role(self):
        r = ss.scan(self.sol({"TechnicianController.cs":
            "public class TechnicianController : ShiftEntitySecureControllerAsync<A, B, C, D>\n{\n"
            "    public TechnicianController() : base(ToyotaIraq.ActionTrees.Services.Technician) { }\n}\n"}))
        self.assertEqual(r["roles"], ["Services"])
        self.assertEqual(r["api_actions"]["Technician/*"]["auth"], "Technician")

    def test_entity_level_secure_endpoint_attribute_is_an_api_action(self):
        r = ss.scan(self.sol({"Technician.cs":
            '[ShiftEntitySecureEndpoint<TechnicianListDTO, TechnicianDTO, ServicesTree>("api/Technician", '
            'nameof(ServicesTree.Technician))]\npublic class Technician : ShiftEntity<Technician> { }\n'}))
        self.assertEqual(r["api_actions"]["Technician/*"]["auth"], "Technician")
        self.assertIn("ServicesTree", r["roles"])


class SymbolStabilityTests(TmpSolution):
    def test_same_class_name_in_two_namespaces_stays_distinct(self):
        got = self.symbols({
            "A.cs": "namespace Alpha;\npublic class Settings\n{\n    public const int Max = 5;\n}\n",
            "B.cs": "namespace Beta\n{\n    public class Settings\n    {\n        public const int Max = 6;\n    }\n}\n"},
            "rule")
        self.assertEqual(got, {"Alpha.Settings.Max", "Beta.Settings.Max"})

    def test_identical_refusals_in_one_method_get_distinct_symbols_and_own_conditions(self):
        r = ss.scan(self.sol({"R.cs":
            "namespace N;\npublic class R\n{\n    public void A()\n    {\n"
            '        if (a) throw new ShiftEntityException(new Message("Error", "one"));\n'
            '        if (b) throw new ShiftEntityException(new Message("Error", "two"));\n    }\n}\n'}))
        rules = {i["symbol"]: i for i in r["items"] if i["kind"] == "rule"}
        self.assertEqual(set(rules), {"N.R.A#Error", "N.R.A#Error~2"})
        self.assertIn("if (a)", rules["N.R.A#Error"]["behaviour"])
        self.assertIn("if (b)", rules["N.R.A#Error~2"]["behaviour"])

    def test_doc_comment_text_does_not_rename_the_class(self):
        got = self.symbols({"C.cs": "namespace N;\npublic class Real\n{\n"
                            "    /// Checks this class against limits\n    public const int Max = 1;\n}\n"}, "rule")
        self.assertEqual(got, {"N.Real.Max"})

    def test_nested_class_does_not_leak_into_outer_constants(self):
        got = self.symbols({"C.cs": "namespace N;\npublic class Outer\n{\n    public class Inner\n    {\n"
                            "        public const int A = 1;\n    }\n    public const int B = 2;\n}\n"}, "rule")
        self.assertEqual(got, {"N.Inner.A", "N.Outer.B"})

    def test_method_without_access_modifier_gets_its_own_name(self):
        got = self.symbols({"C.cs": "namespace N;\npublic class R\n{\n    public void A()\n    {\n"
                            '        if (x) throw new ShiftEntityException(new Message("X", "a"));\n    }\n\n'
                            "    async Task Validate()\n    {\n"
                            '        if (y) throw new ShiftEntityException(new Message("Y", "b"));\n    }\n}\n'}, "rule")
        self.assertEqual(got, {"N.R.A#X", "N.R.Validate#Y"})


class RobustnessTests(TmpSolution):
    def test_non_utf8_files_do_not_crash_the_scan(self):
        got = self.symbols({
            "Old.cs": "// تعليق\npublic class C\n{\n    public const int Max = 1;\n}\n".encode("cp1256"),
            "New.cs": "namespace U;\npublic class D\n{\n    public const int Max = 2;\n}\n".encode("utf-16")}, "rule")
        self.assertEqual(got, {"C.Max", "U.D.Max"})

    def test_gt_inside_an_attribute_value_does_not_truncate_the_tag(self):
        r = ss.scan(self.sol({"P.razor":
            '<ShiftList EntitySet="Order" T="X" Where="x => x.Active" TypeAuthAction="OrdersActions.OrdersRead">\n'
            '    <ActionButton T="X" OnClick="@(() => Go())" Endpoint="api/Order/cancel" />\n</ShiftList>\n'}))
        feature = next(i for i in r["items"] if i["kind"] == "feature")
        self.assertIn("OrdersRead", feature["behaviour"])
        self.assertEqual(r["ui_actions"]["Order/cancel"]["auth"], "OrdersRead")

    def test_scan_of_a_missing_path_fails_but_an_empty_folder_only_warns(self):
        def run(*argv):
            out, err = io.StringIO(), io.StringIO()
            with contextlib.redirect_stdout(out), contextlib.redirect_stderr(err):
                return cli.main([str(a) for a in argv]), err.getvalue()

        code, err = run("scan", self.tmp / "nope", "--out", self.tmp / "o.json")
        self.assertEqual(code, 1)
        self.assertIn("not a folder", err)
        empty = self.tmp / "empty"
        empty.mkdir()
        code, err = run("scan", empty, "--out", self.tmp / "o2.json")
        self.assertEqual(code, 0)
        self.assertIn("no .cs, .razor or .feature files", err)


class ReconcileFixTests(unittest.TestCase):
    def test_model_written_items_never_raise_drift(self):
        it = {"kind": "scenario", "symbol": "P#cancel", "behaviour": "reworded steps", "title": "Cancel",
              "parent_symbol": None, "refs": [], "origin": "model"}
        record = {"id": "r1", "kind": "scenario", "title": "Cancel",
                  "description": rc.build_marker(rc.item_id("scenario", "P#cancel"), rc.behaviour_hash("old steps"))}
        r = rec.reconcile([it], [record], [])
        self.assertEqual((r["drift"], r["create"], len(r["skip"])), ([], [], 1))

    def test_overlap_matches_bassist_entity_names(self):
        it = {"kind": "fr", "symbol": "S.X", "behaviour": "b", "title": "Customer name required",
              "parent_symbol": None, "refs": []}
        hand = {"id": "h1", "kind": "Functional Requirement", "title": "customer name required",
                "description": "written by a BA"}
        r = rec.reconcile([it], [hand], [])
        self.assertEqual([o["record_id"] for o in r["overlap"]], ["h1"])
        self.assertEqual(r["create"], [])


class CliFixTests(TmpSolution):
    def plan(self, inventory, records=(), comments=(), sensitivity=100):
        for name, data in (("inv", inventory), ("rec", list(records)), ("com", list(comments))):
            (self.tmp / f"{name}.json").write_text(json.dumps(data), encoding="utf-8")
        out = io.StringIO()
        with contextlib.redirect_stdout(out):
            code = cli.main(["plan", "--inventory", str(self.tmp / "inv.json"),
                             "--records", str(self.tmp / "rec.json"), "--comments", str(self.tmp / "com.json"),
                             "--sensitivity", str(sensitivity), "--out", str(self.tmp / "plan.json")])
        self.assertEqual(code, 0)
        return json.loads((self.tmp / "plan.json").read_text(encoding="utf-8"))

    def test_create_items_carry_a_description_that_starts_with_the_marker(self):
        item = {"kind": "rule", "symbol": "N.R.Max", "title": "N.R.Max", "behaviour": "Max = 1",
                "parent_symbol": None, "refs": [{"file": "R.cs", "line": 4}]}
        plan = self.plan({"items": [item], "roles": [], "questions": []})
        d = plan["create"][0]["description"]
        self.assertTrue(d.startswith(plan["create"][0]["marker"]))
        self.assertIn("Inferred from code", d)
        self.assertIn("R.cs:4", d)

    def test_file_questions_name_the_item_and_existing_record_they_concern(self):
        item = {"kind": "rule", "symbol": "N.R.Max", "title": "N.R.Max", "behaviour": "Max = 1",
                "parent_symbol": None, "refs": []}
        q = {"symbol": "N.R.Max", "reason": "hardcoded-value", "text": "t", "signals": ["hardcoded"],
             "dependents": 0, "refs": []}
        loose = {"symbol": "Order/cancel", "reason": "destructive-action:Order/cancel", "text": "t",
                 "signals": ["irreversible"], "dependents": 0, "refs": []}
        inv = {"items": [item], "roles": [], "questions": [q, loose]}
        first = self.plan(inv)
        by = {x["symbol"]: x for x in first["file_questions"]}
        self.assertEqual(by["N.R.Max"]["target_item_id"], rc.item_id("rule", "N.R.Max"))
        self.assertIsNone(by["N.R.Max"]["target_record_id"])
        self.assertIsNone(by["Order/cancel"]["target_item_id"])
        record = {"id": "rec9", "kind": "Business Rule", "title": "N.R.Max",
                  "description": first["create"][0]["description"]}
        second = self.plan(inv, records=[record])
        by2 = {x["symbol"]: x for x in second["file_questions"]}
        self.assertEqual(by2["N.R.Max"]["target_record_id"], "rec9")

    def test_note_when_roles_become_visible_and_main_stakeholder_exists(self):
        inv = {"items": [], "roles": ["Services"], "questions": []}
        main = {"id": "m1", "kind": "Stakeholder Need", "title": "Main stakeholder", "description": ""}
        notes = self.plan(inv, records=[main])["notes"]
        self.assertEqual([n["record_id"] for n in notes], ["m1"])
        posted = {"id": "c1", "record_id": "m1", "status": "open", "body": "x " + notes[0]["marker"]}
        self.assertEqual(self.plan(inv, records=[main], comments=[posted])["notes"], [])
        self.assertEqual(self.plan({"items": [], "roles": [], "questions": []}, records=[main])["notes"], [])


if __name__ == "__main__":
    unittest.main()
