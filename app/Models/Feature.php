<?php

namespace App\Models;

use App\Attributes\Relation;
use App\Attributes\RoutableAttribute;
use App\Models\Concerns\AppliesDefaultPriority;
use App\Models\Concerns\HasEntityNumber;
use App\Models\Concerns\HasEntityStatus;
use App\Services\GherkinDocumentParser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[RoutableAttribute]
class Feature extends BaseModel
{
    use AppliesDefaultPriority;
    use HasEntityNumber;
    use HasEntityStatus;
    use HasFactory;

    protected $displayField = 'title';

    protected $fillable = [
        'title',
        'project_id',
        'stakeholder_need_id',
        'swimlane_flow_step_id',
        'body',
        'priority_id',
        'status_id',
    ];

    protected static function entityNumberPrefix(): string
    {
        return 'FE';
    }

    #[Relation('BelongsTo')]
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    #[Relation('BelongsTo')]
    public function stakeholderNeed(): BelongsTo
    {
        return $this->belongsTo(StakeholderNeed::class);
    }

    #[Relation('BelongsTo')]
    public function swimlaneFlowStep(): BelongsTo
    {
        return $this->belongsTo(SwimlaneFlowStep::class);
    }

    #[Relation('BelongsTo')]
    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class);
    }

    #[Relation('BelongsTo')]
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    #[Relation('HasMany')]
    public function scenarios(): HasMany
    {
        return $this->hasMany(Scenario::class);
    }

    /**
     * Sync list title from Feature: line, normalize the header, and keep @need:{code} in body.
     */
    public function syncDocumentFields(?GherkinDocumentParser $parser = null): void
    {
        $parser ??= app(GherkinDocumentParser::class);
        $fallback = trim((string) ($this->title ?? '')) !== ''
            ? (string) $this->title
            : 'Untitled';

        $this->body = $parser->ensureFeatureHeader($this->body, $fallback);

        $needCode = null;
        if ($this->stakeholder_need_id) {
            $this->loadMissing('stakeholderNeed');
            $needCode = $this->stakeholderNeed?->code;
        }
        $this->body = $parser->syncNeedTraceabilityTag($this->body, $needCode);

        $fromBody = $parser->extractFeatureTitle($this->body);
        if ($fromBody !== null) {
            $this->title = $fromBody;
        }
    }
}
