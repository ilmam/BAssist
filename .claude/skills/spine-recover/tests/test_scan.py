import shutil, sys, tempfile, unittest
from pathlib import Path

HERE = Path(__file__).resolve().parent
sys.path.insert(0, str(HERE.parent))
import shift_scan as ss

FIXTURE = HERE / "fixture_shift"


def kinds_and_symbols(result):
    return {(i["kind"], i["symbol"]) for i in result["items"]}


class ScanFixtureTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.result = ss.scan(FIXTURE)

    def test_items_are_exactly_the_expected_kinds_and_symbols(self):
        self.assertEqual(kinds_and_symbols(self.result), {
            ("feature", "Web/Pages/Orders/OrderList"),
            ("feature", "Web/Pages/Orders/OrderForm"),
            ("feature", "Web/Pages/Orders/OrderArchive"),
            ("feature", "Functions/OrderReminderTimer"),
            ("role", "OrdersActions"),
            ("fr", "OrderDTO.CustomerName"),
            ("rule", "Order.MaxDiscount"),
            ("rule", "Order.CancelWindowDays"),
            ("rule", "Order.DataLevelAccess.Company"),
            ("rule", "OrderRepository.UpsertAsync#Discount"),
            ("scenario", "Tests/Features/Orders.feature#Cancel an order"),
        })

    def test_roles_listed(self):
        self.assertEqual(self.result["roles"], ["OrdersActions"])

    def test_arabic_title_is_preserved_verbatim(self):
        archive = next(i for i in self.result["items"] if i["symbol"].endswith("OrderArchive"))
        self.assertEqual(archive["title"], "أرشيف الطلبات")

    def test_refs_carry_file_and_line(self):
        lst = next(i for i in self.result["items"] if i["symbol"].endswith("OrderList"))
        self.assertEqual(lst["refs"][0]["file"], "Web/Pages/Orders/OrderList.razor")
        self.assertGreaterEqual(lst["refs"][0]["line"], 1)

    def test_rule_behaviour_and_documented_flag(self):
        by = {i["symbol"]: i for i in self.result["items"]}
        self.assertIn("0.20m", by["Order.MaxDiscount"]["behaviour"])
        self.assertFalse(by["Order.MaxDiscount"]["documented"])
        self.assertTrue(by["Order.CancelWindowDays"]["documented"])
        self.assertIn("dto.Discount > Order.MaxDiscount",
                      by["OrderRepository.UpsertAsync#Discount"]["behaviour"])

    def test_api_and_ui_actions_recorded(self):
        self.assertEqual(self.result["api_actions"]["Order/cancel"]["auth"], "OrdersCancel")
        self.assertEqual(self.result["ui_actions"]["Order/cancel"]["auth"], "OrdersRead")
        self.assertTrue(self.result["ui_actions"]["Order/cancel"]["page_symbol"].endswith("OrderList"))

    def test_action_refs_split_by_razor_and_cs(self):
        refs = self.result["action_refs"]
        self.assertTrue(refs["OrdersActions.OrdersRead"]["razor"])
        self.assertEqual(refs["OrdersActions.OrdersCancel"]["razor"], [])
        self.assertTrue(refs["OrdersActions.OrdersCancel"]["cs"])

    def test_crlf_and_bom_give_the_same_result_as_lf(self):
        tmp = Path(tempfile.mkdtemp())
        try:
            copy = tmp / "lf"
            shutil.copytree(FIXTURE, copy)
            for f in copy.rglob("*"):
                if f.is_file():
                    text = f.read_bytes().decode("utf-8-sig").replace("\r\n", "\n")
                    f.write_bytes(text.encode("utf-8"))
            self.assertEqual(ss.scan(copy), self.result)
        finally:
            shutil.rmtree(tmp, ignore_errors=True)


class ScanEdgeTests(unittest.TestCase):
    def test_empty_directory(self):
        tmp = Path(tempfile.mkdtemp())
        try:
            r = ss.scan(tmp)
            self.assertEqual((r["items"], r["roles"], r["api_actions"], r["ui_actions"]), ([], [], {}, {}))
        finally:
            shutil.rmtree(tmp, ignore_errors=True)

    def test_build_output_folders_are_ignored(self):
        tmp = Path(tempfile.mkdtemp())
        try:
            (tmp / "obj").mkdir()
            (tmp / "obj" / "X.razor").write_text('<ShiftList EntitySet="A" T="B" />', encoding="utf-8")
            self.assertEqual(ss.scan(tmp)["items"], [])
        finally:
            shutil.rmtree(tmp, ignore_errors=True)

    def test_attention_interface_becomes_a_rule(self):
        tmp = Path(tempfile.mkdtemp())
        try:
            (tmp / "Ticket.cs").write_text(
                "public class Ticket : ShiftEntity<Ticket>, IHasAttention\n{\n}\n", encoding="utf-8")
            self.assertIn(("rule", "Ticket.Attention"), kinds_and_symbols(ss.scan(tmp)))
        finally:
            shutil.rmtree(tmp, ignore_errors=True)


if __name__ == "__main__":
    unittest.main()
