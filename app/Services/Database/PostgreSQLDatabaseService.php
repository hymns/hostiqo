<?php

namespace App\Services\Database;

use Illuminate\Support\Facades\Cache;
use Exception;
use PDO;

class PostgreSQLDatabaseService extends AbstractDatabaseService
{
    protected ?PDO $connection = null;
    
    protected function getConnection(): PDO
    {
        if ($this->connection) {
            return $this->connection;
        }
        
        $password = $this->readRootPassword();
        
        if (!$password) {
            throw new Exception('PostgreSQL root password not found');
        }
        
        $this->connection = new PDO(
            'pgsql:host=localhost;dbname=postgres',
            'postgres',
            $password
        );
        
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        return $this->connection;
    }
    
    public function canCreateDatabase(): array
    {
        try {
            $pdo = $this->getConnection();
            
            $cacheKey = 'postgres_permissions_' . md5('postgres');
            
            return Cache::remember($cacheKey, 600, function () use ($pdo) {
                return $this->testDatabasePermissions($pdo);
            });
        } catch (Exception $e) {
            return [
                'can_create' => false,
                'has_create_db' => true,
                'has_create_user' => true,
                'has_grant_option' => true,
                'current_user' => 'postgres',
                'grants' => [],
                'missing_privileges' => ['Error: ' . $e->getMessage()],
                'message' => 'Failed to check permissions: ' . $e->getMessage(),
            ];
        }
    }
    
    protected function testDatabasePermissions(PDO $pdo): array
    {
        try {
            $testDbName = '_test_permission_check_' . time();
            
            $pdo->exec("CREATE DATABASE {$testDbName}");
            $pdo->exec("DROP DATABASE {$testDbName}");
            
            return [
                'can_create' => true,
                'has_create_db' => true,
                'has_create_user' => true,
                'has_grant_option' => true,
                'current_user' => 'postgres',
                'grants' => ['SUPERUSER'],
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
                'current_user' => 'postgres',
                'grants' => [],
                'missing_privileges' => ['CREATE DATABASE'],
                'message' => 'Insufficient permissions: ' . $e->getMessage(),
                'cached' => false,
            ];
        }
    }
    
    public function createDatabase(string $name, string $username, string $password, string $host): void
    {
        $pdo = $this->getConnection();
        
        $pdo->exec("CREATE DATABASE \"{$name}\"");
        
        $pdo->exec("CREATE USER \"{$username}\" WITH PASSWORD '{$password}'");
        
        $pdo->exec("GRANT ALL PRIVILEGES ON DATABASE \"{$name}\" TO \"{$username}\"");
    }
    
    public function deleteDatabase(string $name): void
    {
        $pdo = $this->getConnection();
        
        $pdo->exec("DROP DATABASE IF EXISTS \"{$name}\"");
    }
    
    public function databaseExists(string $name): bool
    {
        try {
            $pdo = $this->getConnection();
            
            $stmt = $pdo->prepare("SELECT 1 FROM pg_database WHERE datname = ?");
            $stmt->execute([$name]);
            
            return (bool) $stmt->fetchColumn();
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function getDatabaseSize(string $name): float
    {
        try {
            $pdo = $this->getConnection();
            
            $stmt = $pdo->prepare("SELECT pg_database_size(?) / 1024.0 / 1024.0 as size_mb");
            $stmt->execute([$name]);
            
            $result = $stmt->fetchColumn();
            
            return round((float) $result, 2);
        } catch (Exception $e) {
            return 0.0;
        }
    }
    
    public function getTableCount(string $name): int
    {
        try {
            $tempPdo = new PDO(
                "pgsql:host=localhost;dbname={$name}",
                'postgres',
                $this->readRootPassword()
            );
            
            $stmt = $tempPdo->query("
                SELECT COUNT(*) 
                FROM information_schema.tables 
                WHERE table_schema = 'public' 
                AND table_type = 'BASE TABLE'
            ");
            
            return (int) $stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }
    
    public function createUser(string $username, string $password, string $host): void
    {
        $pdo = $this->getConnection();
        
        $pdo->exec("CREATE USER \"{$username}\" WITH PASSWORD '{$password}'");
    }
    
    public function deleteUser(string $username, string $host): void
    {
        $pdo = $this->getConnection();
        
        $pdo->exec("DROP USER IF EXISTS \"{$username}\"");
    }
    
    public function userExists(string $username, string $host): bool
    {
        try {
            $pdo = $this->getConnection();
            
            $stmt = $pdo->prepare("SELECT 1 FROM pg_user WHERE usename = ?");
            $stmt->execute([$username]);
            
            return (bool) $stmt->fetchColumn();
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function changeUserPassword(string $username, string $password, string $host): void
    {
        $pdo = $this->getConnection();
        
        $pdo->exec("ALTER USER \"{$username}\" WITH PASSWORD '{$password}'");
    }
    
    public function getAllDatabaseStats(): array
    {
        try {
            $pdo = $this->getConnection();
            
            $stmt = $pdo->query("
                SELECT 
                    datname as db_name,
                    pg_database_size(datname) / 1024.0 / 1024.0 as size_mb
                FROM pg_database
                WHERE datistemplate = false
                AND datname NOT IN ('postgres', 'template0', 'template1')
            ");
            
            $stats = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $dbName = $row['db_name'];
                
                $tableCount = 0;
                try {
                    $tempPdo = new PDO(
                        "pgsql:host=localhost;dbname={$dbName}",
                        'postgres',
                        $this->readRootPassword()
                    );
                    
                    $tableStmt = $tempPdo->query("
                        SELECT COUNT(*) 
                        FROM information_schema.tables 
                        WHERE table_schema = 'public' 
                        AND table_type = 'BASE TABLE'
                    ");
                    
                    $tableCount = (int) $tableStmt->fetchColumn();
                } catch (Exception $e) {
                    // Skip if can't connect to database
                }
                
                $stats[$dbName] = [
                    'size_mb' => round((float) $row['size_mb'], 2),
                    'table_count' => $tableCount,
                ];
            }
            
            return $stats;
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function clearPermissionCache(): void
    {
        Cache::forget('postgres_permissions_' . md5('postgres'));
    }
    
    protected function getCredentialFile(): string
    {
        return '/root/.postgres_root_password';
    }
}
