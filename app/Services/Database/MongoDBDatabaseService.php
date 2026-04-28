<?php

namespace App\Services\Database;

use Illuminate\Support\Facades\Cache;
use Exception;
use MongoDB\Client;
use MongoDB\Driver\Exception\Exception as MongoException;

class MongoDBDatabaseService extends AbstractDatabaseService
{
    protected ?Client $client = null;
    
    protected function getClient(): Client
    {
        if ($this->client) {
            return $this->client;
        }
        
        $password = $this->readRootPassword();
        
        if (!$password) {
            throw new Exception('MongoDB root password not found');
        }
        
        $this->client = new Client(
            "mongodb://root:" . urlencode($password) . "@localhost:27017/admin"
        );
        
        return $this->client;
    }
    
    public function canCreateDatabase(): array
    {
        try {
            $client = $this->getClient();
            
            $cacheKey = 'mongodb_permissions_' . md5('root');
            
            return Cache::remember($cacheKey, 600, function () use ($client) {
                return $this->testDatabasePermissions($client);
            });
        } catch (Exception $e) {
            return [
                'can_create' => false,
                'has_create_db' => true,
                'has_create_user' => true,
                'has_grant_option' => true,
                'current_user' => 'root',
                'grants' => [],
                'missing_privileges' => ['Error: ' . $e->getMessage()],
                'message' => 'Failed to check permissions: ' . $e->getMessage(),
            ];
        }
    }
    
    protected function testDatabasePermissions(Client $client): array
    {
        try {
            $testDbName = '_test_permission_check_' . time();
            
            // Try to create a test database and collection
            $db = $client->selectDatabase($testDbName);
            $db->createCollection('test');
            
            // Drop test database
            $db->drop();
            
            return [
                'can_create' => true,
                'has_create_db' => true,
                'has_create_user' => true,
                'has_grant_option' => true,
                'current_user' => 'root',
                'grants' => ['root'],
                'missing_privileges' => [],
                'message' => 'Full permissions available',
                'cached' => false,
            ];
        } catch (Exception $e) {
            return [
                'can_create' => false,
                'has_create_db' => false,
                'has_create_user' => false,
                'has_grant_option' => false,
                'current_user' => 'root',
                'grants' => [],
                'missing_privileges' => ['CREATE DATABASE'],
                'message' => 'Insufficient permissions: ' . $e->getMessage(),
                'cached' => false,
            ];
        }
    }
    
    public function createDatabase(string $name, string $username, string $password, string $host): void
    {
        $client = $this->getClient();
        
        // Create database by creating a collection (MongoDB creates DB on first write)
        $db = $client->selectDatabase($name);
        $db->createCollection('_init');
        
        // Create user with readWrite role
        $db->command([
            'createUser' => $username,
            'pwd' => $password,
            'roles' => [
                ['role' => 'readWrite', 'db' => $name]
            ]
        ]);
    }
    
    public function deleteDatabase(string $name): void
    {
        $client = $this->getClient();
        
        $db = $client->selectDatabase($name);
        $db->drop();
    }
    
    public function databaseExists(string $name): bool
    {
        try {
            $client = $this->getClient();
            
            $databases = $client->listDatabases();
            
            foreach ($databases as $database) {
                if ($database->getName() === $name) {
                    return true;
                }
            }
            
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function getDatabaseSize(string $name): float
    {
        try {
            $client = $this->getClient();
            
            $db = $client->selectDatabase($name);
            $stats = $db->command(['dbStats' => 1])->toArray();
            
            if (isset($stats[0]->dataSize)) {
                // Convert bytes to MB
                return round($stats[0]->dataSize / 1024 / 1024, 2);
            }
            
            return 0.0;
        } catch (Exception $e) {
            return 0.0;
        }
    }
    
    public function getTableCount(string $name): int
    {
        try {
            $client = $this->getClient();
            
            $db = $client->selectDatabase($name);
            $collections = iterator_to_array($db->listCollections());
            
            // Filter out system collections
            $count = 0;
            foreach ($collections as $collection) {
                if (!str_starts_with($collection->getName(), 'system.')) {
                    $count++;
                }
            }
            
            return $count;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    public function createUser(string $username, string $password, string $host): void
    {
        $client = $this->getClient();
        
        $admin = $client->selectDatabase('admin');
        $admin->command([
            'createUser' => $username,
            'pwd' => $password,
            'roles' => [
                ['role' => 'readWriteAnyDatabase', 'db' => 'admin']
            ]
        ]);
    }
    
    public function deleteUser(string $username, string $host): void
    {
        $client = $this->getClient();
        
        $admin = $client->selectDatabase('admin');
        $admin->command([
            'dropUser' => $username
        ]);
    }
    
    public function userExists(string $username, string $host): bool
    {
        try {
            $client = $this->getClient();
            
            $admin = $client->selectDatabase('admin');
            $result = $admin->command([
                'usersInfo' => $username
            ])->toArray();
            
            return isset($result[0]->users) && count($result[0]->users) > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function changeUserPassword(string $username, string $password, string $host): void
    {
        $client = $this->getClient();
        
        // Find which database the user belongs to
        $admin = $client->selectDatabase('admin');
        $userInfo = $admin->command([
            'usersInfo' => $username
        ])->toArray();
        
        if (isset($userInfo[0]->users[0]->db)) {
            $userDb = $userInfo[0]->users[0]->db;
            $db = $client->selectDatabase($userDb);
            
            $db->command([
                'updateUser' => $username,
                'pwd' => $password
            ]);
        } else {
            throw new Exception("User {$username} not found");
        }
    }
    
    public function getAllDatabaseStats(): array
    {
        try {
            $client = $this->getClient();
            
            $databases = $client->listDatabases();
            
            $stats = [];
            foreach ($databases as $database) {
                $dbName = $database->getName();
                
                // Skip system databases
                if (in_array($dbName, ['admin', 'config', 'local'])) {
                    continue;
                }
                
                $db = $client->selectDatabase($dbName);
                
                // Get size
                $dbStats = $db->command(['dbStats' => 1])->toArray();
                $sizeBytes = $dbStats[0]->dataSize ?? 0;
                
                // Get collection count
                $collections = iterator_to_array($db->listCollections());
                $collectionCount = 0;
                foreach ($collections as $collection) {
                    if (!str_starts_with($collection->getName(), 'system.')) {
                        $collectionCount++;
                    }
                }
                
                $stats[$dbName] = [
                    'size_mb' => round($sizeBytes / 1024 / 1024, 2),
                    'table_count' => $collectionCount,
                ];
            }
            
            return $stats;
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function clearPermissionCache(): void
    {
        Cache::forget('mongodb_permissions_' . md5('root'));
    }
    
    protected function getCredentialFile(): string
    {
        return '/root/.mongodb_root_password';
    }
}
