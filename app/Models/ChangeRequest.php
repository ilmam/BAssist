<?php

namespace App\Models;

use App\Attributes\Relation;
use App\Attributes\RoutableAttribute;
use App\Models\Concerns\AppliesDefaultPriority;
use App\Models\Concerns\HasEntityNumber;
use App\Support\ChangeRequestImpact;
use App\Support\ChangeRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[RoutableAttribute]
class ChangeRequest extends BaseModel
{
    use AppliesDefaultPriority;
    use HasEntityNumber;
    use HasFactory;

    protected $displayField = 'title';

    protected $fillable = [
        'title',
        'project_id',
        'problem',
        'proposed_change',
        'requestor',
        'impact_level',
        'impact_notes',
        'affected_type',
        'affected_id',
        'priority_id',
        'status',
    ];

    protected static function entityNumberPrefix(): string
    {
        return 'CR';
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (self $model): void {
            if (blank($model->status)) {
                $model->status = ChangeRequestStatus::default();
            }
            if (blank($model->impact_level)) {
                $model->impact_level = ChangeRequestImpact::default();
            }
        });
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

    public function statusLabel(): string
    {
        return ChangeRequestStatus::label((string) $this->status);
    }

    public function impactLabel(): string
    {
        return ChangeRequestImpact::label((string) $this->impact_level);
    }

    public function hasAffectedRequirement(): bool
    {
        return filled($this->affected_type) && (int) $this->affected_id > 0;
    }
}
