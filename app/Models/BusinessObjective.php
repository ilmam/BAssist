<?php

namespace App\Models;

use App\Attributes\Relation;
use App\Attributes\RoutableAttribute;
use App\Models\Concerns\HasEntityNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[RoutableAttribute]
class BusinessObjective extends BaseModel
{
    use HasEntityNumber;
    use HasFactory;

    protected $displayField = 'title';

    protected $fillable = [
        'title',
        'project_id',
        'description',
        'success_measure',
        'potential_value',
    ];

    protected static function entityNumberPrefix(): string
    {
        return 'BO';
    }

    #[Relation('BelongsTo')]
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    #[Relation('BelongsToMany')]
    public function businessNeeds(): BelongsToMany
    {
        return $this->belongsToMany(BusinessNeed::class)
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    #[Relation('BelongsToMany')]
    public function stakeholderNeeds(): BelongsToMany
    {
        return $this->belongsToMany(StakeholderNeed::class)
            ->withTimestamps();
    }

    /**
     * Primary parent business need (the "why") for this objective.
     * Pivot is_primary marks the primary need for the objective.
     */
    public function primaryBusinessNeed(): ?BusinessNeed
    {
        return $this->businessNeeds()->wherePivot('is_primary', true)->first()
            ?? $this->businessNeeds()->first();
    }

    public function hasNeeds(): bool
    {
        return $this->businessNeeds()->exists();
    }
}
