import sys, unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent.parent))
import recover_core as rc
import questions as qs


def make_questions(n):
    """n questions with distinct scores: question i has i dependents (capped at 5 by score)."""
    out = []
    for i in range(n):
        out.append({"symbol": f"Sym{i}", "reason": "hardcoded-value", "signals": ["hardcoded"],
                    "dependents": i % 6, "text": f"q{i}", "refs": []})
    return out


def qid(q):
    return rc.item_id("question", f"{q['symbol']}#{q['reason']}")


def thread(q, status):
    return {"status": status, "body": "asked " + rc.build_question_marker(qid(q))}


class SensitivityParseTests(unittest.TestCase):
    def test_rejects_bad_values_with_clear_message(self):
        for bad in ("-5", "150", "abc", "30.5", ""):
            with self.assertRaises(ValueError) as ctx:
                qs.parse_sensitivity(bad)
            self.assertIn("0 to 100", str(ctx.exception))

    def test_accepts_bounds_and_ints(self):
        self.assertEqual(qs.parse_sensitivity("0"), 0)
        self.assertEqual(qs.parse_sensitivity(100), 100)
        self.assertEqual(qs.parse_sensitivity(" 30 "), 30)


class ScoreTests(unittest.TestCase):
    def test_score_sums_weights_and_caps_dependents(self):
        q = {"signals": ["money", "security"], "dependents": 9}
        self.assertEqual(qs.score(q), 5 + 5 + 5)
        self.assertEqual(qs.score({"signals": [], "dependents": 0}), 0)


class SelectTests(unittest.TestCase):
    def setUp(self):
        self.qs = make_questions(10)

    def test_budget_from_sensitivity(self):
        self.assertEqual(len(qs.select(self.qs, [], 0)["file"]), 0)
        self.assertEqual(len(qs.select(self.qs, [], 100)["file"]), 10)
        self.assertEqual(len(qs.select(self.qs, [], 30)["file"]), 3)
        self.assertEqual(len(qs.select(self.qs, [], 25)["file"]), 3)  # 2.5 rounds up

    def test_filed_in_rank_order_highest_score_first(self):
        filed = qs.select(self.qs, [], 30)["file"]
        scores = [qs.score(q) for q in filed]
        self.assertEqual(scores, sorted(scores, reverse=True))
        self.assertEqual(scores[0], max(qs.score(q) for q in self.qs))

    def test_existing_open_threads_fill_the_budget(self):
        first = qs.select(self.qs, [], 30)["file"]
        threads = [thread(q, "open") for q in first]
        again = qs.select(self.qs, threads, 30)
        self.assertEqual(again["file"], [])
        self.assertEqual(again["occupied"], 3)

    def test_closing_threads_frees_room_and_never_refiles_closed(self):
        first = qs.select(self.qs, [], 30)["file"]
        threads = [thread(first[0], "closed"), thread(first[1], "closed"), thread(first[2], "open")]
        r = qs.select(self.qs, threads, 30)
        # non-closed total 8 -> budget ceil(2.4)=3, occupied 1 -> 2 new
        self.assertEqual(r["budget"], 3)
        self.assertEqual(r["occupied"], 1)
        self.assertEqual(r["closed"], 2)
        self.assertEqual(len(r["file"]), 2)
        refiled = {qid(q) for q in r["file"]}
        self.assertTrue(refiled.isdisjoint({qid(first[0]), qid(first[1]), qid(first[2])}))

    def test_answered_and_implemented_threads_occupy_budget(self):
        first = qs.select(self.qs, [], 30)["file"]
        threads = [thread(first[0], "answered"), thread(first[1], "implemented"),
                   thread(first[2], "open")]
        self.assertEqual(qs.select(self.qs, threads, 30)["file"], [])

    def test_ties_are_ordered_by_id_and_stable(self):
        tied = [{"symbol": f"T{i}", "reason": "r", "signals": ["money"], "dependents": 0,
                 "text": "", "refs": []} for i in range(6)]
        a = qs.select(tied, [], 50)["file"]
        b = qs.select(list(reversed(tied)), [], 50)["file"]
        self.assertEqual([qid(q) for q in a], [qid(q) for q in b])
        self.assertEqual([qid(q) for q in a], sorted(qid(q) for q in a))

    def test_empty_question_list(self):
        r = qs.select([], [], 30)
        self.assertEqual((r["file"], r["budget"], r["unfiled_left"]), ([], 0, 0))


if __name__ == "__main__":
    unittest.main()
