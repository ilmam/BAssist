<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;

class StateFlowData extends BaseData
{
    public function __construct(
        public ?int $id = null,

        #[ListForm('text')]
        public string $title = '',

        #[Form('select', 'Project', hideQuick: true)]
        public int $project_id = 0,

        #[Form('textarea', hideQuick: true)]
        public ?string $description = null,

        /** @var list<array{from?: string, to?: string, trigger?: string|null}> */
        public array $transitions = [],

        public ?string $initial_state = null,

        public ?string $final_states = null,

        #[ListForm('select', 'Status', hideQuick: true)]
        public ?int $status_id = null,
    ) {
    }

    public static function rules()
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'description' => ['nullable', 'string'],
            'status_id' => ['nullable', 'integer', 'exists:statuses,id'],
            'initial_state' => ['nullable', 'string', 'max:255'],
            'final_states' => ['nullable', 'string', 'max:1000'],
            'transitions' => ['nullable', 'array'],
            'transitions.*.from' => ['nullable', 'string', 'max:255'],
            'transitions.*.to' => ['nullable', 'string', 'max:255'],
            'transitions.*.trigger' => ['nullable', 'string', 'max:255'],
            'transitions.*' => [
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_array($value)) {
                        return;
                    }

                    $from = trim((string) ($value['from'] ?? ''));
                    $to = trim((string) ($value['to'] ?? ''));
                    $trigger = trim((string) ($value['trigger'] ?? ''));

                    if ($from === '' && $to === '' && $trigger === '') {
                        return;
                    }

                    if ($from === '' || $to === '') {
                        $fail('Each transition row needs both From and To.');
                    }
                },
            ],
        ];
    }
}
