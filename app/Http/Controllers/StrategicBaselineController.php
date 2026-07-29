<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\StrategicBaseline;
use App\Repositories\StrategicBaselineRepository;
use App\Support\EntityAccess;
use Illuminate\Http\Request;

class StrategicBaselineController extends CrudController
{
    public function modalCreate()
    {
        return redirect()->route(model_route_name($this->modelName, 'create'));
    }

    public function modalEdit($id)
    {
        return redirect()->route(model_route_name($this->modelName, 'edit'), $id);
    }

    public function modalView($id)
    {
        return redirect()->route(model_route_name($this->modelName, 'show'), $id);
    }

    public function modalQuickCreate()
    {
        return redirect()->route(model_route_name($this->modelName, 'create'));
    }

    /**
     * Open (or create) the single strategic baseline for a project.
     */
    public function forProject(Request $request, Project $project)
    {
        /** @var StrategicBaselineRepository $repo */
        $repo = $this->modelRepository;
        $existing = StrategicBaseline::query()->where('project_id', $project->id)->first();

        if ($existing === null) {
            EntityAccess::authorize(auth()->user(), $this->modelName, EntityAccess::CREATE);
            $baseline = $repo->findOrCreateForProject($project);
        } else {
            EntityAccess::authorize(auth()->user(), $this->modelName, EntityAccess::VIEW);
            $baseline = $existing;
        }

        if (EntityAccess::can(auth()->user(), $this->modelName, EntityAccess::UPDATE)) {
            return redirect()->route(model_route_name($this->modelName, 'edit'), $baseline->id);
        }

        return redirect()->route(model_route_name($this->modelName, 'show'), $baseline->id);
    }
}
