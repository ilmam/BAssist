<?php

namespace App\Models;

use App\Attributes\Relation;
use App\Attributes\RoutableAttribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[RoutableAttribute]
class StateFlow extends BaseModel
{
    use HasFactory;

    protected $displayField = 'title';

    protected $fillable = [
        'title',
        'project_id',
        'description',
        'transitions',
        'status_id',
    ];

    protected $casts = [
        'transitions' => 'array',
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
     * @return list<array{from: string, to: string, trigger?: string|null}>
     */
    public function normalizedTransitions(): array
    {
        $rows = is_array($this->transitions) ? $this->transitions : [];

        return array_values(array_filter(array_map(
            static function ($row): ?array {
                if (! is_array($row)) {
                    return null;
                }

                $from = trim((string) ($row['from'] ?? ''));
                $to = trim((string) ($row['to'] ?? ''));

                if ($from === '' || $to === '') {
                    return null;
                }

                $trigger = trim((string) ($row['trigger'] ?? ''));

                return [
                    'from' => $from,
                    'to' => $to,
                    'trigger' => $trigger !== '' ? $trigger : null,
                ];
            },
            $rows
        )));
    }
}
