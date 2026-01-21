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
use Illuminate\Support\Facades\Process;

class CheckAlertsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     *
     * @return void
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
     * Check a single alert rule.
     *
     * @param AlertRule $rule The alert rule to check
     * @param SystemMetric $metric The current system metric
     * @return void
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
     * Get current metric value based on rule.
     *
     * @param AlertRule $rule The alert rule
     * @param SystemMetric $metric The system metric
     * @return float|null The metric value or null
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
     * Check if service is running.
     *
     * @param string|null $serviceName The service name
     * @return float|null 1.0 if running, 0.0 if not, null on error
     */
    protected function checkServiceStatus(?string $serviceName): ?float
    {
        if (!$serviceName) {
            return null;
        }

        try {
            $result = Process::run("systemctl is-active {$serviceName}");
            return trim($result->output()) === 'active' ? 1.0 : 0.0;
        } catch (\Exception $e) {
            Log::channel('alerts')->error("Failed to check service status: {$serviceName}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Check if condition is met.
     *
     * @param AlertRule $rule The alert rule
     * @param float $value The current value
     * @return bool True if condition is met
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
     * Trigger an alert.
     *
     * @param AlertRule $rule The alert rule
     * @param float $value The current value
     * @return void
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
     * Format alert message.
     *
     * @param AlertRule $rule The alert rule
     * @param float $value The current value
     * @return string The formatted message
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
     * Determine alert severity.
     *
     * @param AlertRule $rule The alert rule
     * @param float $value The current value
     * @return string The severity level
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
     * Send notifications via configured channels.
     *
     * @param AlertRule $rule The alert rule
     * @param Alert $alert The alert model
     * @return void
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
            Log::channel('alerts')->error('Failed to send alert notifications', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send email notification.
     *
     * @param AlertRule $rule The alert rule
     * @param Alert $alert The alert model
     * @return void
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
            Log::channel('alerts')->error('Failed to send email alert', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send Slack notification.
     *
     * @param AlertRule $rule The alert rule
     * @param Alert $alert The alert model
     * @return void
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
                    'footer' => 'Hostiqo',
                    'ts' => $alert->created_at->timestamp,
                ]]
            ]);
        } catch (\Exception $e) {
            Log::channel('alerts')->error('Failed to send Slack alert', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
