<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use App\Support\EntityAccess;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListProjectsTool extends BAssistTool
{
    protected string $name = 'list-projects';

    protected string $title = 'List projects';

    protected string $description = 'List the projects the user can see, with their ids. Call this first: most other tools need a project_id.';

    public function handle(Request $request): Response|ResponseFactory
    {
        return $this->respond(function (): array {
            EntityAccess::authorize(auth()->user(), 'Project', EntityAccess::VIEW);

            $projects = Project::query()
                ->with('workspace:id,name')
                ->orderBy('name')
                ->get()
                ->map(fn (Project $project): array => [
                    'id' => (int) $project->id,
                    'code' => $project->code,
                    'name' => $project->name,
                    'description' => $project->description,
                    'workspace' => $project->workspace?->name,
                ])
                ->all();

            return ['total' => count($projects), 'projects' => $projects];
        });
    }
}
