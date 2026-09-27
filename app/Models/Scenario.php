<?php

namespace App\Models;

use App\Attributes\Relation;
use App\Attributes\RoutableAttribute;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasEntityStatus;
use App\Services\GherkinDocumentParser;
use App\Support\Tenancy;
use Illuminate\Database\Eloquent\Builder;
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
        ]);
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

        $this->body = $parser->ensureScenarioHeader(
            $this->body,
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

    public function applyTenantConstraint(Builder $query, int $tenantId): void
    {
        $query->whereIn(
            $this->qualifyColumn('feature_id'),
            DB::table('features')
                ->select('features.id')
                ->whereIn('features.project_id', Tenancy::projectIdsQuery($tenantId))
                ->whereNull('features.deleted_at')
        );
    }
}
