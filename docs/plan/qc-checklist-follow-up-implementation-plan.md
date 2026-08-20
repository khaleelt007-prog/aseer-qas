# QC Checklist Follow-Up Implementation Plan

## Objective
Build a new follow-up module/page for checklist-based quality evaluations so the QC team can track bad checklist answers (`0.5` or `0`), add deadlines and follow-up comments, mark issues as solved/skipped, and automatically raise a warning on overdue evaluations.

## Scope Summary
The new page will only include `QualityEvaluation` records where:
- `type = 'checklist'`
- `status = 'completed'`
- the evaluation has at least one related `QcAnswer` with `answer_value IN ('0.5', '0', 0.5, 0)`

The follow-up detail page will:
- group issues by checklist section
- show only bad-answer questions (`0.5` or `0`)
- show section comment questions (`q_type = 2`) for context
- show section attachments/photos for context
- allow QC follow-up actions per bad question

## Main User Flow
1. QC user opens the new follow-up list page.
2. User filters by country / brand / branch / start date / end date using the same UX as `resources/js/Components/QualityFilter.vue`.
3. User sees checklist evaluations that contain at least one bad answer.
4. Warning evaluations appear at the top of the list and use a distinct warning style.
5. User opens an evaluation.
6. User sees only problematic questions, grouped by section, with section comments and section photos.
7. For each bad question, user can:
   - set/update expected deadline (date only)
   - add multiple follow-up comments
   - label each comment as `branch_reply` or `qc_comment`
   - mark the issue as solved
   - mark the issue as skipped / warning ignored
8. When solved, store the solved timestamp.
9. Hourly scheduled processing marks parent evaluations with warning flag when an open follow-up passes its deadline.

## Proposed Data Model
### 1) `quality_evaluations` changes
Add warning tracking fields in `database/sql/db_changes.sql`:
- `warning_flag` TINYINT(1) NOT NULL DEFAULT 0
- `warning_flagged_at` TIMESTAMP NULL

Purpose:
- surface overdue follow-up evaluations at the top of the list
- allow distinct UI color/state for urgent items

### 2) New table: `qc_answer_follow_ups`
One record per bad checklist answer that needs tracking.

Suggested columns:
- `id`
- `qc_answer_id` FK -> `qc_answers.id`
- `quality_evaluation_id` FK -> `quality_evaluations.id`
- `question_id` FK -> `qc_questions.id`
- `section_id` FK -> `qc_sections.id`
- `expected_deadline` DATE NULL
- `status` VARCHAR/ENUM-like string: `open`, `solved`, `skipped`
- `solved_at` TIMESTAMP NULL
- `skipped_at` TIMESTAMP NULL
- `last_commented_at` TIMESTAMP NULL
- `created_by` nullable FK -> `users.id`
- timestamps

Recommended constraints/indexes:
- unique index on `qc_answer_id` (one follow-up thread per bad answer)
- indexes on `quality_evaluation_id`, `status`, `expected_deadline`

### 3) New table: `qc_answer_follow_up_comments`
Stores follow-up history per question.

Suggested columns:
- `id`
- `follow_up_id` FK -> `qc_answer_follow_ups.id`
- `comment_type` string: `branch_reply` / `qc_comment`
- `comment_date` TIMESTAMP
- `comment_text` TEXT
- `created_by` nullable FK -> `users.id`
- timestamps

Recommended indexes:
- `follow_up_id`
- `(follow_up_id, comment_date)`

## Backend Implementation Plan
### 1) Models / Relationships
Add models and relationships for:
- `QualityEvaluation -> hasMany(QcAnswerFollowUp)`
- `QcAnswer -> hasOne(QcAnswerFollowUp)`
- `QcAnswerFollowUp -> belongsTo(QualityEvaluation, QcAnswer, QcQuestion, QcSection, User)`
- `QcAnswerFollowUp -> hasMany(QcAnswerFollowUpComment)`
- `QcAnswerFollowUpComment -> belongsTo(QcAnswerFollowUp, User)`

Update `QualityEvaluation` with helpers/scopes for:
- follow-up eligible evaluations
- warning-first ordering
- overdue/open follow-up detection

