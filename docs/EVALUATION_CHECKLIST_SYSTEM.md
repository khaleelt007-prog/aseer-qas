# 🎯 Quality Control Evaluation Checklist System

## Overview

A comprehensive Quality Control Checklist system that extends the existing Quality Evaluation feature by allowing brands to define custom checklist templates with sections and questions. The system supports two evaluation types:
- **Regular Evaluations**: Traditional weighted scoring system
- **Checklist Evaluations**: Yes/No/N/A answers with automatic scoring

## Architecture

### Two-Step Form Flow

1. **Step 1: Branch Selection**
   - User selects a branch from the dropdown
   - System checks if the branch's brand has an active template

2. **Step 2: Dynamic Form Display**
   - **If template exists**: Display checklist template form
   - **If no template**: Display traditional evaluation items form

## Database Schema

### New Columns in `quality_evaluations` Table
```sql
ALTER TABLE quality_evaluations ADD COLUMN type VARCHAR(50) DEFAULT 'regular';
ALTER TABLE quality_evaluations ADD COLUMN template_id INT UNSIGNED;
ALTER TABLE quality_evaluations ADD COLUMN max_score INT UNSIGNED;
ALTER TABLE quality_evaluations ADD CONSTRAINT fk_quality_evaluations_template
  FOREIGN KEY (template_id) REFERENCES qc_templates(id)
  ON DELETE SET NULL ON UPDATE CASCADE;
```

### Related Tables

**qc_templates**
- `id`: Primary key
- `brand_id`: Foreign key to brands
- `name_en`, `name_ar`: Bilingual template names
- `is_active`: Boolean flag for active templates
- `created_at`, `updated_at`: Timestamps

**qc_sections**
- `id`: Primary key
- `template_id`: Foreign key to qc_templates
- `name`, `name_ar`: Bilingual section names
- `sort_order`: Display order
- `created_at`, `updated_at`: Timestamps

**qc_questions**
- `id`: Primary key
- `section_id`: Foreign key to qc_sections
- `q_type`: Question type (1=Yes/No, 2=Text)
- `name`, `name_ar`: Bilingual question text
- `sort_order`: Display order
- `is_required`: Boolean flag for required fields
- `score`: Optional numeric score value for question-level scoring (nullable float)
- `created_at`, `updated_at`: Timestamps

**qc_answers**
- `id`: Primary key
- `quality_evaluation_id`: Foreign key to quality_evaluations
- `question_id`: Foreign key to qc_questions
- `answer_value`: Answer text (up to 1000 chars, null for N/A)
- `created_at`, `updated_at`: Timestamps

## Backend Implementation

### Models

**QualityEvaluation** (updated)
```php
public function template(): BelongsTo
{
    return $this->belongsTo(QcTemplate::class, 'template_id');
}

public function answers(): HasMany
{
    return $this->hasMany(QcAnswer::class);
}

public function calculateChecklistScore(): array
{
    // Returns ['numerator' => int, 'denominator' => int]
    // Automatically detects scoring mode:
    // - If any questions have scores: Uses question-level scoring
    // - Otherwise: Uses simple yes/no counting
}

private function calculateChecklistScoreSimple(): array
{
    // Simple yes/no counting (default mode)
    // Yes answers count as 1, No as 0, N/A (null) excluded
}

private function calculateChecklistScoreWithQuestionScores(): array
{
    // Question-level scoring mode
    // Yes answers award the question's score value
    // No answers award 0 points
    // N/A and empty answers are excluded
}
```

### Controller Methods

**QualityEvaluationController**

- `store()`: Handles both evaluation items and checklist templates
  - Detects form type via `is_checklist_template` flag
  - Calculates and saves checklist scores
  - Sets `type`, `template_id`, `total_score`, and `max_score`

- `show()`: Loads template with sections and questions for checklist evaluations
  - Formats checklist data for frontend display
  - Passes `checklistData` prop with sections and questions

- `checkTemplate()`: API endpoint to check template availability
  - Query: `branch_id`
  - Response: `{ has_template: boolean }`

- `getTemplate()`: API endpoint to fetch template data
  - Query: `branch_id`
  - Response: Template object with sections and questions

### API Routes

```php
GET /api/quality-evaluations/check-template?branch_id={id}
GET /api/quality-evaluations/get-template?branch_id={id}
```

## Frontend Implementation

### Components

**QcChecklistTemplate.vue**
- Props: `template` (object)
- Emits: `update:answers`
- Features:
  - Renders sections with questions
  - Supports Yes/No and Text question types
  - N/A option for yes/no questions (stores null)
  - Validates required fields
  - Character counter for text fields
  - Bilingual support (English/Arabic)
  - **NEW**: Displays question score badge if score is defined
    - Shows as blue badge next to question label
    - Format: "Score: X" where X is the numeric value
    - Only displayed when question has a score value

### Create.vue Updates

- Imports `QcChecklistTemplate` component
- Adds template loading state management
- Implements branch change watcher to load templates
- Conditionally renders template or evaluation items
- Updates form submission to handle both types
- Validates checklist answers before submission

