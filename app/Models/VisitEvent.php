<?php

namespace App\Models;

use App\Enums\VisitEventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitEvent extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['visit_id', 'type', 'payload', 'occurred_at'];

    protected function casts(): array
    {
        return [
            'type' => VisitEventType::class,
            'payload' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }
}
