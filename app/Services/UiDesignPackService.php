<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Screen;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * The UI designs pack of a project (docs/design-layer.md): every screen with the requirements it
 * realizes and the Salt text that draws it, as a printable page and as a downloadable ZIP
 * (one .puml per screen, an index, and an offline viewer).
 */
class UiDesignPackService
{
    public function __construct(protected SaltScreenAssembler $assembler)
    {
    }

    /**
     * @return array{
     *   project: Project,
     *   generated_at: \Illuminate\Support\Carbon,
     *   screens: list<array{model: Screen, number: int, slug: string, file: string, salt: string, requirements: list<string>}>
     * }
     */
    public function build(Project $project): array
    {
        $screens = Screen::query()
            ->where('project_id', $project->id)
            ->with(['status', 'functionalRequirements', 'screenElements'])
            ->orderBy('title')
            ->get();

        $items = [];
        foreach ($screens->values() as $i => $screen) {
            $slug = Str::slug((string) $screen->title) ?: 'screen-'.$screen->id;

            $items[] = [
                'model' => $screen,
                'number' => $i + 1,
                'slug' => $slug,
                'file' => sprintf('screens/%02d-%s.puml', $i + 1, $slug),
                'salt' => $this->assembler->assembleScreen($screen),
                'requirements' => $screen->functionalRequirements
                    ->map(fn ($fr) => $fr->code.' — '.$fr->title)
                    ->values()
                    ->all(),
            ];
        }

        return [
            'project' => $project,
            'generated_at' => now(),
            'screens' => $items,
        ];
    }

    /** File name for the download, e.g. dealer-parts-ui-designs.zip. */
    public function fileName(Project $project): string
    {
        return (Str::slug((string) ($project->code ?: $project->name)) ?: 'project').'-ui-designs.zip';
    }

    /**
     * Builds the ZIP in a temporary file and returns its path (the caller deletes it after sending).
     */
    public function zip(Project $project): string
    {
        $pack = $this->build($project);
        $path = tempnam(sys_get_temp_dir(), 'uidz');

        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create the ZIP file.');
        }

        $zip->addFromString('README.md', $this->readme($pack));
        $zip->addFromString('index.md', $this->index($pack));
        $zip->addFromString('wireframes.html', $this->viewer($pack));

        foreach ($pack['screens'] as $item) {
            $zip->addFromString($item['file'], $item['salt']."\n");
        }

        $zip->close();

        return $path;
    }

    /** @param  array<string, mixed>  $pack */
    protected function readme(array $pack): string
    {
        $project = $pack['project'];

        return implode("\n", [
            '# UI designs: '.$project->name,
            '',
            'Generated '.$pack['generated_at']->timezone(config('app.timezone'))->format('Y-m-d H:i').' from BAssist.',
            '',
            '- `index.md`: the screens, their status and the functional requirements each one realizes.',
            '- `screens/*.puml`: one PlantUML Salt file per screen. The text is the design: paste it into any PlantUML tool that supports Salt (wireframes) to draw it.',
            '- `wireframes.html`: open it in a browser (no internet needed) to see every screen drawn.',
            '',
            'Designs realize functional requirements; they are not requirements themselves.',
            '',
        ]);
    }

    /** @param  array<string, mixed>  $pack */
    protected function index(array $pack): string
    {
        $lines = ['# '.$pack['project']->name.': UI designs', ''];

        if ($pack['screens'] === []) {
            return implode("\n", [...$lines, 'No UI designs yet.', '']);
        }

        $lines[] = '| # | Screen | Status | Realizes | File |';
        $lines[] = '|---|--------|--------|----------|------|';

        foreach ($pack['screens'] as $item) {
            $lines[] = sprintf(
                '| %d | %s | %s | %s | `%s` |',
                $item['number'],
                str_replace('|', '/', (string) $item['model']->title),
                $item['model']->status?->name ?: '—',
                $item['requirements'] === [] ? '—' : str_replace('|', '/', implode('; ', $item['requirements'])),
                $item['file'],
            );
        }

        foreach ($pack['screens'] as $item) {
            $lines[] = '';
            $lines[] = '## '.$item['number'].'. '.$item['model']->title;
            if (filled($item['model']->description)) {
                $lines[] = '';
                $lines[] = (string) $item['model']->description;
            }
            $lines[] = '';
            $lines[] = '```';
            $lines[] = $item['salt'];
            $lines[] = '```';
        }

        return implode("\n", [...$lines, '']);
    }

    /** One self-contained page: renderer and styles inlined, so it opens offline. @param  array<string, mixed>  $pack */
    protected function viewer(array $pack): string
    {
        return view('pages.projects.ui-designs-offline', [
            'project' => $pack['project'],
            'generated_at' => $pack['generated_at'],
            'screens' => $pack['screens'],
            'css' => (string) @file_get_contents(public_path('css/salt-wireframe.css')),
            'js' => (string) @file_get_contents(public_path('js/salt-wireframe.js')),
        ])->render();
    }
}
