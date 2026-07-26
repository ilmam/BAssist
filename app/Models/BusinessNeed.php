<?php

namespace App\Models;

use App\Attributes\Relation;
use App\Attributes\RoutableAttribute;
use App\Models\Concerns\AppliesDefaultPriority;
use App\Models\Concerns\HasEntityNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[RoutableAttribute]
class BusinessNeed extends BaseModel
{
    use AppliesDefaultPriority;
    use HasEntityNumber;
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

    protected static function entityNumberPrefix(): string
    {
        return 'BN';
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
    public function businessObjectives(): BelongsToMany
    {
        return $this->belongsToMany(BusinessObjective::class)
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    #[Relation('BelongsToMany')]
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
