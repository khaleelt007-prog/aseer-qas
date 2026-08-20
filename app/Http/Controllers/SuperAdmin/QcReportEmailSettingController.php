<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\QcReportEmailSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class QcReportEmailSettingController extends Controller
{
    public function index(): Response
    {
        $companies = Company::query()
            ->with('qcReportEmailSetting')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function (Company $company): array {
                $setting = $company->qcReportEmailSetting;

                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'email_setting' => $setting ? [
                        'id' => $setting->id,
                        'to_emails' => $setting->to_emails ?? [],
                        'cc_emails' => $setting->cc_emails ?? [],
                        'is_active' => $setting->is_active,
                    ] : null,
                ];
            });

        return Inertia::render('SuperAdmin/QcReportEmailSettings/Index', [
            'companies' => $companies,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'integer', Rule::exists('sma_company', 'id')],
            'to_emails' => [Rule::requiredIf($request->boolean('is_active')), 'array', 'max:20'],
            'to_emails.*' => ['required', 'email:rfc', 'max:255', 'distinct:ignore_case'],
            'cc_emails' => ['present', 'array', 'max:20'],
            'cc_emails.*' => ['required', 'email:rfc', 'max:255', 'distinct:ignore_case'],
            'is_active' => ['required', 'boolean'],
        ]);

        $toEmails = $this->normalizeEmails($validated['to_emails'] ?? []);
        $ccEmails = array_values(array_diff(
            $this->normalizeEmails($validated['cc_emails']),
            $toEmails,
        ));

        QcReportEmailSetting::query()->updateOrCreate(
            ['company_id' => $validated['company_id']],
            [
                'to_emails' => $toEmails,
                'cc_emails' => $ccEmails,
                'is_active' => $validated['is_active'],
            ],
        );

        return redirect()
            ->route('super-admin.qc-report-email-settings.index')
            ->with('success', 'QC report email settings saved successfully.');
    }

    private function normalizeEmails(array $emails): array
    {
        return array_values(array_unique(array_map(
            fn (string $email): string => strtolower(trim($email)),
            $emails,
        )));
    }
}
