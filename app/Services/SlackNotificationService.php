<?php

namespace App\Services;

use App\Models\Deployment;
use App\Models\Webhook;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackNotificationService
{
    /**
     * Send deployment notification to Slack.
     *
     * @param Webhook $webhook
     * @param Deployment $deployment
     * @param string $status 'success' or 'failed'
     * @param string|null $errorMessage
     * @return bool
     */
    public function sendDeploymentNotification(
        Webhook $webhook,
        Deployment $deployment,
        string $status,
        ?string $errorMessage = null
    ): bool {
        if (!$webhook->slack_webhook_url) {
            return false;
        }

        $emoji = $status === 'success' ? ':white_check_mark:' : ':x:';
        $statusText = $status === 'success' ? 'Success' : 'Failed';
        $color = $status === 'success' ? 'good' : 'danger';

        $commitHash = $deployment->commit_hash ? substr($deployment->commit_hash, 0, 7) : 'N/A';
        $duration = $this->formatDuration($deployment->created_at, $deployment->updated_at);

        // Build message text
        $text = "{$emoji} *Deployment {$statusText}*\n";
        $text .= "*Webhook:* {$webhook->name}\n";
        $text .= "*Branch:* `{$webhook->branch}`\n";
        $text .= "*Commit:* `{$commitHash}`\n";
        $text .= "*Duration:* {$duration}\n";

        if ($webhook->domain) {
            $text .= "*Domain:* {$webhook->domain}\n";
        }

        if ($errorMessage) {
            $text .= "\n*Error:*\n```{$errorMessage}```";
        }

        $payload = [
            'text' => "{$emoji} Deployment {$statusText}",
            'attachments' => [
                [
                    'color' => $color,
                    'text' => $text,
                    'footer' => 'Hostiqo',
                    'ts' => $deployment->updated_at->timestamp
                ]
            ]
        ];

        try {
            Log::info('Sending Slack notification', [
                'webhook_id' => $webhook->id,
                'deployment_id' => $deployment->id,
                'slack_url' => substr($webhook->slack_webhook_url, 0, 50) . '...',
                'payload' => $payload
            ]);

            $response = Http::post($webhook->slack_webhook_url, $payload);

            if ($response->successful()) {
                Log::info('Slack notification sent successfully', [
                    'webhook_id' => $webhook->id,
                    'deployment_id' => $deployment->id,
                    'status' => $status
                ]);
                return true;
            }

            Log::warning('Slack notification failed', [
                'webhook_id' => $webhook->id,
                'deployment_id' => $deployment->id,
                'response_status' => $response->status(),
                'response_body' => $response->body()
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Slack notification exception', [
                'webhook_id' => $webhook->id,
                'deployment_id' => $deployment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Format duration between two timestamps.
     *
     * @param \Carbon\Carbon $start
     * @param \Carbon\Carbon $end
     * @return string
     */
    private function formatDuration($start, $end): string
    {
        $seconds = $end->diffInSeconds($start);

        if ($seconds < 60) {
            return "{$seconds}s";
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        return "{$minutes}m {$remainingSeconds}s";
    }
}
