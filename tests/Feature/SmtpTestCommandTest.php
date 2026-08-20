<?php

namespace Tests\Feature;

use Closure;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class SmtpTestCommandTest extends TestCase
{
    public function test_it_sends_an_smtp_test_email_to_the_recipient(): void
    {
        Mail::shouldReceive('raw')
            ->once()
            ->withArgs(function (string $body, Closure $configure): bool {
                $email = new Email;
                $configure(new Message($email));

                return str_contains($body, 'SMTP configuration is working')
                    && $email->getSubject() === 'Aseer QAS SMTP Test'
                    && $email->getTo()[0]->getAddress() === 'recipient@example.com';
            });

        $this->artisan('mail:test', ['recipient' => 'recipient@example.com'])
            ->expectsOutput('SMTP test email sent to recipient@example.com.')
            ->assertSuccessful();
    }

    public function test_it_rejects_an_invalid_recipient(): void
    {
        Mail::shouldReceive('raw')->never();

        $this->artisan('mail:test', ['recipient' => 'not-an-email'])
            ->expectsOutput('Invalid recipient email address.')
            ->assertFailed();
    }
}
