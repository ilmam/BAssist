<?php

namespace App\Http\Controllers;

use App\Services\AcceptancePlanBuilder;
use App\Support\EntityAccess;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AcceptancePlanController extends Controller
{
    public function __construct(protected AcceptancePlanBuilder $builder)
    {
    }

    public function index(Request $request): View
    {
        $this->authorizeView();

        $plan = $this->builder->build($request->only([
            'project_id',
            'feature_id',
            'type',
            'stakeholder_need_id',
        ]));

        return view('pages.acceptance-plan.index', [
            'rows' => $plan['rows'],
            'summary' => $plan['summary'],
            'projects' => $plan['projects'],
            'features' => $plan['features'],
            'filters' => $plan['filters'],
        ]);
    }

    public function export(Request $request): StreamedResponse|Response
    {
        $this->authorizeView();

        $format = strtolower((string) $request->query('format', 'csv'));
        if (! in_array($format, ['csv', 'md'], true)) {
            $format = 'csv';
        }

        $plan = $this->builder->build($request->only([
            'project_id',
            'feature_id',
            'type',
            'stakeholder_need_id',
        ]));

        $stamp = now()->format('Y-m-d-His');
        $projectSuffix = $plan['filters']['project_id']
            ? '-p'.$plan['filters']['project_id']
            : '';

        if ($format === 'md') {
            $filename = 'acceptance-plan'.$projectSuffix.'-'.$stamp.'.md';
            $markdown = $this->toMarkdown($plan['rows']);

            return response($markdown, 200, [
                'Content-Type' => 'text/markdown; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        }

        $filename = 'acceptance-plan'.$projectSuffix.'-'.$stamp.'.csv';

        return response()->streamDownload(function () use ($plan): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Test ID',
                'Feature',
                'Rule',
                'Scenario',
                'Type',
                'Status',
            ]);

            foreach ($plan['rows'] as $row) {
                fputcsv($handle, [
                    $row['test_id'] ?? '',
                    $row['feature_title'] ?? '',
                    $row['rule'] ?? '',
                    $row['scenario_title'] ?? '',
                    $row['type'] ?? '',
                    $row['status'] ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    protected function toMarkdown(array $rows): string
    {
        $lines = [
            '| Test ID | Feature | Rule | Scenario | Type | Status |',
            '| --- | --- | --- | --- | --- | --- |',
        ];

        foreach ($rows as $row) {
            $lines[] = '| '.$this->mdCell($row['test_id'] ?? '')
                .' | '.$this->mdCell($row['feature_title'] ?? '')
                .' | '.$this->mdCell($row['rule'] ?? '')
                .' | '.$this->mdCell($row['scenario_title'] ?? '')
                .' | '.$this->mdCell($row['type'] ?? '')
                .' | '.$this->mdCell($row['status'] ?? '')
                .' |';
        }

        if ($rows === []) {
            $lines[] = '|  |  |  |  |  |  |';
        }

        return implode("\n", $lines)."\n";
    }

    protected function mdCell(mixed $value): string
    {
        $text = str_replace(['|', "\r\n", "\n", "\r"], ['\|', ' ', ' ', ' '], (string) $value);

        return trim($text);
    }

    protected function authorizeView(): void
    {
        $user = auth()->user();

        $canView = EntityAccess::can($user, 'Feature', EntityAccess::VIEW)
            || EntityAccess::can($user, 'Scenario', EntityAccess::VIEW);

        if (! $canView) {
            EntityAccess::authorize($user, 'Feature', EntityAccess::VIEW);
        }
    }
}
