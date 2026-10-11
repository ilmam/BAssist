<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\UiDesignPackService;
use App\Support\EntityAccess;
use App\Support\Tenancy;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The UI designs pack of a project: a printable page and a ZIP download (docs/design-layer.md).
 */
class ProjectUiDesignController extends Controller
{
    public function __construct(protected UiDesignPackService $pack)
    {
    }

    public function show(Project $project): View
    {
        $this->authorizeProject($project);

        return view('pages.projects.ui-designs', $this->pack->build($project));
    }

    public function download(Project $project): BinaryFileResponse
    {
        $this->authorizeProject($project);

        return response()
            ->download($this->pack->zip($project), $this->pack->fileName($project))
            ->deleteFileAfterSend(true);
    }

    protected function authorizeProject(Project $project): void
    {
        EntityAccess::authorize(auth()->user(), 'Project', EntityAccess::VIEW);
        EntityAccess::authorize(auth()->user(), 'Screen', EntityAccess::VIEW);

        Tenancy::assertProject($project);
    }
}
