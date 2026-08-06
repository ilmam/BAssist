@extends('auth.layout')

@section('title', 'Sign Up - '.config('app.name'))

@section('content')
    <div class="kt-card-content flex flex-col gap-5 p-10">
        <div class="text-center mb-2.5">
            <h3 class="text-lg font-medium text-mono leading-none mb-2.5">Sign up</h3>
            <p class="text-sm text-secondary-foreground">
                Fill in your details to create a new account.
            </p>
        </div>

        @include('auth.partials.alerts')

        <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-5">
            @csrf

            <div class="flex flex-col gap-1">
                <label class="kt-form-label font-normal text-mono" for="name">Name</label>
                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    autofocus
                    autocomplete="name"
                    class="kt-input"
                />
            </div>

            <div class="flex flex-col gap-1">
                <label class="kt-form-label font-normal text-mono" for="email">Email</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autocomplete="username"
                    class="kt-input"
                />
            </div>

            <div class="flex flex-col gap-1">
                <label class="kt-form-label font-normal text-mono" for="password">Password</label>
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
                Create account
            </button>
        </form>

        <p class="text-sm text-secondary-foreground text-center">
            Already have an account?
            <a href="{{ route('login') }}" class="text-sm kt-link">Sign in</a>
        </p>
    </div>
@endsection
