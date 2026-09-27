<?php

namespace App\Models;

use App\Attributes\Relation;
use App\Attributes\RoutableAttribute;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasEntityStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use RuntimeException;

#[RoutableAttribute]
class Stakeholder extends BaseModel
{
    use BelongsToTenant;
    use HasEntityStatus;
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

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'is_system' => 'boolean',
        ]);
    }

    protected static function booted(): void
    {
        parent::booted();

        static::deleting(function (self $stakeholder): void {
            if ($stakeholder->is_system) {
                throw new RuntimeException('System stakeholders cannot be deleted.');
            }
        });
    }

    #[Relation('BelongsTo')]
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    #[Relation('BelongsTo')]
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    #[Relation('BelongsToMany')]
    public function stakeholderNeeds(): BelongsToMany
    {
        return $this->belongsToMany(StakeholderNeed::class)
            ->withTimestamps();
    }
}
