<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Contracts\View\View;

trait RespondsWithModal
{
    protected function wantsModalFragment(): bool
    {
        return request()->ajax()
            || request()->header('X-Modal-Request') === '1'
            || request()->wantsJson();
    }

    protected function respondModalOrPage(string $fragmentView, array $data, string $pageView, ?array $pageData = null): View
    {
        if ($this->wantsModalFragment()) {
            return view($fragmentView, $data);
        }

        return view($pageView, $pageData ?? $data);
    }
}
