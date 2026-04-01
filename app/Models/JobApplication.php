<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Database\Factories\JobApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'company_name',
    'job_title',
    'job_url',
    'location',
    'date_applied',
    'status',
    'salary_min',
    'salary_max',
    'interest',
    'notes',
])]
class JobApplication extends Model
{
    /** @use HasFactory<JobApplicationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'date_applied' => 'date',
            'salary_min' => 'integer',
            'salary_max' => 'integer',
            'interest' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Format salary range as "$120k – $180k" or "—" if both null.
     */
    public function salaryRange(): string
    {
        if ($this->salary_min === null && $this->salary_max === null) {
            return '—';
        }

        $format = fn (int $value): string => '$'.number_format($value / 1000).'k';

        if ($this->salary_min !== null && $this->salary_max !== null) {
            return $format($this->salary_min).' – '.$format($this->salary_max);
        }

        return $this->salary_min !== null ? $format($this->salary_min) : $format($this->salary_max);
    }
}
