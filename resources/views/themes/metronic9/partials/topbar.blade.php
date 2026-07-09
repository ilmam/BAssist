<span class="text-sm text-secondary-foreground font-medium hidden md:inline">
    {{ config('app.name') }}
</span>

@auth
    <div class="flex items-center gap-3">
        <span class="text-sm text-foreground hidden sm:inline">{{ auth()->user()->name }}</span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="kt-btn kt-btn-sm kt-btn-outline">
                Sign out
            </button>
        </form>
    </div>
@else
    <a href="{{ route('login') }}" class="kt-btn kt-btn-sm kt-btn-primary">
        Sign in
    </a>
@endauth
