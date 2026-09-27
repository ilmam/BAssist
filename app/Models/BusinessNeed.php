<?php

namespace App\Models;

use App\Attributes\Attachable;
use App\Attributes\Relation;
use App\Attributes\RoutableAttribute;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasEntityNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[RoutableAttribute]
#[Attachable]
class BusinessNeed extends BaseModel
{
    use BelongsToTenant;
    use HasAttachments;
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

    #[Relation('BelongsToMany')]
    public function businessObjectives(): BelongsToMany
    {
        return $this->belongsToMany(BusinessObjective::class)
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function hasObjectives(): bool
    {
        return $this->businessObjectives()->exists();
    }
}
