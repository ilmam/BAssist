using System.ComponentModel.DataAnnotations;

namespace Fixture.Shared.DTOs;

public class OrderDTO : ShiftEntityViewAndUpsertDTO
{
    public override string? ID { get; set; }

    [Required]
    public string CustomerName { get; set; }

    public decimal Discount { get; set; }
}
