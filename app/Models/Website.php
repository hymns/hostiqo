<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Website extends Model
{
    protected $fillable = [
        'name',
        'domain',
        'root_path',
        'working_directory',
        'project_type',
        'framework',
        'php_version',
        'node_version',
        'php_settings',
        'php_pool_name',
        'port',
        'ssl_enabled',
        'www_redirect',
        'is_active',
        'nginx_status',
        'ssl_status',
        'ssl_issuer',
        'ssl_issued_at',
        'ssl_expires_at',
        'ssl_last_checked_at',
        'ssl_auto_renew',
        'pm2_status',
        'cloudflare_zone_id',
        'cloudflare_record_id',
        'cloudflare_www_record_id',
        'server_ip',
        'dns_status',
        'dns_error',
        'dns_last_synced_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'ssl_enabled' => 'boolean',
        'is_active' => 'boolean',
        'ssl_auto_renew' => 'boolean',
        'php_settings' => 'array',
        'ssl_issued_at' => 'datetime',
        'ssl_expires_at' => 'datetime',
        'ssl_last_checked_at' => 'datetime',
        'dns_last_synced_at' => 'datetime',
    ];

    /**
     * Get the badge color for project type
     */
    public function getProjectTypeBadgeAttribute(): string
    {
        return match($this->project_type) {
            'php' => 'primary',
            'node' => 'success',
            default => 'secondary',
        };
    }

    /**
     * Get the version display text
     */
    public function getVersionDisplayAttribute(): string
    {
        return $this->project_type === 'php' 
            ? ($this->php_version ?? 'Default')
            : ($this->node_version ?? 'Default');
    }

    /**
     * Get the status badge color
     */
    public function getStatusBadgeAttribute(): string
    {
        return $this->is_active ? 'success' : 'secondary';
    }

    /**
     * Get the Nginx status badge color
     */
    public function getNginxStatusBadgeAttribute(): string
    {
        return match($this->nginx_status) {
            'active' => 'success',
            'pending' => 'warning',
            'failed' => 'danger',
            'inactive' => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Get the SSL status badge color
     */
    public function getSslStatusBadgeAttribute(): string
    {
        return match($this->ssl_status) {
            'active' => 'success',
            'pending' => 'warning',
            'failed' => 'danger',
            'none' => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Get the PM2 status badge color
     */
    public function getPm2StatusBadgeAttribute(): string
    {
        return match($this->pm2_status) {
            'running' => 'success',
            'stopped' => 'secondary',
            'error' => 'danger',
            'unknown' => 'warning',
            default => 'secondary',
        };
    }

    /**
     * Scope to filter by project type or framework
     */
    public function scopeOfType($query, string $type)
    {
        // Special case: 'deployment' shows all 1-click deployed apps
        if ($type === 'deployment') {
            return $query->whereNotNull('framework');
        }
        
        // Legacy support: 'wordpress' filters by framework
        if ($type === 'wordpress') {
            return $query->where('framework', 'wordpress')
                         ->where('project_type', 'php');
        }
        
        return $query->where('project_type', $type);
    }

    /**
     * Scope to get only active websites
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get days until SSL certificate expires
     */
    public function getSslDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->ssl_expires_at) {
            return null;
        }

        return (int) now()->diffInDays($this->ssl_expires_at, false);
    }

    /**
     * Check if SSL certificate is expiring soon (within 30 days)
     */
    public function getSslExpiringSoonAttribute(): bool
    {
        $days = $this->ssl_days_until_expiry;
        
        return $days !== null && $days <= 30 && $days > 0;
    }

    /**
     * Check if SSL certificate is expired
     */
    public function getSslExpiredAttribute(): bool
    {
        $days = $this->ssl_days_until_expiry;
        
        return $days !== null && $days < 0;
    }

    /**
     * Get SSL expiry status badge color
     */
    public function getSslExpiryBadgeAttribute(): string
    {
        if ($this->ssl_expired) {
            return 'danger';
        }

        if ($this->ssl_expiring_soon) {
            return 'warning';
        }

        return 'success';
    }

    /**
     * Get the DNS status badge color
     */
    public function getDnsStatusBadgeAttribute(): string
    {
        return match($this->dns_status) {
            'active' => 'success',
            'pending' => 'warning',
            'failed' => 'danger',
            'none' => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Check if DNS is configured
     */
    public function hasDnsConfigured(): bool
    {
        return !empty($this->cloudflare_zone_id) && !empty($this->cloudflare_record_id);
    }
}
