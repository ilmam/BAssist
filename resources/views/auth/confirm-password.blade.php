@extends('auth.layout')

@section('title', 'Confirm Password - '.config('app.name'))

@section('content')
    <div class="kt-card-content flex flex-col gap-5 p-10">
        <div class="text-center mb-2.5">
            <h3 class="text-lg font-medium text-mono leading-none mb-2.5">Password confirmation</h3>
            <p class="text-sm text-secondary-foreground">
                This is a secure area. Please confirm your password to proceed.
            </p>
        </div>

        @include('auth.partials.alerts')

        <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-5">
            @csrf

            <div class="flex flex-col gap-1">
                <label class="kt-form-label font-normal text-mono" for="password">Password</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autofocus
                    autocomplete="current-password"
                    class="kt-input"
                />
            </div>

            <button type="submit" class="kt-btn kt-btn-primary flex justify-center grow">
                Confirm
            </button>
        </form>
    </div>
@endsection
