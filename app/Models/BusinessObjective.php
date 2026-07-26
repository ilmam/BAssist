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
class BusinessObjective extends BaseModel
{
    use AppliesDefaultPriority;
    use HasEntityNumber;
    use HasFactory;

    protected $displayField = 'title';

    protected $fillable = [
        'title',
        'project_id',
        'description',
        'success_measure',
        'potential_value',
        'priority_id',
        'status_id',
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
            ->withPivot('is_primary')
            ->withTimestamps();
    }
}
