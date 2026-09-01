<?php

namespace App\Http\Controllers;

use App\Services\GherkinFeatureAssembler;

class ScenarioController extends CrudController
{
    public function __construct(
        protected GherkinFeatureAssembler $assembler,
    ) {
    }

    public function show($id)
    {
        $dto = $this->modelRepository->getById($id);
        $fields = $dto->getFields(onlyHeaders: false, withPrefix: false, object: $dto);
        $scenario = $this->modelRepository->findForDocument((int) $id);
        $body = $this->assembler->assembleScenario($scenario);

        return view(model_page_view($this->modelName, 'details'), [
            'dto' => $dto,
            'model' => $this->modelName,
            'fields' => $fields,
            'scenario' => $scenario,
            'gherkin' => $body,
            'tagList' => $this->assembler->scenarioDisplayTags($scenario),
        ]);
    }

    public function modalView($id)
    {
        $dto = $this->modelRepository->getById($id);
        $fields = $dto->getFields(onlyHeaders: false, withPrefix: false, object: $dto);
        $scenario = $this->modelRepository->findForDocument((int) $id);
        $body = $this->assembler->assembleScenario($scenario);
        $data = [
            'dto' => $dto,
            'model' => $this->modelName,
            'fields' => $fields,
            'scenario' => $scenario,
            'gherkin' => $body,
            'tagList' => $this->assembler->scenarioDisplayTags($scenario),
        ];

        return $this->respondModalOrPage(
            model_modal_view($this->modelName, 'view'),
            $data,
            model_page_view($this->modelName, 'details'),
            $data
        );
    }

    protected function applyStickyContextDefaults(object $dto): object
    {
        $dto = parent::applyStickyContextDefaults($dto);
        $payload = method_exists($dto, 'toArray') ? $dto->toArray() : [];

        $featureId = (int) request()->query('feature_id', 0);
        if ($featureId > 0 && array_key_exists('feature_id', $payload)) {
            $payload['feature_id'] = $featureId;
        }

        return $dto::from($payload);
    }
}
