<?php

namespace App\Models;

use App\Attributes\Attachable;
use App\Attributes\Commentable;
use App\Attributes\Relation;
use App\Attributes\RoutableAttribute;
use App\Attributes\Tracked;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasAttachments;
use App\Support\BriefStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The ask, as received. Sits above projects, owned by a workspace.
 * It is not an early Business Need: it records what the sponsor gave us,
 * and the reframing into needs happens in the Spine.
 */
#[RoutableAttribute]
#[Attachable]
#[Commentable]
#[Tracked]
class Brief extends BaseModel
{
    use BelongsToTenant;
    use HasAttachments;
    use HasFactory;

    protected $displayField = 'title';

    protected $fillable = [
        'title',
        'workspace_id',
        'ask',
        'source_type',
        'source_note',
        'stated_constraints',
        'stated_success_measures',
        'open_questions',
        'status',
        'status_reason',
        'received_at',
        'version',
        'previous_version_id',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (self $model): void {
            if (blank($model->status)) {
                $model->status = BriefStatus::default();
            }
        });

        static::saving(function (self $model): void {
            $status = (string) $model->status;

            if (BriefStatus::needsReason($status) && blank($model->status_reason)) {
                throw ValidationException::withMessages(['status_reason' => __('ui.brief_reason_required')]);
            }

            // Approving needs something to approve: the ask in words, or the original attached.
            if ($model->exists && $status === BriefStatus::APPROVED && $model->isDirty('status')
                && blank($model->ask) && ! $model->attachments()->exists()) {
                throw ValidationException::withMessages(['ask' => __('ui.brief_ask_required')]);
            }
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'received_at' => 'date',
        ]);
    }

    #[Relation('BelongsTo')]
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    #[Relation('BelongsTo')]
    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_version_id');
    }

    /** Projects that came from this brief; the scope note says which part of the ask each takes. */
    #[Relation('BelongsToMany')]
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)
            ->withPivot('scope_note')
            ->withTimestamps();
    }

    public function isApproved(): bool
    {
        return $this->status === BriefStatus::APPROVED;
    }

    public function statusLabel(): string
    {
        return BriefStatus::label((string) $this->status);
    }

    /**
     * Re-brief: keep this version exactly as received and start the next one from it.
     * Attachments are not copied; each version carries its own files.
     *
     * @param  array<string, mixed>  $changes
     */
    public function newVersion(array $changes = []): self
    {
        return static::create(array_merge(
            $this->only([
                'title', 'workspace_id', 'ask', 'source_type', 'source_note',
                'stated_constraints', 'stated_success_measures', 'open_questions',
            ]),
            $changes,
            [
                'status' => BriefStatus::RECEIVED,
                'status_reason' => null,
                // The column default is not on a just-created instance, so treat null as version 1.
                'version' => max(1, (int) $this->version) + 1,
                'previous_version_id' => $this->getKey(),
            ],
        ));
    }

    /** @return list<int> */
    public function owningTenantIds(): array
    {
        $workspaceIds = array_values(array_unique(array_filter([
            $this->getAttribute('workspace_id'),
            $this->getOriginal('workspace_id'),
        ])));

        return DB::table('workspaces')
            ->whereIn('id', $workspaceIds)
            ->pluck('tenant_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /** Briefs belong to no project, so project-level access never applies. */
    public function owningProjectIds(): array
    {
        return [];
    }

    /** A brief is visible when its workspace is (Workspace has its own tenant scope). */
    public function applyTenantConstraint(Builder $query, int $tenantId): void
    {
        $query->whereIn(
            $this->qualifyColumn('workspace_id'),
            Workspace::query()->select('id')
        );
    }
}
