<?php

namespace App\Services;

use App\Models\SupervisorProgram;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Exception;

class SupervisorService
{
    /**
     * Generate supervisor config from program model.
     *
     * @param SupervisorProgram $program The supervisor program model
     * @return string The generated configuration content
     */
    public function generateConfig(SupervisorProgram $program): string
    {
        $stdoutLogfileMaxBytes = $program->stdout_logfile_maxbytes;
        if ($stdoutLogfileMaxBytes === null || $stdoutLogfileMaxBytes === '') {
            $stdoutLogfileMaxBytes = 50 * 1024 * 1024;
        }

        $stdoutLogfileBackups = $program->stdout_logfile_backups;
        if ($stdoutLogfileBackups === null || $stdoutLogfileBackups === '') {
            $stdoutLogfileBackups = 10;
        }

        $config = "[program:{$program->name}]\n";
        $config .= "command={$program->command}\n";
        $config .= "directory={$program->directory}\n";
        $config .= "numprocs={$program->numprocs}\n";
        $config .= "process_name=%(program_name)s_%(process_num)02d\n";
        $config .= "user={$program->user}\n";
        $config .= "autostart=" . ($program->autostart ? 'true' : 'false') . "\n";
        $config .= "autorestart=" . ($program->autorestart ? 'true' : 'false') . "\n";
        $config .= "startsecs={$program->startsecs}\n";
        $config .= "stopwaitsecs={$program->stopwaitsecs}\n";
        $config .= "stdout_logfile={$program->getLogFilePath()}\n";
        $config .= "stdout_logfile_maxbytes={$stdoutLogfileMaxBytes}\n";
        $config .= "stdout_logfile_backups={$stdoutLogfileBackups}\n";
        $config .= "redirect_stderr=" . $this->toSupervisorBool($program->redirect_stderr, true) . "\n";
        $config .= "stopasgroup=" . $this->toSupervisorBool($program->stopasgroup, true) . "\n";
        $config .= "killasgroup=" . $this->toSupervisorBool($program->killasgroup, true) . "\n";
        
        // Add environment variables if set
        if ($program->environment && is_array($program->environment)) {
            $envVars = [];
            foreach ($program->environment as $key => $value) {
                $envVars[] = "{$key}=\"{$value}\"";
            }
            if (!empty($envVars)) {
                $config .= "environment=" . implode(',', $envVars) . "\n";
            }
        }
        
        return $config;
    }

