<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;

class ScenarioData extends BaseData
{
    public function __construct(
        public ?int $id = null,

        #[ListForm('text', help: 'List label. Synced from the Scenario: / Scenario Outline: line on save when present.')]
        public string $title = '',

        #[Form('select', 'Feature', section: 'traceability')]
        public int $feature_id = 0,

        /**
         * Entire scenario block as in a .feature file: @tags, Scenario:/Scenario Outline:,
         * steps, and Examples:.
         */
        #[Form('code', language: 'gherkin', hideQuick: true)]
        public ?string $body = null,

        /** Derived from body on save when Scenario Outline / Examples: is present; kept for list filters. */
        #[Form('checkbox', hideQuick: true, help: 'Usually derived from the document (Scenario Outline / Examples:). You can set it manually if needed.')]
        public bool $is_outline = false,

        #[ListForm('select', 'Status', hideQuick: true)]
        public ?int $status_id = null,
    ) {
    }

    public static function rules()
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'feature_id' => ['required', 'integer', 'exists:features,id'],
            'body' => ['nullable', 'string'],
            'is_outline' => ['sometimes', 'boolean'],
            'status_id' => ['nullable', 'integer', 'exists:statuses,id'],
        ];
    }
}
