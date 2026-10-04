<?php

namespace App\Models;

use App\Attributes\Commentable;
use App\Attributes\Tracked;
use App\Attributes\Relation;
use App\Attributes\RoutableAttribute;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasEntityStatus;
use App\Services\DataDictionaryNormalizer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[RoutableAttribute]
#[Commentable]
#[Tracked]
class DataDictionary extends BaseModel
{
    use BelongsToTenant;
    use HasEntityStatus;
    use HasFactory;

    protected $displayField = 'title';

    protected $fillable = [
        'title',
        'project_id',
        'description',
        'entities',
        'status_id',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'entities' => 'array',
        ]);
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

    /**
     * @return list<array{name: string, meaning: string, fields: list<array<string, mixed>>}>
     */
    public function normalizedEntities(): array
    {
        return app(DataDictionaryNormalizer::class)
            ->normalize(is_array($this->entities) ? $this->entities : []);
    }
}
