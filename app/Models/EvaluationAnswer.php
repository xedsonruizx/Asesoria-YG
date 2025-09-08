<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'evaluation_id',
        'question_id',
        'answer_value',
        'points_earned',
    ];

    protected $casts = [
        'answer_value' => 'array',
    ];

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(EvaluationQuestion::class, 'question_id');
    }

    public function calculatePoints(): void
    {
        $question = $this->question;
        $answer = $this->answer_value;
        $points = 0;

        if ($question->type === 'yes_no') {
            $points = $answer === true ? $question->points : 0;
        } elseif ($question->type === 'checkbox') {
            $points = is_array($answer) && count($answer) > 0 ? $question->points : 0;
        } elseif (!empty($answer)) {
            $points = $question->points;
        }

        $this->update(['points_earned' => $points]);
    }
}