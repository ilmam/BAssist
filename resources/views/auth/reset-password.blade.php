@extends('auth.layout')

@section('title', 'Reset Password - '.config('app.name'))

@section('content')
    <div class="kt-card-content flex flex-col gap-5 p-10">
        <div class="text-center mb-2.5">
            <h3 class="text-lg font-medium text-mono leading-none mb-2.5">Reset password</h3>
            <p class="text-sm text-secondary-foreground">
                Choose a strong password for your account.
            </p>
        </div>

        @include('auth.partials.alerts')

        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-5">
            @csrf

            <input type="hidden" name="token" value="{{ $request->route('token') }}" />

            <div class="flex flex-col gap-1">
                <label class="kt-form-label font-normal text-mono" for="email">Email</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email', $request->email) }}"
                    required
                    autofocus
                    autocomplete="username"
                    class="kt-input"
                />
            </div>

            <div class="flex flex-col gap-1">
                <label class="kt-form-label font-normal text-mono" for="password">New password</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    class="kt-input"
                />
            </div>

            <div class="flex flex-col gap-1">
                <label class="kt-form-label font-normal text-mono" for="password_confirmation">Confirm password</label>
                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    class="kt-input"
                />
            </div>

            <button type="submit" class="kt-btn kt-btn-primary flex justify-center grow">
                Reset password
            </button>
        </form>
    </div>
@endsection