### 2) Controller / Service Layer
Create a dedicated follow-up controller instead of overloading `QualityEvaluationController`.

Planned responsibilities:
- `index()` -> return eligible evaluations with filters, counts, warning-first sorting
- `show()` -> return grouped issue view for one evaluation
- `upsertQuestionFollowUp()` -> create/update deadline or status for one bad answer
- `storeQuestionComment()` -> append comment row to one follow-up thread

Create a small service class for:
- querying follow-up eligible evaluations
- formatting grouped section/question payload for the page
- synchronizing warning flag status from follow-up state

### 3) Detail Page Data Rules
For checklist evaluations only:
- include sections only if they contain at least one bad point-based answer
- include only questions where `q_type = 1` and answer is `0.5` or `0`
- also include same-section text questions (`q_type = 2`) as section comments/context
- include section photos from `quality_evaluation_photos.section_id`
- include follow-up status, deadline, solved timestamp, and comment history for each bad answer

### 4) Permissions / Access
Reuse current quality evaluation permission checks and branch access rules:
- list/show follow-up pages require existing view permission + branch access
- update actions require existing edit permission + branch access

## Frontend Implementation Plan
### 1) New Pages
Create a new page set, likely under:
- `resources/js/Pages/QualityEvaluationFollowUp/Index.vue`
- `resources/js/Pages/QualityEvaluationFollowUp/Show.vue`

### 2) Follow-Up List Page
Reuse the same filter behavior/pattern as `resources/js/Components/QualityFilter.vue`.

List item content should show:
- branch name
- created/completed dates
- checklist score
- count of bad questions
- count of open / solved / skipped follow-ups
- warning badge if overdue follow-up exists

Sorting:
1. warning evaluations first
2. then newest / most recent activity

### 3) Follow-Up Show Page
Use the existing checklist show page structure as reference from `resources/js/Pages/QualityEvaluation/Show.vue`, but change the content to:
- show only problematic sections/questions
- highlight bad answers visually (`0` = red, `0.5` = yellow)
- show section comment text answers
- show section attachments/photos
- show follow-up timeline/comments under each bad question
- provide actions to set deadline, add comment, mark solved, mark skipped

## Scheduler / Cron Plan
Create an Artisan command for hourly processing.

Hourly job behavior:
- find `qc_answer_follow_ups` where `status = open`
- deadline is set
- `expected_deadline < today/current processing threshold`
- set parent `quality_evaluations.warning_flag = 1`
- set `warning_flagged_at` when first raised
- clear `warning_flag` when no overdue open follow-ups remain

Register the command in Laravel scheduling (`routes/console.php`) with hourly execution.
Server cron will continue calling Laravel scheduler as usual.

## SQL Delivery Plan
All schema changes will be written to:
- `database/sql/db_changes.sql`

No migration files will be created.

## Validation Rules
- Follow-up actions are only allowed for bad checklist answers (`0` or `0.5`)
- `expected_deadline` must be a valid date
- comment text is required when adding a comment
- comment type must be `branch_reply` or `qc_comment`
- marking solved stores `solved_at`
- marking skipped stores `skipped_at` and removes it from warning processing

## UI/Translation Notes
Add new translation keys for:
- follow-up list/show labels
- deadline/status badges
- branch reply / QC comment labels
- solved / skipped / overdue states
- warning highlight text

## Testing Plan
1. Backend feature tests for:
- follow-up eligible evaluation query
- detail payload only includes bad questions
- follow-up create/update/comment actions
- warning flag hourly processing

2. Frontend/manual verification for:
- filters
- warning-first ordering
- answer highlight colors
- section comments/photos visibility
- question follow-up workflow

## Assumptions To Confirm Before Implementation
1. `branch_reply` comments are entered manually by QC user, not directly by branch users.
2. One follow-up thread per bad answer is enough.
3. Solved/skipped questions should remain visible in history, but warning logic should only consider `open` items.
4. The warning should be raised at evaluation level if any open question has passed its deadline.
5. Preferred column name will be `warning_flag` even though the request text used `warring`.
