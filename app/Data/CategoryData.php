<?php
namespace App\Data;

use Spatie\LaravelData\Data;
use Illuminate\Http\Request;
use App\Attributes\ValuePropertyAttribute;
use App\Attributes\ListPropertyAttribute;
use App\Attributes\FormFieldAttribute;

class CategoryData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[ListPropertyAttribute]
        #[ValuePropertyAttribute]
        #[FormFieldAttribute('text')]
        public string $category = '',
        #[ValuePropertyAttribute]
        #[FormFieldAttribute('textarea')]
        public ?string $description = null
    ) {
    }

    public static function rules()
    {
        return [];
    }
}
?>