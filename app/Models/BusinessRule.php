<?php

namespace App\Models;

use App\Attributes\Relation;
use App\Attributes\RoutableAttribute;
use App\Support\BusinessRuleStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[RoutableAttribute]
class BusinessRule extends BaseModel
{
    use HasFactory;

    protected $displayField = 'title';

    protected $fillable = [
        'title',
        'project_id',
        'description',
        'status',
        'source',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (self $model): void {
            if (blank($model->status)) {
                $model->status = BusinessRuleStatus::default();
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
        return BusinessRuleStatus::label((string) $this->status);
    }
}
