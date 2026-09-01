<?php

namespace App\Http\Controllers\Concerns;

use App\Support\ModalUrl;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

trait RespondsWithModal
{
    protected function wantsModalFragment(): bool
    {
        return request()->ajax()
            || request()->header('X-Modal-Request') === '1'
            || request()->wantsJson();
    }

    protected function respondModalOrPage(string $fragmentView, array $data, string $pageView, ?array $pageData = null): View|RedirectResponse
    {
        if ($this->wantsModalFragment()) {
            return view($fragmentView, $data);
        }

        $canonical = ModalUrl::canonicalUrl(request());
        if ($canonical !== null) {
            return redirect()->to($canonical);
        }

        return view($pageView, $pageData ?? $data);
    }
}