    /**
     * Convert value to supervisor boolean string.
     *
     * @param mixed $value The value to convert
     * @param bool $default The default value if null/empty
     * @return string 'true' or 'false'
     */
    protected function toSupervisorBool(mixed $value, bool $default): string
    {
        if ($value === null) {
            return $default ? 'true' : 'false';
        }

        if (is_string($value) && trim($value) === '') {
            return $default ? 'true' : 'false';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value)) {
            return ($value !== 0) ? 'true' : 'false';
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return 'true';
            }
            if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
                return 'false';
            }
        }

        return $default ? 'true' : 'false';
    }

    /**
     * Deploy supervisor config to system.
     *
     * @param SupervisorProgram $program The supervisor program to deploy
     * @return array{success: bool, message?: string, error?: string, config_path?: string}
     */
    public function deploy(SupervisorProgram $program): array
    {
        try {
            // Check if supervisor is available
            if (!$this->isSupervisorAvailable()) {
                return [
                    'success' => false,
                    'error' => 'Supervisor is not installed or not available on this system. This feature requires a Linux environment with supervisor installed.'
                ];
            }

            $config = $this->generateConfig($program);
            $configPath = $program->getConfigFilePath();
            $tempFile = '/tmp/hostiqo-' . $program->getConfigFileName();
            
            // Write to temp file
            File::put($tempFile, $config);
            
            // Copy to supervisor conf.d
            $result = Process::run(['/usr/bin/sudo', '/usr/bin/cp', $tempFile, $configPath]);
            if ($result->failed()) {
                $error = $result->errorOutput();
                
                // Check for read-only filesystem error
                if (str_contains($error, 'Read-only file system')) {
                    throw new Exception("Filesystem is read-only. Please check disk space with 'df -h' and remount with 'sudo mount -o remount,rw /'");
                }
                
                throw new Exception("Failed to copy config file: " . $error);
            }
            
            // Set permissions
            Process::run(['/usr/bin/sudo', '/usr/bin/chmod', '644', $configPath]);
            
            // Clean up temp file
            File::delete($tempFile);
            
            // Reload supervisor
            $reloadResult = $this->reloadSupervisor();
            if (!$reloadResult['success']) {
                throw new Exception("Failed to reload supervisor: " . $reloadResult['message']);
            }
            
            Log::info("Supervisor program deployed", [
                'program' => $program->name,
                'config_path' => $configPath
            ]);
            
            return [
                'success' => true,
                'message' => "Program {$program->name} deployed successfully",
                'config_path' => $configPath
            ];
            
        } catch (Exception $e) {
            Log::error("Failed to deploy supervisor program", [
                'program' => $program->name ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if supervisor is available on the system.
     *
     * @return bool True if supervisor is available
     */
    protected function isSupervisorAvailable(): bool
    {
        // Check if supervisorctl exists
        $result = Process::run(['which', 'supervisorctl']);
        if ($result->failed()) {
            return false;
        }
        
        // Check if supervisor config directory exists
        if (!file_exists('/etc/supervisor/conf.d')) {
            return false;
        }
        
        return true;
    }

    /**
     * Remove supervisor config from system.
     *
     * @param SupervisorProgram $program The supervisor program to remove
     * @return array{success: bool, message?: string, error?: string}
     */
    public function remove(SupervisorProgram $program): array
    {
        if (!$this->isSupervisorAvailable()) {
            return [
                'success' => false,
                'error' => 'Supervisor is not installed or not available on this system.'
            ];
        }
        
        try {
            $configPath = $program->getConfigFilePath();
            
            // Stop the program first
            $this->stopProgram($program->name);
            
            // Remove config file
            $result = Process::run(['/usr/bin/sudo', '/usr/bin/rm', '-f', $configPath]);
            if ($result->failed()) {
                throw new Exception("Failed to remove config file: " . $result->errorOutput());
            }
            
            // Reload supervisor
            $reloadResult = $this->reloadSupervisor();
            if (!$reloadResult['success']) {
                throw new Exception("Failed to reload supervisor: " . $reloadResult['message']);
            }
            
            Log::info("Supervisor program removed", [
                'program' => $program->name,
                'config_path' => $configPath
            ]);
            
            return [
                'success' => true,
                'message' => "Program {$program->name} removed successfully"
            ];
            
        } catch (Exception $e) {
            Log::error("Failed to remove supervisor program", [
                'program' => $program->name ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Reload supervisor configuration.
     *
     * @return array{success: bool, message: string, reread_output?: string, update_output?: string}
     */
    public function reloadSupervisor(): array
    {
        if (!$this->isSupervisorAvailable()) {
            return [
                'success' => false,
                'message' => 'Supervisor is not installed or not available on this system.'
            ];
        }
        
        try {
            // Run supervisorctl reread
            $rereadResult = Process::run(['/usr/bin/sudo', '/usr/bin/supervisorctl', 'reread']);
            if ($rereadResult->failed()) {
                $error = $rereadResult->errorOutput() ?: $rereadResult->output();
                throw new Exception("supervisorctl reread failed. Exit code: " . $rereadResult->exitCode() . ". Error: " . $error);
            }
            
            // Run supervisorctl update
            $updateResult = Process::run(['/usr/bin/sudo', '/usr/bin/supervisorctl', 'update']);
            if ($updateResult->failed()) {
                $error = $updateResult->errorOutput() ?: $updateResult->output();
                throw new Exception("supervisorctl update failed. Exit code: " . $updateResult->exitCode() . ". Error: " . $error);
            }
            
            return [
                'success' => true,
                'message' => 'Supervisor reloaded successfully',
                'reread_output' => $rereadResult->output(),
                'update_output' => $updateResult->output()
            ];
            
        } catch (Exception $e) {
            Log::error("Supervisor reload failed", [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Start a supervisor program.
     *
     * @param string $programName The program name to start
     * @return array{success: bool, message: string, output?: string}
     */
    public function startProgram(string $programName): array
    {
        if (!$this->isSupervisorAvailable()) {
            return [
                'success' => false,
                'message' => 'Supervisor is not installed or not available on this system.'
            ];
        }
        
        try {
            $result = Process::run(['/usr/bin/sudo', '/usr/bin/supervisorctl', 'start', $programName . ':*']);
            
            if ($result->failed()) {
                throw new Exception($result->errorOutput());
            }
            
            return [
                'success' => true,
                'message' => "Program {$programName} started successfully",
                'output' => $result->output()
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Stop a supervisor program.
     *
     * @param string $programName The program name to stop
     * @return array{success: bool, message: string, output?: string}
     */
    public function stopProgram(string $programName): array
    {
        if (!$this->isSupervisorAvailable()) {
            return [
                'success' => false,
                'message' => 'Supervisor is not installed or not available on this system.'
            ];
        }
        
        try {
            $result = Process::run(['/usr/bin/sudo', '/usr/bin/supervisorctl', 'stop', $programName . ':*']);
            
            if ($result->failed()) {
                throw new Exception($result->errorOutput());
            }
            
            return [
                'success' => true,
                'message' => "Program {$programName} stopped successfully",
                'output' => $result->output()
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Restart a supervisor program.
     *
     * @param string $programName The program name to restart
     * @return array{success: bool, message: string, output?: string}
     */
    public function restartProgram(string $programName): array
    {
        if (!$this->isSupervisorAvailable()) {
            return [
                'success' => false,
                'message' => 'Supervisor is not installed or not available on this system.'
            ];
        }
        
        try {
            $result = Process::run(['/usr/bin/sudo', '/usr/bin/supervisorctl', 'restart', $programName . ':*']);
            
            if ($result->failed()) {
                throw new Exception($result->errorOutput());
            }
            
            return [
                'success' => true,
                'message' => "Program {$programName} restarted successfully",
                'output' => $result->output()
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get supervisor program status.
     *
     * @param string $programName The program name to check
     * @return array{success: bool, message?: string, processes: array}
     */
    public function getProgramStatus(string $programName): array
    {
        if (!$this->isSupervisorAvailable()) {
            return [
                'success' => false,
                'message' => 'Supervisor is not installed or not available on this system.',
                'processes' => []
            ];
        }
        
        try {
            $result = Process::run(['/usr/bin/sudo', '/usr/bin/supervisorctl', 'status', $programName . ':*']);
            
            $output = $result->output();
            $processes = [];
            
            // Parse output
            $lines = explode("\n", trim($output));
            foreach ($lines as $line) {
                if (empty($line)) continue;
                
                // Parse: program_name_00   RUNNING   pid 1234, uptime 1:23:45
                if (preg_match('/^(\S+)\s+(\S+)\s+(.*)$/', $line, $matches)) {
                    $processes[] = [
                        'name' => $matches[1],
                        'status' => $matches[2],
                        'info' => $matches[3] ?? ''
                    ];
                }
            }
            
            return [
                'success' => true,
                'processes' => $processes
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'processes' => []
            ];
        }
    }

    /**
     * Get all supervisor programs.
     *
     * @return array{success: bool, message?: string, programs: array}
     */
    public function getAllPrograms(): array
    {
        if (!$this->isSupervisorAvailable()) {
            return [
                'success' => false,
                'message' => 'Supervisor is not installed or not available on this system.',
                'programs' => []
            ];
        }
        
        try {
            $result = Process::run(['/usr/bin/sudo', '/usr/bin/supervisorctl', 'status']);
            
            $output = $result->output();
            $programs = [];
            
            // Parse output
            $lines = explode("\n", trim($output));
            foreach ($lines as $line) {
                if (empty($line)) continue;
                
                if (preg_match('/^(\S+)\s+(\S+)\s+(.*)$/', $line, $matches)) {
                    $programName = explode(':', $matches[1])[0]; // Get base program name
                    
                    if (!isset($programs[$programName])) {
                        $programs[$programName] = [
                            'name' => $programName,
                            'status' => $matches[2],
                            'info' => $matches[3] ?? '',
                            'processes' => []
                        ];
                    }
                    
                    $programs[$programName]['processes'][] = [
                        'name' => $matches[1],
                        'status' => $matches[2],
                        'info' => $matches[3] ?? ''
                    ];
                }
            }
            
            return [
                'success' => true,
                'programs' => array_values($programs)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'programs' => []
            ];
        }
    }

    /**
     * Get program logs.
     *
     * @param SupervisorProgram $program The supervisor program
     * @param int $lines Number of log lines to retrieve
     * @return string The log content
     */
    public function getProgramLogs(SupervisorProgram $program, int $lines = 100): string
    {
        if (!$this->isSupervisorAvailable()) {
            return 'Supervisor is not installed or not available on this system.';
        }
        
        try {
            $logFile = $program->getLogFilePath();
            
            if (!File::exists($logFile)) {
                return "Log file not found: {$logFile}";
            }
            
            $result = Process::run(['/usr/bin/sudo', 'tail', '-n', (string)$lines, $logFile]);
            
            return $result->output();
            
        } catch (Exception $e) {
            return "Error reading logs: " . $e->getMessage();
        }
    }
}
