<?php

namespace App\Models;

use App\Attributes\RelationAttribute;
use App\Attributes\RoutableAttribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[RoutableAttribute]
class BusinessNeed extends BaseModel
{
    use HasFactory;

    protected $displayField = 'title';

    protected $fillable = [
        'title',
        'need_type',
        'project_id',
        'description',
        'rationale',
        'impact',
        'do_nothing_consequence',
        'priority_id',
        'status_id',
    ];

    #[RelationAttribute('BelongsTo')]
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    #[RelationAttribute('BelongsTo')]
    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class);
    }

    #[RelationAttribute('BelongsTo')]
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    #[RelationAttribute('BelongsToMany')]
    public function businessObjectives(): BelongsToMany
    {
        return $this->belongsToMany(BusinessObjective::class)
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    #[RelationAttribute('BelongsToMany')]
    public function stakeholderNeeds(): BelongsToMany
    {
        return $this->belongsToMany(StakeholderNeed::class)
            ->withTimestamps();
    }

    public function primaryBusinessObjective(): ?BusinessObjective
    {
        return $this->businessObjectives()->wherePivot('is_primary', true)->first()
            ?? $this->businessObjectives()->first();
    }

    public function hasObjectives(): bool
    {
        return $this->businessObjectives()->exists();
    }
}
