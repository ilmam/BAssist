import sys, unittest
from pathlib import Path

HERE = Path(__file__).resolve().parent
sys.path.insert(0, str(HERE.parent))
import shift_scan as ss

FIXTURE = HERE / "fixture_shift"


def by_reason(questions, prefix):
    return [q for q in questions if q["reason"].startswith(prefix)]


def bare(api=None, ui=None, items=None, refs=None):
    return {"items": items or [], "roles": [], "api_actions": api or {},
            "ui_actions": ui or {}, "action_refs": refs or {}}


class DetectOnFixtureTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.questions = ss.detect_questions(ss.scan(FIXTURE))

    def test_seeded_ui_api_mismatch_is_found_with_both_locations(self):
        found = by_reason(self.questions, "ui-api-mismatch")
        self.assertEqual(len(found), 1)
        q = found[0]
        self.assertTrue(q["symbol"].endswith("OrderList"))
        self.assertIn("cross_layer", q["signals"])
        self.assertIn("security", q["signals"])
        files = {r["file"] for r in q["refs"]}
        self.assertEqual(files, {"Web/Pages/Orders/OrderList.razor", "API/Controllers/OrderController.cs"})
        self.assertIn("OrdersRead", q["text"])
        self.assertIn("OrdersCancel", q["text"])

    def test_undocumented_constant_is_a_money_hardcoded_value(self):
        found = by_reason(self.questions, "hardcoded-value")
        self.assertEqual([q["symbol"] for q in found], ["Fixture.Data.Entities.Order.MaxDiscount"])
        self.assertEqual(sorted(found[0]["signals"]), ["hardcoded", "money"])

    def test_unused_action_reported_only_for_actions_no_ui_references(self):
        found = by_reason(self.questions, "unused-action")
        self.assertEqual([q["reason"] for q in found], ["unused-action:OrdersCancel"])
        self.assertEqual(found[0]["symbol"], "OrdersActions")

    def test_destructive_looking_action_is_flagged_irreversible(self):
        found = by_reason(self.questions, "destructive-action")
        self.assertEqual(len(found), 1)
        self.assertIn("irreversible", found[0]["signals"])
        self.assertTrue(found[0]["symbol"].endswith("OrderList"))

    def test_output_is_deterministic_and_sorted(self):
        again = ss.detect_questions(ss.scan(FIXTURE))
        self.assertEqual(again, self.questions)
        keys = [(q["symbol"], q["reason"]) for q in self.questions]
        self.assertEqual(keys, sorted(keys))

    def test_every_question_has_the_contract_fields(self):
        for q in self.questions:
            self.assertTrue({"symbol", "reason", "text", "signals", "dependents", "refs"} <= set(q))


class DetectEdgeTests(unittest.TestCase):
    def test_no_mismatch_when_ui_and_api_agree(self):
        r = bare(api={"A/x": {"auth": "T", "file": "a.cs", "line": 1}},
                 ui={"A/x": {"auth": "T", "file": "p.razor", "line": 2, "page_symbol": "P"}})
        self.assertEqual(by_reason(ss.detect_questions(r), "ui-api-mismatch"), [])

    def test_unguarded_ui_against_guarded_api_is_a_security_mismatch(self):
        r = bare(api={"A/x": {"auth": "T", "file": "a.cs", "line": 1}},
                 ui={"A/x": {"auth": None, "file": "p.razor", "line": 2, "page_symbol": "P"}})
        q = by_reason(ss.detect_questions(r), "ui-api-mismatch")
        self.assertEqual(len(q), 1)
        self.assertIn("security", q[0]["signals"])

    def test_dependents_count_scenarios_that_mention_the_rule(self):
        rule = {"kind": "rule", "symbol": "Order.MaxDiscount", "title": "x", "behaviour": "MaxDiscount = 0.2m",
                "parent_symbol": None, "refs": [{"file": "O.cs", "line": 3}], "documented": False}
        sc = {"kind": "scenario", "symbol": "f#s", "title": "s", "behaviour": "When discount above MaxDiscount",
              "parent_symbol": None, "refs": []}
        q = by_reason(ss.detect_questions(bare(items=[rule, sc])), "hardcoded-value")
        self.assertEqual(q[0]["dependents"], 1)

    def test_empty_scan_gives_no_questions(self):
        self.assertEqual(ss.detect_questions(bare()), [])


if __name__ == "__main__":
    unittest.main()
