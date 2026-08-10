<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithModal;
use App\Support\HelpRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class HelpController extends Controller
{
    use RespondsWithModal;

    public function show(string $helpKey): View|Response|RedirectResponse
    {
        if (! $this->wantsModalFragment()) {
            return redirect('/');
        }

        $guide = HelpRegistry::load($helpKey);

        if ($guide === null) {
            abort(404);
        }

        return view('pages.help.drawer', $guide);
    }
}
