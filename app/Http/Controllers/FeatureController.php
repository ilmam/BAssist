<?php

namespace App\Http\Controllers;

use App\Models\Feature;
use App\Services\FeatureImportPreview;
use App\Services\FeatureImportService;
use App\Services\GherkinFeatureAssembler;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use InvalidArgumentException;
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
            'importUrl' => route('features.import', $feature->id),
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
            'importUrl' => route('features.import', $feature->id),
        ];

        return $this->respondModalOrPage(
            model_modal_view($this->modelName, 'view'),
            $data,
            model_page_view($this->modelName, 'details'),
            $data
        );
    }

    /**
     * Lightweight modal with assembled .feature content (list "View raw" action).
     */
    public function modalRaw($id): View
    {
        $feature = $this->loadFeature((int) $id);
        $assembler = app(GherkinFeatureAssembler::class);

        return view('pages.features.modals.raw', [
            'feature' => $feature,
            'assembledGherkin' => $assembler->assembleFeature($feature),
            'exportUrl' => route('features.export', $feature->id),
            'printUrl' => route('features.print', $feature->id),
        ]);
    }

    public function edit($id)
    {
        $form = $this->buildEditForm($id);

        return view(model_page_view($this->modelName, 'form'), $this->featureFormViewData($form, 'edit', (int) $id));
    }

    public function modalEdit($id)
    {
        $form = $this->buildEditForm($id);
        $data = $this->featureFormViewData($form, 'edit', (int) $id);

        return $this->respondModalOrPage(
            model_modal_view($this->modelName, 'form'),
            $data,
            model_page_view($this->modelName, 'form'),
            $data
        );
    }

    public function store(Request $request)
    {
        $dtoClass = '\\App\\Data\\'.$this->modelName.'Data';
        $data = $dtoClass::from($request);
        $created = $this->modelRepository->create($data->toArray());

        return $this->respondAfterMutation($request, $created);
    }

    /**
     * @param  array{dto: object, formFields: array<string, mixed>}  $form
     * @return array<string, mixed>
     */
    protected function featureFormViewData(array $form, string $operation, ?int $featureId = null): array
    {
        $data = [
            'dto' => $form['dto'],
            'model' => $this->modelName,
            'formFields' => $form['formFields'],
            'operation' => $operation,
            'feature' => null,
            'scenarios' => collect(),
        ];

        if ($featureId !== null && $featureId > 0) {
            $feature = $this->loadFeature($featureId);
            $data['feature'] = $feature;
            $data['scenarios'] = $feature->scenarios;
        }

        return $data;
    }

    public function importForm($id): View
    {
        $feature = $this->loadFeature((int) $id);
        $dto = $this->modelRepository->getById($id);

        return view('pages.features.import', [
            'feature' => $feature,
            'dto' => $dto,
            'model' => $this->modelName,
            'previewUrl' => route('features.import.preview', $feature->id),
            'backUrl' => route('features.show', $feature->id),
        ]);
    }

    public function importPreview(Request $request, $id): RedirectResponse
    {
        $feature = $this->loadFeature((int) $id);
        $request->validate([
            'feature_file' => ['required', 'file', 'max:1024'],
        ]);

        $file = $request->file('feature_file');
        $contents = (string) file_get_contents($file->getRealPath());
        $filename = (string) $file->getClientOriginalName();

        try {
            $preview = app(FeatureImportService::class)->previewReplace($feature, $contents, $filename);
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('features.import', $feature->id)
                ->withErrors(['feature_file' => $e->getMessage()]);
        }

        $token = Str::random(40);
        $request->session()->put(FeatureImportService::SESSION_KEY, [
            'token' => $token,
            'feature_id' => (int) $feature->id,
            'preview' => $preview->toArray(),
        ]);

        return redirect()->route('features.import.preview.show', $feature->id);
    }

    public function importPreviewShow(Request $request, $id): View|RedirectResponse
    {
        $feature = $this->loadFeature((int) $id);
        $payload = $request->session()->get(FeatureImportService::SESSION_KEY);

        if (! is_array($payload)
            || (int) ($payload['feature_id'] ?? 0) !== (int) $feature->id
            || empty($payload['preview'])
            || empty($payload['token'])
        ) {
            return redirect()
                ->route('features.import', $feature->id)
                ->withErrors(['feature_file' => __('ui.feature_import_session_expired')]);
        }

        $preview = FeatureImportPreview::fromArray($payload['preview']);
        $dto = $this->modelRepository->getById($id);

        return view('pages.features.import-preview', [
            'feature' => $feature,
            'dto' => $dto,
            'model' => $this->modelName,
            'preview' => $preview,
            'token' => $payload['token'],
            'confirmUrl' => route('features.import.confirm', $feature->id),
            'backUrl' => route('features.import', $feature->id),
            'cancelUrl' => route('features.show', $feature->id),
        ]);
    }

    public function importConfirm(Request $request, $id): RedirectResponse
    {
        $feature = $this->loadFeature((int) $id);
        $request->validate([
            'token' => ['required', 'string'],
            'overwrite_title' => ['sometimes', 'boolean'],
        ]);

        $payload = $request->session()->get(FeatureImportService::SESSION_KEY);
        if (! is_array($payload)
            || (int) ($payload['feature_id'] ?? 0) !== (int) $feature->id
            || ! hash_equals((string) ($payload['token'] ?? ''), (string) $request->input('token'))
            || empty($payload['preview']['source'])
        ) {
            return redirect()
                ->route('features.import', $feature->id)
                ->withErrors(['feature_file' => __('ui.feature_import_session_expired')]);
        }

        $source = (string) $payload['preview']['source'];
        $overwriteTitle = $request->boolean('overwrite_title', true);

        try {
            app(FeatureImportService::class)->applyReplace($feature, $source, [
                'overwrite_title' => $overwriteTitle,
            ]);
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('features.import', $feature->id)
                ->withErrors(['feature_file' => $e->getMessage()]);
        }

        $request->session()->forget(FeatureImportService::SESSION_KEY);

        return redirect()
            ->route('features.show', $feature->id)
            ->with('status', __('ui.feature_import_success'));
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
                'changeRequest',
                'swimlaneFlowStep',
                'priority',
                'status',
            ])
            ->findOrFail($id);
    }
}
