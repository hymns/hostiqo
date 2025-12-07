<?php

namespace App\Jobs;

use App\Models\Alert;
use App\Models\AlertRule;
use App\Models\SystemMetric;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class CheckAlertsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $activeRules = AlertRule::where('is_active', true)->get();
        
        if ($activeRules->isEmpty()) {
            return;
        }

        $latestMetric = SystemMetric::getLatest();
        
        if (!$latestMetric) {
            return;
        }

        foreach ($activeRules as $rule) {
            $this->checkRule($rule, $latestMetric);
        }
    }

    /**
     * Check a single alert rule
     */
    protected function checkRule(AlertRule $rule, SystemMetric $metric): void
    {
        $currentValue = $this->getMetricValue($rule, $metric);
        
        if ($currentValue === null) {
            return;
        }

        // Check if condition is met
        if (!$this->conditionMet($rule, $currentValue)) {
            return;
        }

        // Check if we already have a recent unresolved alert
        $recentAlert = Alert::where('alert_rule_id', $rule->id)
            ->where('is_resolved', false)
            ->where('created_at', '>=', now()->subMinutes($rule->duration))
            ->first();

        if ($recentAlert) {
            return; // Don't spam alerts
        }

        // Trigger alert
        $this->triggerAlert($rule, $currentValue);
    }

    /**
     * Get current metric value based on rule
     */
    protected function getMetricValue(AlertRule $rule, SystemMetric $metric): ?float
    {
        return match($rule->metric) {
            'cpu' => $metric->cpu_usage,
            'memory' => $metric->memory_usage,
            'disk' => $metric->disk_usage,
            'service' => $this->checkServiceStatus($rule->service_name),
            default => null,
        };
    }

    /**
     * Check if service is running
     */
    protected function checkServiceStatus(?string $serviceName): ?float
    {
        if (!$serviceName) {
            return null;
        }

        try {
            $output = shell_exec("systemctl is-active {$serviceName} 2>&1");
            return trim($output) === 'active' ? 1.0 : 0.0;
        } catch (\Exception $e) {
            Log::error("Failed to check service status: {$serviceName}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Check if condition is met
     */
    protected function conditionMet(AlertRule $rule, float $value): bool
    {
        return match($rule->condition) {
            '>' => $value > $rule->threshold,
            '<' => $value < $rule->threshold,
            '==' => abs($value - $rule->threshold) < 0.01,
            '!=' => abs($value - $rule->threshold) >= 0.01,
            default => false,
        };
    }

    /**
     * Trigger an alert
     */
    protected function triggerAlert(AlertRule $rule, float $value): void
    {
        $severity = $this->determineSeverity($rule, $value);
        
        $alert = Alert::create([
            'alert_rule_id' => $rule->id,
            'title' => "Alert: {$rule->name}",
            'message' => $this->formatMessage($rule, $value),
            'severity' => $severity,
        ]);

        // Update last triggered timestamp
        $rule->update(['last_triggered_at' => now()]);

        // Send notifications
        $this->sendNotifications($rule, $alert);
    }

    /**
     * Format alert message
     */
    protected function formatMessage(AlertRule $rule, float $value): string
    {
        if ($rule->metric === 'service') {
            return $value > 0 
                ? "{$rule->service_name} is running" 
                : "{$rule->service_name} is down";
        }

        return "{$rule->metric} is {$value}% (threshold: {$rule->threshold}%)";
    }

    /**
     * Determine alert severity
     */
    protected function determineSeverity(AlertRule $rule, float $value): string
    {
        if ($rule->metric === 'service' && $value == 0) {
            return 'critical';
        }

        $diff = abs($value - $rule->threshold);
        
        if ($diff > 20) {
            return 'critical';
        } elseif ($diff > 10) {
            return 'warning';
        }
        
        return 'info';
    }

    /**
     * Send notifications via configured channels
     */
    protected function sendNotifications(AlertRule $rule, Alert $alert): void
    {
        try {
            // Send email
            if (in_array($rule->channel, ['email', 'both']) && $rule->email) {
                $this->sendEmail($rule, $alert);
            }

            // Send Slack
            if (in_array($rule->channel, ['slack', 'both']) && $rule->slack_webhook) {
                $this->sendSlack($rule, $alert);
            }

            $alert->update(['notification_sent' => true]);
        } catch (\Exception $e) {
            Log::error('Failed to send alert notifications', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send email notification
     */
    protected function sendEmail(AlertRule $rule, Alert $alert): void
    {
        // Simple email sending
        // You can create a Mailable class for better formatting
        try {
            $emoji = match($alert->severity) {
                'critical' => '🔴',
                'warning' => '⚠️',
                default => 'ℹ️',
            };

            Mail::raw(
                "{$emoji} {$alert->title}\n\n{$alert->message}\n\nTime: {$alert->created_at}",
                function ($message) use ($rule, $alert) {
                    $message->to($rule->email)
                        ->subject("[{$alert->severity}] {$alert->title}");
                }
            );
        } catch (\Exception $e) {
            Log::error('Failed to send email alert', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send Slack notification
     */
    protected function sendSlack(AlertRule $rule, Alert $alert): void
    {
        try {
            $color = match($alert->severity) {
                'critical' => 'danger',
                'warning' => 'warning',
                default => 'good',
            };

            $emoji = match($alert->severity) {
                'critical' => ':rotating_light:',
                'warning' => ':warning:',
                default => ':information_source:',
            };

            Http::post($rule->slack_webhook, [
                'text' => "{$emoji} {$alert->title}",
                'attachments' => [[
                    'color' => $color,
                    'text' => $alert->message,
                    'footer' => 'Git Webhook Manager',
                    'ts' => $alert->created_at->timestamp,
                ]]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send Slack alert', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
