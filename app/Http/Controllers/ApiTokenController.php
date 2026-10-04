<?php

namespace App\Http\Controllers;

use App\Support\ApiTokenAbility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Lets a signed-in user create, see and revoke their own personal API tokens.
 * A token acts as its owner: same tenant, same role permissions.
 */
class ApiTokenController extends Controller
{
    public function index(Request $request): View
    {
        return view('pages.profile.api-tokens', [
            'tokens' => $request->user()->tokens()->latest()->get(),
            'expiryOptions' => ApiTokenAbility::EXPIRY_DAYS,
            'defaultExpiry' => ApiTokenAbility::DEFAULT_EXPIRY_DAYS,
            'newToken' => $request->session()->get('new_token'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'write' => ['nullable', 'boolean'],
            'expires_in' => ['required', 'integer', Rule::in(ApiTokenAbility::EXPIRY_DAYS)],
        ]);

        $abilities = [ApiTokenAbility::READ];
        if ($request->boolean('write')) {
            $abilities[] = ApiTokenAbility::WRITE;
        }

        $token = $request->user()->createToken(
            $validated['name'],
            $abilities,
            now()->addDays((int) $validated['expires_in']),
        );

        return redirect()
            ->route('profile.api-tokens.index')
            ->with('new_token', $token->plainTextToken)
            ->with('status', __('ui.api_token_created'));
    }

    public function destroy(Request $request, int $token): RedirectResponse
    {
        // Scoped to the signed-in user's own tokens: anyone else's id is a 404.
        $request->user()->tokens()->whereKey($token)->firstOrFail()->delete();

        return redirect()
            ->route('profile.api-tokens.index')
            ->with('status', __('ui.api_token_revoked'));
    }
}
