<?php

namespace App\Models;

use App\Enums\Device;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id', 'visitor_uuid', 'ip_hash', 'user_agent', 'device', 'country', 'city',
        'referrer', 'landing_path', 'started_at', 'last_ping_at', 'ended_at',
        'duration_seconds', 'is_return', 'summary_sent_at',
    ];

    protected $attributes = [
        'duration_seconds' => 0,
        'is_return' => false,
    ];

    protected function casts(): array
    {
        return [
            'device' => Device::class,
            'started_at' => 'datetime',
            'last_ping_at' => 'datetime',
            'ended_at' => 'datetime',
            'summary_sent_at' => 'datetime',
            'duration_seconds' => 'integer',
            'is_return' => 'boolean',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(VisitEvent::class)->orderBy('occurred_at')->orderBy('id');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(VisitAlert::class);
    }

    public function scopeOpen(Builder $query): void
    {
        $query->whereNull('ended_at');
    }

    /** Open visits with no heartbeat for $seconds (default 60s). */
    public function scopeStale(Builder $query, int $seconds = 60): void
    {
        $threshold = now()->subSeconds($seconds);

        $query->whereNull('ended_at')->where(function (Builder $q) use ($threshold) {
            $q->where('last_ping_at', '<', $threshold)
                ->orWhere(fn (Builder $q) => $q->whereNull('last_ping_at')->where('started_at', '<', $threshold));
        });
    }

    public function isOpen(): bool
    {
        return $this->ended_at === null;
    }
}
