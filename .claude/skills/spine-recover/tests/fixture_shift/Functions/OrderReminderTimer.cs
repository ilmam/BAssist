namespace Fixture.Functions;

public class OrderReminderTimer
{
    [Function("OrderReminderTimer")]
    public async Task Run([TimerTrigger("0 0 8 * * *")] TimerInfo timer)
    {
        await Task.CompletedTask;
    }
}
