@extends('auth.layout')

@section('title', __('ui.invite_gone_title').' - '.config('app.name'))

@section('content')
    <div class="kt-card-content flex flex-col gap-5 p-10 text-center">
        <h3 class="text-lg font-medium text-mono leading-none">{{ __('ui.invite_gone_title') }}</h3>
        <p class="text-sm text-secondary-foreground">{{ __('ui.invite_gone_body') }}</p>
    </div>
@endsection
