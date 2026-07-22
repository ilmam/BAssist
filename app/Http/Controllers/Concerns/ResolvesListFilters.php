<?php

namespace App\Http\Controllers\Concerns;

use App\Support\WorkspaceContext;
use Illuminate\Http\Request;

trait ResolvesListFilters
{
    /**
     * @return array<string, mixed>
     */
    protected function resolveListFilters(Request $request): array
    {
        $allowed = method_exists($this->modelRepository, 'allowedListFilters')
            ? $this->modelRepository->allowedListFilters()
            : [];

        if ($allowed === []) {
            return [];
        }

        $filters = $request->only($allowed);

        return app(WorkspaceContext::class)->mergeIntoFilters($filters, $this->modelRepository);
    }

    /**
     * @return list<string>
     */
    protected function allowedListFilters(): array
    {
        if (! method_exists($this->modelRepository, 'allowedListFilters')) {
            return [];
        }

        return $this->modelRepository->allowedListFilters();
    }
}
