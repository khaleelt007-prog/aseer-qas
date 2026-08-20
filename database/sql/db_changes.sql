-- 2025-07-19: Add allow_purchasing_on_day_close setting to branch settings
-- This setting enables users to submit purchasing transactions on day close
ALTER TABLE `sma_pos_settings` ADD COLUMN `allow_purchasing_on_day_close` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Allow purchasing transaction submission on day close (0=No, 1=Yes)';

-- 2025-10-16: Quality Control Checklist Template System
-- Tables for managing QC templates with sections and questions

CREATE TABLE IF NOT EXISTS qc_templates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  brand_id INT UNSIGNED NOT NULL,
  name_en VARCHAR(255) NOT NULL,
  name_ar VARCHAR(255) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_brand (brand_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS qc_sections (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  template_id INT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  name_ar VARCHAR(255) NOT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_qc_sections_template
    FOREIGN KEY (template_id) REFERENCES qc_templates(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  KEY idx_template_order (template_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS qc_questions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  section_id INT UNSIGNED NOT NULL,
  q_type TINYINT UNSIGNED NOT NULL COMMENT '1=Point-Based (1 PT, 0.5 PT, 0 PT, N/A), 2=Text',
  name VARCHAR(500) NOT NULL,
  name_ar VARCHAR(500) NOT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 1,
  is_required TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_qc_questions_section
    FOREIGN KEY (section_id) REFERENCES qc_sections(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  KEY idx_section_order (section_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS qc_answers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  quality_evaluation_id INT UNSIGNED NOT NULL,
  question_id INT UNSIGNED NOT NULL,
  answer_value VARCHAR(1000),
  achieved_score FLOAT NULL COMMENT 'Score achieved for this answer (e.g., 0 for no, 5 for yes on a 5-point question)',
  max_score FLOAT NULL COMMENT 'Maximum possible score for this question',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_qc_answers_evaluation
    FOREIGN KEY (quality_evaluation_id) REFERENCES quality_evaluations(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_qc_answers_question
    FOREIGN KEY (question_id) REFERENCES qc_questions(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  KEY idx_evaluation (quality_evaluation_id),
  KEY idx_question (question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add type and template_id columns to quality_evaluations table
ALTER TABLE quality_evaluations ADD COLUMN type VARCHAR(50) DEFAULT 'regular' COMMENT 'Type of evaluation: regular or checklist' AFTER status;
ALTER TABLE quality_evaluations ADD COLUMN template_id INT UNSIGNED COMMENT 'Reference to QC template if type is checklist' AFTER type;
ALTER TABLE quality_evaluations ADD CONSTRAINT fk_quality_evaluations_template
  FOREIGN KEY (template_id) REFERENCES qc_templates(id)
  ON DELETE SET NULL ON UPDATE CASCADE;

-- 2025-10-17: Add section_id to quality_evaluation_photos for section-based photo organization
-- This allows photos to be associated with specific checklist sections
ALTER TABLE quality_evaluation_photos ADD COLUMN section_id INT UNSIGNED COMMENT 'Reference to QC section for checklist evaluations (NULL for regular evaluations)' AFTER quality_evaluation_id;
ALTER TABLE quality_evaluation_photos ADD CONSTRAINT fk_quality_evaluation_photos_section
  FOREIGN KEY (section_id) REFERENCES qc_sections(id)
  ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE quality_evaluation_photos ADD KEY idx_section (section_id);

-- 2025-10-17: Add max_score column to quality_evaluations for checklist scoring
-- Stores the denominator (total possible score) for checklist evaluations
-- For checklist type: max_score = count of answered questions (excluding N/A)
-- For regular type: max_score remains NULL
ALTER TABLE quality_evaluations ADD COLUMN max_score INT UNSIGNED COMMENT 'Maximum possible score (denominator) for checklist evaluations' AFTER total_score;

-- 2025-10-27: Add achieved_score and max_score columns to qc_answers for individual question scoring
-- These columns are kept for backward compatibility but are no longer used for point-based questions
-- For point-based questions (q_type=1), the answer_value directly contains the point value (1, 0.5, 0, or null for N/A)
-- The total score is calculated by summing all point values from answered questions
ALTER TABLE qc_answers ADD COLUMN achieved_score FLOAT NULL COMMENT 'Deprecated: Score achieved for this answer (kept for backward compatibility)' AFTER answer_value;
ALTER TABLE qc_answers ADD COLUMN max_score FLOAT NULL COMMENT 'Deprecated: Maximum possible score for this question (kept for backward compatibility)' AFTER achieved_score;

-- 2025-11-13: Add answer_type column to qc_templates for template-level answer type configuration
-- Allows templates to specify whether questions use Points (1 PT, 0.5 PT, 0 PT, N/A) or Yes/No (Yes, No, N/A) answer choices
-- answer_type: 'Points' = point-based system, 'Yes/No' = binary choice system
ALTER TABLE qc_templates ADD COLUMN answer_type VARCHAR(50) NOT NULL DEFAULT 'Points' COMMENT 'Answer type for template: Points (1 PT, 0.5 PT, 0 PT, N/A) or Yes/No (Yes, No, N/A)' AFTER is_active;

-- 2026-04-14: Checklist follow-up management and warning processing
ALTER TABLE quality_evaluations ADD COLUMN warning_flag TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Marks evaluations with overdue unresolved follow-up issues' AFTER pdf_filename;
ALTER TABLE quality_evaluations ADD COLUMN warning_flagged_at TIMESTAMP NULL COMMENT 'When the evaluation was flagged for overdue follow-up' AFTER warning_flag;

CREATE TABLE IF NOT EXISTS qc_answer_follow_ups (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  qc_answer_id INT UNSIGNED NOT NULL,
  quality_evaluation_id INT UNSIGNED NOT NULL,
  question_id INT UNSIGNED NOT NULL,
  section_id INT UNSIGNED NOT NULL,
  expected_deadline DATE NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'open' COMMENT 'open, solved, skipped',
  solved_at TIMESTAMP NULL,
  skipped_at TIMESTAMP NULL,
  last_commented_at TIMESTAMP NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_qc_answer_follow_ups_answer
    FOREIGN KEY (qc_answer_id) REFERENCES qc_answers(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_qc_answer_follow_ups_question
    FOREIGN KEY (question_id) REFERENCES qc_questions(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_qc_answer_follow_ups_section
    FOREIGN KEY (section_id) REFERENCES qc_sections(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY uq_qc_answer_follow_ups_answer (qc_answer_id),
  KEY idx_qc_answer_follow_ups_evaluation (quality_evaluation_id),
  KEY idx_qc_answer_follow_ups_status_deadline (status, expected_deadline)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS qc_answer_follow_up_comments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  follow_up_id INT UNSIGNED NOT NULL,
  comment_type VARCHAR(50) NOT NULL COMMENT 'branch_reply or qc_comment',
  comment_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  comment_text TEXT NOT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_qc_answer_follow_up_comments_follow_up
    FOREIGN KEY (follow_up_id) REFERENCES qc_answer_follow_ups(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  KEY idx_qc_answer_follow_up_comments_follow_up (follow_up_id),
  KEY idx_qc_answer_follow_up_comments_follow_up_date (follow_up_id, comment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 2026-07-12: Super Admin Template Setup
-- Adds many-to-many country/brand assignment and per-question type metadata
-- for Yes/No, Multi-Select, and manual Score questions.

CREATE TABLE IF NOT EXISTS qc_template_country (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  template_id INT UNSIGNED NOT NULL,
  country_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_qc_template_country (template_id, country_id),
  KEY idx_qc_template_country_country (country_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS qc_template_brand (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  template_id INT UNSIGNED NOT NULL,
  brand_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_qc_template_brand (template_id, brand_id),
  KEY idx_qc_template_brand_brand (brand_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE qc_questions
  ADD COLUMN question_type VARCHAR(50) NULL COMMENT 'points, yes_no, multi_select, score, comment; NULL keeps legacy q_type behavior' AFTER q_type,
  ADD COLUMN options JSON NULL COMMENT 'Options for multi-select questions' AFTER name_ar,
  ADD COLUMN score_value DECIMAL(8,2) NULL COMMENT 'Maximum/manual score for score questions; yes/no awards this value for Yes' AFTER options,
  ADD COLUMN allow_manual_score TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Shows a manual score input when enabled for score questions' AFTER score_value;

-- Backfill template setup question types for the QC evaluation form.
-- Legacy q_type=1 questions should keep the old point-based 1 PT / 0.5 PT / 0 PT flow unless the template was explicitly Yes/No.
UPDATE qc_questions
SET question_type = 'comment'
WHERE question_type IS NULL
  AND q_type = 2;

UPDATE qc_questions q
JOIN qc_sections s ON q.section_id = s.id
JOIN qc_templates t ON s.template_id = t.id
SET q.question_type = 'yes_no'
WHERE q.question_type IS NULL
  AND q.q_type = 1
  AND t.answer_type = 'Yes/No';

UPDATE qc_questions
SET question_type = 'points'
WHERE question_type IS NULL
  AND q_type = 1;

-- Restore one optional section comment textarea per checklist section when missing.
INSERT INTO qc_questions (
  section_id,
  q_type,
  question_type,
  name,
  name_ar,
  sort_order,
  is_required,
  created_at,
  updated_at
)
SELECT
  s.id,
  2,
  'comment',
  'Section Comment',
  'تعليق القسم',
  COALESCE(MAX(q.sort_order), 0) + 1,
  0,
  NOW(),
  NOW()
FROM qc_sections s
LEFT JOIN qc_questions q ON q.section_id = s.id
WHERE NOT EXISTS (
  SELECT 1
  FROM qc_questions existing_comment
  WHERE existing_comment.section_id = s.id
    AND (existing_comment.question_type = 'comment' OR existing_comment.q_type = 2)
)
GROUP BY s.id;

UPDATE qc_questions q
JOIN qc_sections s ON s.id = q.section_id
SET q.question_type = 'points'
WHERE s.template_id IN (1, 2, 3)
  AND q.q_type = 1;

-- 2026-07-26: Company QC report email delivery settings
-- One setting per company controls TO/CC recipients and whether completed QC reports are emailed.
CREATE TABLE IF NOT EXISTS qc_report_email_settings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id INT NOT NULL,
  to_emails JSON NOT NULL,
  cc_emails JSON NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_qc_report_email_settings_company (company_id),
  CONSTRAINT fk_qc_report_email_settings_company
    FOREIGN KEY (company_id) REFERENCES sma_company(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Successful QC report email delivery audit log.
CREATE TABLE IF NOT EXISTS qc_report_email_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  quality_evaluation_id BIGINT UNSIGNED NOT NULL,
  company_id INT NULL,
  to_emails JSON NOT NULL,
  cc_emails JSON NULL,
  sent_at TIMESTAMP NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_qc_report_email_logs_evaluation
    FOREIGN KEY (quality_evaluation_id) REFERENCES quality_evaluations(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_qc_report_email_logs_company
    FOREIGN KEY (company_id) REFERENCES sma_company(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  KEY idx_qc_report_email_logs_evaluation_sent (quality_evaluation_id, sent_at),
  KEY idx_qc_report_email_logs_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;