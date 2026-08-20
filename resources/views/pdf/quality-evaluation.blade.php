<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>Evaluation Report</title>
</head>

<body
    style="font-family: cairo, Arial, sans-serif; direction: rtl; text-align: right; margin: 0; padding: 0; font-size: 12px; line-height: 1.4;">
    <!-- Header Section -->
    <div
        style="text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; direction: rtl;">
        <h1 style="font-size: 18px; font-weight: bold; margin: 0 0 10px 0; color: #333;">
            تقرير زيارات الفروع / الخاص بقسم الجودة
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
                    تاريخ التقييم:
                </td>
                <td
                    style="padding: 5px 10px; border: 1px solid #ddd; background-color: #f9f9f9; direction: rtl; text-align: right;">
                    {{ \Carbon\Carbon::parse($evaluation->created_at)->format('Y/m/d') }}
                </td>
                <td
                    style="padding: 5px 10px; border: 1px solid #ddd; background-color: #e9ecef; font-weight: bold; direction: rtl; text-align: right;">
                    حالة التقييم:
                </td>
            </tr>
        </table>
    </div>

    <!-- Evaluation Details Table -->
    <table style="width: 100%; border-collapse: collapse; margin: 20px 0; ">
        <thead>
            <tr>
                <th
                    style="width: 5%; border: 1px solid #333; padding: 5px; text-align: center; background-color: #f0f0f0; font-weight: bold; font-size: 11px; direction: rtl;">
                    ملاحظات
                </th>
                <th
                    style="width: 15%; border: 1px solid #333; padding: 5px; text-align: center; background-color: #f0f0f0; font-weight: bold; font-size: 11px; direction: rtl;">
                    الزيارة الثالثة
                </th>
                <th
                    style="width: 15%; border: 1px solid #333; padding: 5px; text-align: center; background-color: #f0f0f0; font-weight: bold; font-size: 11px; direction: rtl;">
                    الزيارة الثانية
                </th>
                <th
                    style="width: 15%; border: 1px solid #333; padding: 5px; text-align: center; background-color: #f0f0f0; font-weight: bold; font-size: 11px; direction: rtl;">
                    الزيارة الأولى
                </th>
                <th
                    style="width: 10%; border: 1px solid #333; padding: 5px; text-align: center; background-color: #f0f0f0; font-weight: bold; font-size: 11px; direction: rtl;">
                    الوزن %
                </th>
                <th
                    style="width: 40%; border: 1px solid #333; padding: 5px; text-align: center; background-color: #f0f0f0; font-weight: bold; font-size: 11px; direction: rtl;">
                    معايير التقييم
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach ($evaluationItems as $item)
                @php
                    $percentage = $item['achieved'] !== null ? ($item['achieved'] / $item['max']) * 100 : 0;
                    $scoreColor = '';
                    if ($percentage >= 85) {
                        $scoreColor = 'background-color: #d4edda;';
                    } elseif ($percentage >= 70) {
                        $scoreColor = 'background-color: #fff3cd;';
                    } elseif ($percentage >= 50) {
                        $scoreColor = 'background-color: #f8d7da;';
                    } else {
                        $scoreColor = 'background-color: #f5c6cb;';
                    }
                    $excludedStyle = $item['exclude_from_score'] ? 'opacity: 0.6; font-style: italic;' : '';
                @endphp
                <tr style="{{ $excludedStyle }}">
                    <td
                        style="border: 1px solid #333; padding: 1px; text-align: center; font-size: 10px; direction: rtl;">
                        -</td>
                    <td
                        style="border: 1px solid #333; padding: 1px; text-align: center; font-size: 10px; direction: rtl;">
                        -</td>
                    <td
                        style="border: 1px solid #333; padding: 1px; text-align: center; font-size: 10px; direction: rtl;">
                        -</td>
                    <td
                        style="border: 1px solid #333; padding: 1px; text-align: center; font-size: 12px; direction: rtl; {{ $item['achieved'] !== null ? $scoreColor : '' }}">
                        @if ($item['achieved'] !== null)
                            {{ $item['max'] }}/{{ $item['achieved'] }}
                         
                        @else
                            -
                        @endif
                    </td>
                    <td
                        style="border: 1px solid #333; padding: 1px; text-align: center; font-size: 12px; direction: rtl;">
                        %{{ $item['weight'] }}
                    </td>
                    <td
                        style="border: 1px solid #333; padding: 1px; text-align: right; padding-right: 10px; font-size: 12px; direction: rtl;">
                        {{ $item['title_ar'] ?: $item['title'] }}
                        @if ($item['exclude_from_score'])
                            <small>(مستبعد من النتيجة)</small>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals Section -->
    <div style="margin: 20px 0; border: 2px solid #333; padding: 15px; background-color: #f8f9fa; direction: rtl;">
        {{-- <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px; direction: rtl;">
            <tr>

                <td
                    style="padding: 5px; border: 1px solid #ddd; text-align: center; font-weight: bold; width: 30%; direction: rtl;">
                    %{{ number_format(($evaluation->total_score ?? 0) - ($evaluation->extra_points ?? 0), 2) }}
                </td>
                <td
                    style="padding: 5px; border: 1px solid #ddd; background-color: #e9ecef; font-weight: bold; width: 70%; direction: rtl; text-align: right;">
                    النتيجة الأساسية (قبل النقاط الإضافية):
                </td>
            </tr>
        </table>
--}}
        @if ($evaluation->extra_points && $evaluation->extra_points > 0)
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px; direction: rtl;">
                <tr>

                    <td
                        style="padding: 5px; border: 1px solid #ddd; text-align: center; font-weight: bold; width: 30%; direction: rtl;">
                        +%{{ $pdfService->formatScore($evaluation->extra_points) }}
                    </td>
                    <td
                        style="padding: 5px; border: 1px solid #ddd; background-color: #e9ecef; font-weight: bold; width: 70%; direction: rtl; text-align: right;">
                        النقاط الإضافية:
                    </td>
                </tr>
            </table>
        @endif

        <table style="width: 100%; border-collapse: collapse; direction: rtl;">
            <tr>

                <td
                    style="padding: 5px; border: 1px solid #ddd; text-align: center; font-weight: bold; width: 30%; font-size: 16px; color: #333; direction: rtl;">
                    %{{ $pdfService->formatScore($evaluation->total_score ?? 0) }}
                </td>
                <td
                    style="padding: 5px; border: 1px solid #ddd; background-color: #e9ecef; font-weight: bold; width: 70%; font-size: 16px; color: #333; direction: rtl; text-align: right;">
                    النتيجة النهائية:
                </td>
            </tr>
        </table>
    </div>

    <!-- Comments Section -->
    @if ($evaluation->comments)
        <div style="margin-top: 20px; direction: rtl;">
            <div style="font-weight: bold; margin-bottom: 10px; text-align: right;">
                الملاحظات:
            </div>
            <div
                style="border: 1px solid #ddd; padding: 10px; background-color: #f9f9f9; text-align: right; direction: rtl;">
                {{ $evaluation->comments }}
            </div>
        </div>
    @endif

    <!-- Photos Section -->
    @if (!empty($photos))
        <div style="margin-top: 20px; direction: rtl; page-break-inside: avoid;">
            <div style="font-weight: bold; margin-bottom: 15px; text-align: right; font-size: 14px;">
                الصور المرفقة:
            </div>
            <div style="border: 1px solid #ddd; padding: 15px; background-color: #f9f9f9;">
                @foreach ($photos as $index => $photo)
                    <div style="margin-bottom: 20px; text-align: center; page-break-inside: avoid;">
                        <!-- Photo container with proper spacing -->
                        <div style="margin-bottom: 10px;">
                            <img src="{{ $photo['data_uri'] }}"
                                 alt="{{ $photo['original_filename'] }}"
                                 style="width: 70%;
                                        max-width: 420px;
                                        height: auto;
                                        border: 1px solid #ccc;
                                        border-radius: 4px;
                                        display: block;
                                        margin: 0 auto;">
                        </div>
                        <!-- Photo caption -->
                        <div style="font-size: 10px; color: #666; text-align: center; direction: rtl;">
                            {{ $photo['original_filename'] }} -
                            تاريخ الرفع: {{ \Carbon\Carbon::parse($photo['uploaded_at'])->format('Y/m/d H:i') }}
                        </div>
                        @if (!$loop->last)
                            <hr style="margin: 15px 0; border: none; border-top: 1px solid #ddd;">
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Signatures Section -->
    <table style=" width: 100%; border-collapse: collapse; direction: rtl;">
        <tr>
            <td style="width: 33.33%; text-align: center; padding: 10px 10px; border: 1px solid #333; direction: rtl;">
                <div style="font-weight: bold; margin-bottom: 30px;">
                    مراقب الجودة
                </div>
                <div style="border-top: 1px solid #333; margin-top: 30px; padding-top: 5px; font-size: 10px;">
                    -
                </div>
            </td>
            <td style="width: 33.33%; text-align: center; padding: 10px 10px; border: 1px solid #333; direction: rtl;">
                <div style="font-weight: bold; margin-bottom: 30px;">
                    مدير الفرع
                </div>
                <div style="border-top: 1px solid #333; margin-top: 30px; padding-top: 5px; font-size: 10px;">
                    -
                </div>
            </td>
            <td style="width: 33.33%; text-align: center; padding: 10px 10px; border: 1px solid #333; direction: rtl;">
                <div style="font-weight: bold; margin-bottom: 30px;">
                    مدير الجودة
                </div>
                <div style="border-top: 1px solid #333; margin-top: 30px; padding-top: 5px; font-size: 10px;">
                    -
                </div>
            </td>
        </tr>
    </table>

    <!-- Footer -->
    <div
        style="text-align: center; margin-top: 20px; font-size: 10px; color: #666; border-top: 1px solid #ddd; padding-top: 10px; direction: rtl;">
        تم إنشاء هذا التقرير بواسطة نظام ضمان الجودة - {{ \Carbon\Carbon::now()->format('Y/m/d H:i') }}
    </div>
</body>

</html>
