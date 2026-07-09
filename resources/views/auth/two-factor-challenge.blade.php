@extends('auth.layout')

@section('title', 'Two-Factor Challenge - '.config('app.name'))
@section('aside-title', 'Two-factor authentication')
@section('aside-description', 'Enter the code from your authenticator app or use a recovery code.')

@section('content')
    <div class="mb-8">
        <h2 class="text-2xl font-semibold text-foreground mb-2">Confirm your identity</h2>
        <p class="text-secondary-foreground text-sm" id="two-factor-description">
            Enter the authentication code from your authenticator app.
        </p>
    </div>

    @include('auth.partials.alerts')

    <form method="POST" action="{{ route('two-factor.login.store') }}" id="two-factor-form">
        @csrf

        <div id="code-field" class="flex flex-col gap-1 mb-6">
            <label class="text-sm font-medium text-foreground" for="code">Authentication code</label>
            <input
                id="code"
                type="text"
                name="code"
                inputmode="numeric"
                autocomplete="one-time-code"
                class="kt-input w-full"
            />
        </div>

        <div id="recovery-field" class="hidden flex flex-col gap-1 mb-6">
            <label class="text-sm font-medium text-foreground" for="recovery_code">Recovery code</label>
            <input
                id="recovery_code"
                type="text"
                name="recovery_code"
                autocomplete="one-time-code"
                class="kt-input w-full"
            />
        </div>

        <button type="submit" class="kt-btn kt-btn-primary w-full justify-center mb-4">
            Continue
        </button>
    </form>

    <button type="button" id="toggle-recovery" class="text-sm text-primary font-medium hover:underline">
        Use a recovery code
    </button>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggle = document.getElementById('toggle-recovery');
            const codeField = document.getElementById('code-field');
            const recoveryField = document.getElementById('recovery-field');
            const description = document.getElementById('two-factor-description');
            const codeInput = document.getElementById('code');
            const recoveryInput = document.getElementById('recovery_code');
            let usingRecovery = false;

            toggle.addEventListener('click', function () {
                usingRecovery = !usingRecovery;
                codeField.classList.toggle('hidden', usingRecovery);
                recoveryField.classList.toggle('hidden', !usingRecovery);
                description.textContent = usingRecovery
                    ? 'Enter one of your emergency recovery codes.'
                    : 'Enter the authentication code from your authenticator app.';
                toggle.textContent = usingRecovery
                    ? 'Use an authentication code'
                    : 'Use a recovery code';

                if (usingRecovery) {
                    codeInput.removeAttribute('required');
                    recoveryInput.setAttribute('required', 'required');
                    recoveryInput.focus();
                } else {
                    recoveryInput.removeAttribute('required');
                    codeInput.setAttribute('required', 'required');
                    codeInput.focus();
                }
            });

            codeInput.setAttribute('required', 'required');
        });
    </script>
@endpush
