<?php

namespace App\Models;

use App\Attributes\Relation;
use App\Models\Concerns\HasEntityNumber;
use App\Services\SwimlaneMermaidGenerator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * BPD process-step row (source of truth for swimlane flow elements).
 * Not a standalone CRUD entity — edited via SwimlaneFlow.
 */
class SwimlaneFlowStep extends BaseModel
{
    use HasEntityNumber;
    use HasFactory;

    protected $displayField = 'label';

    protected $fillable = [
        'number',
        'swimlane_flow_id',
        'project_id',
        'position',
        'lane',
        'lane_color',
        'element_color',
        'from_label',
        'type',
        'label',
        'line_title',
        'stakeholder_need_id',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    protected static function entityNumberPrefix(): string
    {
        return SwimlaneMermaidGenerator::STEP_CODE_PREFIX;
    }

    protected static function booted(): void
    {
        parent::booted();

        static::deleting(function (self $step): void {
            // Soft delete does not fire FK nullOnDelete; clear coverage links explicitly.
            Feature::query()->where('swimlane_flow_step_id', $step->id)->update(['swimlane_flow_step_id' => null]);
            FunctionalRequirement::query()->where('swimlane_flow_step_id', $step->id)->update(['swimlane_flow_step_id' => null]);
        });
    }

    #[Relation('BelongsTo')]
    public function swimlaneFlow(): BelongsTo
    {
        return $this->belongsTo(SwimlaneFlow::class);
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

    #[Relation('HasMany')]
    public function features(): HasMany
    {
        return $this->hasMany(Feature::class);
    }

    #[Relation('HasMany')]
    public function functionalRequirements(): HasMany
    {
        return $this->hasMany(FunctionalRequirement::class);
    }

    /**
     * Editor / Mermaid array shape (keeps `from` key for compatibility).
     *
     * @return array{id: int, lane: string, lane_color: string|null, element_color: string|null, from: string|null, type: string, label: string, line_title: string|null, code: string|null, stakeholder_need_id: int|null, number: int|null}
     */
    public function toElementArray(): array
    {
        return [
            'id' => (int) $this->id,
            'lane' => (string) $this->lane,
            'lane_color' => $this->lane_color !== null && $this->lane_color !== ''
                ? (string) $this->lane_color
                : null,
            'element_color' => $this->element_color !== null && $this->element_color !== ''
                ? (string) $this->element_color
                : null,
            'from' => $this->from_label,
            'type' => (string) $this->type,
            'label' => (string) $this->label,
            'line_title' => $this->line_title,
            'code' => $this->code,
            'stakeholder_need_id' => $this->stakeholder_need_id !== null ? (int) $this->stakeholder_need_id : null,
            'number' => $this->number !== null ? (int) $this->number : null,
        ];
    }
}
