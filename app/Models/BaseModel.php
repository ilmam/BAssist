<?php

namespace App\Models;

use App\Support\EntityStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

abstract class BaseModel extends Model
{
    use \App\Traits\RelationsManagerTrait;
    use SoftDeletes;

    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const DELETED_AT = 'deleted_at';
    public const CREATED_BY = 'created_by';
    public const UPDATED_BY = 'updated_by';
    public const DELETED_BY = 'deleted_by';
    public const STATUS = 'status_id';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $casts = [
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
        self::DELETED_AT => 'datetime',
        self::CREATED_BY => 'integer',
        self::UPDATED_BY => 'integer',
        self::DELETED_BY => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $userId = static::currentUserId();

            if ($userId !== null && empty($model->{self::CREATED_BY})) {
                $model->{self::CREATED_BY} = $userId;
            }

            if (
                ! $model instanceof Status
                && ! $model instanceof Priority
                && in_array(self::STATUS, $model->getFillable(), true)
                && blank($model->{self::STATUS})
            ) {
                $defaultStatusId = EntityStatus::defaultId();

                if ($defaultStatusId !== null) {
                    $model->{self::STATUS} = $defaultStatusId;
                }
            }
        });

        static::updating(function (self $model): void {
            $userId = static::currentUserId();

            if ($userId !== null) {
                $model->{self::UPDATED_BY} = $userId;
            }
        });

        static::deleting(function (self $model): void {
            if ($model->isForceDeleting()) {
                return;
            }

            $userId = static::currentUserId();

            if ($userId !== null) {
                $model->{self::DELETED_BY} = $userId;
                $model->saveQuietly();
            }
        });
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, self::CREATED_BY);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, self::UPDATED_BY);
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, self::DELETED_BY);
    }

    public function getListFields()
    {
        $fields = [];
        $fields[] = $this->displayField;
        $fields[] = $this->primaryKey;

        return $fields;
    }

    public function isDraft(): bool
    {
        return EntityStatus::is(EntityStatus::DRAFT, $this->{self::STATUS});
    }

    public function isAgreed(): bool
    {
        return EntityStatus::is(EntityStatus::AGREED, $this->{self::STATUS});
    }

    public function isDeprecated(): bool
    {
        return EntityStatus::is(EntityStatus::DEPRECATED, $this->{self::STATUS});
    }

    protected static function currentUserId(): ?int
    {
        return auth()->id();
    }
}
