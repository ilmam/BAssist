<?php

namespace App\Models;

use App\Attributes\Relation;
use App\Attributes\RoutableAttribute;
use App\Models\Concerns\HasEntityStatus;
use App\Services\SwimlaneMermaidGenerator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[RoutableAttribute]
class SwimlaneFlow extends BaseModel
{
    use HasEntityStatus;
    use HasFactory;

    protected $displayField = 'title';

    protected $fillable = [
        'title',
        'project_id',
        'description',
        'direction',
        'color_mode',
        'elements',
        'status_id',
    ];

    protected $casts = [
        'elements' => 'array',
    ];

    protected $attributes = [
        'direction' => 'TB',
        'color_mode' => 'both',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::deleting(function (self $flow): void {
            // Soft-delete child steps with the flow (DB cascade only applies to force delete).
            $flow->swimlaneFlowSteps()->each(static function (SwimlaneFlowStep $step): void {
                $step->delete();
            });
        });
    }

    #[Relation('BelongsTo')]
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    #[Relation('BelongsTo')]
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    #[Relation('HasMany')]
    public function swimlaneFlowSteps(): HasMany
    {
        return $this->hasMany(SwimlaneFlowStep::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return list<array{id: int, lane: string, from: string|null, type: string, label: string, line_title: string|null, code: string|null, stakeholder_need_id: int|null, number: int|null}>
     */
    public function elementsForEditor(): array
    {
        $this->loadMissing('swimlaneFlowSteps');

        return $this->swimlaneFlowSteps
            ->map(fn (SwimlaneFlowStep $step) => $step->toElementArray())
            ->values()
            ->all();
    }

    /**
     * @return list<array{id?: int|null, lane: string, from: string|null, type: string, label: string, line_title: string|null, code: string|null, stakeholder_need_id: int|null, number?: int|null}>
     */
    public function normalizedElements(): array
    {
        return app(SwimlaneMermaidGenerator::class)
            ->normalizeElements($this->elementsForEditor());
    }
}
