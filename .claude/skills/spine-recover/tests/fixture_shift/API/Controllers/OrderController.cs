namespace Fixture.API.Controllers;

[Route("api/[controller]")]
public class OrderController : ShiftEntitySecureControllerAsync<OrderRepository, Order, OrderListDTO, OrderDTO>
{
    public OrderController() : base(OrdersActions.OrdersRead) { }

    [HttpPost("{id}/cancel")]
    [TypeAuth<OrdersActions>(nameof(OrdersActions.OrdersCancel), Access.Write)]
    public async Task<IActionResult> Cancel(string id)
    {
        return Ok();
    }
}
