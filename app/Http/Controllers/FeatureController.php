<?php

namespace App\Http\Controllers;

use App\Models\Feature;
use App\Models\Scenario;
use App\Services\GherkinDocumentParser;
use App\Services\GherkinFeatureAssembler;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FeatureController extends CrudController
{
    public function show($id)
    {
        $dto = $this->modelRepository->getById($id);
        $fields = $dto->getFields(onlyHeaders: false, withPrefix: false, object: $dto);
        $feature = $this->loadFeature((int) $id);
        $assembler = app(GherkinFeatureAssembler::class);

        return view(model_page_view($this->modelName, 'details'), [
            'dto' => $dto,
            'model' => $this->modelName,
            'fields' => $fields,
            'feature' => $feature,
            'assembledGherkin' => $assembler->assembleFeature($feature),
            'tagList' => $assembler->featureDisplayTags($feature),
            'exportUrl' => route('features.export', $feature->id),
            'printUrl' => route('features.print', $feature->id),
        ]);
    }

    public function modalView($id)
    {
        $dto = $this->modelRepository->getById($id);
        $fields = $dto->getFields(onlyHeaders: false, withPrefix: false, object: $dto);
        $feature = $this->loadFeature((int) $id);
        $assembler = app(GherkinFeatureAssembler::class);
        $data = [
            'dto' => $dto,
            'model' => $this->modelName,
            'fields' => $fields,
            'feature' => $feature,
            'assembledGherkin' => $assembler->assembleFeature($feature),
            'tagList' => $assembler->featureDisplayTags($feature),
            'exportUrl' => route('features.export', $feature->id),
            'printUrl' => route('features.print', $feature->id),
        ];

        return $this->respondModalOrPage(
            model_modal_view($this->modelName, 'view'),
            $data,
            model_page_view($this->modelName, 'details'),
            $data
        );
    }

    public function store(Request $request)
    {
        $importSource = trim((string) $request->input('import_source', ''));
        $parsedScenarios = [];
        $parser = app(GherkinDocumentParser::class);

        if ($importSource !== '') {
            $parsed = $parser->splitFeatureFile($importSource);
            $parsedScenarios = $parsed['scenarios'];
            $body = $parsed['preamble'] !== ''
                ? $parsed['preamble']
                : (string) $request->input('body', '');
            $titleFromBody = $parser->extractFeatureTitle($body);
            $request->merge([
                'body' => $body,
                'title' => $titleFromBody ?? $request->input('title'),
            ]);
        }

        $dtoClass = '\\App\\Data\\'.$this->modelName.'Data';
        $data = $dtoClass::from($request);
        $created = $this->modelRepository->create($data->toArray());

        foreach ($parsedScenarios as $block) {
            $scenario = new Scenario([
                'feature_id' => $created->id,
                'title' => $block['title'],
                'body' => $block['body'],
                'is_outline' => $block['is_outline'],
            ]);
            $scenario->syncDocumentFields($parser);
            $scenario->save();
        }

        return $this->respondAfterMutation($request, $created);
    }

    protected function respondAfterMutation(Request $request, mixed $record = null)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return parent::respondAfterMutation($request, $record);
        }

        $id = null;
        if (is_object($record) && isset($record->id)) {
            $id = (int) $record->id;
        } elseif (is_array($record) && ! empty($record['id'])) {
            $id = (int) $record['id'];
        }

        if ($id > 0) {
            return redirect()->route('features.show', $id);
        }

        return parent::respondAfterMutation($request, $record);
    }

    public function export($id): StreamedResponse|Response
    {
        $feature = $this->loadFeature((int) $id);
        $assembler = app(GherkinFeatureAssembler::class);
        $body = $assembler->assembleFeature($feature);
        $filename = $assembler->downloadFilename($feature);

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function print($id): View
    {
        $feature = $this->loadFeature((int) $id);
        $assembler = app(GherkinFeatureAssembler::class);

        return view('pages.features.print', [
            'feature' => $feature,
            'gherkin' => $assembler->assembleFeature($feature),
            'filename' => $assembler->downloadFilename($feature),
            'exportUrl' => route('features.export', $feature->id),
            'backUrl' => route('features.show', $feature->id),
        ]);
    }

    protected function loadFeature(int $id): Feature
    {
        return Feature::query()
            ->with([
                'scenarios' => fn ($query) => $query->orderBy('id'),
                'project',
                'stakeholderNeed',
                'priority',
                'status',
            ])
            ->findOrFail($id);
    }

}
