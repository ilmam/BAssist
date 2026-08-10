<?php

namespace App\Models;

use App\Attributes\Relation;
use App\Attributes\RoutableAttribute;
use App\Models\Concerns\HasEntityStatus;
use App\Services\SystemStakeholderSeeder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[RoutableAttribute]
class Project extends BaseModel
{
    use HasEntityStatus;
    use HasFactory;

    protected $displayField = 'name';

    protected $fillable = [
        'name',
        'code',
        'workspace_id',
        'description',
        'status_id',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::created(function (self $project): void {
            app(SystemStakeholderSeeder::class)->seedForProject($project);
        });
    }

    #[Relation('BelongsTo')]
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    #[Relation('BelongsTo')]
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    #[Relation('HasMany')]
    public function businessObjectives(): HasMany
    {
        return $this->hasMany(BusinessObjective::class);
    }

    #[Relation('HasMany')]
    public function businessNeeds(): HasMany
    {
        return $this->hasMany(BusinessNeed::class);
    }

    #[Relation('HasMany')]
    public function stakeholders(): HasMany
    {
        return $this->hasMany(Stakeholder::class);
    }

    #[Relation('HasMany')]
    public function stakeholderNeeds(): HasMany
    {
        return $this->hasMany(StakeholderNeed::class);
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

    #[Relation('HasMany')]
    public function changeRequests(): HasMany
    {
        return $this->hasMany(ChangeRequest::class);
    }

    #[Relation('HasMany')]
    public function risks(): HasMany
    {
        return $this->hasMany(Risk::class);
    }

    #[Relation('HasMany')]
    public function stateFlows(): HasMany
    {
        return $this->hasMany(StateFlow::class);
    }

    #[Relation('HasMany')]
    public function swimlaneFlows(): HasMany
    {
        return $this->hasMany(SwimlaneFlow::class);
    }

    #[Relation('HasMany')]
    public function assumptions(): HasMany
    {
        return $this->hasMany(Assumption::class);
    }

    #[Relation('HasMany')]
    public function constraints(): HasMany
    {
        return $this->hasMany(Constraint::class);
    }

    #[Relation('HasMany')]
    public function businessRules(): HasMany
    {
        return $this->hasMany(BusinessRule::class);
    }

    #[Relation('HasOne')]
    public function architecture(): HasOne
    {
        return $this->hasOne(Architecture::class);
    }

    #[Relation('HasOne')]
    public function strategicBaseline(): HasOne
    {
        return $this->hasOne(StrategicBaseline::class);
    }

    #[Relation('HasMany')]
    public function scopeItems(): HasMany
    {
        return $this->hasMany(ScopeItem::class);
    }
}
