<?php

declare(strict_types=1);

namespace App\Infrastructure\Organization\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class OrganizationInviteMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $token,
        public readonly string $organizationName,
    ) {
        $this->onQueue('notifications');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You've been invited to join {$this->organizationName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml(),
        );
    }

    private function buildHtml(): string
    {
        $url = config('app.url').'/invites/accept?token='.$this->token;
        $appName = config('app.name');

        return <<<HTML
        <h1>Organization Invite</h1>
        <p>You've been invited to join <strong>{$this->organizationName}</strong> on {$appName}.</p>
        <p>Click the link below to accept the invitation:</p>
        <p><a href="{$url}">{$url}</a></p>
        <p>If you did not expect this invitation, please ignore this email.</p>
        HTML;
    }
}
