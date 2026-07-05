<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Traits\DataHelperTrait;
use Yajra\DataTables\Facades\DataTables;

class BaseApiController extends \App\Http\Controllers\Controller
{
    use DataHelperTrait;

    public $modelName = 'GenericModel';
    public $modelRepository;

    public function index()
    {
        $result = $this->modelRepository->getAll();
        $result = $this->flatten_collection($result);

        return DataTables::of($result)->make(true);
    }

    public function store(Request $request)
    {
        $data = $this->getData($request);
        $newModel = $this->modelRepository->create($data->toArray());

        return response()->json($newModel, 201);
    }

    public function show($id)
    {
        $result = $this->modelRepository->getById($id);

        return response()->json($result);
    }

    public function update(Request $request, $id)
    {
        $data = $this->getData($request);
        $this->modelRepository->update($id, $data->toArray());

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $this->modelRepository->delete($id);

        return response()->json(['success' => true]);
    }

    private function getData(Request $request)
    {
        $dtoClass = "\\App\\Data\\".$this->modelName.'Data';

        return $dtoClass::from($request);
    }

    protected function initiateModelRepository($modelName)
    {
        $repositoryClassName = "\\App\\Repositories\\".$modelName.'Repository';

        return new $repositoryClassName;
    }
}
