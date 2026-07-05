<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use App\Data\CategoryData;
use App\Data\PostData;
use App\Repositories\BaseRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\PostRepository;

class CategoryController extends BaseApiController
{
    public function __construct()
    {
        $this->modelName = 'Category';
        $this->modelRepository = $this->initiateModelRepository($this->modelName);
    }


}
