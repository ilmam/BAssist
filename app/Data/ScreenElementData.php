<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;

class ScreenElementData extends BaseData
{
    public function __construct(
        public ?int $id = null,

        #[Form('select', 'Screen', hideQuick: true)]
        public int $screen_id = 0,

        #[Form('select', 'ScreenElement', help: 'The container (panel, columns, column, table) this element sits in; empty = top level.', hideQuick: true)]
        public ?int $parent_id = null,

        #[Form('select', 'Project', hideQuick: true)]
        public int $project_id = 0,

        #[Form('number', help: 'Order within the screen.')]
        public int $position = 0,

        #[Form('number', help: 'Placement hint: elements with the same row value share one line.', hideQuick: true)]
        public ?int $row = null,

        #[Form('select', 'ScreenElementKind')]
        #[ListForm('select', 'ScreenElementKind')]
        public string $kind = '',

        #[ListForm('text')]
        public string $label = '',

        #[Form('select', 'FunctionalRequirement', help: 'Only for an element that maps to its own requirement (e.g. a field tied to a validation rule).', hideQuick: true, section: 'traceability')]
        public ?int $functional_requirement_id = null,
    ) {
    }
}
