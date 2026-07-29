<?php

namespace App\Models;

use App\Attributes\Relation;
use App\Attributes\RoutableAttribute;
use App\Support\StrategicBaselineStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[RoutableAttribute]
class StrategicBaseline extends BaseModel
{
    use HasFactory;

    protected $displayField = 'status';

    protected $fillable = [
        'project_id',
        'current_state',
        'future_state',
        'change_strategy',
        'status',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (self $model): void {
            if (blank($model->status)) {
                $model->status = StrategicBaselineStatus::default();
            }
        });
    }

    #[Relation('BelongsTo')]
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function statusLabel(): string
    {
        return StrategicBaselineStatus::label((string) $this->status);
    }

    /**
     * True when at least one strategy narrative field has content.
     */
    public function hasStrategyContent(): bool
    {
        return filled($this->current_state)
            || filled($this->future_state)
            || filled($this->change_strategy);
    }
}
