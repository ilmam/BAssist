<span class="text-gray-700 fw-semibold d-none d-lg-inline">{{ config('app.name') }}</span>

@auth
    <div class="d-flex align-items-center gap-3">
        <span class="text-gray-700 fw-semibold d-none d-sm-inline">{{ auth()->user()->name }}</span>
        <a href="{{ route('profile.api-tokens.index') }}" class="btn btn-sm btn-light">{{ __('ui.api_tokens') }}</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-sm btn-light-primary">Sign out</button>
        </form>
    </div>
@else
    <a href="{{ route('login') }}" class="btn btn-sm btn-primary">Sign in</a>
@endauth
