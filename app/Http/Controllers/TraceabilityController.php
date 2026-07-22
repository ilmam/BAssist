<?php

namespace App\Http\Controllers;

use App\Services\TraceabilityMatrixService;
use App\Support\EntityAccess;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TraceabilityController extends Controller
{
    public function __construct(protected TraceabilityMatrixService $matrix)
    {
    }

    public function index(Request $request): View
    {
        $this->authorizeView();

        $matrix = $this->matrix->build($request->only(['project_id', 'orphans_only']));

        return view('pages.traceability.matrix', [
            'rows' => $matrix['rows'],
            'summary' => $matrix['summary'],
            'projects' => $matrix['projects'],
            'filters' => $matrix['filters'],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorizeView();

        $matrix = $this->matrix->build($request->only(['project_id', 'orphans_only']));
        $filename = 'traceability-matrix-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($matrix): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Project',
                'Project Code',
                'Business Objective',
                'Business Need',
                'Stakeholder Need',
                'Stakeholders',
                'Gaps',
            ]);

            foreach ($matrix['rows'] as $row) {
                fputcsv($handle, [
                    $row['project_name'] ?? '',
                    $row['project_code'] ?? '',
                    $row['objective_title'] ?? '',
                    $row['need_title'] ?? '',
                    $row['stakeholder_need_title'] ?? '',
                    implode('; ', $row['stakeholder_names'] ?? []),
                    implode('; ', $row['gaps'] ?? []),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function authorizeView(): void
    {
        $user = auth()->user();

        $canView = EntityAccess::can($user, 'BusinessNeed', EntityAccess::VIEW)
            || EntityAccess::can($user, 'BusinessObjective', EntityAccess::VIEW)
            || EntityAccess::can($user, 'StakeholderNeed', EntityAccess::VIEW);

        if (! $canView) {
            EntityAccess::authorize($user, 'BusinessNeed', EntityAccess::VIEW);
        }
    }
}
