<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deployment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'webhook_id',
        'status',
        'commit_hash',
        'commit_message',
        'author',
        'output',
        'error_message',
        'started_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the webhook that owns the deployment.
     */
    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    /**
     * Get the status badge class.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'processing' => 'info',
            'completed' => 'success',
            'failed' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get the status icon.
     */
    public function getStatusIconAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'bi-clock-history',
            'processing' => 'bi-arrow-repeat',
            'completed' => 'bi-check-circle-fill',
            'failed' => 'bi-x-circle-fill',
            default => 'bi-question-circle',
        };
    }

    /**
     * Get the duration of deployment in seconds.
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->completed_at->diffInSeconds($this->started_at);
    }

    /**
     * Get the short commit hash.
     */
    public function getShortCommitHashAttribute(): string
    {
        return substr($this->commit_hash ?? '', 0, 7);
    }

    /**
     * Scope a query to only include completed deployments.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include failed deployments.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
    
    /**
     * Scope a query to only include pending deployments.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
    
    /**
     * Scope a query to only include processing deployments.
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }
}
