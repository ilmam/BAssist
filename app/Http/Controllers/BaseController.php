<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithModal;
use Illuminate\Http\Request;
use App\Helpers\AttributeHelper;
use App\Helpers\FormHelper;
use App\Support\RepositoryResolver;

class BaseController extends Controller
{
    use RespondsWithModal;
    public $modelName = '';
    public $modelRepository;

    public function index()
    {
        $result = $this->modelRepository->getFirst();

        return view(model_page_view($this->modelName, 'list'), ['dto' => $result, 'model' => $this->modelName]);
    }

    public function create()
    {
        $dtoClass = $this->modelRepository->editDto;
        $dto = $dtoClass::from($dtoClass::empty());
        $fields = AttributeHelper::getPropertyAttributes($this->modelRepository->editDto, 'FormFieldAttribute', true);
        $formFields = FormHelper::getFormFields($fields);

        return view(model_page_view($this->modelName, 'form'), [
            'dto' => $dto,
            'model' => $this->modelName,
            'formFields' => $formFields,
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
            'themes.'.ui_theme().'.pages.modalview',
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
            'themes.'.ui_theme().'.pages.modaldetails',
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
            'themes.'.ui_theme().'.pages.modalform',
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
        $dto = $this->modelRepository->editById($id);
        $fields = AttributeHelper::getPropertyAttributes($this->modelRepository->editDto, 'FormFieldAttribute', true);

        foreach ($fields as $title => &$options) {
            $type = $options[0];
            if (isset($options[1]) && $type === 'select') {
                $repository = $this->initiateModelRepository($options[1]);
                $options['list'] = $repository->getSelectOptions();
            }
        }

        return [
            'dto' => $dto,
            'formFields' => FormHelper::getFormFields($fields),
        ];
    }

    protected function initiateModelRepository($modelName)
    {
        return RepositoryResolver::make($modelName);
    }
}
