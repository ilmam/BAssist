@extends('auth.layout')

@section('title', 'Verify Email - '.config('app.name'))
@section('aside-title', 'Verify your email')
@section('aside-description', 'Check your inbox for a verification link before continuing.')

@section('content')
    <div class="mb-8">
        <h2 class="text-2xl font-semibold text-foreground mb-2">Verify your email</h2>
        <p class="text-secondary-foreground text-sm">
            Thanks for signing up. Before getting started, please verify your email address by clicking the link we sent you.
            If you did not receive the email, we can send another.
        </p>
    </div>

    @include('auth.partials.alerts')

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf

        <button type="submit" class="kt-btn kt-btn-primary w-full justify-center">
            Resend verification email
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4">
        @csrf

        <button type="submit" class="kt-btn kt-btn-outline w-full justify-center">
            Sign out
        </button>
    </form>
@endsection
