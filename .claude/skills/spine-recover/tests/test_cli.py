import contextlib, io, json, math, shutil, sys, tempfile, unittest
from pathlib import Path

HERE = Path(__file__).resolve().parent
sys.path.insert(0, str(HERE.parent))
import recover_cli as cli
import recover_core as rc

FIXTURE = HERE / "fixture_shift"


def run(*argv):
    out, err = io.StringIO(), io.StringIO()
    with contextlib.redirect_stdout(out), contextlib.redirect_stderr(err):
        code = cli.main([str(a) for a in argv])
    return code, out.getvalue(), err.getvalue()


def load(path):
    return json.loads(Path(path).read_text(encoding="utf-8"))


class CliTests(unittest.TestCase):
    def setUp(self):
        self.tmp = Path(tempfile.mkdtemp())
        self.inv = self.tmp / "inventory.json"
        self.plan = self.tmp / "plan.json"
        self.records = self.tmp / "records.json"
        self.comments = self.tmp / "comments.json"
        self.records.write_text("[]")
        self.comments.write_text("[]")

    def tearDown(self):
        shutil.rmtree(self.tmp, ignore_errors=True)

    def scan(self, root=FIXTURE):
        self.assertEqual(run("scan", root, "--out", self.inv)[0], 0)
        return load(self.inv)

    def do_plan(self, sensitivity):
        code, out, err = run("plan", "--inventory", self.inv, "--records", self.records,
                             "--comments", self.comments, "--sensitivity", sensitivity,
                             "--out", self.plan)
        return code, out, err

    def test_scan_writes_items_roles_questions_and_keeps_arabic(self):
        inv = self.scan()
        self.assertTrue(inv["items"] and inv["roles"] and inv["questions"])
        raw = self.inv.read_text(encoding="utf-8")
        self.assertIn("أرشيف الطلبات", raw)

    def test_first_plan_creates_everything_and_files_ceil_share_of_questions(self):
        inv = self.scan()
        self.assertEqual(self.do_plan(30)[0], 0)
        plan = load(self.plan)
        self.assertEqual(len(plan["create"]), len(inv["items"]))
        self.assertEqual(len(plan["file_questions"]), math.ceil(len(inv["questions"]) * 0.30))
        item = plan["create"][0]
        self.assertEqual(item["marker"], rc.build_marker(item["id"], item["hash"]))
        q = plan["file_questions"][0]
        self.assertEqual(q["marker"], rc.build_question_marker(q["id"]))

    def test_second_plan_after_applying_the_first_is_empty(self):
        self.scan()
        self.do_plan(30)
        first = load(self.plan)
        self.records.write_text(json.dumps([
            {"id": f"r{n}", "kind": i["kind"], "title": i["title"],
             "description": "Inferred from code: " + i["marker"]}
            for n, i in enumerate(first["create"])]))
        self.comments.write_text(json.dumps([
            {"id": f"c{n}", "record_id": "r0", "status": "open", "body": "Q " + q["marker"]}
            for n, q in enumerate(first["file_questions"])]))
        self.do_plan(30)
        second = load(self.plan)
        self.assertEqual(second["create"], [])
        self.assertEqual(second["file_questions"], [])
        self.assertEqual(len(second["skip"]), len(first["create"]))

    def test_sensitivity_zero_files_nothing_but_still_creates_records(self):
        self.scan()
        self.do_plan(100)
        full = load(self.plan)
        self.do_plan(0)
        zero = load(self.plan)
        self.assertEqual(zero["file_questions"], [])
        self.assertEqual(len(zero["create"]), len(full["create"]))
        self.assertGreater(len(full["file_questions"]), 0)

    def test_invalid_sensitivity_exits_2_with_message(self):
        self.scan()
        for bad in ("-5", "150", "abc", "30.5"):
            code, _, err = self.do_plan(bad)
            self.assertEqual(code, 2)
            self.assertIn("0 to 100", err)

    def test_empty_solution_plans_cleanly_and_says_zero(self):
        empty = self.tmp / "empty"
        empty.mkdir()
        self.scan(empty)
        code, out, _ = self.do_plan(30)
        self.assertEqual(code, 0)
        plan = load(self.plan)
        self.assertEqual((plan["create"], plan["file_questions"]), ([], []))
        self.assertIn("0 questions found", plan["summary"])
        self.assertIn("0 records to create", plan["summary"])
        self.assertIn(plan["summary"], out)

    def test_summary_mentions_below_threshold_and_possibly_removed(self):
        self.scan()
        self.records.write_text(json.dumps([
            {"id": "gone", "kind": "rule", "title": "Old",
             "description": rc.build_marker(rc.item_id("rule", "Old.Thing"), "abcd1234")}]))
        self.do_plan(30)
        plan = load(self.plan)
        self.assertRegex(plan["summary"], r"\d+ below threshold")
        self.assertIn("1 possibly removed or renamed", plan["summary"])


if __name__ == "__main__":
    unittest.main()
