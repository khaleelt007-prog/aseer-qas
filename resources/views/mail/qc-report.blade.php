<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quality Control Report</title>
</head>
<body style="margin:0;background:#f3f4f6;font-family:Arial,sans-serif;color:#1f2937;">
    <div style="max-width:640px;margin:0 auto;padding:32px 16px;">
        <div style="overflow:hidden;border:1px solid #e5e7eb;border-radius:12px;background:#ffffff;">
            <div style="padding:24px;background:#111827;color:#ffffff;">
                <h1 style="margin:0;font-size:22px;">Quality Control Report</h1>
                <p style="margin:8px 0 0;color:#d1d5db;">Evaluation #{{ $evaluation->id }}</p>
            </div>

            <div style="padding:24px;">
                <p style="margin-top:0;">Hello,</p>
                <p>The quality control evaluation for <strong>{{ $evaluation->branch?->name }}</strong> has been completed.</p>

                <table style="width:100%;margin:24px 0;border-collapse:collapse;">
                    <tr>
                        <td style="padding:10px;border-bottom:1px solid #e5e7eb;color:#6b7280;">Company</td>
                        <td style="padding:10px;border-bottom:1px solid #e5e7eb;text-align:right;">{{ $evaluation->branch?->company?->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:10px;border-bottom:1px solid #e5e7eb;color:#6b7280;">Branch</td>
                        <td style="padding:10px;border-bottom:1px solid #e5e7eb;text-align:right;">{{ $evaluation->branch?->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:10px;border-bottom:1px solid #e5e7eb;color:#6b7280;">Completed</td>
                        <td style="padding:10px;border-bottom:1px solid #e5e7eb;text-align:right;">{{ $evaluation->completed_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                    <tr>
                        <td style="padding:10px;color:#6b7280;">Score</td>
                        <td style="padding:10px;text-align:right;font-weight:bold;">{{ $evaluation->total_score }}</td>
                    </tr>
                </table>

                <p>The complete QC report is attached as a PDF.</p>
                <p style="margin-bottom:0;color:#6b7280;font-size:13px;">This is an automated message from Aseer QAS.</p>
            </div>
        </div>
    </div>
</body>
</html>