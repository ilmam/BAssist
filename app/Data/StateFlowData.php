<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;
use Spatie\LaravelData\Attributes\Validation\Max;

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

        #[Max(255)]
        public ?string $initial_state = null,

        #[Max(1000)]
        public ?string $final_states = null,

        #[ListForm('select', 'Status', hideQuick: true)]
        public ?int $status_id = null,
    ) {
    }

    public static function rules()
    {
        return [
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

                    // Blank placeholder row.
                    if ($from === '' && $to === '' && $trigger === '') {
                        return;
                    }

                    // Empty / * / start / end endpoints are allowed — they map to Mermaid [*].
                    // Only reject a trigger with no endpoints at all.
                    if ($from === '' && $to === '') {
                        $fail('Each transition needs a From and/or To state (use start, end, or * for terminals).');
                    }
                },
            ],
        ];
    }
}
