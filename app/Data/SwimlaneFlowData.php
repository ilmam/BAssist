<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;
use Illuminate\Validation\Rule;

class SwimlaneFlowData extends BaseData
{
    public function __construct(
        public ?int $id = null,

        #[ListForm('text')]
        public string $title = '',

        #[Form('select', 'Project')]
        public int $project_id = 0,

        #[Form('textarea', hideQuick: true)]
        public ?string $description = null,

        public string $direction = 'TB',

        /** @var list<array{lane?: string, from?: string|null, type?: string, label?: string, line_title?: string|null}> */
        public array $elements = [],

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
            'direction' => ['nullable', 'string', Rule::in(['TB', 'LR', 'tb', 'lr'])],
            'status_id' => ['nullable', 'integer', 'exists:statuses,id'],
            'elements' => ['nullable', 'array'],
            'elements.*.lane' => ['nullable', 'string', 'max:255'],
            'elements.*.from' => ['nullable', 'string', 'max:255'],
            'elements.*.type' => ['nullable', 'string', Rule::in(['start', 'process', 'decision', 'end', ''])],
            'elements.*.label' => ['nullable', 'string', 'max:255'],
            'elements.*.line_title' => ['nullable', 'string', 'max:255'],
            'elements.*' => [
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_array($value)) {
                        return;
                    }

                    $lane = trim((string) ($value['lane'] ?? ''));
                    $from = trim((string) ($value['from'] ?? ''));
                    $type = trim((string) ($value['type'] ?? ''));
                    $label = trim((string) ($value['label'] ?? ''));
                    $lineTitle = trim((string) ($value['line_title'] ?? ''));

                    if ($lane === '' && $from === '' && $type === '' && $label === '' && $lineTitle === '') {
                        return;
                    }

                    if ($lane === '' || $label === '' || $type === '') {
                        $fail('Each element row needs Lane, Type, and Label.');
                    }
                },
            ],
        ];
    }
}
