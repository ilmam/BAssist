<?php

namespace App\Models;

use App\Attributes\Relation;
use App\Attributes\RoutableAttribute;
use App\Models\Concerns\AppliesDefaultPriority;
use App\Models\Concerns\HasEntityNumber;
use App\Models\Concerns\HasEntityStatus;
use App\Support\NfrCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[RoutableAttribute]
class NonFunctionalRequirement extends BaseModel
{
    use AppliesDefaultPriority;
    use HasEntityNumber;
    use HasEntityStatus;
    use HasFactory;

    protected $displayField = 'title';

    protected $fillable = [
        'title',
        'project_id',
        'stakeholder_need_id',
        'change_request_id',
        'category',
        'description',
        'acceptance_criteria',
        'priority_id',
        'status_id',
    ];

    protected static function entityNumberPrefix(): string
    {
        return 'NFR';
    }

    public function categoryLabel(): string
    {
        return NfrCategory::label((string) $this->category);
    }

    #[Relation('BelongsTo')]
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    #[Relation('BelongsTo')]
    public function stakeholderNeed(): BelongsTo
    {
        return $this->belongsTo(StakeholderNeed::class);
    }

    #[Relation('BelongsTo')]
    public function changeRequest(): BelongsTo
    {
        return $this->belongsTo(ChangeRequest::class);
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
}
