<p>{{ __('ui.invite_mail_intro', ['inviter' => $invitation->inviter?->name, 'project' => $invitation->project?->name, 'app' => config('app.name')]) }}</p>

<p>{{ __('ui.invite_mail_access', ['access' => $invitation->access->label()]) }}</p>

<p><a href="{{ $url }}">{{ __('ui.invite_mail_button') }}</a></p>

<p>{{ __('ui.invite_mail_expires', ['date' => $invitation->expires_at->toFormattedDateString()]) }}</p>

<p style="color: #6b7280; font-size: 12px;">{{ $url }}</p>
