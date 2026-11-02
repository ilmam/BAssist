import sys, unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent.parent))
import recover_core as rc
import reconcile as rec


def item(kind, symbol, behaviour="does a thing", title=None):
    return {"kind": kind, "symbol": symbol, "behaviour": behaviour, "title": title or symbol,
            "parent_symbol": None, "refs": []}


def record_for(it, rid, behaviour=None):
    marker = rc.build_marker(rc.item_id(it["kind"], it["symbol"]),
                             rc.behaviour_hash(behaviour or it["behaviour"]))
    return {"id": rid, "kind": it["kind"], "title": it["title"],
            "description": "Inferred from code: " + marker}


ITEMS = [item("feature", "Pages/OrderList"), item("rule", "Orders.DiscountCap", "cap is 0.20"),
         item("fr", "Order.CustomerName", "required")]


class ReconcileTests(unittest.TestCase):
    def test_empty_project_creates_everything(self):
        r = rec.reconcile(ITEMS, [], [])
        self.assertEqual(len(r["create"]), 3)
        self.assertEqual((r["skip"], r["drift"], r["possibly_removed"], r["overlap"]), ([], [], [], []))

    def test_second_run_skips_everything(self):
        records = [record_for(it, f"r{i}") for i, it in enumerate(ITEMS)]
        r = rec.reconcile(ITEMS, records, [])
        self.assertEqual(r["create"], [])
        self.assertEqual(len(r["skip"]), 3)

    def test_changed_behaviour_is_drift_once(self):
        records = [record_for(ITEMS[0], "r0"), record_for(ITEMS[1], "r1", "cap is 0.15"),
                   record_for(ITEMS[2], "r2")]
        r = rec.reconcile(ITEMS, records, [])
        self.assertEqual(len(r["drift"]), 1)
        d = r["drift"][0]
        self.assertEqual(d["record_id"], "r1")
        self.assertEqual(d["old_hash"], rc.behaviour_hash("cap is 0.15"))
        self.assertEqual(d["new_hash"], rc.behaviour_hash("cap is 0.20"))
        self.assertEqual(r["create"], [])
        # once the drift comment exists for that new hash, it is not raised again
        iid = rc.item_id("rule", "Orders.DiscountCap")
        comment = {"id": "c1", "record_id": "r1", "status": "closed",
                   "body": rc.build_drift_marker(iid, rc.behaviour_hash("cap is 0.20"))}
        again = rec.reconcile(ITEMS, records, [comment])
        self.assertEqual(again["drift"], [])
        self.assertEqual(again["create"], [])

    def test_rename_creates_new_and_reports_old_as_possibly_removed(self):
        old = item("rule", "Orders.OldName")
        records = [record_for(old, "rOld")] + [record_for(it, f"r{i}") for i, it in enumerate(ITEMS)]
        r = rec.reconcile(ITEMS, records, [])
        self.assertEqual(r["create"], [])
        self.assertEqual([p["record_id"] for p in r["possibly_removed"]], ["rOld"])
        renamed = ITEMS + [item("rule", "Orders.NewName")]
        r2 = rec.reconcile(renamed, records, [])
        self.assertEqual([i["symbol"] for i in r2["create"]], ["Orders.NewName"])

    def test_hand_written_record_with_same_title_is_overlap_not_create(self):
        mine = item("feature", "Pages/OrderList", title="Order List")
        hand = {"id": "h1", "kind": "feature", "title": "order list", "description": "Written by a BA"}
        r = rec.reconcile([mine], [hand], [])
        self.assertEqual(r["create"], [])
        self.assertEqual([(o["record_id"], o["item"]["symbol"]) for o in r["overlap"]],
                         [("h1", "Pages/OrderList")])

    def test_marker_edited_away_is_treated_as_new(self):
        rcd = record_for(ITEMS[0], "r0")
        rcd["description"] = "A person rewrote this"
        rcd["title"] = "Something else"
        r = rec.reconcile([ITEMS[0]], [rcd], [])
        self.assertEqual(len(r["create"]), 1)

    def test_partial_run_creates_only_the_rest(self):
        records = [record_for(ITEMS[0], "r0")]
        r = rec.reconcile(ITEMS, records, [])
        self.assertEqual([i["symbol"] for i in r["create"]], ["Orders.DiscountCap", "Order.CustomerName"])
        self.assertEqual(len(r["skip"]), 1)

    def test_empty_inputs(self):
        r = rec.reconcile([], [], [])
        self.assertTrue(all(v == [] for v in r.values()))


if __name__ == "__main__":
    unittest.main()
