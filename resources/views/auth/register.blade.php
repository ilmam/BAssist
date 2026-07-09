@extends('auth.layout')

@section('title', 'Sign Up - '.config('app.name'))
@section('aside-title', 'Create your account')
@section('aside-description', 'Register to start using the application and manage your data.')

@section('content')
    <div class="mb-8">
        <h2 class="text-2xl font-semibold text-foreground mb-2">Sign up</h2>
        <p class="text-secondary-foreground text-sm">
            Fill in your details to create a new account.
        </p>
    </div>

    @include('auth.partials.alerts')

    <form method="POST" action="{{ route('register') }}" class="space-y-1">
        @csrf

        <div class="flex flex-col gap-1 mb-5">
            <label class="text-sm font-medium text-foreground" for="name">Name</label>
            <input
                id="name"
                type="text"
                name="name"
                value="{{ old('name') }}"
                required
                autofocus
                autocomplete="name"
                class="kt-input w-full"
            />
        </div>

        <div class="flex flex-col gap-1 mb-5">
            <label class="text-sm font-medium text-foreground" for="email">Email</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autocomplete="username"
                class="kt-input w-full"
            />
        </div>

        <div class="flex flex-col gap-1 mb-5">
            <label class="text-sm font-medium text-foreground" for="password">Password</label>
            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="new-password"
                class="kt-input w-full"
            />
        </div>

        <div class="flex flex-col gap-1 mb-6">
            <label class="text-sm font-medium text-foreground" for="password_confirmation">Confirm password</label>
            <input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                class="kt-input w-full"
            />
        </div>

        <button type="submit" class="kt-btn kt-btn-primary w-full justify-center">
            Create account
        </button>
    </form>

    <p class="text-sm text-secondary-foreground text-center mt-6">
        Already have an account?
        <a href="{{ route('login') }}" class="text-primary font-medium hover:underline">Sign in</a>
    </p>
@endsection
