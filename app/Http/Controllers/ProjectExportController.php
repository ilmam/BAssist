<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectExportService;
use App\Support\EntityAccess;
use App\Support\Tenancy;
use Illuminate\View\View;

class ProjectExportController extends Controller
{
    public function __construct(protected ProjectExportService $export)
    {
    }

    public function show(Project $project): View
    {
        EntityAccess::authorize(auth()->user(), 'Project', EntityAccess::VIEW);

        Tenancy::assertProject($project);

        $pack = $this->export->build($project);

        return view('pages.projects.export', $pack);
    }
}
