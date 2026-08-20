<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class QualityEvaluationPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'quality_evaluation_id',
        'section_id',
        'filename',
        'original_filename',
        'file_path',
        'file_size',
        'mime_type',
        'uploaded_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'uploaded_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = [
        'url',
        'formatted_file_size',
    ];

    /**
     * Get the quality evaluation that owns this photo.
     */
    public function qualityEvaluation(): BelongsTo
    {
        return $this->belongsTo(QualityEvaluation::class);
    }

    /**
     * Get the QC section this photo is associated with (for checklist evaluations).
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(QcSection::class, 'section_id');
    }

    /**
     * Get the full URL for the photo.
     */
    public function getUrlAttribute(): string
    {
        // Use the dedicated route to serve photos
        // This ensures proper access control and works regardless of symbolic link status
        return route('quality-evaluations.photos.serve', $this->id);
    }

    /**
     * Get the full file path for the photo.
     */
    public function getFullPathAttribute(): string
    {
        return Storage::disk('public')->path($this->file_path);
    }

    /**
     * Get human-readable file size.
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Check if the photo file exists on disk.
     */
    public function fileExists(): bool
    {
        return Storage::disk('public')->exists($this->file_path);
    }

    /**
     * Delete the photo file from storage.
     */
    public function deleteFile(): bool
    {
        if ($this->fileExists()) {
            return Storage::disk('public')->delete($this->file_path);
        }
        
        return true;
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically delete the file when the model is deleted
        static::deleting(function ($photo) {
            $photo->deleteFile();
        });
    }
}
