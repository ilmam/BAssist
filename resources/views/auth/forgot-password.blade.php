@extends('auth.layout')

@section('title', 'Forgot Password - '.config('app.name'))
@section('aside-title', 'Reset your password')
@section('aside-description', 'We will send you a secure link to choose a new password.')

@section('content')
    <div class="mb-8">
        <h2 class="text-2xl font-semibold text-foreground mb-2">Forgot password?</h2>
        <p class="text-secondary-foreground text-sm">
            Enter your email address and we will email you a reset link.
        </p>
    </div>

    @include('auth.partials.alerts')

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="flex flex-col gap-1 mb-6">
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

        <button type="submit" class="kt-btn kt-btn-primary w-full justify-center">
            Email reset link
        </button>
    </form>

    <p class="text-sm text-secondary-foreground text-center mt-6">
        <a href="{{ route('login') }}" class="text-primary font-medium hover:underline">Back to sign in</a>
    </p>
@endsection
