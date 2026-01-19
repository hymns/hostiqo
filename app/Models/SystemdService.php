<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemdService extends Model
{
    protected $fillable = [
        'name',
        'description',
        'exec_start',
        'working_directory',
        'user',
        'type',
        'restart',
        'restart_sec',
        'environment',
        'standard_output',
        'standard_error',
        'is_active',
        'status',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the service file name.
     */
    public function getServiceFileNameAttribute(): string
    {
        return $this->name . '.service';
    }

    /**
     * Get the service file path.
     */
    public function getServiceFilePathAttribute(): string
    {
        return '/etc/systemd/system/' . $this->service_file_name;
    }

    /**
     * Get status badge color.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'active' => 'success',
            'inactive' => 'secondary',
            'failed' => 'danger',
            'activating' => 'warning',
            default => 'secondary',
        };
    }
}
