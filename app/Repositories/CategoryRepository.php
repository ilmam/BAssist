<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use App\Models\Category;
use App\Data\CategoryData;
use App\Data\CategoryViewData;
use Spatie\LaravelData\Data;

class CategoryRepository extends BaseRepository
{
    public Model $model;
    public $editDto = CategoryData::class;
    public $viewDto = CategoryViewData::class;

    public function __construct()
    {
        $this->model = new Category();
        // $this->editDto = CategoryData::class;
        // $this->viewDto = CategoryViewData::class;
    }

    // public function getAll()
    // {
    //     return $this->editDto::collection(
    //         $this->model::all()
    //     );
    // }

    // public function getById($Id)
    // {
    //     return $this->viewDto::from(
    //         $this->model::findOrFail($Id)
    //     );
    // }

    // public function deleteOrder($Id)
    // {
    //     $this->model::destroy($Id);
    // }

    // public function createOrder(array $details)
    // {
    //     return $this->model::create($details);
    // }

    // public function updateOrder($Id, array $newDetails)
    // {
    //     return $this->model::whereId($Id)->update($newDetails);
    // }

    // public function getFulfilledOrders()
    // {
    //     return $this->model::where('is_fulfilled', true);
    // }
}