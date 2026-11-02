using ShiftSoftware.TypeAuth.Core;

namespace Fixture.Shared;

public class OrdersActions : ActionTree
{
    public static readonly ActionTreeAction OrdersRead = new("Orders Read");
    public static readonly ActionTreeAction OrdersCancel = new("Orders Cancel");
}
