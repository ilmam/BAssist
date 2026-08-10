<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ListUi;
use App\Http\Controllers\Concerns\ResolvesListFilters;
use Illuminate\Http\Request;
use App\Support\CollectionFlattener;
use App\Support\RepositoryResolver;
use Yajra\DataTables\Facades\DataTables;

class BaseApiController extends \App\Http\Controllers\Controller
{
    use ResolvesListFilters;

    public $modelName = 'GenericModel';
    public $modelRepository;

    public function index(Request $request)
    {
        $filters = $this->resolveListFilters($request);
        $result = $this->modelRepository->getAll($filters);
        $result = app(CollectionFlattener::class)->flatten($result);

        $datatable = DataTables::of($result);
        $rawColumns = ListUi::rawHtmlColumns($result);
        if ($rawColumns !== []) {
            $datatable->rawColumns($rawColumns);
        }

        return $datatable->make(true);
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
        return RepositoryResolver::make($modelName);
    }
}
