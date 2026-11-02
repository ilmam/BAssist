using ShiftSoftware.TypeAuth.Core;
using ShiftSoftware.TypeAuth.Core.Actions;

namespace Fixture.Shared;

[ActionTree("Orders", "Orders")]
public class OrdersActions
{
    public static ReadAction OrdersRead = new("Orders Read");
    public static ReadWriteAction OrdersCancel = new("Orders Cancel");
}
