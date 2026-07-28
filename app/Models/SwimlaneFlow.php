<?php

namespace App\Models;

use App\Attributes\Relation;
use App\Attributes\RoutableAttribute;
use App\Models\Concerns\HasEntityStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[RoutableAttribute]
class SwimlaneFlow extends BaseModel
{
    use HasEntityStatus;
    use HasFactory;

    protected $displayField = 'title';

    protected $fillable = [
        'title',
        'project_id',
        'description',
        'direction',
        'elements',
        'status_id',
    ];

    protected $casts = [
        'elements' => 'array',
    ];

    protected $attributes = [
        'direction' => 'TB',
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
     * @return list<array{lane: string, from: string|null, type: string, label: string, line_title: string|null}>
     */
    public function normalizedElements(): array
    {
        return app(\App\Services\SwimlaneMermaidGenerator::class)
            ->normalizeElements(is_array($this->elements) ? $this->elements : []);
    }
}
