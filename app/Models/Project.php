<?php

namespace App\Models;

use App\Attributes\RelationAttribute;
use App\Attributes\RoutableAttribute;
use App\Services\SystemStakeholderSeeder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[RoutableAttribute]
class Project extends BaseModel
{
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

    #[RelationAttribute('BelongsTo')]
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    #[RelationAttribute('BelongsTo')]
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    #[RelationAttribute('HasMany')]
    public function businessObjectives(): HasMany
    {
        return $this->hasMany(BusinessObjective::class);
    }

    #[RelationAttribute('HasMany')]
    public function businessNeeds(): HasMany
    {
        return $this->hasMany(BusinessNeed::class);
    }

    #[RelationAttribute('HasMany')]
    public function stakeholders(): HasMany
    {
        return $this->hasMany(Stakeholder::class);
    }

    #[RelationAttribute('HasMany')]
    public function stakeholderNeeds(): HasMany
    {
        return $this->hasMany(StakeholderNeed::class);
    }
}
