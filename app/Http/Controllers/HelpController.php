<?php

namespace App\Http\Controllers;

use App\Support\HelpRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

class HelpController extends Controller
{
    public function show(string $helpKey): View|Response
    {
        $guide = HelpRegistry::load($helpKey);

        if ($guide === null) {
            abort(404);
        }

        return view('pages.help.drawer', $guide);
    }
}
