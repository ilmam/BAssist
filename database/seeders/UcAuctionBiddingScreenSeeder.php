<?php

namespace Database\Seeders;

use App\Models\FunctionalRequirement;
use App\Models\Project;
use App\Models\Screen;
use Database\Seeders\Concerns\SeedsScreenElements;
use Illuminate\Database\Seeder;

/**
 * Design layer PoC (docs/design-layer.md): one hand-filled UCAuction screen,
 * the bidder's bidding screen, drawn only from what FR-3 states.
 *
 *   php artisan db:seed --class=UcAuctionBiddingScreenSeeder
 */
class UcAuctionBiddingScreenSeeder extends Seeder
{
    use SeedsScreenElements;

    public function run(): void
    {
        $project = Project::withoutGlobalScopes()->where('name', 'Used Cars Auction')->firstOrFail();
        $fr3 = FunctionalRequirement::withoutGlobalScopes()
            ->where('project_id', $project->id)->where('number', 3)->firstOrFail();

        $screen = Screen::withoutGlobalScopes()->updateOrCreate(
            ['project_id' => $project->id, 'title' => 'Vehicle bidding'],
            ['description' => 'The bidder sees the current highest bid and raises it. Realizes FR-3.'],
        );
        $screen->functionalRequirements()->syncWithoutDetaching([$fr3->id]);

        // Sample amounts are illustrative placeholders, not business data.
        // [kind, label, row hint, children]; FR-3 asks for the current highest bid, a time limit,
        // an amount field, quick-raise buttons with the resulting bid beneath each, and an
        // indication of whether the highest bid is the bidder's own.
        $this->seedScreenElements($screen, [
            ['columns', '', null, [
                ['panel', 'Auction', null, [
                    ['label', 'Time left: 00:45'],
                    ['label', 'Current highest bid: 12,500,000 IQD'],
                    ['label', 'Highest bid is: yours / another bidder'],
                ]],
                ['panel', 'Raise the bid', null, [
                    ['input', 'Bid amount'],
                    ['button', 'Place bid'],
                    ['separator', ''],
                    ['button', '+50K IQD', 1],
                    ['button', '+100K IQD', 1],
                    ['button', '+250K IQD', 1],
                    ['button', '+500K IQD', 1],
                    ['label', '12,550,000', 2],
                    ['label', '12,600,000', 2],
                    ['label', '12,750,000', 2],
                    ['label', '13,000,000', 2],
                ]],
            ]],
        ]);
    }
}
