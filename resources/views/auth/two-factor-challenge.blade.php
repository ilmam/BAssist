@extends('auth.layout')

@section('title', 'Two-Factor Challenge - '.config('app.name'))

@section('content')
    <div class="kt-card-content flex flex-col gap-5 p-10">
        <div class="text-center mb-2.5">
            <h3 class="text-lg font-medium text-mono leading-none mb-2.5">Confirm your identity</h3>
            <p class="text-sm text-secondary-foreground" id="two-factor-description">
                Enter the authentication code from your authenticator app.
            </p>
        </div>

        @include('auth.partials.alerts')

        <form method="POST" action="{{ route('two-factor.login.store') }}" id="two-factor-form" class="flex flex-col gap-5">
            @csrf

            <div id="code-field" class="flex flex-col gap-1">
                <label class="kt-form-label font-normal text-mono" for="code">Authentication code</label>
                <input
                    id="code"
                    type="text"
                    name="code"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    class="kt-input"
                />
            </div>

            <div id="recovery-field" class="hidden flex flex-col gap-1">
                <label class="kt-form-label font-normal text-mono" for="recovery_code">Recovery code</label>
                <input
                    id="recovery_code"
                    type="text"
                    name="recovery_code"
                    autocomplete="one-time-code"
                    class="kt-input"
                />
            </div>

            <button type="submit" class="kt-btn kt-btn-primary flex justify-center grow">
                Continue
            </button>
        </form>

        <button type="button" id="toggle-recovery" class="text-sm kt-link text-center">
            Use a recovery code
        </button>
    </div>
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
