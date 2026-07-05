<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Data\CategoryData;
use App\Repositories\CategoryRepository;

class CategoryController extends BaseController
{
    public $modelRepository;
    public function __construct()
    {
        $this->modelName = 'Category';
        // $repostioryCalssName = $this->getRepositoryName();
        $this->modelRepository = $this->initiateModelRepository($this->modelName);
    }
}
