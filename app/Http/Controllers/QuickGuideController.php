<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithModal;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class QuickGuideController extends Controller
{
    use RespondsWithModal;

    public function show(): View|RedirectResponse
    {
        if (! $this->wantsModalFragment()) {
            return redirect('/');
        }

        return view('pages.help.quick-guide', [
            'title' => __('ui.quick_guide'),
        ]);
    }
}
