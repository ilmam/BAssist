@extends('auth.layout')

@section('title', 'Sign In - '.config('app.name'))
@section('aside-title', 'Welcome back')
@section('aside-description', 'Use your account credentials to access the application.')

@section('content')
    <div class="mb-8">
        <h2 class="text-2xl font-semibold text-foreground mb-2">Sign in</h2>
        <p class="text-secondary-foreground text-sm">
            Enter your email and password to continue.
        </p>
    </div>

    @include('auth.partials.alerts')

    <form method="POST" action="{{ route('login') }}" class="space-y-1">
        @csrf

        <div class="flex flex-col gap-1 mb-5">
            <label class="text-sm font-medium text-foreground" for="email">Email</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                class="kt-input w-full"
            />
        </div>

        <div class="flex flex-col gap-1 mb-5">
            <div class="flex items-center justify-between gap-2">
                <label class="text-sm font-medium text-foreground" for="password">Password</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-sm text-primary hover:underline">
                        Forgot password?
                    </a>
                @endif
            </div>
            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                class="kt-input w-full"
            />
        </div>

        <label class="flex items-center gap-2.5 mb-6 cursor-pointer">
            <input type="checkbox" name="remember" class="kt-checkbox" {{ old('remember') ? 'checked' : '' }} />
            <span class="text-sm text-foreground">Remember me</span>
        </label>

        <button type="submit" class="kt-btn kt-btn-primary w-full justify-center">
            Sign in
        </button>
    </form>

    @if (Route::has('register'))
        <p class="text-sm text-secondary-foreground text-center mt-6">
            Don't have an account?
            <a href="{{ route('register') }}" class="text-primary font-medium hover:underline">Create one</a>
        </p>
    @endif
@endsection
