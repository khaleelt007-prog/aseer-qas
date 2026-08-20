<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>Checklist Evaluation Report</title>
</head>

<body
    style="font-family: cairo, Arial, sans-serif; direction: rtl; text-align: right; margin: 0; padding: 0; font-size: 12px; line-height: 1.4;">
    <!-- Header Section -->
    <div
        style="text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; direction: rtl;">
        <h1 style="font-size: 18px; font-weight: bold; margin: 0 0 10px 0; color: #333;">
            تقرير قائمة التحقق من الجودة
        </h1>

        <table style="width: 100%; margin-top: 15px; border-collapse: collapse; direction: ltr;">
            <tr>
                <td
                    style="padding: 5px 10px; border: 1px solid #ddd; background-color: #f9f9f9; direction: rtl; text-align: right;">
                    {{ \Carbon\Carbon::parse($evaluation->created_at)->format('Y/m') }}
                </td>
                <td
                    style="padding: 5px 10px; border: 1px solid #ddd; background-color: #e9ecef; font-weight: bold; direction: rtl; text-align: right;">
                    شهر التقييم:
                </td>
                <td
                    style="padding: 5px 10px; border: 1px solid #ddd; background-color: #f9f9f9; direction: rtl; text-align: right;">
                    {{ $branch->localized_name ?? $branch->name }}
                </td>
                <td
                    style="padding: 5px 10px; border: 1px solid #ddd; background-color: #e9ecef; font-weight: bold; direction: rtl; text-align: right;">
                    اسم الفرع:
                </td>
            </tr>
            <tr>
                <td
                    style="padding: 5px 10px; border: 1px solid #ddd; background-color: #f9f9f9; direction: rtl; text-align: right;">
                    {{ $evaluation->status === 'completed' ? 'مكتمل' : 'مسودة' }}
                </td>
                <td
                    style="padding: 5px 10px; border: 1px solid #ddd; background-color: #e9ecef; font-weight: bold; direction: rtl; text-align: right;">
                    حالة التقييم:
                </td>
                <td
                    style="padding: 5px 10px; border: 1px solid #ddd; background-color: #f9f9f9; direction: rtl; text-align: right;">
                    {{ \Carbon\Carbon::parse($evaluation->created_at)->format('Y/m/d') }}
                </td>
                <td
                    style="padding: 5px 10px; border: 1px solid #ddd; background-color: #e9ecef; font-weight: bold; direction: rtl; text-align: right;">
                    تاريخ التقييم:
                </td>
            </tr>
            <tr>
                <td
                    style="padding: 5px 10px; border: 1px solid #ddd; background-color: #f9f9f9; direction: rtl; text-align: right;">
                    {{ $evaluation->template->localized_name ?? 'N/A' }}
                </td>
                <td colspan="3"
                    style="padding: 5px 10px; border: 1px solid #ddd; background-color: #e9ecef; font-weight: bold; direction: rtl; text-align: right;">
                    اسم قالب التحقق:
                </td>
            </tr>
        </table>

        <!-- Checklist Score Section -->
        @if ($evaluation->total_score !== null && $evaluation->max_score !== null && $evaluation->max_score > 0)
            <div style="margin-top: 20px; padding: 15px; background-color: #f0f8ff; border: 2px solid #4a90e2; border-radius: 4px; text-align: center; direction: rtl;">
                <div style="font-size: 14px; font-weight: bold; color: #333; margin-bottom: 8px;">
                    درجة قائمة التحقق:
                </div>
                <div style="font-size: 24px; font-weight: bold; color: #4a90e2; direction: ltr;">
                    {{ $pdfService->formatScore($evaluation->total_score) }} / {{ $pdfService->formatScore($evaluation->max_score) }}
                </div>
                <div style="font-size: 12px; color: #666; margin-top: 8px; direction: rtl;">
                    النسبة المئوية: {{ $evaluation->max_score > 0 ? number_format(((float)$evaluation->total_score / (float)$evaluation->max_score) * 100) : 0 }}%
                </div>
            </div>
        @endif
    </div>

    <!-- Checklist Sections -->
    <div style="margin: 20px 0; direction: rtl;">
        @if ($evaluation->template && $evaluation->template->sections)
            @foreach ($evaluation->template->sections as $section)
                <div style="margin-bottom: 25px; page-break-inside: avoid;">
                    <!-- Section Header -->
                    <div
                        style="background-color: #e9ecef; padding: 10px; margin-bottom: 15px; border-right: 4px solid #333; font-weight: bold; font-size: 13px; direction: rtl;">
                        {{ $section->localized_name }}
                    </div>

                    <!-- Questions and Answers -->
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
                        @if ($section->questions)
                            @foreach ($section->questions as $question)
                                @php
                                    // Find the answer for this question
                                    $answer = $evaluation->answers
                                        ->where('question_id', $question->id)
                                        ->first();

                                    // Determine the display value based on template answer type
                                    $answerType = $evaluation->template->answer_type ?? 'Points';

                                    if (!$answer || $answer->answer_value === null || $answer->answer_value === '') {
                                        $answerValue = 'لم يتم الإجابة';
                                    } elseif ($answerType === 'Yes/No') {
                                        // Yes/No answer type
                                        if ($answer->answer_value === '1' || $answer->answer_value === 1) {
                                            $answerValue = 'نعم';
                                        } elseif ($answer->answer_value === '0' || $answer->answer_value === 0) {
                                            $answerValue = 'لا';
                                        } else {
                                            $answerValue = $answer->answer_value;
                                        }
                                    } else {
                                        // Points answer type (default)
                                        if ($answer->answer_value === '1' || $answer->answer_value === 1) {
                                            $answerValue = '1 نقطة';
                                        } elseif ($answer->answer_value === '0.5' || $answer->answer_value === 0.5) {
                                            $answerValue = '0.5 نقطة';
                                        } elseif ($answer->answer_value === '0' || $answer->answer_value === 0) {
                                            $answerValue = '0 نقطة';
                                        } else {
                                            $answerValue = $answer->answer_value;
                                        }
                                    }
                                @endphp
                                <tr>

                                    <td
                                        style="padding: 10px; border: 1px solid #ddd; background-color: #ffffff; width: 30%; direction: rtl; text-align: right; vertical-align: top; word-wrap: break-word;">
                                        {{ $answerValue }}
                                    </td>
                                    <td  dir="rtl"
                                        style="padding: 10px; border: 1px solid #ddd; background-color: #f9f9f9; width: 70%; text-align: right; vertical-align: top;">
                                        <strong>{{ $question->localized_name }}</strong>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </table>

                    <!-- Section Photos -->
                    @if (!empty($photosBySection) && isset($photosBySection[$section->id]) && !empty($photosBySection[$section->id]['photos']))
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                            <div style="background-color: #f0f0f0; padding: 8px; margin-bottom: 10px; font-weight: bold; font-size: 12px; direction: rtl;">
                                صور القسم:
                            </div>
                            <div style="border: 1px solid #ddd; padding: 10px; background-color: #fafafa;">
                                @foreach ($photosBySection[$section->id]['photos'] as $photo)
                                    <div style="margin-bottom: 15px; text-align: center; page-break-inside: avoid;">
                                        <div style="margin-bottom: 8px;">
                                            <img src="{{ $photo['data_uri'] }}"
                                                 alt="{{ $photo['original_filename'] }}"
                                                 style="width: 60%;
                                                        max-width: 350px;
                                                        height: auto;
                                                        border: 1px solid #ccc;
                                                        border-radius: 4px;
                                                        display: block;
                                                        margin: 0 auto;">
                                        </div>
                                        <div style="font-size: 9px; color: #666; direction: rtl; text-align: center;">
                                            {{ $photo['original_filename'] }} - {{ \Carbon\Carbon::parse($photo['uploaded_at'])->format('Y/m/d H:i') }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        @endif
    </div>

    <!-- Comments Section -->
    @if ($evaluation->comments)
        <div style="margin-top: 30px; page-break-inside: avoid; direction: rtl;">
            <div
                style="background-color: #e9ecef; padding: 10px; margin-bottom: 10px; border-right: 4px solid #333; font-weight: bold; font-size: 13px; direction: rtl;">
                ملاحظات عامة:
            </div>
            <div
                style="padding: 10px; border: 1px solid #ddd; background-color: #f9f9f9; direction: rtl; text-align: right; word-wrap: break-word;">
                {{ $evaluation->comments }}
            </div>
        </div>
    @endif

    <!-- General Photos Section -->
    @if (!empty($generalPhotos))
        <div style="margin-top: 30px; page-break-inside: avoid; direction: rtl;">
            <div
                style="background-color: #e9ecef; padding: 10px; margin-bottom: 10px; border-right: 4px solid #333; font-weight: bold; font-size: 13px; direction: rtl;">
                صور عامة:
            </div>
            <div style="border: 1px solid #ddd; padding: 10px; background-color: #fafafa;">
                @foreach ($generalPhotos as $photo)
                    <div style="margin-bottom: 15px; text-align: center; page-break-inside: avoid;">
                        <div style="margin-bottom: 8px;">
                            <img src="{{ $photo['data_uri'] }}"
                                 alt="{{ $photo['original_filename'] }}"
                                 style="width: 60%;
                                        max-width: 350px;
                                        height: auto;
                                        border: 1px solid #ccc;
                                        border-radius: 4px;
                                        display: block;
                                        margin: 0 auto;">
                        </div>
                        <div style="font-size: 9px; color: #666; direction: rtl; text-align: center;">
                            {{ $photo['original_filename'] }} - {{ \Carbon\Carbon::parse($photo['uploaded_at'])->format('Y/m/d H:i') }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Footer Section -->
    <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #333; text-align: center; font-size: 10px; color: #666; direction: rtl;">
        <p style="margin: 5px 0;">
            تم إنشاء هذا التقرير بواسطة نظام إدارة الجودة
        </p>
        <p style="margin: 5px 0;">
            {{ \Carbon\Carbon::now()->format('Y/m/d H:i') }}
        </p>
    </div>
</body>

</html>

