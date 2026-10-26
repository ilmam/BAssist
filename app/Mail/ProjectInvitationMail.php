<?php

namespace App\Mail;

use App\Models\ProjectInvitation;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ProjectInvitationMail extends Mailable
{
    public function __construct(public ProjectInvitation $invitation, public string $url)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('ui.invite_mail_subject', [
            'project' => $this->invitation->project?->name,
            'app' => config('app.name'),
        ]));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.project-invitation');
    }
}
