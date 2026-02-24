<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class ResetPasswordMail extends Mailable implements ShouldQueue
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
            subject: 'Reset your password',
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
        $url = config('app.url').'/reset-password?token='.$this->token;
        $appName = config('app.name');

        return <<<HTML
        <h1>Password Reset</h1>
        <p>You requested a password reset for your {$appName} account.</p>
        <p>Click the link below to reset your password:</p>
        <p><a href="{$url}">{$url}</a></p>
        <p>This link expires in 1 hour.</p>
        <p>If you did not request this, please ignore this email.</p>
        HTML;
    }
}
