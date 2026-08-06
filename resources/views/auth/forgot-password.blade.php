@extends('auth.layout')

@section('title', 'Forgot Password - '.config('app.name'))

@section('content')
    <div class="kt-card-content flex flex-col gap-5 p-10">
        <div class="text-center mb-2.5">
            <h3 class="text-lg font-medium text-mono leading-none mb-2.5">Forgot password?</h3>
            <p class="text-sm text-secondary-foreground">
                Enter your email address and we will email you a reset link.
            </p>
        </div>

        @include('auth.partials.alerts')

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-5">
            @csrf

            <div class="flex flex-col gap-1">
                <label class="kt-form-label font-normal text-mono" for="email">Email</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="username"
                    class="kt-input"
                />
            </div>

            <button type="submit" class="kt-btn kt-btn-primary flex justify-center grow">
                Email reset link
            </button>
        </form>

        <p class="text-sm text-secondary-foreground text-center">
            <a href="{{ route('login') }}" class="text-sm kt-link">Back to sign in</a>
        </p>
    </div>
@endsection
