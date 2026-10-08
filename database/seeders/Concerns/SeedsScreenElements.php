<?php

namespace Database\Seeders\Concerns;

use App\Models\Screen;
use App\Models\ScreenElement;

/**
 * Writes a nested element tree for a screen (docs/design-layer.md).
 *
 * A node is [kind, label, ?row hint, ?children]. Order is the order written; a node
 * with children is a container (panel, columns, column, table).
 */
trait SeedsScreenElements
{
    /**
     * @param  list<array<int, mixed>>  $tree
     */
    protected function seedScreenElements(Screen $screen, array $tree): void
    {
        $screen->screenElements()->each(fn (ScreenElement $element) => $element->forceDelete());

        $position = 0;
        $this->seedScreenLevel($screen, $tree, null, $position);
    }

    /**
     * @param  list<array<int, mixed>>  $nodes
     */
    private function seedScreenLevel(Screen $screen, array $nodes, ?int $parentId, int &$position): void
    {
        foreach ($nodes as $node) {
            [$kind, $label] = $node;

            $element = new ScreenElement([
                'project_id' => $screen->project_id,
                'parent_id' => $parentId,
                'position' => $position++,
                'row' => $node[2] ?? null,
                'kind' => $kind,
                'label' => $label,
            ]);
            $element->screen_id = $screen->id;
            $element->save();

            if (! empty($node[3])) {
                $this->seedScreenLevel($screen, $node[3], (int) $element->id, $position);
            }
        }
    }
}
