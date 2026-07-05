<?php
namespace App\Data;

use Spatie\LaravelData\Data;
use Illuminate\Http\Request;
use App\Attributes\ValuePropertyAttribute;
use App\Attributes\FormFieldAttribute;

class CategoryData extends BaseData
{
    public function __construct(
        public ?int $id = null,
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
        return [
            //'category'=>['email','endswith:a']
        ];
    }


    // public static function fromRequest($object) : static
    // {
    //     // foreach($object->input as $f) {
    //     //     print $f;
    //     // }
    //     $class_vars = get_class_vars(get_called_class());
    //     // dd($class_vars);
    //     foreach ($object as $field=>$value) {
    //         if (array_key_exists($field, $class_vars)) {
    //             $class_vars[$field] = $value;
    //         }
    //     }
    //     // dd($class_vars);
    //     return new static(...$class_vars);
    // }
}
?>