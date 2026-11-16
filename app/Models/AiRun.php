<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One AI content generation run (whole site or selected sections). */
class AiRun extends Model
{
    public const QUEUED = 'queued';

    public const RUNNING = 'running';

    public const DONE = 'done';

    public const FAILED = 'failed';

    protected $fillable = [
        'site_id', 'section_ids', 'status', 'progress', 'stage', 'options', 'usage', 'error', 'started_at', 'finished_at',
    ];

    protected $attributes = [
        'status' => self::QUEUED,
        'progress' => 0,
    ];

    protected function casts(): array
    {
        return [
            'section_ids' => 'array',
            'options' => 'array',
            'usage' => 'array',
            'progress' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', [self::QUEUED, self::RUNNING]);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::QUEUED, self::RUNNING], true);
    }

    public function isWholeSite(): bool
    {
        return $this->section_ids === null;
    }

    public function stage(string $text, ?int $progress = null): void
    {
        $this->forceFill(array_filter(['stage' => $text, 'progress' => $progress], fn ($v) => $v !== null))->save();
    }
}
