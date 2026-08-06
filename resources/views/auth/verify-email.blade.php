@extends('auth.layout')

@section('title', 'Verify Email - '.config('app.name'))

@section('content')
    <div class="kt-card-content flex flex-col gap-5 p-10">
        <div class="text-center mb-2.5">
            <h3 class="text-lg font-medium text-mono leading-none mb-2.5">Verify your email</h3>
            <p class="text-sm text-secondary-foreground">
                Thanks for signing up. Before getting started, please verify your email address by clicking the link we sent you.
                If you did not receive the email, we can send another.
            </p>
        </div>

        @include('auth.partials.alerts')

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <button type="submit" class="kt-btn kt-btn-primary flex justify-center grow w-full">
                Resend verification email
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="kt-btn kt-btn-outline flex justify-center grow w-full">
                Sign out
            </button>
        </form>
    </div>
@endsection
