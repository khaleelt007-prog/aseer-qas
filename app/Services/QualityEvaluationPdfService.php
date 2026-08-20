<?php

namespace App\Services;

use App\Models\QualityEvaluation;
use Omaralalwi\Gpdf\Gpdf;
use Illuminate\Support\Facades\View;

class QualityEvaluationPdfService
{
    /**
     * Generate PDF for quality evaluation.
     * Returns array with 'path' and 'filename' keys.
     */
    public function generatePdf(QualityEvaluation $evaluation, array $evaluationItems): array
    {
        // Validate required data
        $this->validateEvaluationData($evaluation, $evaluationItems);

        try {
            // Initialize Gpdf instance
            $gpdf = app(Gpdf::class);

            // Load evaluation photos
            $evaluation->load('photos');
            $photos = $this->preparePhotosForPdf($evaluation->photos);

            // Generate HTML content
            $html = View::make('pdf.quality-evaluation', [
                'evaluation' => $evaluation,
                'evaluationItems' => $evaluationItems,
                'branch' => $evaluation->branch,
                'user' => $evaluation->user,
                'photos' => $photos,
                'pdfService' => $this,
            ])->render();

            // Generate filename and save to permanent storage
            $filename = 'evaluation_' . $evaluation->id . '_' . time() . '.pdf';
            $pdfPath = storage_path('app/public/pdfs/' . $filename);

            // Ensure pdfs directory exists
            if (!file_exists(dirname($pdfPath))) {
                if (!mkdir(dirname($pdfPath), 0755, true)) {
                    throw new \Exception('Failed to create PDFs directory for permanent storage.');
                }
            }

            // Generate PDF using Gpdf and save to file
            $pdfContent = $gpdf->generate($html);
            file_put_contents($pdfPath, $pdfContent);

            // Verify file was created
            if (!file_exists($pdfPath)) {
                throw new \Exception('PDF file was not created successfully.');
            }

            return [
                'path' => $pdfPath,
                'filename' => $filename
            ];

        } catch (\Exception $e) {
            throw new \Exception('An error occurred during PDF generation: ' . $e->getMessage());
        }
    }

    /**
     * Generate PDF for checklist evaluation.
     * Returns array with 'path' and 'filename' keys.
     */
    public function generateChecklistPdf(QualityEvaluation $evaluation): array
    {
        // Validate required data for checklist
        $this->validateChecklistData($evaluation);

        try {
            // Initialize Gpdf instance
            $gpdf = app(Gpdf::class);

            // Load evaluation photos with sections
            $evaluation->load(['photos', 'template.sections']);
            $photos = $this->preparePhotosForPdf($evaluation->photos);

            // Organize photos by section
            $photosBySection = $this->organizePhotosBySection($evaluation, $photos);

            // Extract general photos (where section_id is NULL)
            $generalPhotos = $this->extractGeneralPhotos($photos);

            // Generate HTML content for checklist template
            $html = View::make('pdf.quality-evaluation-checklist', [
                'evaluation' => $evaluation,
                'branch' => $evaluation->branch,
                'user' => $evaluation->user,
                'photos' => $photos,
                'photosBySection' => $photosBySection,
                'generalPhotos' => $generalPhotos,
                'pdfService' => $this,
            ])->render();

            // Generate filename and save to permanent storage
            $filename = 'evaluation_' . $evaluation->id . '_' . time() . '.pdf';
            $pdfPath = storage_path('app/public/pdfs/' . $filename);

            // Ensure pdfs directory exists
            if (!file_exists(dirname($pdfPath))) {
                if (!mkdir(dirname($pdfPath), 0755, true)) {
                    throw new \Exception('Failed to create PDFs directory for permanent storage.');
                }
            }

            // Generate PDF using Gpdf and save to file
            $pdfContent = $gpdf->generate($html);
            file_put_contents($pdfPath, $pdfContent);

            // Verify file was created
            if (!file_exists($pdfPath)) {
                throw new \Exception('PDF file was not created successfully.');
            }

            return [
                'path' => $pdfPath,
                'filename' => $filename
            ];

        } catch (\Exception $e) {
            throw new \Exception('An error occurred during checklist PDF generation: ' . $e->getMessage());
        }
    }

    /**
     * Get the public URL for a PDF file.
     */
    public function getPdfUrl(string $pdfPath): string
    {
        $filename = basename($pdfPath);
        return asset('storage/pdfs/' . $filename);
    }

    /**
     * Get quality score color class based on percentage.
     */
    public function getScoreColorClass(float $percentage): string
    {
        if ($percentage >= 85) {
            return 'score-excellent';
        } elseif ($percentage >= 70) {
            return 'score-good';
        } elseif ($percentage >= 50) {
            return 'score-acceptable';
        } else {
            return 'score-poor';
        }
    }

    /**
     * Format date for PDF display.
     */
    public function formatDate($date): string
    {
        if (!$date) {
            return '';
        }

        return \Carbon\Carbon::parse($date)->format('Y/m/d');
    }

    /**
     * Get quality level text in Arabic based on score.
     */
    public function getQualityLevelText(float $percentage): string
    {
        if ($percentage >= 85) {
            return 'جودة ممتازة';
        } elseif ($percentage >= 70) {
            return 'جودة جيدة';
        } elseif ($percentage >= 50) {
            return 'جودة مقبولة';
        } else {
            return 'يحتاج إلى تحسين';
        }
    }

    /**
     * Calculate base score without extra points.
     */
    public function getBaseScore(QualityEvaluation $evaluation): float
    {
        return ($evaluation->total_score ?? 0) - ($evaluation->extra_points ?? 0);
    }

