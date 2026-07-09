@extends('auth.layout')

@section('title', 'Confirm Password - '.config('app.name'))
@section('aside-title', 'Confirm your password')
@section('aside-description', 'For your security, please confirm your password before continuing.')

@section('content')
    <div class="mb-8">
        <h2 class="text-2xl font-semibold text-foreground mb-2">Password confirmation</h2>
        <p class="text-secondary-foreground text-sm">
            This is a secure area. Please confirm your password to proceed.
        </p>
    </div>

    @include('auth.partials.alerts')

    <form method="POST" action="{{ route('password.confirm.store') }}">
        @csrf

        <div class="flex flex-col gap-1 mb-6">
            <label class="text-sm font-medium text-foreground" for="password">Password</label>
            <input
                id="password"
                type="password"
                name="password"
                required
                autofocus
                autocomplete="current-password"
                class="kt-input w-full"
            />
        </div>

        <button type="submit" class="kt-btn kt-btn-primary w-full justify-center">
            Confirm
        </button>
    </form>
@endsection
