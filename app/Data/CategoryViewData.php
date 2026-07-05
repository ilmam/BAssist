<?php
namespace App\Data;

use Spatie\LaravelData\Data;
use Illuminate\Http\Request;
use App\Attributes\ValuePropertyAttribute;

class CategoryViewData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[ValuePropertyAttribute]
        public string $category = '',
        #[ValuePropertyAttribute]
        public ?string $description = null
    ) {
    }
}
?>