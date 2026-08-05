<?php

namespace App\Models;

use App\Attributes\Relation;
use App\Attributes\RoutableAttribute;
use App\Models\Concerns\AppliesDefaultPriority;
use App\Models\Concerns\HasEntityNumber;
use App\Models\Concerns\HasEntityStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[RoutableAttribute]
class StakeholderNeed extends BaseModel
{
    use AppliesDefaultPriority;
    use HasEntityNumber;
    use HasEntityStatus;
    use HasFactory;

    protected $displayField = 'title';

    protected $fillable = [
        'title',
        'project_id',
        'description',
        'priority_id',
        'status_id',
    ];

    protected static function entityNumberPrefix(): string
    {
        return 'SN';
    }

    #[Relation('BelongsTo')]
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
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

    #[Relation('BelongsToMany')]
    public function businessNeeds(): BelongsToMany
    {
        return $this->belongsToMany(BusinessNeed::class)
            ->withTimestamps();
    }

    #[Relation('BelongsToMany')]
    public function stakeholders(): BelongsToMany
    {
        return $this->belongsToMany(Stakeholder::class)
            ->withTimestamps();
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
    public function swimlaneFlowSteps(): HasMany
    {
        return $this->hasMany(SwimlaneFlowStep::class);
    }
}
