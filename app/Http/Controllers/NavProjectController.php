<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Support\EntityAccess;
use App\Support\NavTreeBuilder;
use Illuminate\Http\Response;

/**
 * The folders of one project for the sidebar, loaded when the project is first opened
 * (the page itself only carries the active project's folders; see NavTreeBuilder).
 */
class NavProjectController extends Controller
{
    public function show(Project $project, NavTreeBuilder $builder): Response
    {
        // Route binding already applies the tenant scope: a foreign project is a 404.
        abort_unless(EntityAccess::can(auth()->user(), 'Project', EntityAccess::VIEW), 403);

        return response(
            view('themes.metronic9.partials.menu-items', ['items' => $builder->projectItems($project), 'level' => 3])->render()
        );
    }
}
