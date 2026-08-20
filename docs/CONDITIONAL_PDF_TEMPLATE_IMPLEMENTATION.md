# Conditional PDF Template Generation Implementation

## Overview
This document describes the implementation of conditional PDF template generation based on evaluation type in the Quality Evaluation system. The system now supports two distinct PDF templates:
1. **Regular Evaluation Template** - For traditional evaluation items with scoring
2. **Checklist Template** - For quality control checklist evaluations with sections and questions

## Changes Made

### 1. Controller Updates
**File**: `app/Http/Controllers/QualityEvaluationController.php`

#### Modified Methods:

**`exportPdf()` method (lines 514-592)**
- Added type checking: `if ($qualityEvaluation->type === 'checklist')`
- For checklist evaluations:
  - Loads: `template.sections.questions`, `answers`, `branch.brand`, `user`, `photos`
  - Calls: `$pdfService->generateChecklistPdf($qualityEvaluation)`
- For regular evaluations:
  - Loads: `responsesWithItems`, `branch.brand`, `user`, `photos`
  - Calls: `$pdfService->generatePdf($qualityEvaluation, $evaluationItems)` (existing behavior)

**`downloadPdf()` method (lines 594-689)**
- Added type-based PDF generation logic
- Handles both checklist and regular evaluation types
- Generates PDF on-demand if not already stored

**`generatePdfForEvaluation()` method (lines 793-839)**
- Updated to handle both evaluation types
- Loads appropriate relationships based on type
- Calls appropriate PDF generation method

### 2. Service Updates
**File**: `app/Services/QualityEvaluationPdfService.php`

#### New Method:

**`generateChecklistPdf(QualityEvaluation $evaluation): array`** (lines 71-120)
- Generates PDF for checklist-type evaluations
- Uses new Blade template: `pdf.quality-evaluation-checklist`
- Returns array with `path` and `filename` keys
- Follows same pattern as `generatePdf()` method

#### New Validation Method:

**`validateChecklistData(QualityEvaluation $evaluation): void`** (lines 237-256)
- Validates checklist evaluation data before PDF generation
- Checks for:
  - Evaluation data existence
  - Branch information
  - Template information (specific to checklist)
  - Cairo font availability

### 3. New Blade Template
**File**: `resources/views/pdf/quality-evaluation-checklist.blade.php`

#### Structure:
- **Header Section**: Displays evaluation metadata
  - Evaluation month
  - Branch name
  - Evaluation status
  - Template name
  
- **Checklist Sections**: Organized content blocks
  - Section headers with visual styling
  - Questions and answers displayed in table format
  - Question on left (50% width), Answer on right (50% width)
  - Handles missing answers with "لم يتم الإجابة" (Not answered)
  
- **Comments Section**: General comments/notes
  - Only displayed if comments exist
  
- **Photos Section**: Attached images
  - Displays photos with proper sizing
  - Shows filename and upload timestamp
  - Same styling as regular evaluation template
  
- **Footer Section**: Report metadata
  - Generation timestamp
  - System attribution

#### Key Features:
- **RTL Support**: Full Arabic right-to-left layout
- **Cairo Font**: Uses Cairo font for Arabic text rendering
- **Inline Styles**: All CSS is inline for PDF compatibility
- **Page Break Control**: Uses `page-break-inside: avoid` for better layout
- **Responsive Sizing**: Photos scaled appropriately for A4 format
- **Localized Content**: Uses `localized_name` attributes for bilingual support

## Data Flow

### For Checklist Evaluations:
```
exportPdf() 
  → Check type === 'checklist'
  → Load template.sections.questions, answers
  → generateChecklistPdf()
  → Render quality-evaluation-checklist.blade.php
  → Generate PDF with Gpdf
  → Store filename in database
  → Return PDF response
```

### For Regular Evaluations:
```
exportPdf()
  → Check type !== 'checklist'
  → Load responsesWithItems
  → generatePdf()
  → Render quality-evaluation.blade.php (existing)
  → Generate PDF with Gpdf
  → Store filename in database
  → Return PDF response
```

## PDF Generation Preferences Maintained

✅ **Gpdf Package**: Uses Omaralalwi\Gpdf for PDF generation
✅ **RTL Support**: Full Arabic right-to-left layout
✅ **A4 Format**: Configured for A4 page size
✅ **Cairo Font**: Uses Cairo font (weight 200-1000) for Arabic text
✅ **Arabic-Only Content**: PDF templates are Arabic-only
✅ **Inline Styles**: All CSS is inline for proper PDF rendering
✅ **Database Storage**: PDF filenames stored in `pdf_filename` column
✅ **Automatic Generation**: PDFs generated after save operations
✅ **On-Demand Generation**: PDFs generated if not found when accessed
✅ **New Tab Opening**: PDFs open in new browser tab (inline display)

## Testing Recommendations

1. **Create a checklist evaluation** and export PDF
   - Verify sections display correctly
   - Verify questions and answers display properly
   - Verify photos are included

2. **Create a regular evaluation** and export PDF
   - Verify existing template still works
   - Verify scoring table displays correctly

3. **Test edge cases**:
   - Evaluation with no answers
   - Evaluation with no comments
   - Evaluation with no photos
   - Evaluation with multiple sections and questions

4. **Verify PDF storage**:
   - Check `storage/app/public/pdfs/` directory
   - Verify filenames are stored in database
   - Test re-downloading existing PDFs

## Files Modified
- `app/Http/Controllers/QualityEvaluationController.php`
- `app/Services/QualityEvaluationPdfService.php`

## Files Created
- `resources/views/pdf/quality-evaluation-checklist.blade.php`

## Backward Compatibility
✅ All changes are backward compatible
✅ Existing regular evaluations continue to use the original PDF template
✅ No database schema changes required
✅ No breaking changes to existing APIs

