<?php

namespace App\Models;

use App\Attributes\RoutableAttribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[RoutableAttribute]
class Priority extends BaseModel
{
    use HasFactory;

    protected $displayField = 'name';

    protected $fillable = [
        'name',
        'code',
        'sort_order',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_by' => 'integer',
        'sort_order' => 'integer',
    ];
}