**Updated `canComplete` computed property**:
- For checklist templates: Only requires branch selection
- For regular evaluations: Requires all items to be scored

### Show.vue Updates

**Props**:
- Added `checklistData` prop to receive formatted checklist data

**Conditional Rendering**:
- Total Score Display: Only shown for regular evaluations
- Checklist Score Display: Shows "X from Y" format with progress bar
- Checklist Template Info: Displays template name for checklist evaluations
- Checklist Sections: Renders sections with questions and answers
- Evaluation Items: Only shown for regular evaluations

**Checklist Display Features**:
- Displays sections with their questions
- Shows Yes/No answers with color-coded badges (green for yes, red for no)
- Shows text answers in formatted boxes
- Displays "Not Answered" for missing answers
- Shows required field indicators (*)
- Calculates and displays total sections and questions

### Index.vue Updates

**New Methods**:
```javascript
const getChecklistScoreColor = (totalScore, maxScore) => {
    if (maxScore <= 0) return 'text-gray-600'
    const percentage = (totalScore / maxScore) * 100
    if (percentage >= 90) return 'text-green-600'
    if (percentage >= 80) return 'text-yellow-600'
    if (percentage >= 70) return 'text-orange-600'
    return 'text-red-600'
}
```

**Display Logic**:
- Checks `evaluation.type` to determine if checklist or regular
- For checklists: Uses `max_score` to calculate percentage
- Properly handles null values for `max_score` (regular evaluations)
- Color-coded based on percentage: Green (90+), Yellow (80+), Orange (70+), Red (<70)

## Scoring System

### Two Scoring Modes

#### 1. Simple Yes/No Scoring (Default)
Used when questions do not have individual scores defined.

**Scoring Logic**:
- **Yes answers**: Count as 1 point each
- **No answers**: Count as 0 points
- **N/A (Not Applicable)**: Excluded from both numerator and denominator
- **Total Score Format**: `numerator / denominator`
  - Numerator: Count of "Yes" answers
  - Denominator: Count of answered questions (excluding N/A)
- **Example**: 20 Yes, 3 No, 2 N/A = Score of "20 from 23"

#### 2. Question-Level Scoring
Used when questions have individual score values defined in the `qc_questions.score` field.

**Scoring Logic**:
- **Yes answers**: Award the question's score value
- **No answers**: Award 0 points
- **N/A (Not Applicable)**: Excluded from both numerator and denominator
- **Empty answers**: Excluded from both numerator and denominator
- **Total Score Format**: `numerator / denominator`
  - Numerator: Sum of scores for "Yes" answers
  - Denominator: Sum of all question scores (for answered questions excluding N/A)
- **Example**:
  - Question 1 (score: 5): Yes → +5 points
  - Question 2 (score: 3): No → +0 points
  - Question 3 (score: 2): N/A → excluded
  - Result: Score of "5 from 8"

**Question Score Display**:
- Question scores are displayed in the checklist form as a blue badge next to the question label
- Format: "Score: X" where X is the numeric value
- Only displayed if the question has a score defined

### Score Calculation Process
1. User completes checklist with yes/no/N/A answers
2. System calculates score on save:
   - Checks if any questions have scores defined
   - If yes: Uses question-level scoring (weighted by individual question scores)
   - If no: Uses simple yes/no counting
3. Saves numerator to `total_score`
4. Saves denominator to `max_score`

### Interaction with Other Features

#### Question-Level Scoring and Weighted Scoring
- Question-level scoring is specific to checklist evaluations
- Weighted scoring applies only to regular evaluations with evaluation items
- These two systems are independent and do not interact

#### Question-Level Scoring and Zero Override
- Zero override (`overwrite_total_score_if_zero`) applies only to regular evaluations
- Not applicable to checklist evaluations with question-level scoring
- Checklist evaluations calculate scores based on answers only

#### Question-Level Scoring and Score Exclusion
- Score exclusion (`exclude_from_score`) applies only to regular evaluations
- Not applicable to checklist evaluations with question-level scoring
- All answered questions (excluding N/A) contribute to checklist scores

## Internationalization (i18n)

### New Translations Added

**Files Updated**:
- `resources/js/i18n.js` (English & Arabic)
- `lang/en/quality.php`
- `lang/ar/quality.php`

**New Keys**:
- `checklist_template`: Checklist Template / نموذج قائمة التحقق
- `sections`: Sections / الأقسام
- `questions`: Questions / الأسئلة
- `not_answered`: Not Answered / لم يتم الإجابة
- `no_answer_provided`: No answer provided / لم يتم تقديم إجابة
- `not_applicable`: N/A / غير قابل للتطبيق
- `checklist_score`: Checklist Score / درجة قائمة التحقق
- `from`: from / من

## PDF Generation

### PDF Template
**File**: `resources/views/pdf/quality-evaluation-checklist.blade.php`

- Displays score in "X / Y" format
- Shows percentage calculation
- Uses inline styles for PDF compatibility
- Arabic-only display (as per user preference)
- Includes header/details/footer sections

## Key Features

