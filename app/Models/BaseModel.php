<?php

namespace App\Models;

use App\Attributes\RelationAttribute;
use App\Traits\RelationsManagerTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class BaseModel extends Model
{
    use \App\Traits\RelationsManagerTrait;

    public function DoSomething()
    {
        return true;
    }

    public function getListFields()
    {
        $fields = [];
        $fields[] = $this->displayField;
        $fields[] = $this->primaryKey;
        return $fields;
    }
}
