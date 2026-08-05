<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;
use App\Services\SwimlaneMermaidGenerator;
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

        /** @var list<array{id?: int|null, lane?: string, from?: string|null, type?: string, label?: string, line_title?: string|null, code?: string|null, stakeholder_need_id?: int|null}> */
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
            'elements.*.id' => ['nullable', 'integer', 'min:1'],
            'elements.*.lane' => ['nullable', 'string', 'max:255'],
            'elements.*.from' => ['nullable', 'string', 'max:255'],
            'elements.*.type' => ['nullable', 'string', Rule::in([...SwimlaneMermaidGenerator::TYPES, ''])],
            'elements.*.label' => ['nullable', 'string', 'max:255'],
            'elements.*.line_title' => ['nullable', 'string', 'max:255'],
            'elements.*.code' => ['nullable', 'string', 'max:32'],
            'elements.*.stakeholder_need_id' => ['nullable', 'integer', 'exists:stakeholder_needs,id'],
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
                    $code = trim((string) ($value['code'] ?? ''));
                    $needId = trim((string) ($value['stakeholder_need_id'] ?? ''));

                    // Blank editor rows default type to "process"; ignore those until Lane/Label are filled.
                    if ($lane === '' && $from === '' && $label === '' && $lineTitle === '' && $code === '' && $needId === '') {
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
