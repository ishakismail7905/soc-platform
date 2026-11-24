<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public $timestamps = false;

    // Rows are only ever created by App\Services\ActivityLogger.
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'properties' => 'array',
        ];
    }

    /** Layer 1: the application can never change or remove a log entry. */
    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('activity log is append-only'));
        static::deleting(fn () => throw new \LogicException('activity log is append-only'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
