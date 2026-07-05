<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends BaseModel
{
    use HasFactory;

    protected $primaryKey = 'id';
    protected $displayField = 'category';

    protected $fillable = [
        'category',
        'description'
    ];
}
