import sys, unicodedata, unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent.parent))
import recover_core as rc


class ItemIdTests(unittest.TestCase):
    def test_id_ignores_whitespace_kind_case_and_line_endings(self):
        a = rc.item_id("Rule", "Orders.DiscountCap")
        b = rc.item_id("rule", "  Orders.DiscountCap\r\n")
        self.assertEqual(a, b)
        self.assertEqual(len(a), 12)

    def test_id_ignores_unicode_normalisation_form(self):
        text = "طلب الشراء é"
        nfc = unicodedata.normalize("NFC", text)
        nfd = unicodedata.normalize("NFD", text)
        self.assertNotEqual(nfc, nfd)
        self.assertEqual(rc.item_id("feature", nfc), rc.item_id("feature", nfd))

    def test_different_kinds_differ(self):
        self.assertNotEqual(rc.item_id("rule", "X"), rc.item_id("fr", "X"))


class BehaviourHashTests(unittest.TestCase):
    def test_hash_collapses_whitespace(self):
        self.assertEqual(rc.behaviour_hash("a  and  b"), rc.behaviour_hash("a and b\r\n"))
        self.assertEqual(len(rc.behaviour_hash("a")), 8)

    def test_hash_changes_with_behaviour(self):
        self.assertNotEqual(rc.behaviour_hash("cap 0.20"), rc.behaviour_hash("cap 0.25"))


class MarkerTests(unittest.TestCase):
    def test_parse_finds_all_three_marker_types_in_prose(self):
        text = (
            "Inferred from code. " + rc.build_marker("aaaaaaaaaaaa", "11111111")
            + " and also " + rc.build_question_marker("bbbbbbbbbbbb")
            + "\nplus " + rc.build_drift_marker("cccccccccccc", "22222222")
        )
        found = rc.parse_markers(text)
        self.assertEqual(
            found,
            [
                {"type": "record", "id": "aaaaaaaaaaaa", "hash": "11111111"},
                {"type": "question", "id": "bbbbbbbbbbbb", "hash": None},
                {"type": "drift", "id": "cccccccccccc", "hash": "22222222"},
            ],
        )

    def test_parse_returns_empty_for_none_or_malformed(self):
        self.assertEqual(rc.parse_markers("no markers here"), [])
        self.assertEqual(rc.parse_markers("broken [recover: and [recover:zz]"), [])


if __name__ == "__main__":
    unittest.main()
