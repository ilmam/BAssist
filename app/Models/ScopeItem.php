<?php

namespace App\Models;

use App\Attributes\Relation;
use App\Attributes\RoutableAttribute;
use App\Support\ScopeItemDirection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[RoutableAttribute]
class ScopeItem extends BaseModel
{
    use HasFactory;

    protected $displayField = 'title';

    protected $fillable = [
        'title',
        'project_id',
        'direction',
        'description',
        'business_need_id',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (self $model): void {
            if (blank($model->direction)) {
                $model->direction = ScopeItemDirection::default();
            }
        });
    }

    #[Relation('BelongsTo')]
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    #[Relation('BelongsTo')]
    public function businessNeed(): BelongsTo
    {
        return $this->belongsTo(BusinessNeed::class);
    }

    public function directionLabel(): string
    {
        return ScopeItemDirection::label((string) $this->direction);
    }
}
