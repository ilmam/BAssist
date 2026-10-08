<?php

namespace App\Models;

use App\Attributes\Commentable;
use App\Attributes\Relation;
use App\Attributes\RoutableAttribute;
use App\Attributes\Tracked;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasEntityStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Design, not requirement (docs/design-layer.md). Links upstream only: to the
 * functional requirement(s) it realizes. Element rows belong by composition.
 */
#[RoutableAttribute]
#[Commentable]
#[Tracked]
class Screen extends BaseModel
{
    use BelongsToTenant;
    use HasEntityStatus;
    use HasFactory;

    protected $displayField = 'title';

    protected $fillable = [
        'title',
        'project_id',
        'description',
        'status_id',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::deleting(function (self $screen): void {
            // Soft delete does not cascade; remove the composed rows with the screen.
            $screen->screenElements()->each(static fn (ScreenElement $element) => $element->delete());
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
    public function functionalRequirements(): BelongsToMany
    {
        return $this->belongsToMany(FunctionalRequirement::class, 'functional_requirement_screen')
            ->withTimestamps();
    }

    #[Relation('HasMany')]
    public function screenElements(): HasMany
    {
        return $this->hasMany(ScreenElement::class)->orderBy('position')->orderBy('id');
    }
}
