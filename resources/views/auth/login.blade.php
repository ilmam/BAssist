@extends('auth.layout')

@section('title', 'Sign In - '.config('app.name'))

@section('content')
    <form method="POST" action="{{ route('login') }}" class="kt-card-content flex flex-col gap-5 p-10" id="sign_in_form">
        @csrf

        <div class="text-center mb-2.5">
            <h3 class="text-lg font-medium text-mono leading-none mb-2.5">
                Sign in
            </h3>
        </div>

        @include('auth.partials.alerts')

        <div class="flex flex-col gap-1">
            <label class="kt-form-label font-normal text-mono" for="email">
                Email
            </label>
            <input
                id="email"
                class="kt-input"
                type="email"
                name="email"
                value="{{ old('email') }}"
                placeholder="email@email.com"
                required
                autofocus
                autocomplete="username"
            />
        </div>

        <div class="flex flex-col gap-1">
            <div class="flex items-center justify-between gap-1">
                <label class="kt-form-label font-normal text-mono" for="password">
                    Password
                </label>
                @if (Route::has('password.request'))
                    <a class="text-sm kt-link shrink-0" href="{{ route('password.request') }}">
                        Forgot Password?
                    </a>
                @endif
            </div>
            <div class="kt-input" data-kt-toggle-password="true">
                <input
                    id="password"
                    name="password"
                    placeholder="Enter Password"
                    type="password"
                    required
                    autocomplete="current-password"
                />
                <button class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon bg-transparent! -me-1.5" data-kt-toggle-password-trigger="true" type="button">
                    <span class="kt-toggle-password-active:hidden">
                        <i class="ki-filled ki-eye text-muted-foreground"></i>
                    </span>
                    <span class="hidden kt-toggle-password-active:block">
                        <i class="ki-filled ki-eye-slash text-muted-foreground"></i>
                    </span>
                </button>
            </div>
        </div>

        <label class="kt-label">
            <input class="kt-checkbox kt-checkbox-sm" name="remember" type="checkbox" value="1" {{ old('remember') ? 'checked' : '' }}>
            Remember me
        </label>

        <button type="submit" class="kt-btn kt-btn-primary flex justify-center grow">
            Sign In
        </button>
    </form>
@endsection
