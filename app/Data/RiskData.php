<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;
use App\Support\RiskCategory;
use App\Support\RiskImpact;
use App\Support\RiskLikelihood;
use App\Support\RiskResponse;
use App\Support\RiskStatus;
use Illuminate\Validation\Rule;

class RiskData extends BaseData
{
    public function __construct(
        public ?int $id = null,

        #[Form('text', readonly: true)]
        public ?string $code = null,

        #[ListForm('text')]
        public string $title = '',

        #[Form('select', 'Project', hideQuick: true)]
        public int $project_id = 0,

        #[Form('textarea', hideQuick: true, help: 'Condition and the resulting negative impact to value if it occurs.')]
        public ?string $description = null,

        #[ListForm('select', 'RiskCategory')]
        public string $category = RiskCategory::TECHNICAL,

        #[ListForm('select', 'RiskLikelihood', help: 'Probability the risk event occurs (1 Low – 3 High).')]
        public string $likelihood = RiskLikelihood::MEDIUM,

        #[ListForm('select', 'RiskImpact', help: 'Severity of negative consequence to value, schedule, or cost (1 Low – 3 High).')]
        public string $impact = RiskImpact::MEDIUM,

        #[ListForm('select', 'RiskResponse')]
        public ?string $response = null,

        #[Form('textarea', hideQuick: true, help: 'Treatment plan — or acceptance rationale when Response is Accept.')]
        public ?string $treatment = null,

        #[Form('text', hideQuick: true, help: 'Observable early-warning condition that means this risk is starting (optional).')]
        public ?string $trigger = null,

        #[ListForm('text', help: 'Stakeholder or role accountable for monitoring this risk.')]
        public ?string $owner = null,

        #[ListForm('select', 'RiskStatus')]
        public string $status = RiskStatus::OPEN,

        #[Form('text', hideQuick: true, help: 'Optional origin note (e.g. “Assumption: dealers have Wi-Fi”).')]
        public ?string $source = null,

        #[Form('text', hideQuick: true, help: 'Optional reference to a requirement (e.g. BO-1, BN-2 Improve delivery…).')]
        public ?string $related_to = null,
    ) {
    }

    public static function rules()
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string', Rule::in(RiskCategory::values())],
            'likelihood' => ['required', 'string', Rule::in(RiskLikelihood::values())],
            'impact' => ['required', 'string', Rule::in(RiskImpact::values())],
            'response' => ['nullable', 'string', Rule::in(RiskResponse::values())],
            'treatment' => ['nullable', 'string'],
            'trigger' => ['nullable', 'string', 'max:255'],
            'owner' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in(RiskStatus::values())],
            'source' => ['nullable', 'string', 'max:255'],
            'related_to' => ['nullable', 'string', 'max:255'],
        ];
    }
}
