<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\BabokDocumentService;
use App\Support\EntityAccess;
use App\Support\Tenancy;
use Illuminate\Http\Response;
use Illuminate\View\View;

class BabokDocumentController extends Controller
{
    public function __construct(protected BabokDocumentService $documents)
    {
    }

    public function index(Project $project): View
    {
        $this->authorizeProject($project);

        return view('pages.projects.babok.index', [
            'project' => $project,
            'documents' => $this->documents->catalog($project),
            'fullPackUrl' => route('projects.export', $project),
        ]);
    }

    public function show(Project $project, string $document): View
    {
        $this->authorizeProject($project);

        if (! $this->documents->hasDocument($document)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $payload = $this->documents->build($project, $document);

        return view('pages.projects.babok.document', $payload);
    }

    protected function authorizeProject(Project $project): void
    {
        EntityAccess::authorize(auth()->user(), 'Project', EntityAccess::VIEW);

        Tenancy::assertProject($project);
    }
}
