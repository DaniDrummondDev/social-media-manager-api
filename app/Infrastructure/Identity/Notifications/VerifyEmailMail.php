<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class VerifyEmailMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $token,
    ) {
        $this->onQueue('notifications');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify your email address',
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
        $url = config('app.url').'/verify-email?token='.$this->token;
        $appName = config('app.name');

        return <<<HTML
        <h1>Email Verification</h1>
        <p>Thank you for registering with {$appName}.</p>
        <p>Click the link below to verify your email address:</p>
        <p><a href="{$url}">{$url}</a></p>
        <p>This link expires in 24 hours.</p>
        HTML;
    }
}
