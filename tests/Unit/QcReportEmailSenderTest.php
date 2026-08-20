<?php

namespace Tests\Unit;

use App\Jobs\SendQcReportEmail;
use App\Mail\QcReportMail;
use App\Models\Branch;
use App\Models\Company;
use App\Models\QcReportEmailLog;
use App\Models\QcReportEmailSetting;
use App\Models\QualityEvaluation;
use App\Services\QcReportEmailLogger;
use App\Services\QcReportEmailSender;
use Illuminate\Mail\Attachment;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class QcReportEmailSenderTest extends TestCase
{
    private array $pdfPaths = [];

    protected function tearDown(): void
    {
        foreach ($this->pdfPaths as $path) {
            File::delete($path);
        }

        parent::tearDown();
    }

    public function test_it_sends_completed_report_to_active_company_recipients(): void
    {
        Mail::fake();
        [$evaluation, $pdfPath] = $this->makeEvaluation(isActive: true);

        $logger = Mockery::mock(QcReportEmailLogger::class);
        $logger->shouldReceive('record')
            ->once()
            ->with($evaluation, Mockery::type(QcReportEmailSetting::class))
            ->andReturn(new QcReportEmailLog);

        $sent = (new QcReportEmailSender($logger))->send($evaluation);

        $this->assertTrue($sent);
        Mail::assertSent(QcReportMail::class, function (QcReportMail $mail) use ($evaluation, $pdfPath): bool {
            $mail->assertHasTo('owner@example.com');
            $mail->assertHasCc('manager@example.com');
            $mail->assertHasAttachment(
                Attachment::fromPath($pdfPath)
                    ->as("QC_Report_{$evaluation->id}.pdf")
                    ->withMime('application/pdf'),
            );

            return true;
        });
    }

    public function test_it_skips_report_when_company_setting_is_inactive(): void
    {
        Mail::fake();
        [$evaluation] = $this->makeEvaluation(isActive: false);

        $logger = Mockery::mock(QcReportEmailLogger::class);
        $logger->shouldNotReceive('record');

        $sent = (new QcReportEmailSender($logger))->send($evaluation);

        $this->assertFalse($sent);
        Mail::assertNothingSent();
    }

    public function test_it_skips_draft_evaluations(): void
    {
        Mail::fake();
        [$evaluation] = $this->makeEvaluation(isActive: true, status: 'draft');

        $logger = Mockery::mock(QcReportEmailLogger::class);
        $logger->shouldNotReceive('record');

        $sent = (new QcReportEmailSender($logger))->send($evaluation);

        $this->assertFalse($sent);
        Mail::assertNothingSent();
    }

    public function test_job_uses_the_qc_emails_queue(): void
    {
        $this->assertSame('qc-emails', (new SendQcReportEmail(123))->queue);
    }

    private function makeEvaluation(bool $isActive, string $status = 'completed'): array
    {
        $setting = new QcReportEmailSetting([
            'to_emails' => ['owner@example.com'],
            'cc_emails' => ['manager@example.com'],
            'is_active' => $isActive,
        ]);

        $company = (new Company(['name' => 'Franchise Company']))->setRelation(
            'qcReportEmailSetting',
            $setting,
        );

        $branch = (new Branch([
            'name' => 'Test Branch',
            'name_ar' => 'فرع الاختبار',
        ]))->setRelation('company', $company);

        $evaluation = (new QualityEvaluation([
            'title' => 'QC Evaluation',
            'total_score' => 95,
            'status' => $status,
            'completed_at' => $status === 'completed' ? now() : null,
            'pdf_filename' => 'qc-report-test-'.uniqid().'.pdf',
        ]))->setRelation('branch', $branch);
        $evaluation->id = 123;

        $pdfPath = storage_path('app/public/pdfs/'.$evaluation->pdf_filename);
        File::ensureDirectoryExists(dirname($pdfPath));
        File::put($pdfPath, '%PDF-1.4 test');
        $this->pdfPaths[] = $pdfPath;

        return [$evaluation, $pdfPath];
    }
}
