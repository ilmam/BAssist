namespace Fixture.Data.Repositories;

public class OrderRepository : ShiftRepository<DB, Order, OrderListDTO, OrderDTO>
{
    public OrderRepository(DB db) : base(db) { }

    public override async ValueTask<Order> UpsertAsync(Order entity, OrderDTO dto, ActionTypes actionType, long? userId = null)
    {
        if (dto.Discount > Order.MaxDiscount)
            throw new ShiftEntityException(new Message("Discount", "Discount is above the allowed maximum"));

        return await base.UpsertAsync(entity, dto, actionType, userId);
    }
}
