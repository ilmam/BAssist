@extends('auth.layout')

@section('title', 'Reset Password - '.config('app.name'))
@section('aside-title', 'Choose a new password')
@section('aside-description', 'Enter and confirm your new password to regain access.')

@section('content')
    <div class="mb-8">
        <h2 class="text-2xl font-semibold text-foreground mb-2">Reset password</h2>
        <p class="text-secondary-foreground text-sm">
            Choose a strong password for your account.
        </p>
    </div>

    @include('auth.partials.alerts')

    <form method="POST" action="{{ route('password.update') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}" />

        <div class="flex flex-col gap-1 mb-5">
            <label class="text-sm font-medium text-foreground" for="email">Email</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email', $request->email) }}"
                required
                autofocus
                autocomplete="username"
                class="kt-input w-full"
            />
        </div>

        <div class="flex flex-col gap-1 mb-5">
            <label class="text-sm font-medium text-foreground" for="password">New password</label>
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
            Reset password
        </button>
    </form>
@endsection
