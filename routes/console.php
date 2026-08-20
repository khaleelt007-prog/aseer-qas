<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mail:test {recipient : Email address that should receive the test message}', function (string $recipient) {
    if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
        $this->error('Invalid recipient email address.');

        return Command::FAILURE;
    }

    Mail::raw('This email confirms that the Aseer QAS SMTP configuration is working.', function (Message $message) use ($recipient) {
        $message->to($recipient)->subject('Aseer QAS SMTP Test');
    });

    $this->info("SMTP test email sent to {$recipient}.");

    return Command::SUCCESS;
})->purpose('Send an email to verify the SMTP configuration');

Schedule::command('quality-evaluations:process-warnings')->hourly();
