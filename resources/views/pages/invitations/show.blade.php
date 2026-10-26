@extends('auth.layout')

@section('title', __('ui.invite_page_title', ['project' => $invitation->project?->name]).' - '.config('app.name'))

@section('content')
    <div class="kt-card-content flex flex-col gap-5 p-10">
        <div class="text-center mb-2.5">
            <h3 class="text-lg font-medium text-mono leading-none mb-2.5">{{ __('ui.invite_page_title', ['project' => $invitation->project?->name]) }}</h3>
            <p class="text-sm text-secondary-foreground">
                {{ __('ui.invite_page_intro', ['inviter' => $invitation->inviter?->name, 'access' => $invitation->access->label()]) }}
            </p>
        </div>

        @include('auth.partials.alerts')

        <form method="POST" action="{{ route('invitations.accept', $token) }}" class="flex flex-col gap-5">
            @csrf

            @if ($register)
                <div class="flex flex-col gap-1">
                    <label class="kt-form-label font-normal text-mono" for="email">{{ __('ui.email') }}</label>
                    <input id="email" type="email" value="{{ $invitation->email }}" disabled class="kt-input opacity-70" />
                </div>

                <div class="flex flex-col gap-1">
                    <label class="kt-form-label font-normal text-mono" for="name">{{ __('ui.name') }}</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" class="kt-input" />
                </div>

                <div class="flex flex-col gap-1">
                    <label class="kt-form-label font-normal text-mono" for="password">{{ __('ui.invite_password') }}</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password" class="kt-input" />
                </div>

                <div class="flex flex-col gap-1">
                    <label class="kt-form-label font-normal text-mono" for="password_confirmation">{{ __('ui.invite_password_confirm') }}</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="kt-input" />
                </div>
            @endif

            <button type="submit" class="kt-btn kt-btn-primary flex justify-center grow">
                {{ $register ? __('ui.invite_create_and_join') : __('ui.invite_join') }}
            </button>
        </form>
    </div>
@endsection
