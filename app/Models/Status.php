<?php

namespace App\Models;

use App\Attributes\RoutableAttribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use RuntimeException;

#[RoutableAttribute]
class Status extends BaseModel
{
    use HasFactory;

    protected $displayField = 'name';

    protected $fillable = [
        'name',
        'code',
        'sort_order',
        'description',
        'is_system',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_by' => 'integer',
        'sort_order' => 'integer',
        'is_system' => 'boolean',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::deleting(function (self $status): void {
            if ($status->is_system) {
                throw new RuntimeException('System statuses cannot be deleted.');
            }
        });
    }
}
