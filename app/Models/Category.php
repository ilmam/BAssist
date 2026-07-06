<?php

namespace App\Models;

use App\Attributes\RoutableAttribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[RoutableAttribute]
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
