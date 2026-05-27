<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Webhook extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'domain',
        'git_provider',
        'repository_url',
        'branch',
        'local_path',
        'deploy_user',
        'secret_token',
        'is_active',
        'pre_deploy_script',
        'post_deploy_script',
        'last_deployed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'last_deployed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the SSH key associated with the webhook.
     *
     * @return HasOne
     */
    public function sshKey(): HasOne
    {
        return $this->hasOne(SshKey::class);
    }

    /**
     * Get the deployments for the webhook.
     *
     * @return HasMany
     */
    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }

    /**
     * Get the latest deployment.
     *
     * @return HasOne
     */
    public function latestDeployment(): HasOne
    {
        return $this->hasOne(Deployment::class)->latestOfMany();
    }

    /**
     * Get the associated website by matching local_path with root_path.
     *
     * @return HasOne
     */
    public function website(): HasOne
    {
        return $this->hasOne(Website::class, 'root_path', 'local_path');
    }

    /**
     * Scope a query to only include active webhooks.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the webhook endpoint URL.
     *
     * @return string The webhook URL
     */
    public function getWebhookUrlAttribute(): string
    {
        return route('webhook.handle', ['webhook' => $this->id, 'token' => $this->secret_token]);
    }

    /**
     * Get the provider icon class.
     *
     * @return string The icon class
     */
    public function getProviderIconAttribute(): string
    {
        return match ($this->git_provider) {
            'github' => 'bi-github',
            'gitlab' => 'bi-gitlab',
            default => 'bi-git',
        };
    }

    /**
     * Get the status badge class.
     *
     * @return string The badge color class
     */
    public function getStatusBadgeAttribute(): string
    {
        return $this->is_active ? 'success' : 'secondary';
    }
}
