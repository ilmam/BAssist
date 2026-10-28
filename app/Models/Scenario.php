<?php

namespace App\Models;

use App\Attributes\Relation;
use App\Attributes\RoutableAttribute;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasEntityStatus;
use App\Services\GherkinDocumentParser;
use App\Support\Tenancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

#[RoutableAttribute]
class Scenario extends BaseModel
{
    use BelongsToTenant;
    use HasEntityStatus;
    use HasFactory;

    protected $displayField = 'title';

    protected $fillable = [
        'title',
        'number',
        'feature_id',
        'stakeholder_need_id',
        'is_outline',
        'body',
        'status_id',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'is_outline' => 'boolean',
            'number' => 'integer',
        ]);
    }

    protected static function booted(): void
    {
        parent::booted();

        // SC-n, numbered per project through the owning feature. Never reused.
        static::creating(function (self $scenario): void {
            if (! blank($scenario->number) || blank($scenario->feature_id)) {
                return;
            }

            $projectId = (int) DB::table('features')->where('id', $scenario->feature_id)->value('project_id');
            if ($projectId < 1) {
                return;
            }

            $scenario->number = (int) DB::transaction(function () use ($projectId) {
                // Serialize numbering within a project when concurrent creates race.
                DB::table('projects')->where('id', $projectId)->lockForUpdate()->first();

                return (int) DB::table('scenarios')
                    ->join('features', 'features.id', '=', 'scenarios.feature_id')
                    ->where('features.project_id', $projectId)
                    ->max('scenarios.number') + 1;
            });
        });
    }

    /** Stable display code: SC-n (null until numbered). */
    protected function code(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->number === null ? null : 'SC-'.$this->number);
    }

    #[Relation('BelongsTo')]
    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }

    #[Relation('BelongsTo')]
    public function stakeholderNeed(): BelongsTo
    {
        return $this->belongsTo(StakeholderNeed::class);
    }

    #[Relation('BelongsTo')]
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    /**
     * Gherkin keyword for labels: Scenario vs Scenario Outline.
     */
    public function gherkinKeyword(): string
    {
        if ($this->is_outline || $this->bodyLooksLikeOutline()) {
            return 'Scenario Outline';
        }

        return 'Scenario';
    }

    /**
     * Sync list title / is_outline from the document body and normalize the header.
     */
    public function syncDocumentFields(?GherkinDocumentParser $parser = null): void
    {
        $parser ??= app(GherkinDocumentParser::class);
        $fallback = trim((string) ($this->title ?? '')) !== ''
            ? (string) $this->title
            : 'Untitled';

        $this->is_outline = $parser->bodyLooksLikeOutline($this->body)
            || (bool) $this->is_outline;

        // An exported file carries SC / status / rev tags; they are never stored.
        $this->body = $parser->ensureScenarioHeader(
            $parser->stripSystemTags($this->body),
            $fallback,
            (bool) $this->is_outline
        );

        $this->is_outline = $parser->bodyLooksLikeOutline($this->body);

        $fromBody = $parser->extractScenarioTitle($this->body);
        if ($fromBody !== null) {
            $this->title = $fromBody;
        }
    }

    protected function bodyLooksLikeOutline(): bool
    {
        return app(GherkinDocumentParser::class)->bodyLooksLikeOutline($this->body);
    }

    /** @return list<int> */
    public function owningProjectIds(): array
    {
        $featureIds = array_values(array_unique(array_filter([
            $this->getAttribute('feature_id'),
            $this->getOriginal('feature_id'),
        ])));

        return DB::table('features')->whereIn('id', $featureIds)->pluck('project_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    public function applyTenantConstraint(Builder $query, int $tenantId): void
    {
        $query->whereIn(
            $this->qualifyColumn('feature_id'),
            DB::table('features')
                ->select('features.id')
                ->whereIn('features.project_id', Tenancy::visibleProjectIdsQuery($tenantId))
                ->whereNull('features.deleted_at')
        );
    }
}
