<?php

namespace App\Models;

use App\Attributes\Relation;
use App\Attributes\RoutableAttribute;
use App\Models\Concerns\AppliesDefaultPriority;
use App\Models\Concerns\HasEntityNumber;
use App\Support\ChangeRequestImpact;
use App\Support\ChangeRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[RoutableAttribute]
class ChangeRequest extends BaseModel
{
    use AppliesDefaultPriority;
    use HasEntityNumber;
    use HasFactory;

    protected $displayField = 'title';

    protected $fillable = [
        'title',
        'project_id',
        'problem',
        'proposed_change',
        'requestor',
        'impact_level',
        'impact_notes',
        'stakeholder_need_id',
        'priority_id',
        'status',
    ];

    protected static function entityNumberPrefix(): string
    {
        return 'CR';
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (self $model): void {
            if (blank($model->status)) {
                $model->status = ChangeRequestStatus::default();
            }
            if (blank($model->impact_level)) {
                $model->impact_level = ChangeRequestImpact::default();
            }
        });
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
    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class);
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

    #[Relation('HasMany')]
    public function nonFunctionalRequirements(): HasMany
    {
        return $this->hasMany(NonFunctionalRequirement::class);
    }

    public function statusLabel(): string
    {
        return ChangeRequestStatus::label((string) $this->status);
    }

    public function impactLabel(): string
    {
        return ChangeRequestImpact::label((string) $this->impact_level);
    }

    public function hasStakeholderNeed(): bool
    {
        return (int) ($this->stakeholder_need_id ?? 0) > 0;
    }

    public function isApproved(): bool
    {
        return (string) $this->status === ChangeRequestStatus::APPROVED
            || (string) $this->status === ChangeRequestStatus::IMPLEMENTED;
    }
}
