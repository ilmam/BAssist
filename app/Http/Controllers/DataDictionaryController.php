<?php

namespace App\Http\Controllers;

use App\Models\DataDictionary;
use App\Services\DataDictionaryStubExporter;
use App\Services\EfModelStubExporter;
use App\Services\ErdMermaidGenerator;
use App\Support\EntityAccess;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataDictionaryController extends CrudController
{
    public function create()
    {
        $form = $this->buildCreateForm();

        return view(model_page_view($this->modelName, 'form'), $this->formViewData($form, 'create'));
    }

    public function edit($id)
    {
        $form = $this->buildEditForm($id);

        return view(model_page_view($this->modelName, 'form'), $this->formViewData($form, 'edit', (int) $id));
    }

    public function modalCreate()
    {
        return redirect()->route('data_dictionaries.create');
    }

    public function modalEdit($id)
    {
        return redirect()->route('data_dictionaries.edit', $id);
    }

    public function modalView($id)
    {
        return redirect()->route('data_dictionaries.show', $id);
    }

    public function modalQuickCreate()
    {
        return redirect()->route('data_dictionaries.create');
    }

    public function show($id)
    {
        $dto = $this->modelRepository->getById($id);
        $fields = $dto->getFields(onlyHeaders: false, withPrefix: false, object: $dto);

        return view(model_page_view($this->modelName, 'details'), $this->viewData($dto, $fields, (int) $id));
    }

    public function exportCsharp(int $id): StreamedResponse
    {
        EntityAccess::authorize(auth()->user(), 'DataDictionary', EntityAccess::VIEW);

        $dictionary = DataDictionary::query()->findOrFail($id);
        $body = app(EfModelStubExporter::class)->export(
            $dictionary->title,
            $dictionary->normalizedEntities()
        );
        $filename = $this->exportBasename($dictionary).'.cs';

        return response()->streamDownload(function () use ($body): void {
            echo $body;
        }, $filename, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function exportPhp(int $id): Response
    {
        EntityAccess::authorize(auth()->user(), 'DataDictionary', EntityAccess::VIEW);

        $dictionary = DataDictionary::query()->findOrFail($id);
        $body = app(DataDictionaryStubExporter::class)->toPhp($dictionary->normalizedEntities());
        $filename = $this->exportBasename($dictionary).'.php';

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * @param  array{dto: object, formFields: array<string, mixed>}  $form
     * @return array<string, mixed>
     */
    protected function formViewData(array $form, string $operation, ?int $id = null): array
    {
        $entities = is_array($form['dto']->entities ?? null) ? $form['dto']->entities : [];

        return [
            'dto' => $form['dto'],
            'model' => $this->modelName,
            'formFields' => $form['formFields'],
            'operation' => $operation,
            'entities' => $entities,
            'mermaid' => app(ErdMermaidGenerator::class)->generate($entities, 'design'),
            'mermaidConceptual' => app(ErdMermaidGenerator::class)->generate($entities, 'conceptual'),
            'exportCsharpUrl' => $id !== null ? route('data_dictionaries.export-csharp', $id) : null,
            'exportPhpUrl' => $id !== null ? route('data_dictionaries.export-php', $id) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function viewData(object $dto, array $fields, int $id): array
    {
        $dictionary = DataDictionary::query()->findOrFail($id);
        $entities = $dictionary->normalizedEntities();

        return [
            'dto' => $dto,
            'model' => $this->modelName,
            'fields' => $fields,
            'entities' => $entities,
            'mermaid' => app(ErdMermaidGenerator::class)->generate($entities, 'design'),
            'mermaidConceptual' => app(ErdMermaidGenerator::class)->generate($entities, 'conceptual'),
            'exportCsharpUrl' => route('data_dictionaries.export-csharp', $id),
            'exportPhpUrl' => route('data_dictionaries.export-php', $id),
        ];
    }

    protected function exportBasename(DataDictionary $dictionary): string
    {
        $slug = preg_replace('/[^A-Za-z0-9]+/', '-', strtolower($dictionary->title)) ?: 'data-dictionary';

        return trim($slug, '-');
    }
}