### Task 1: Display Checklist Evaluations
✅ Checklist evaluations display sections with questions
✅ Shows Yes/No answers with visual indicators
✅ Shows text answers in formatted boxes
✅ Displays required field indicators
✅ Shows checklist summary with section and question counts
✅ Maintains RTL support for Arabic
✅ Proper i18n support for both languages

### Task 2: Enable Complete Evaluation Button
✅ Checklist forms can be completed without score validation
✅ Only requires branch selection for checklist completion
✅ Regular evaluations still require all items to be scored
✅ Appropriate disable messages for each form type
✅ Seamless user experience for both evaluation types

### Task 3: Scoring System
✅ Automatic score calculation on save
✅ Yes/No/N/A answer support
✅ "X from Y" format display
✅ Progress bar with percentage
✅ Color-coded scoring in list view
✅ PDF score display

## Question-Level Scoring Implementation

### How It Works

1. **Automatic Detection**: When calculating checklist scores, the system automatically detects if any questions have scores defined
2. **Fallback to Simple Scoring**: If no question scores are found, the system uses simple yes/no counting
3. **Weighted Calculation**: When question scores exist, each "yes" answer contributes the question's score value to the total

### Database Schema

The `qc_questions` table includes an optional `score` column (nullable float):
```sql
ALTER TABLE qc_questions ADD COLUMN score FLOAT NULL;
```

### Frontend Display

Question scores are displayed in the checklist form:
- Blue badge next to question label
- Shows "Score: X" format
- Only visible when score is defined
- Helps users understand the relative importance of each question

### Backend Calculation

The `calculateChecklistScore()` method in `QualityEvaluation` model:
1. Loads answers with their related questions
2. Checks if any questions have scores defined
3. Routes to appropriate calculation method:
   - `calculateChecklistScoreWithQuestionScores()` - for weighted scoring
   - `calculateChecklistScoreSimple()` - for yes/no counting
4. Returns array with numerator (total score) and denominator (max score)

### Service Layer

The `QcTemplateService` includes question scores in template data:
- `getTemplateForBranch()` now includes `score` field for each question
- Scores are passed to frontend for display

## Backward Compatibility

- All existing regular evaluations continue to work as before
- New `type` field defaults to 'regular' for existing evaluations
- `template_id` is NULL for regular evaluations
- Show page automatically detects evaluation type and displays appropriate content
- Create form automatically loads template based on branch selection
- **NEW**: Existing checklist evaluations without question scores continue to use simple yes/no counting
- **NEW**: Question scores are optional - templates can mix questions with and without scores

## Usage Examples

### Creating a Checklist Evaluation
1. Navigate to `/quality-evaluations/create`
2. Select a branch with an active template
3. Template form loads automatically
4. Complete all required questions
5. Submit form to save evaluation and answers

### Creating a Regular Evaluation
1. Navigate to `/quality-evaluations/create`
2. Select a branch without an active template
3. Traditional evaluation items form displays
4. Complete scoring as usual
5. Submit form to save evaluation

### Viewing Evaluations
- **Index**: `/quality-evaluations` - List all evaluations
- **Show**: `/quality-evaluations/{id}` - View detailed evaluation
- **Edit**: `/quality-evaluations/{id}/edit` - Edit existing evaluation

## Files Modified

### Original Implementation
1. `database/sql/db_changes.sql` - Schema changes
2. `app/Models/QualityEvaluation.php` - Model updates
3. `app/Http/Controllers/QualityEvaluationController.php` - Controller logic
4. `resources/js/Pages/QualityEvaluation/Show.vue` - Frontend display
5. `resources/js/Pages/QualityEvaluation/Create.vue` - Form validation
6. `resources/js/Pages/QualityEvaluation/Index.vue` - List display
7. `resources/js/Components/QcChecklistTemplate.vue` - Checklist form
8. `resources/views/pdf/quality-evaluation-checklist.blade.php` - PDF template
9. `lang/en/quality.php` - English translations
10. `lang/ar/quality.php` - Arabic translations
11. `resources/js/i18n.js` - Frontend i18n

### Question-Level Scoring Implementation
1. `app/Models/QualityEvaluation.php` - Added scoring methods:
   - `calculateChecklistScoreWithQuestionScores()` - Question-level scoring
   - `calculateChecklistScoreSimple()` - Simple yes/no counting
   - Updated `calculateChecklistScore()` - Auto-detection logic
2. `app/Services/QcTemplateService.php` - Updated `getTemplateForBranch()` to include question scores
3. `resources/js/Components/QcChecklistTemplate.vue` - Added question score display badge
4. `docs/EVALUATION_CHECKLIST_SYSTEM.md` - Documentation updates

## Notes

- Only one active template per brand
- Templates are brand-specific, not branch-specific
- Existing evaluation items system remains unchanged
- Both systems can coexist in the application
- N/A selections store `null` in database (not 'n/a' string)
- Backend scoring already handles `null` values correctly
- All changes maintain backward compatibility
- Bilingual support (English/Arabic) preserved
- RTL layout properly handled in all components

