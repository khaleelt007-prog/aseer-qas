# PDF Template Generation - Quick Reference

## How It Works

The system automatically selects the correct PDF template based on the evaluation type:

```php
// In QualityEvaluationController::exportPdf()
if ($qualityEvaluation->type === 'checklist') {
    // Generate checklist PDF
    $pdfResult = $pdfService->generateChecklistPdf($qualityEvaluation);
} else {
    // Generate regular evaluation PDF
    $pdfResult = $pdfService->generatePdf($qualityEvaluation, $evaluationItems);
}
```

## Evaluation Types

### Type: 'checklist'
- Uses template-based questions and answers
- Organized into sections
- PDF Template: `resources/views/pdf/quality-evaluation-checklist.blade.php`
- Service Method: `generateChecklistPdf()`

### Type: 'regular' (default)
- Uses traditional evaluation items with scoring
- Displays scoring table with weights
- PDF Template: `resources/views/pdf/quality-evaluation.blade.php`
- Service Method: `generatePdf()`

## PDF Generation Flow

### 1. Export PDF (User Action)
```
Route: POST /quality-evaluations/{id}/export-pdf
Controller: QualityEvaluationController::exportPdf()
```

### 2. Download PDF (On-Demand Generation)
```
Route: GET /quality-evaluations/{id}/download-pdf
Controller: QualityEvaluationController::downloadPdf()
- Checks if PDF exists in database
- Generates if missing
- Returns inline PDF response
```

### 3. Automatic Generation (After Save)
```
Controller: QualityEvaluationController::store() or update()
- Calls: generatePdfForEvaluation()
- Stores filename in database
- Happens silently in background
```

## Service Methods

### QualityEvaluationPdfService

**Regular Evaluation PDF:**
```php
public function generatePdf(
    QualityEvaluation $evaluation, 
    array $evaluationItems
): array
```
Returns: `['path' => string, 'filename' => string]`

**Checklist Evaluation PDF:**
```php
public function generateChecklistPdf(
    QualityEvaluation $evaluation
): array
```
Returns: `['path' => string, 'filename' => string]`

## Checklist PDF Template Structure

### Header Section
- Evaluation month
- Branch name
- Evaluation status
- Template name

### Sections (Dynamic)
For each section in template:
- Section header (styled background)
- Questions and answers table
  - Left column: Question text
  - Right column: Answer value

### Comments Section
- Displayed only if comments exist
- Full-width text area

### Photos Section
- Displayed only if photos exist
- Images with captions
- Filename and upload timestamp

### Footer
- Generation timestamp
- System attribution

## Data Requirements

### For Checklist PDF Generation
```php
$evaluation->load([
    'template.sections.questions',  // Template structure
    'answers',                       // User answers
    'branch.brand',                  // Branch info
    'user',                          // User info
    'photos'                         // Attached photos
]);
```

### For Regular PDF Generation
```php
$evaluation->load([
    'responsesWithItems',            // Scores and items
    'branch.brand',                  // Branch info
    'user',                          // User info
    'photos'                         // Attached photos
]);
```

## PDF Storage

**Location**: `storage/app/public/pdfs/`

**Filename Format**: `evaluation_{id}_{timestamp}.pdf`

**Database**: Stored in `quality_evaluations.pdf_filename` column

## Customizing Templates

### Checklist Template
File: `resources/views/pdf/quality-evaluation-checklist.blade.php`

Key variables available:
- `$evaluation` - QualityEvaluation model
- `$evaluation->template` - QcTemplate model
- `$evaluation->template->sections` - Collection of QcSection
- `$evaluation->answers` - Collection of QcAnswer
- `$branch` - Branch model
- `$user` - User model
- `$photos` - Array of prepared photo data

### Regular Template
File: `resources/views/pdf/quality-evaluation.blade.php`

Key variables available:
- `$evaluation` - QualityEvaluation model
- `$evaluationItems` - Array of evaluation items with scores
- `$branch` - Branch model
- `$user` - User model
- `$photos` - Array of prepared photo data

## Important Notes

1. **Type Field**: Must be set when creating evaluation
   - `'checklist'` for template-based
   - `'regular'` for traditional scoring

2. **Template Required**: Checklist evaluations must have `template_id` set

3. **Inline Styles**: All CSS must be inline in PDF templates (Gpdf limitation)

4. **Cairo Font**: Required for Arabic text rendering
   - Font file: `public/vendor/gpdf/fonts/Cairo-Normal.ttf`

5. **RTL Layout**: All templates use `dir="rtl"` for Arabic

6. **Photo Sizing**: Photos automatically scaled to fit A4 page
   - Max width: 420px
   - Max height: 350px

## Troubleshooting

### PDF Not Generating
1. Check Cairo font exists: `public/vendor/gpdf/fonts/Cairo-Normal.ttf`
2. Check `storage/app/public/pdfs/` directory is writable
3. Check evaluation has required relationships loaded
4. Check logs: `storage/logs/laravel.log`

### Missing Template Data
1. Verify `template_id` is set on evaluation
2. Verify template has sections and questions
3. Verify answers are stored in `qc_answers` table

### Photos Not Showing
1. Check photos exist in storage
2. Check `QualityEvaluationPhoto::fileExists()` returns true
3. Check photo mime type is supported

## Related Files
- Controller: `app/Http/Controllers/QualityEvaluationController.php`
- Service: `app/Services/QualityEvaluationPdfService.php`
- Regular Template: `resources/views/pdf/quality-evaluation.blade.php`
- Checklist Template: `resources/views/pdf/quality-evaluation-checklist.blade.php`

