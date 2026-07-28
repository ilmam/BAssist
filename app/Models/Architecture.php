<?php

namespace App\Models;

use App\Attributes\Relation;
use App\Attributes\RoutableAttribute;
use App\Models\Concerns\HasEntityStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[RoutableAttribute]
class Architecture extends BaseModel
{
    use HasEntityStatus;
    use HasFactory;

    protected $displayField = 'title';

    protected $fillable = [
        'title',
        'project_id',
        'description',
        'elements',
        'relationships',
        'layout',
        'status_id',
    ];

    protected $casts = [
        'elements' => 'array',
        'relationships' => 'array',
        'layout' => 'array',
    ];

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

    /**
     * @return list<array<string, mixed>>
     */
    public function normalizedElements(): array
    {
        return app(\App\Services\C4ArchitectureNormalizer::class)
            ->normalizeElements(is_array($this->elements) ? $this->elements : []);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function normalizedRelationships(): array
    {
        return app(\App\Services\C4ArchitectureNormalizer::class)
            ->normalizeRelationships(is_array($this->relationships) ? $this->relationships : []);
    }

    /**
     * @return array{shapes_per_row: int, boundaries_per_row: int}
     */
    public function normalizedLayout(): array
    {
        return app(\App\Services\C4ArchitectureNormalizer::class)
            ->normalizeLayout(is_array($this->layout) ? $this->layout : []);
    }
}
