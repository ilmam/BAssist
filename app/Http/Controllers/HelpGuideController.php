<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithModal;
use App\Support\HelpRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class HelpGuideController extends Controller
{
    use RespondsWithModal;

    public function index(): View|RedirectResponse
    {
        if (! $this->wantsModalFragment()) {
            return redirect('/');
        }

        return view('pages.help.guide-toc', [
            'title' => __(config('help_booklet.title_key', 'ui.ba_guide')),
            'steps' => $this->availableSteps(),
        ]);
    }

    public function show(string $key): View|Response|RedirectResponse
    {
        if (! $this->wantsModalFragment()) {
            return redirect('/');
        }

        $key = HelpRegistry::normalizeKey($key);
        $steps = $this->availableSteps();
        $index = collect($steps)->search(fn (array $step): bool => $step['key'] === $key);

        if ($index === false || ! HelpRegistry::exists($key)) {
            abort(404);
        }

        $guide = HelpRegistry::load($key);

        if ($guide === null) {
            abort(404);
        }

        $prev = $index > 0 ? $steps[$index - 1] : null;
        $next = $index < count($steps) - 1 ? $steps[$index + 1] : null;

        return view('pages.help.guide-step', [
            'title' => $guide['title'],
            'html' => $guide['html'],
            'key' => $key,
            'prev' => $prev,
            'next' => $next,
        ]);
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    protected function availableSteps(): array
    {
        $configured = config('help_booklet.steps', []);

        if (! is_array($configured)) {
            return [];
        }

        $steps = [];

        foreach ($configured as $step) {
            if (! is_array($step)) {
                continue;
            }

            $key = HelpRegistry::normalizeKey((string) ($step['key'] ?? ''));

            if ($key === '' || ! HelpRegistry::exists($key)) {
                continue;
            }

            $label = is_string($step['label'] ?? null) && $step['label'] !== ''
                ? $step['label']
                : str_replace('_', ' ', $key);

            $steps[] = [
                'key' => $key,
                'label' => $label,
            ];
        }

        return $steps;
    }
}
