<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithModal;
use Illuminate\Http\Request;
use App\Support\DtoMetadata;
use App\Support\EntityFormBuilder;
use App\Support\RepositoryResolver;

class BaseController extends Controller
{
    use RespondsWithModal;
    public $modelName = '';
    public $modelRepository;

    public function index()
    {
        $result = $this->modelRepository->getFirst();
        $columns = DtoMetadata::for($this->modelRepository->viewDto)->listColumns(withPrefix: true);

        return view(model_page_view($this->modelName, 'list'), [
            'dto' => $result,
            'model' => $this->modelName,
            'columns' => $columns,
        ]);
    }

    public function create()
    {
        $dtoClass = $this->modelRepository->editDto;
        $dto = $dtoClass::from($dtoClass::empty());

        return view(model_page_view($this->modelName, 'form'), [
            'dto' => $dto,
            'model' => $this->modelName,
            'formFields' => $this->formBuilder()->fields($dtoClass),
            'operation' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->getData($request);
        $this->modelRepository->create($data->toArray());

        return $this->respondAfterMutation($request);
    }

    public function show($id)
    {
        $dto = $this->modelRepository->getById($id);
        $fields = $dto->getFields(onlyHeaders: false, withPrefix: false, object: $dto);

        return view(model_page_view($this->modelName, 'details'), ['dto' => $dto, 'model' => $this->modelName, 'fields' => $fields]);
    }

    public function modalView($id)
    {
        $dto = $this->modelRepository->getById($id);
        $fields = $dto->getFields(onlyHeaders: false, withPrefix: false, object: $dto);
        $data = ['dto' => $dto, 'model' => $this->modelName, 'fields' => $fields];

        return $this->respondModalOrPage(
            model_modal_view($this->modelName, 'view'),
            $data,
            model_page_view($this->modelName, 'details'),
            $data
        );
    }

    public function modalShow($id)
    {
        return $this->modalDelete($id);
    }

    public function modalDelete($id)
    {
        $dto = $this->modelRepository->getById($id);
        $fields = $dto->getFields(onlyHeaders: false, withPrefix: false, object: $dto);
        $data = ['dto' => $dto, 'model' => $this->modelName, 'fields' => $fields];

        return $this->respondModalOrPage(
            model_modal_view($this->modelName, 'delete'),
            $data,
            model_page_view($this->modelName, 'details'),
            $data
        );
    }

    public function modalEdit($id)
    {
        $form = $this->buildEditForm($id);
        $data = [
            'dto' => $form['dto'],
            'model' => $this->modelName,
            'formFields' => $form['formFields'],
            'operation' => 'edit',
        ];

        return $this->respondModalOrPage(
            model_modal_view($this->modelName, 'form'),
            $data,
            model_page_view($this->modelName, 'form'),
            $data
        );
    }

    public function edit($id)
    {
        $form = $this->buildEditForm($id);

        return view(model_page_view($this->modelName, 'form'), [
            'dto' => $form['dto'],
            'model' => $this->modelName,
            'formFields' => $form['formFields'],
            'operation' => 'edit',
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $this->getData($request);
        $this->modelRepository->update($id, $data->toArray());

        return $this->respondAfterMutation($request);
    }

    public function destroy($id)
    {
        $this->modelRepository->delete($id);

        return $this->respondAfterMutation(request());
    }

    protected function respondAfterMutation(Request $request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route(model_route_name($this->modelName, 'index'));
    }

    private function getData(Request $request)
    {
        $dtoClass = "\\App\\Data\\".$this->modelName.'Data';

        return $dtoClass::from($request);
    }

    protected function buildEditForm($id): array
    {
        return [
            'dto' => $this->modelRepository->editById($id),
            'formFields' => $this->formBuilder()->fields($this->modelRepository->editDto),
        ];
    }

    protected function formBuilder(): EntityFormBuilder
    {
        return app(EntityFormBuilder::class);
    }

    protected function initiateModelRepository($modelName)
    {
        return RepositoryResolver::make($modelName);
    }
}
