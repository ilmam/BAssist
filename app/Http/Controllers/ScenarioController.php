<?php

namespace App\Http\Controllers;

use App\Models\Scenario;
use App\Services\GherkinFeatureAssembler;
use Illuminate\Http\Request;

class ScenarioController extends CrudController
{
    public function show($id)
    {
        $dto = $this->modelRepository->getById($id);
        $fields = $dto->getFields(onlyHeaders: false, withPrefix: false, object: $dto);
        $scenario = $this->loadScenario((int) $id);
        $assembler = app(GherkinFeatureAssembler::class);
        $body = $assembler->assembleScenario($scenario);

        return view(model_page_view($this->modelName, 'details'), [
            'dto' => $dto,
            'model' => $this->modelName,
            'fields' => $fields,
            'scenario' => $scenario,
            'gherkin' => $body,
            'tagList' => $assembler->scenarioDisplayTags($scenario),
        ]);
    }

    public function modalView($id)
    {
        $dto = $this->modelRepository->getById($id);
        $fields = $dto->getFields(onlyHeaders: false, withPrefix: false, object: $dto);
        $scenario = $this->loadScenario((int) $id);
        $assembler = app(GherkinFeatureAssembler::class);
        $body = $assembler->assembleScenario($scenario);
        $data = [
            'dto' => $dto,
            'model' => $this->modelName,
            'fields' => $fields,
            'scenario' => $scenario,
            'gherkin' => $body,
            'tagList' => $assembler->scenarioDisplayTags($scenario),
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

    protected function respondAfterMutation(Request $request, mixed $record = null)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return parent::respondAfterMutation($request, $record);
        }

        $featureId = $this->resolveFeatureIdFromRecord($record)
            ?? (int) $request->input('feature_id', 0);

        if ($featureId > 0) {
            return redirect()->route('features.show', $featureId);
        }

        return parent::respondAfterMutation($request, $record);
    }

    protected function loadScenario(int $id): Scenario
    {
        return Scenario::query()
            ->with(['feature.project', 'status'])
            ->findOrFail($id);
    }

    protected function resolveFeatureIdFromRecord(mixed $record): ?int
    {
        if (is_object($record)) {
            if (isset($record->feature_id) && (int) $record->feature_id > 0) {
                return (int) $record->feature_id;
            }

            if (method_exists($record, 'toArray')) {
                $values = $record->toArray();
                if (! empty($values['feature_id'])) {
                    return (int) $values['feature_id'];
                }
            }
        }

        if (is_array($record) && ! empty($record['feature_id'])) {
            return (int) $record['feature_id'];
        }

        return null;
    }
}
