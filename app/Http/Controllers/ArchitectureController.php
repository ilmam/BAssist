<?php

namespace App\Http\Controllers;

use App\Models\Architecture;
use App\Models\Feature;
use App\Models\Project;
use App\Repositories\ArchitectureRepository;
use App\Services\C4MermaidGenerator;
use App\Services\StructurizrExporter;
use App\Support\EntityAccess;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArchitectureController extends CrudController
{
    public function show($id)
    {
        $dto = $this->modelRepository->getById($id);
        $fields = $dto->getFields(onlyHeaders: false, withPrefix: false, object: $dto);
        /** @var Architecture $architecture */
        $architecture = Architecture::query()->with('project')->findOrFail($id);

        return view(model_page_view($this->modelName, 'details'), array_merge(
            $this->diagramPayload($architecture),
            [
                'dto' => $dto,
                'model' => $this->modelName,
                'fields' => $fields,
                'architecture' => $architecture,
            ]
        ));
    }

    public function edit($id)
    {
        $form = $this->buildEditForm($id);
        /** @var Architecture $architecture */
        $architecture = Architecture::query()->findOrFail($id);

        return view(model_page_view($this->modelName, 'form'), array_merge(
            $this->diagramPayload($architecture),
            [
                'dto' => $form['dto'],
                'model' => $this->modelName,
                'formFields' => $form['formFields'],
                'operation' => 'edit',
                'features' => $this->featureOptions((int) $architecture->project_id),
            ]
        ));
    }

    public function create()
    {
        $form = $this->buildCreateForm();
        $projectId = (int) ($form['dto']->project_id ?? 0);

        return view(model_page_view($this->modelName, 'form'), [
            'dto' => $form['dto'],
            'model' => $this->modelName,
            'formFields' => $form['formFields'],
            'operation' => 'create',
            'features' => $this->featureOptions($projectId),
            'layout' => app(\App\Services\C4ArchitectureNormalizer::class)->normalizeLayout([]),
            'mermaidContext' => "C4Context\n",
            'mermaidContainer' => "C4Container\n",
            'mermaidComponent' => "C4Component\n",
            'exportDslUrl' => null,
            'exportJsonUrl' => null,
        ]);
    }

    public function modalCreate()
    {
        return redirect()->route('architectures.create');
    }

    public function modalEdit($id)
    {
        return redirect()->route('architectures.edit', $id);
    }

    public function modalView($id)
    {
        return redirect()->route('architectures.show', $id);
    }

    public function modalQuickCreate()
    {
        return redirect()->route('architectures.create');
    }

    /**
     * Open (or create) the single architecture model for a project.
     */
    public function forProject(Request $request, Project $project)
    {
        /** @var ArchitectureRepository $repo */
        $repo = $this->modelRepository;
        $existing = Architecture::query()->where('project_id', $project->id)->first();

        if ($existing === null) {
            EntityAccess::authorize(auth()->user(), $this->modelName, EntityAccess::CREATE);
            $architecture = $repo->findOrCreateForProject($project);
        } else {
            EntityAccess::authorize(auth()->user(), $this->modelName, EntityAccess::VIEW);
            $architecture = $existing;
        }

        if (EntityAccess::can(auth()->user(), $this->modelName, EntityAccess::UPDATE)) {
            return redirect()->route('architectures.edit', $architecture->id);
        }

        return redirect()->route('architectures.show', $architecture->id);
    }

    public function exportDsl(int $id): StreamedResponse
    {
        $architecture = Architecture::query()->findOrFail($id);
        $dsl = app(StructurizrExporter::class)->toDsl(
            $architecture->title,
            $architecture->normalizedElements(),
            $architecture->normalizedRelationships()
        );

        $filename = 'architecture-'.$architecture->project_id.'.dsl';

        return response()->streamDownload(function () use ($dsl): void {
            echo $dsl;
        }, $filename, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function exportJson(int $id): Response
    {
        $architecture = Architecture::query()->findOrFail($id);
        $json = app(StructurizrExporter::class)->toJson(
            $architecture->title,
            $architecture->normalizedElements(),
            $architecture->normalizedRelationships()
        );

        return response()->json($json, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            ->header('Content-Disposition', 'attachment; filename="architecture-'.$architecture->project_id.'.json"');
    }

    /**
     * @return array<string, mixed>
     */
    protected function diagramPayload(Architecture $architecture): array
    {
        $elements = $architecture->normalizedElements();
        $relationships = $architecture->normalizedRelationships();
        $layout = $architecture->normalizedLayout();
        $mermaid = app(C4MermaidGenerator::class);

        $system = $mermaid->resolveSystem($elements, request('system'));
        $container = $mermaid->resolveContainer($elements, request('container'));

        return [
            'features' => $this->featureOptions((int) $architecture->project_id),
            'layout' => $layout,
            'mermaidContext' => $mermaid->toContext($elements, $relationships, $layout),
            'mermaidContainer' => $mermaid->toContainer($elements, $relationships, $system['key'] ?? null, $layout),
            'mermaidComponent' => $mermaid->toComponent($elements, $relationships, $container['key'] ?? null, $layout),
            'focusSystemKey' => $system['key'] ?? null,
            'focusContainerKey' => $container['key'] ?? null,
            'exportDslUrl' => route('architectures.export-dsl', $architecture->id),
            'exportJsonUrl' => route('architectures.export-json', $architecture->id),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function featureOptions(int $projectId): array
    {
        if ($projectId <= 0) {
            return [];
        }

        return Feature::query()
            ->where('project_id', $projectId)
            ->orderBy('number')
            ->orderBy('title')
            ->get(['id', 'number', 'title'])
            ->mapWithKeys(fn (Feature $f) => [
                $f->id => trim(($f->number ? $f->number.' — ' : '').$f->title),
            ])
            ->all();
    }
}
