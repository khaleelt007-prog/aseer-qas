<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Quality Evaluation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for quality evaluation
    | components and messages.
    |
    */

    // Page titles and headers
    'quality_control_evaluation' => 'تقييم مراقبة الجودة',
    'quality_control_scoring' => 'نظام تسجيل مراقبة الجودة',
    'evaluation_details' => 'تفاصيل التقييم',
    'evaluation_summary' => 'ملخص التقييم',

    // Form labels and descriptions
    'complete_evaluation_description' => 'أكمل التقييم عن طريق تسجيل نقاط لكل عنصر. يتم حساب النتيجة الإجمالية بناءً على النسب المرجحة.',
    'evaluation_title' => 'عنوان التقييم',
    'country' => 'الدولة',
    'select_country' => 'اختر الدولة',
    'search_countries' => 'البحث في الدول...',
    'country_required' => 'يرجى اختيار الدولة',
    'brand' => 'العلامة التجارية',
    'select_brand' => 'اختر العلامة التجارية',
    'search_brands' => 'البحث في العلامات التجارية...',
    'brand_required' => 'يرجى اختيار العلامة التجارية',
    'branch' => 'الفرع',
    'select_branch' => 'اختر الفرع',
    'search_branches' => 'البحث في الفروع...',
    'branch_required' => 'يرجى اختيار الفرع',
    'branch_cannot_be_changed' => 'لا يمكن تغيير الفرع لتقييمات قائمة المراجعة',
    'selected_branch' => 'الفرع المختار',
    'comments' => 'التعليقات',
    'additional_comments' => 'تعليقات إضافية',
    'optional_comments' => 'تعليقات اختيارية حول هذا التقييم',
    'extra_points' => 'نقاط إضافية',
    'bonus_penalty_points' => 'نقاط المكافأة/الخصم',
    'extra_points_description' => 'إضافة أو خصم نقاط من النتيجة المحسوبة (-5 إلى +5)',

    // Progress and status
    'progress' => 'التقدم',
    'items_completed' => 'عنصر مكتمل',
    'status' => 'الحالة',
    'draft' => 'مسودة',
    'completed' => 'مكتمل',
    'total_score' => 'النتيجة الإجمالية',

    // Actions
    'save_draft' => 'حفظ كمسودة',
    'complete_evaluation' => 'إكمال التقييم',
    'edit' => 'تعديل',
    'delete' => 'حذف',
    'view' => 'عرض',
    'create_new' => 'إنشاء تقييم جديد',
    'back_to_list' => 'العودة إلى التقييمات',

    // Score ranges and quality indicators
    'excellent_quality' => 'جودة ممتازة',
    'good_quality' => 'جودة جيدة',
    'acceptable_quality' => 'جودة مقبولة',
    'needs_improvement' => 'يحتاج إلى تحسين',

    // Statistics
    'total_evaluations' => 'إجمالي التقييمات',
    'completed_evaluations' => 'مكتملة',
    'draft_evaluations' => 'مسودات',
    'average_score' => 'متوسط النتيجة',

    // Validation messages
    'score_required' => 'النتيجة مطلوبة',
    'score_must_be_number' => 'يجب أن تكون النتيجة رقماً',
    'score_cannot_exceed_max' => 'لا يمكن أن تتجاوز النتيجة القيمة القصوى',

    // Confirmation messages
    'delete_confirmation' => 'هل أنت متأكد من أنك تريد حذف هذا التقييم؟',
    'delete_warning' => 'لا يمكن التراجع عن هذا الإجراء.',
    'confirm_delete' => 'نعم، احذف',
    'cancel' => 'إلغاء',

    // Success messages
    'evaluation_created' => 'تم إنشاء التقييم بنجاح',
    'evaluation_updated' => 'تم تحديث التقييم بنجاح',
    'evaluation_deleted' => 'تم حذف التقييم بنجاح',

    // Additional translations for Show page
    'control_evaluation' => 'تقييم المراقبة',
    'base' => 'الأساس',
    'bonus_points_added' => 'نقاط مكافأة مضافة للنتيجة النهائية',

    // PDF Export translations
    'export_pdf' => 'تصدير PDF',
    'download_pdf' => 'تحميل PDF',
    'regenerate_pdf' => 'إعادة إنشاء PDF',
    'exporting_pdf' => 'جاري التصدير...',
    'pdf_export_success' => 'تم تصدير PDF بنجاح',
    'pdf_export_failed' => 'فشل في تصدير PDF',
    'branch_visits_report' => 'تقرير زيارات الفروع / الخاص بقسم الجودة',
    'branch_name' => 'اسم الفرع',
    'evaluation_month' => 'شهر التقييم',
    'evaluation_date' => 'تاريخ التقييم',
    'evaluation_status' => 'حالة التقييم',
    'evaluation_criteria' => 'معايير التقييم',
    'weight_percentage' => 'الوزن %',
    'first_visit' => 'الزيارة الأولى',
    'second_visit' => 'الزيارة الثانية',
    'third_visit' => 'الزيارة الثالثة',
    'notes' => 'ملاحظات',
    'excluded_from_score' => 'مستبعد من النتيجة',
    'base_score' => 'النتيجة الأساسية (قبل النقاط الإضافية)',
    'extra_points' => 'النقاط الإضافية',
    'final_score' => 'النتيجة النهائية',
    'quality_monitor' => 'مراقب الجودة',
    'branch_manager' => 'مدير الفرع',
    'quality_manager' => 'مدير الجودة',
    'signature' => 'التوقيع',
    'generated_by_system' => 'تم إنشاء هذا التقرير بواسطة نظام ضمان الجودة',

    // Checklist Template translations
    'loading_template' => 'جاري تحميل النموذج...',
    'complete_checklist_description' => 'يرجى ملء جميع الحقول المطلوبة',
    'yes' => 'نعم',
    'no' => 'لا',
    'not_applicable' => 'غير قابل للتطبيق',
    'one_point' => '1 نقطة',
    'half_point' => '0.5 نقطة',
    'zero_point' => '0 نقطة',
    'enter_response' => 'أدخل ردك',
    'field_required' => 'هذا الحقل مطلوب',
    'points' => 'نقاط',
    'no_branches_available' => 'لا توجد فروع متاحة لمستوى الوصول الخاص بك. يرجى الاتصال بالمسؤول.',
    'complete_all_items' => 'يرجى إكمال جميع عناصر التقييم للتقديم',
    'saving' => 'جاري الحفظ...',
    'submitting' => 'جاري الإرسال...',
    'checklist_template' => 'نموذج قائمة التحقق',
    'sections' => 'الأقسام',
    'questions' => 'الأسئلة',
    'not_answered' => 'لم يتم الإجابة',
    'no_answer_provided' => 'لم يتم تقديم إجابة',
    'created' => 'تم الإنشاء',
    'completed_on' => 'تم الإكمال في',
    'items_evaluated' => 'العناصر المقيمة',
    'weighted_total' => 'الإجمالي المرجح',
    'performance' => 'الأداء',
    'weighted_contribution' => 'المساهمة المرجحة',
    'achieved' => 'تم تحقيقه',
    'excellent' => 'ممتاز',
    'good' => 'جيد',
    'fair' => 'مقبول',
    'needs_improvement' => 'يحتاج إلى تحسين',
    'no_actions_available' => 'لا توجد إجراءات متاحة بناءً على أذوناتك.',
    'confirm_delete_title' => 'تأكيد الحذف',
    'confirm_delete_message' => 'هل أنت متأكد من أنك تريد حذف هذا التقييم؟ لا يمكن التراجع عن هذا الإجراء.',
    'edit_evaluation' => 'تعديل التقييم',
    'create_new_evaluation' => 'إنشاء تقييم جديد',
    'delete_evaluation' => 'حذف التقييم',
    'photo_documentation' => 'توثيق الصور',
    'photo_description' => 'التقط صوراً لتوثيق نتائج التقييم الخاصة بك',
    'photos_attached' => '{count} صورة مرفقة',
    'checklist_score' => 'درجة قائمة التحقق',
    'from' => 'من',

];
