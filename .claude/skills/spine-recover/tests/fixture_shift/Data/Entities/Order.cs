namespace Fixture.Data.Entities;

[TemporalShiftEntity]
public class Order : ShiftEntity<Order>, IEntityHasCompany<Order>
{
    public string CustomerName { get; set; }
    public decimal Discount { get; set; }

    public const decimal MaxDiscount = 0.20m;

    // Orders older than this many days cannot be cancelled. Agreed with finance, ticket 4411.
    public const int CancelWindowDays = 30;
}