    /**
     * Check if evaluation has any zero override items.
     */
    public function hasZeroOverride(array $evaluationItems): bool
    {
        foreach ($evaluationItems as $item) {
            if ($item['overwrite_total_score_if_zero'] && $item['achieved'] === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get localized item name based on current locale.
     */
    public function getLocalizedItemName(array $item): string
    {
        $locale = app()->getLocale();

        if ($locale === 'ar' && !empty($item['title_ar'])) {
            return $item['title_ar'];
        }

        return $item['title'] ?? '';
    }

    /**
     * Format score to show fractions if present.
     */
    public function formatScore($score): string
    {
        if ($score === null || $score === '') {
            return '0';
        }

        $num = (float) $score;

        // Check if it's an integer
        if ($num == (int) $num) {
            return (string) (int) $num;
        }

        // Return with one decimal place if it's a fraction
        return number_format($num, 1, '.', '');
    }

    /**
     * Validate evaluation data before PDF generation.
     */
    private function validateEvaluationData(QualityEvaluation $evaluation, array $evaluationItems): void
    {
        if (!$evaluation) {
            throw new \InvalidArgumentException('Evaluation data is required.');
        }

        if (!$evaluation->branch) {
            throw new \InvalidArgumentException('Branch information is missing from evaluation.');
        }

        if (empty($evaluationItems)) {
            throw new \InvalidArgumentException('No evaluation items found for this evaluation.');
        }

        // Check if Cairo font files exist (Gpdf fonts)
        $fontPath = public_path('vendor/gpdf/fonts/Cairo-Normal.ttf');
        if (!file_exists($fontPath)) {
            throw new \Exception('Cairo font file not found. Please ensure Gpdf fonts are properly published.');
        }
    }

    /**
     * Validate checklist evaluation data before PDF generation.
     */
    private function validateChecklistData(QualityEvaluation $evaluation): void
    {
        if (!$evaluation) {
            throw new \InvalidArgumentException('Evaluation data is required.');
        }

        if (!$evaluation->branch) {
            throw new \InvalidArgumentException('Branch information is missing from evaluation.');
        }

        if (!$evaluation->template) {
            throw new \InvalidArgumentException('Template information is missing from checklist evaluation.');
        }

        // Check if Cairo font files exist (Gpdf fonts)
        $fontPath = public_path('vendor/gpdf/fonts/Cairo-Normal.ttf');
        if (!file_exists($fontPath)) {
            throw new \Exception('Cairo font file not found. Please ensure Gpdf fonts are properly published.');
        }
    }

    /**
     * Organize photos by section for checklist evaluations.
     */
    private function organizePhotosBySection(QualityEvaluation $evaluation, array $photos): array
    {
        $photosBySection = [];

        // Initialize sections
        if ($evaluation->template && $evaluation->template->sections) {
            foreach ($evaluation->template->sections as $section) {
                $photosBySection[$section->id] = [
                    'section' => $section,
                    'photos' => []
                ];
            }
        }

        // Organize photos by section_id
        foreach ($photos as $photo) {
            if (isset($photo['section_id']) && $photo['section_id']) {
                if (!isset($photosBySection[$photo['section_id']])) {
                    $photosBySection[$photo['section_id']] = [
                        'section' => null,
                        'photos' => []
                    ];
                }
                $photosBySection[$photo['section_id']]['photos'][] = $photo;
            }
        }

        return $photosBySection;
    }

    /**
     * Extract general photos (where section_id is NULL) from prepared photos.
     */
    private function extractGeneralPhotos(array $photos): array
    {
        $generalPhotos = [];

        foreach ($photos as $photo) {
            if (!isset($photo['section_id']) || $photo['section_id'] === null) {
                $generalPhotos[] = $photo;
            }
        }

        return $generalPhotos;
    }

    /**
     * Prepare photos for PDF generation by converting them to base64 data URIs.
     */
    private function preparePhotosForPdf($photos): array
    {
        $preparedPhotos = [];

        foreach ($photos as $photo) {
            if (!$photo->fileExists()) {
                continue; // Skip missing files
            }

            try {
                $filePath = $photo->full_path;
                $imageData = file_get_contents($filePath);

                if ($imageData === false) {
                    continue; // Skip if can't read file
                }

                // Convert to base64 data URI for embedding in PDF
                $base64 = base64_encode($imageData);
                $dataUri = 'data:' . $photo->mime_type . ';base64,' . $base64;

                // Get image dimensions for proper sizing
                $imageInfo = getimagesize($filePath);
                $width = $imageInfo[0] ?? 0;
                $height = $imageInfo[1] ?? 0;

                // Calculate appropriate display size for A4 page (70% of ~600px content area = ~420px)
                $maxWidth = 420;
                $maxHeight = 350;

                if ($width > $maxWidth || $height > $maxHeight) {
                    $ratio = min($maxWidth / $width, $maxHeight / $height);
                    $displayWidth = round($width * $ratio);
                    $displayHeight = round($height * $ratio);
                } else {
                    $displayWidth = $width;
                    $displayHeight = $height;
                }

                $preparedPhotos[] = [
                    'id' => $photo->id,
                    'section_id' => $photo->section_id,
                    'original_filename' => $photo->original_filename,
                    'data_uri' => $dataUri,
                    'width' => $displayWidth,
                    'height' => $displayHeight,
                    'uploaded_at' => $photo->uploaded_at,
                ];

            } catch (\Exception $e) {
                // Log error but continue with other photos
                \Log::warning('Failed to prepare photo for PDF', [
                    'photo_id' => $photo->id,
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        return $preparedPhotos;
    }
}
