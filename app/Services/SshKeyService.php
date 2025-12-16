<?php

namespace App\Services;

use App\Models\SshKey;
use App\Models\Webhook;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class SshKeyService
{
    /**
     * Generate SSH key pair for a webhook.
     */
    public function generateKeyPair(Webhook $webhook): SshKey
    {
        $tempDir = storage_path('app/temp');
        File::ensureDirectoryExists($tempDir);

        $keyName = 'webhook_' . $webhook->id . '_' . Str::random(8);
        $keyPath = $tempDir . '/' . $keyName;

        // Generate SSH key pair using ssh-keygen
        Process::run([
            'ssh-keygen',
            '-t', 'ed25519',
            '-f', $keyPath,
            '-N', '', // No passphrase
            '-C', "webhook_{$webhook->id}@hostiqo",
        ]);

        $publicKey = File::get($keyPath . '.pub');
        $privateKey = File::get($keyPath);

        // Get fingerprint
        $fingerprintOutput = Process::run([
            'ssh-keygen',
            '-lf',
            $keyPath . '.pub',
        ]);

        $fingerprint = $this->extractFingerprint($fingerprintOutput->output());

        // Clean up temporary files
        File::delete($keyPath);
        File::delete($keyPath . '.pub');

        // Delete existing SSH key if any
        $webhook->sshKey?->delete();

        // Create new SSH key record
        return SshKey::create([
            'webhook_id' => $webhook->id,
            'public_key' => trim($publicKey),
            'private_key' => $privateKey,
            'fingerprint' => $fingerprint,
        ]);
    }

    /**
     * Extract fingerprint from ssh-keygen output.
     */
    protected function extractFingerprint(string $output): ?string
    {
        if (preg_match('/SHA256:([^\s]+)/', $output, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Save private key to temporary file for git operations.
     */
    public function saveTempPrivateKey(SshKey $sshKey): string
    {
        $tempDir = storage_path('app/temp');
        File::ensureDirectoryExists($tempDir);

        $keyPath = $tempDir . '/temp_key_' . $sshKey->webhook_id . '_' . time();
        File::put($keyPath, $sshKey->private_key);
        chmod($keyPath, 0600);

        return $keyPath;
    }

    /**
     * Delete temporary private key file.
     */
    public function deleteTempPrivateKey(string $keyPath): void
    {
        if (File::exists($keyPath)) {
            File::delete($keyPath);
        }
    }
}
