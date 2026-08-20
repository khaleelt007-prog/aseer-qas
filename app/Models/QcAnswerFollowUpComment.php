<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QcAnswerFollowUpComment extends Model
{
    use HasFactory;

    protected $table = 'qc_answer_follow_up_comments';

    protected $fillable = [
        'follow_up_id',
        'comment_type',
        'comment_date',
        'comment_text',
        'created_by',
    ];

    protected $casts = [
        'comment_date' => 'datetime',
    ];

    public function followUp(): BelongsTo
    {
        return $this->belongsTo(QcAnswerFollowUp::class, 'follow_up_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}