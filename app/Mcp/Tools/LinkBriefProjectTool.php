<?php

namespace App\Mcp\Tools;

use App\Models\Brief;
use App\Models\Project;
use App\Services\BriefProjectService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly(false)]
#[IsDestructive(false)]
class LinkBriefProjectTool extends BAssistTool
{
    protected string $name = 'link-brief-project';

    protected string $title = 'Link a brief to a project';

    protected string $description = 'Connect a Brief (the ask as received) to the projects that came from it. action link: link an existing project in the same workspace, with a scope_note saying which part of the ask it takes (linking again only updates the note). action unlink: remove the link. action create_project: start a new project in the brief\'s workspace from the brief (not allowed for a declined brief) and link it. A project with no brief is normal; do not link one just to fill a gap.';

    public function handle(Request $request, BriefProjectService $links): Response|ResponseFactory
    {
        $this->requireWrite($request);

        $validated = $request->validate([
            'action' => ['required', 'in:link,unlink,create_project'],
            'brief_id' => ['required', 'integer'],
            'project_id' => ['required_if:action,link,unlink', 'nullable', 'integer'],
            'scope_note' => ['nullable', 'string'],
            'name' => ['required_if:action,create_project', 'nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255'],
        ]);

        return $this->respond(function () use ($validated, $links): array {
            $brief = Brief::query()->findOrFail((int) $validated['brief_id']);
            $note = $validated['scope_note'] ?? null;

            if ($validated['action'] === 'create_project') {
                $project = $links->createProject($brief, $validated['name'], $validated['code'] ?? null, $note);
            } else {
                $project = Project::query()->findOrFail((int) $validated['project_id']);

                $validated['action'] === 'link'
                    ? $links->link($brief, $project, $note)
                    : $links->unlink($brief, $project);
            }

            return [
                'action' => $validated['action'],
                'brief_id' => (int) $brief->getKey(),
                'project' => ['id' => (int) $project->getKey(), 'name' => $project->name],
                'linked_projects' => $brief->projects()->get(['projects.id', 'projects.name'])
                    ->map(fn ($p) => ['id' => (int) $p->id, 'name' => $p->name, 'scope_note' => $p->pivot->scope_note])
                    ->values()
                    ->all(),
            ];
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()->description('link, unlink or create_project.')->required(),
            'brief_id' => $schema->integer()->description('The brief.')->required(),
            'project_id' => $schema->integer()->description('The project to link or unlink. Not used by create_project.'),
            'scope_note' => $schema->string()->description('Which part of the ask this project takes.'),
            'name' => $schema->string()->description('create_project only: the new project name.'),
            'code' => $schema->string()->description('create_project only: the new project code.'),
        ];
    }
}
