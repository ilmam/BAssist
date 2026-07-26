<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;

class FeatureData extends BaseData
{
    public function __construct(
        public ?int $id = null,

        #[Form('text', readonly: true)]
        public ?string $code = null,

        #[ListForm('text', help: 'List label. Synced from the Feature: line in the document on save when present.')]
        public string $title = '',

        #[Form('select', 'Project')]
        public int $project_id = 0,

        #[Form('select', 'StakeholderNeed', help: 'Saves as @need:{code} at the top of the Feature document. Also links this Feature in the project traceability matrix.')]
        public ?int $stakeholder_need_id = null,

        /**
         * Feature header document: @tags, Feature:, As a / I want / In order to, Background:.
         * Scenarios are separate records.
         */
        #[Form('code', language: 'gherkin', hideQuick: true)]
        public ?string $body = null,

        #[Form('select', 'Priority')]
        public ?int $priority_id = null,

        #[ListForm('select', 'Status', hideQuick: true)]
        public ?int $status_id = null,
    ) {
    }

    public static function rules()
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'stakeholder_need_id' => ['nullable', 'integer', 'exists:stakeholder_needs,id'],
            'body' => ['nullable', 'string'],
            'priority_id' => ['nullable', 'integer', 'exists:priorities,id'],
            'status_id' => ['nullable', 'integer', 'exists:statuses,id'],
        ];
    }
}
