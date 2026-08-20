# Progressive Form Implementation for Quality Evaluation Creation

## Overview

The quality evaluation creation flow has been modified to implement a progressive form approach where users first select a branch, and then the appropriate form type is displayed based on the branch's configuration.

## Implementation Details

### 1. Backend Changes

**File**: `app/Http/Controllers/QualityEvaluationController.php`

#### Updated `getTemplate()` API Endpoint (lines 901-936)

The endpoint now:
- Fetches the branch to check its `country_id`
- Determines the form type based on country:
  - If `country_id === 5`: Force form type to `'REGULAR'`
  - Otherwise: Use `'CHECKLIST'` (if template exists)
- Returns the template data with an additional `form_type` field

```php
// Determine form type: if country_id is 5, force REGULAR, otherwise use CHECKLIST
$formType = $branch->country_id === 5 ? 'REGULAR' : 'CHECKLIST';

return response()->json([
    ...$template,
    'form_type' => $formType,
]);
```

### 2. Frontend Changes

**File**: `resources/js/Pages/QualityEvaluation/Create.vue`

#### New State Variable (line 313)
```javascript
const formType = ref(null) // 'CHECKLIST' or 'REGULAR'
```

#### Updated `loadTemplate()` Function (lines 339-386)

The function now:
- Extracts `form_type` from the API response
- Sets `formType.value` to the returned form type
- For CHECKLIST form type: Sets `currentTemplate` and `is_checklist_template = true`
- For REGULAR form type: Clears `currentTemplate` and `is_checklist_template = false`
- Handles 404 responses by defaulting to REGULAR form type
- Handles errors by defaulting to REGULAR form type

#### Updated Template Rendering (lines 90-101)

- **Checklist Template**: Only shown if `formType === 'CHECKLIST'` AND `currentTemplate` exists
- **Evaluation Items**: Shown if `formType === 'REGULAR'` OR no template exists

```vue
<!-- Checklist Template Form (shown only if form_type is CHECKLIST) -->
<QcChecklistTemplate
    v-if="currentTemplate && !templateLoading && formType === 'CHECKLIST'"
    ...
/>

<!-- Evaluation Items (shown when no template or form_type is REGULAR) -->
<template v-if="(!currentTemplate || formType === 'REGULAR') && !templateLoading">
```

#### Updated `canComplete` Computed Property (lines 421-442)

- Branch must be selected
- For CHECKLIST form type: Only requires branch selection
- For REGULAR form type: Requires all evaluation items to be scored

#### Updated `submitForm()` Function (line 803)

- Validates checklist template only if `formType === 'CHECKLIST'`

#### Updated Error Messages (lines 276-281)

- Shows appropriate messages based on form state
- Indicates when form is loading

## User Flow

### Step 1: Initial Page Load
- User sees ONLY the Branch selection dropdown
- All other sections are hidden:
  - ✓ Progress bar (hidden)
  - ✓ Evaluation items (hidden)
  - ✓ Total score display (hidden)
  - ✓ Extra points section (hidden)
  - ✓ Photo documentation (hidden)
  - ✓ Additional comments (hidden)
  - ✓ Save/Submit buttons (hidden)

### Step 2: Branch Selection
- User selects a branch
- Loading indicator appears
- API call to `/api/quality-evaluations/get-template?branch_id={branchId}`

### Step 3: Form Type Determination
- Backend checks branch's `country_id`
- If `country_id === 5`: Returns form type `'REGULAR'`
- Otherwise: Returns form type `'CHECKLIST'` (if template exists)

### Step 4: Form Display
- **For CHECKLIST**: Displays the checklist template with sections and questions
- **For REGULAR**: Displays:
  - Progress bar
  - Evaluation items with scoring
  - Total score display
  - Extra points section
  - Photo documentation
  - Additional comments
  - Save/Submit buttons

### Step 5: Submission
- User completes the form based on its type
- Submit button becomes enabled when form is complete
- Form is submitted with appropriate data structure

## Special Rules

### Country ID = 5 Override
- When a branch has `country_id = 5`, the form type is forced to `'REGULAR'`
- This happens regardless of whether a template exists
- The override is applied on the backend in the `getTemplate()` endpoint

## API Response Format

### Success Response (200 OK)
```json
{
    "id": 1,
    "name": "Template Name",
    "name_ar": "اسم القالب",
    "localized_name": "Template Name",
    "form_type": "CHECKLIST",
    "sections": [
        {
            "id": 1,
            "name": "Section Name",
            "name_ar": "اسم القسم",
            "localized_name": "Section Name",
            "sort_order": 1,
            "questions": [...]
        }
    ]
}
```

### No Template Response (404 Not Found)
- Frontend defaults to REGULAR form type
- Displays traditional evaluation items

## Benefits

1. **Progressive Disclosure**: Users only see relevant form fields after branch selection
2. **Flexible Form Types**: Supports both CHECKLIST and REGULAR evaluation types
3. **Country-Specific Rules**: Enforces REGULAR form type for country_id = 5
4. **Better UX**: Reduces cognitive load by showing forms progressively
5. **Mobile-Friendly**: Progressive approach works well on mobile devices
6. **Backward Compatible**: Existing evaluation items still work as fallback

## Visibility Conditions

### Sections Hidden Until Branch Selection

All the following sections are hidden until `form.branch_id` is set:

| Section | Condition | Line |
|---------|-----------|------|
| Progress Bar | `form.branch_id && formType === 'REGULAR'` | 19 |
| Progress Text | `form.branch_id && formType === 'REGULAR'` | 25 |
| Evaluation Items | `form.branch_id && formType === 'REGULAR' && !templateLoading` | 101 |
| Total Score Display | `form.branch_id && formType === 'REGULAR' && totalScore > 0` | 157 |
| Extra Points Section | `form.branch_id && formType === 'REGULAR'` | 171 |
| Photo Documentation | `form.branch_id` | 219 |
| Additional Comments | `form.branch_id` | 238 |
| Action Buttons | `form.branch_id` | 253 |

### Checklist Template Visibility

| Section | Condition | Line |
|---------|-----------|------|
| Checklist Template | `currentTemplate && !templateLoading && formType === 'CHECKLIST'` | 92 |

## Testing Checklist

- [ ] Initial page load shows ONLY branch selection dropdown
- [ ] All other sections are hidden initially
- [ ] Selecting a branch triggers template loading
- [ ] Loading indicator appears during API call
- [ ] CHECKLIST form displays for branches without country_id = 5
- [ ] REGULAR form displays for branches with country_id = 5
- [ ] REGULAR form displays when no template exists
- [ ] Photo documentation section appears after branch selection
- [ ] Comments section appears after branch selection
- [ ] Save/Submit buttons appear after branch selection
- [ ] Submit button is disabled until form is complete
- [ ] Form submission works for both CHECKLIST and REGULAR types
- [ ] Error messages display appropriately
- [ ] Deselecting branch hides all form sections again

