<?php

namespace App\Models;

use App\Attributes\RelationAttribute;
use App\Attributes\RoutableAttribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use RuntimeException;

#[RoutableAttribute]
class Stakeholder extends BaseModel
{
    use HasFactory;

    protected $displayField = 'name';

    protected $fillable = [
        'name',
        'project_id',
        'type',
        'influence',
        'interest',
        'notes',
        'is_system',
        'system_key',
        'status_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_by' => 'integer',
        'is_system' => 'boolean',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::deleting(function (self $stakeholder): void {
            if ($stakeholder->is_system) {
                throw new RuntimeException('System stakeholders cannot be deleted.');
            }
        });
    }

    #[RelationAttribute('BelongsTo')]
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    #[RelationAttribute('BelongsTo')]
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    #[RelationAttribute('BelongsToMany')]
    public function stakeholderNeeds(): BelongsToMany
    {
        return $this->belongsToMany(StakeholderNeed::class)
            ->withTimestamps();
    }
}
