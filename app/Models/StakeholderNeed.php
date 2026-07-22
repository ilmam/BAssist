<?php

namespace App\Models;

use App\Attributes\Relation;
use App\Attributes\RoutableAttribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[RoutableAttribute]
class StakeholderNeed extends BaseModel
{
    use HasFactory;

    protected $displayField = 'title';

    protected $fillable = [
        'title',
        'project_id',
        'description',
        'priority_id',
        'status_id',
    ];

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
}
