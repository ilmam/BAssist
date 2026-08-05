<?php

namespace App\Repositories;

use App\Models\SwimlaneFlowStep;
use App\Services\SwimlaneMermaidGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Select-options helper for FR/Feature swimlane_flow_step_id fields.
 * Swimlane flow steps are not a standalone CRUD resource.
 */
class SwimlaneFlowStepRepository extends BaseRepository
{
    public Model $model;

    public $editDto = null;

    public $viewDto = null;

    public function __construct()
    {
        $this->model = new SwimlaneFlowStep();
    }

    public function getSelectOptions($fields = null): Collection
    {
        // Leading blank keeps swimlane_flow_step_id optional on FR/Feature selects.
        $options = collect(['' => '']);

        return $options->union(
            SwimlaneFlowStep::query()
                ->whereIn('type', SwimlaneMermaidGenerator::SATISFIABLE_TYPES)
                ->orderBy('number')
                ->orderBy('label')
                ->get(['id', 'number', 'label'])
                ->mapWithKeys(function (SwimlaneFlowStep $step) {
                    $code = $step->code ? $step->code.' — ' : '';

                    return [$step->id => $code.$step->label];
                })
        );
    }
}
