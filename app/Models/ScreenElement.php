<?php

namespace App\Models;

use App\Attributes\Relation;
use App\Attributes\RoutableAttribute;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One addressable line of a screen; a container row (panel, columns, column, table)
 * holds the rows whose parent_id points at it. Design only: no upstream link except the
 * optional functional_requirement_id for an element with its own requirement.
 */
#[RoutableAttribute]
class ScreenElement extends BaseModel
{
    use BelongsToTenant;
    use HasFactory;

    protected $displayField = 'label';

    protected $fillable = [
        'screen_id',
        'parent_id',
        'project_id',
        'position',
        'row',
        'kind',
        'label',
        'functional_requirement_id',
    ];

    #[Relation('BelongsTo')]
    public function screen(): BelongsTo
    {
        return $this->belongsTo(Screen::class);
    }

    protected static function booted(): void
    {
        parent::booted();

        static::deleting(function (self $element): void {
            // Soft delete does not cascade; a container takes its contents with it.
            $element->children()->each(static fn (self $child) => $child->delete());
        });
    }

    #[Relation('BelongsTo')]
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    #[Relation('HasMany')]
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position')->orderBy('id');
    }

    #[Relation('BelongsTo')]
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    #[Relation('BelongsTo')]
    public function functionalRequirement(): BelongsTo
    {
        return $this->belongsTo(FunctionalRequirement::class);
    }
}
