<?php

namespace App\Mail;

use App\Models\QualityEvaluation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QcReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public QualityEvaluation $evaluation,
        private readonly string $pdfPath,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "QC Report - {$this->evaluation->branch?->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.qc-report',
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as("QC_Report_{$this->evaluation->id}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
