<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Queue;
use Exception;

class QueueService
{
    /**
     * Get pending jobs (supports both Redis and Database).
     */
    public function getPendingJobs(int $limit = 50): array
    {
        $driver = config('queue.default');
        
        if ($driver === 'redis') {
            return $this->getRedisJobs($limit);
        }
        
        return $this->getDatabaseJobs($limit);
    }

    /**
     * Get pending jobs from Redis queue.
     */
    protected function getRedisJobs(int $limit = 50): array
    {
        try {
            $connection = config('queue.connections.redis.connection', 'default');
            $redis = Redis::connection($connection);
            
            // Get all queue names
            $queueNames = $this->getRedisQueueNames();
            $jobs = [];
            $count = 0;
            
            foreach ($queueNames as $queueName) {
                if ($count >= $limit) break;
                
                // Laravel Redis queue key format (Laravel auto-adds prefix)
                $queueKey = 'queues:' . $queueName;
                $jobsInQueue = $redis->lrange($queueKey, 0, $limit - $count - 1);
                
                foreach ($jobsInQueue as $index => $jobData) {
                    $payload = json_decode($jobData, true);
                    
                    $jobs[] = [
                        'id' => md5($jobData), // Redis doesn't have numeric ID
                        'queue' => $queueName,
                        'payload' => $payload,
                        'attempts' => $payload['attempts'] ?? 0,
                        'reserved_at' => null,
                        'available_at' => isset($payload['pushedAt']) ? date('Y-m-d H:i:s', $payload['pushedAt']) : 'N/A',
                        'created_at' => isset($payload['pushedAt']) ? date('Y-m-d H:i:s', $payload['pushedAt']) : 'N/A',
                        'display_name' => $this->getJobDisplayName(json_encode($payload)),
                    ];
                    
                    $count++;
                    if ($count >= $limit) break;
                }
            }
            
            return $jobs;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get pending jobs from database queue.
     */
    protected function getDatabaseJobs(int $limit = 50): array
    {
        try {
            $jobs = DB::table('jobs')
                ->orderBy('created_at', 'asc')
                ->limit($limit)
                ->get()
                ->map(function ($job) {
                    return [
                        'id' => $job->id,
                        'queue' => $job->queue,
                        'payload' => json_decode($job->payload, true),
                        'attempts' => $job->attempts,
                        'reserved_at' => $job->reserved_at ? date('Y-m-d H:i:s', $job->reserved_at) : null,
                        'available_at' => date('Y-m-d H:i:s', $job->available_at),
                        'created_at' => date('Y-m-d H:i:s', $job->created_at),
                        'display_name' => $this->getJobDisplayName($job->payload),
                    ];
                })
                ->toArray();

            return $jobs;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get failed jobs (supports both Redis and Database).
     */
    public function getFailedJobs(int $limit = 50): array
    {
        try {
            // Failed jobs are always stored in database table
            $jobs = DB::table('failed_jobs')
                ->orderBy('failed_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($job) {
                    return [
                        'id' => $job->id,
                        'uuid' => $job->uuid,
                        'connection' => $job->connection,
                        'queue' => $job->queue,
                        'payload' => json_decode($job->payload, true),
                        'exception' => $job->exception,
                        'failed_at' => $job->failed_at,
                        'display_name' => $this->getJobDisplayName($job->payload),
                    ];
                })
                ->toArray();

            return $jobs;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get queue statistics.
     */
    public function getStatistics(): array
    {
        try {
            $driver = config('queue.default');
            
            if ($driver === 'redis') {
                return $this->getRedisStatistics();
            }
            
            return $this->getDatabaseStatistics();
        } catch (Exception $e) {
            return [
                'pending_jobs' => 0,
                'failed_jobs' => 0,
                'recent_failed' => 0,
                'jobs_by_queue' => [],
            ];
        }
    }

    /**
     * Get statistics from Redis.
     */
    protected function getRedisStatistics(): array
    {
        try {
            $connection = config('queue.connections.redis.connection', 'default');
            $redis = Redis::connection($connection);
            
            // Get all queue names
            $queueNames = $this->getRedisQueueNames();
            $pendingCount = 0;
            $jobsByQueue = [];
            
            foreach ($queueNames as $queueName) {
                $queueKey = 'queues:' . $queueName;
                $count = $redis->llen($queueKey);
                $pendingCount += $count;
                $jobsByQueue[$queueName] = $count;
            }
            
            // Failed jobs are always in database
            $failedCount = DB::table('failed_jobs')->count();
            $recentFailedCount = DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subDay())
                ->count();
            
            return [
                'pending_jobs' => $pendingCount,
                'failed_jobs' => $failedCount,
                'recent_failed' => $recentFailedCount,
                'jobs_by_queue' => $jobsByQueue,
            ];
        } catch (Exception $e) {
            return [
                'pending_jobs' => 0,
                'failed_jobs' => 0,
                'recent_failed' => 0,
                'jobs_by_queue' => [],
            ];
        }
    }

    /**
     * Get statistics from database.
     */
    protected function getDatabaseStatistics(): array
    {
        try {
            $pendingCount = DB::table('jobs')->count();
            $failedCount = DB::table('failed_jobs')->count();
            
            // Get jobs by queue
            $jobsByQueue = DB::table('jobs')
                ->select('queue', DB::raw('count(*) as count'))
                ->groupBy('queue')
                ->get()
                ->pluck('count', 'queue')
                ->toArray();

            // Get recent failed jobs count (last 24 hours)
            $recentFailedCount = DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subDay())
                ->count();

            return [
                'pending_jobs' => $pendingCount,
                'failed_jobs' => $failedCount,
                'recent_failed' => $recentFailedCount,
                'jobs_by_queue' => $jobsByQueue,
            ];
        } catch (Exception $e) {
            return [
                'pending_jobs' => 0,
                'failed_jobs' => 0,
                'recent_failed' => 0,
                'jobs_by_queue' => [],
            ];
        }
    }

    /**
     * Delete a job from queue.
     * Database: Delete by numeric ID
     * Redis: Not supported (jobs in lists, complex to delete by hash)
     */
    public function deleteJob(string $jobId): bool
    {
        try {
            $driver = config('queue.default');
            
            if ($driver === 'redis') {
                // For Redis, deleting specific job from list is complex
                // Would need to find exact position and LREM
                // Not implementing for now - let worker process it
                return false;
            }
            
            // Database driver - ID is numeric string
            return DB::table('jobs')->where('id', $jobId)->delete() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Delete a failed job.
     */
    public function deleteFailedJob(string $uuid): bool
    {
        try {
            return DB::table('failed_jobs')->where('uuid', $uuid)->delete() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Retry a failed job.
     */
    public function retryFailedJob(string $uuid): bool
    {
        try {
            $failedJob = DB::table('failed_jobs')->where('uuid', $uuid)->first();
            
            if (!$failedJob) {
                return false;
            }

            // Push job back to queue
            $payload = json_decode($failedJob->payload, true);
            
            DB::table('jobs')->insert([
                'queue' => $failedJob->queue,
                'payload' => $failedJob->payload,
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => time(),
                'created_at' => time(),
            ]);

            // Delete from failed jobs
            DB::table('failed_jobs')->where('uuid', $uuid)->delete();

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Retry all failed jobs.
     */
    public function retryAllFailedJobs(): int
    {
        try {
            $failedJobs = DB::table('failed_jobs')->get();
            $retried = 0;

            foreach ($failedJobs as $job) {
                if ($this->retryFailedJob($job->uuid)) {
                    $retried++;
                }
            }

            return $retried;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Clear all failed jobs.
     */
    public function clearFailedJobs(): int
    {
        try {
            return DB::table('failed_jobs')->delete();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Extract job display name from payload.
     */
    protected function getJobDisplayName(string $payload): string
    {
        try {
            $data = json_decode($payload, true);
            
            if (isset($data['displayName'])) {
                return $data['displayName'];
            }
            
            if (isset($data['data']['commandName'])) {
                return class_basename($data['data']['commandName']);
            }
            
            if (isset($data['job'])) {
                return class_basename($data['job']);
            }
            
            return 'Unknown Job';
        } catch (Exception $e) {
            return 'Unknown Job';
        }
    }

    /**
     * Get job details by ID (supports both database int ID and Redis string hash).
     */
    public function getJobDetails(string $jobId): ?array
    {
        try {
            $driver = config('queue.default');
            
            if ($driver === 'redis') {
                // For Redis, find job by matching MD5 hash
                $connection = config('queue.connections.redis.connection', 'default');
                $redis = Redis::connection($connection);
                $queueNames = $this->getRedisQueueNames();
                
                foreach ($queueNames as $queueName) {
                    $queueKey = 'queues:' . $queueName;
                    $jobsInQueue = $redis->lrange($queueKey, 0, -1);
                    
                    foreach ($jobsInQueue as $jobData) {
                        if (md5($jobData) === $jobId) {
                            $payload = json_decode($jobData, true);
                            
                            return [
                                'id' => $jobId,
                                'queue' => $queueName,
                                'payload' => $payload,
                                'attempts' => $payload['attempts'] ?? 0,
                                'reserved_at' => null,
                                'available_at' => isset($payload['pushedAt']) ? date('Y-m-d H:i:s', $payload['pushedAt']) : 'N/A',
                                'created_at' => isset($payload['pushedAt']) ? date('Y-m-d H:i:s', $payload['pushedAt']) : 'N/A',
                                'display_name' => $this->getJobDisplayName(json_encode($payload)),
                            ];
                        }
                    }
                }
                
                return null;
            }
            
            // Database driver
            $job = DB::table('jobs')->where('id', $jobId)->first();
            
            if (!$job) {
                return null;
            }

            return [
                'id' => $job->id,
                'queue' => $job->queue,
                'payload' => json_decode($job->payload, true),
                'attempts' => $job->attempts,
                'reserved_at' => $job->reserved_at ? date('Y-m-d H:i:s', $job->reserved_at) : null,
                'available_at' => date('Y-m-d H:i:s', $job->available_at),
                'created_at' => date('Y-m-d H:i:s', $job->created_at),
                'display_name' => $this->getJobDisplayName($job->payload),
            ];
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get failed job details by UUID.
     */
    public function getFailedJobDetails(string $uuid): ?array
    {
        try {
            $job = DB::table('failed_jobs')->where('uuid', $uuid)->first();
            
            if (!$job) {
                return null;
            }

            return [
                'id' => $job->id,
                'uuid' => $job->uuid,
                'connection' => $job->connection,
                'queue' => $job->queue,
                'payload' => json_decode($job->payload, true),
                'exception' => $job->exception,
                'failed_at' => $job->failed_at,
                'display_name' => $this->getJobDisplayName($job->payload),
            ];
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get all Redis queue names from keys.
     */
    protected function getRedisQueueNames(): array
    {
        try {
            $connection = config('queue.connections.redis.connection', 'default');
            $redis = Redis::connection($connection);
            
            // Laravel auto-adds 'laravel-database-' prefix, so we just search 'queues:*'
            $keyPattern = 'queues:*';
            
            // Get all keys that match queue pattern
            $keys = $redis->keys($keyPattern);
            
            $queueNames = [];
            foreach ($keys as $key) {
                // Skip notify keys
                if (strpos($key, ':notify') !== false) {
                    continue;
                }
                
                // Extract queue name from key
                // Key comes back WITH prefix: 'laravel-database-queues:default'
                // We want just: 'default'
                $key = str_replace('laravel-database-', '', $key);
                $parts = explode(':', $key);
                if (count($parts) >= 2) {
                    $queueNames[] = end($parts);
                }
            }
            
            // If no queues found, return default queue
            if (empty($queueNames)) {
                return ['default'];
            }
            
            return array_unique($queueNames);
        } catch (Exception $e) {
            return ['default'];
        }
    }
}
