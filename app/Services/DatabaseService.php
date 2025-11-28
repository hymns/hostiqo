<?php

namespace App\Services;

use App\Models\Database;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Exception;

class DatabaseService
{
    /**
     * Check if current MySQL user has permission to create databases and users.
     * Results are cached for 10 minutes to avoid repeated tests.
     */
    public function canCreateDatabase(): array
    {
        try {
            $currentUser = DB::selectOne("SELECT CURRENT_USER() as user")->user;
            
            // Create cache key based on current user
            $cacheKey = 'db_permissions_' . md5($currentUser);
            
            // Check cache first (valid for 10 minutes)
            return Cache::remember($cacheKey, 600, function () use ($currentUser) {
                return $this->testDatabasePermissions($currentUser);
            });
        } catch (Exception $e) {
            return [
                'can_create' => false,
                'has_create_db' => false,
                'has_create_user' => false,
                'has_grant_option' => false,
                'current_user' => null,
                'grants' => [],
                'missing_privileges' => ['Error: ' . $e->getMessage()],
                'message' => 'Failed to check permissions: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Actually test database permissions by creating temporary database/user.
     */
    protected function testDatabasePermissions(string $currentUser): array
    {
        try {
            // Parse username and host from 'username'@'host' format
            preg_match("/'?([^'@]+)'?@'?([^']+)'?/", $currentUser, $matches);
            $username = $matches[1] ?? '';
            $host = $matches[2] ?? '';
            
            // Get grants for debugging
            $grants = DB::select("SHOW GRANTS FOR '{$username}'@'{$host}'");
            $grantDetails = [];
            foreach ($grants as $grant) {
                $grantDetails[] = array_values((array)$grant)[0];
            }
            
            // Test actual permissions by trying to create/drop a test database
            $testDbName = '_test_permission_check_' . time();
            $hasCreateDb = false;
            $hasCreateUser = false;
            $hasGrant = false;
            
            try {
                // Test CREATE DATABASE
                DB::statement("CREATE DATABASE `{$testDbName}`");
                $hasCreateDb = true;
                // Clean up immediately
                DB::statement("DROP DATABASE `{$testDbName}`");
            } catch (Exception $e) {
                $hasCreateDb = false;
            }
            
            try {
                // Test CREATE USER (create temporary user)
                $testUser = '_test_user_' . time();
                DB::statement("CREATE USER IF NOT EXISTS '{$testUser}'@'localhost' IDENTIFIED BY 'test123'");
                $hasCreateUser = true;
                
                // Test GRANT OPTION (try to grant privilege)
                DB::statement("GRANT SELECT ON mysql.user TO '{$testUser}'@'localhost'");
                $hasGrant = true;
                
                // Clean up
                DB::statement("DROP USER '{$testUser}'@'localhost'");
            } catch (Exception $e) {
                // If CREATE USER failed, we don't have that permission
                if (stripos($e->getMessage(), 'CREATE USER') !== false) {
                    $hasCreateUser = false;
                    $hasGrant = false;
                } elseif (stripos($e->getMessage(), 'Access denied') !== false) {
                    // If CREATE USER worked but GRANT failed
                    $hasGrant = false;
                    // Try to clean up the test user
                    try {
                        DB::statement("DROP USER IF EXISTS '{$testUser}'@'localhost'");
                    } catch (Exception $cleanup) {
                        // Ignore cleanup errors
                    }
                }
            }
            
            $missingPrivileges = [];
            if (!$hasCreateDb) $missingPrivileges[] = 'CREATE DATABASE';
            if (!$hasCreateUser) $missingPrivileges[] = 'CREATE USER';
            if (!$hasGrant) $missingPrivileges[] = 'GRANT OPTION';
            
            return [
                'can_create' => $hasCreateDb && $hasCreateUser && $hasGrant,
                'has_create_db' => $hasCreateDb,
                'has_create_user' => $hasCreateUser,
                'has_grant_option' => $hasGrant,
                'current_user' => $currentUser,
                'grants' => $grantDetails,
                'missing_privileges' => $missingPrivileges,
                'message' => $hasCreateDb && $hasCreateUser && $hasGrant 
                    ? 'You have permission to create databases and users.' 
                    : 'Missing privileges: ' . implode(', ', $missingPrivileges),
                'cached' => false, // First time, not from cache
            ];
        } catch (Exception $e) {
            return [
                'can_create' => false,
                'has_create_db' => false,
                'has_create_user' => false,
                'has_grant_option' => false,
                'current_user' => $currentUser,
                'grants' => [],
                'missing_privileges' => ['Error: ' . $e->getMessage()],
                'message' => 'Failed to test permissions: ' . $e->getMessage(),
                'cached' => false,
            ];
        }
    }

    /**
     * Clear cached permission check to force retest.
     */
    public function clearPermissionCache(): void
    {
        try {
            $currentUser = DB::selectOne("SELECT CURRENT_USER() as user")->user;
            $cacheKey = 'db_permissions_' . md5($currentUser);
            Cache::forget($cacheKey);
        } catch (Exception $e) {
            // Ignore errors
        }
    }

    /**
     * Get list of all MySQL databases (excluding system databases).
     */
    public function listDatabases(): array
    {
        $databases = DB::select('SHOW DATABASES');
        $systemDatabases = ['information_schema', 'mysql', 'performance_schema', 'sys'];
        
        $result = [];
        foreach ($databases as $db) {
            $dbName = $db->Database;
            if (!in_array($dbName, $systemDatabases)) {
                $result[] = $dbName;
            }
        }
        
        return $result;
    }

    /**
     * Create a new database and user with privileges.
     */
    public function createDatabase(string $dbName, string $username, string $password, string $host = 'localhost'): void
    {
        try {
            // Create database
            DB::statement("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Create user (if not exists)
            DB::statement("CREATE USER IF NOT EXISTS '{$username}'@'{$host}' IDENTIFIED BY '{$password}'");
            
            // Grant privileges
            DB::statement("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$username}'@'{$host}'");
            
            // Flush privileges
            DB::statement('FLUSH PRIVILEGES');
        } catch (Exception $e) {
            throw new Exception("Failed to create database: " . $e->getMessage());
        }
    }

    /**
     * Change password for a database user.
     */
    public function changeUserPassword(string $username, string $newPassword, string $host = 'localhost'): void
    {
        try {
            DB::statement("ALTER USER '{$username}'@'{$host}' IDENTIFIED BY '{$newPassword}'");
            DB::statement('FLUSH PRIVILEGES');
        } catch (Exception $e) {
            throw new Exception("Failed to change password: " . $e->getMessage());
        }
    }

    /**
     * Delete a database.
     */
    public function deleteDatabase(string $dbName): void
    {
        try {
            DB::statement("DROP DATABASE IF EXISTS `{$dbName}`");
        } catch (Exception $e) {
            throw new Exception("Failed to delete database: " . $e->getMessage());
        }
    }

    /**
     * Delete a database user.
     */
    public function deleteUser(string $username, string $host = 'localhost'): void
    {
        try {
            DB::statement("DROP USER IF EXISTS '{$username}'@'{$host}'");
            DB::statement('FLUSH PRIVILEGES');
        } catch (Exception $e) {
            throw new Exception("Failed to delete user: " . $e->getMessage());
        }
    }

    /**
     * Check if a database exists.
     */
    public function databaseExists(string $dbName): bool
    {
        $result = DB::select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", [$dbName]);
        return count($result) > 0;
    }

    /**
     * Check if a user exists.
     */
    public function userExists(string $username, string $host = 'localhost'): bool
    {
        $result = DB::select("SELECT User FROM mysql.user WHERE User = ? AND Host = ?", [$username, $host]);
        return count($result) > 0;
    }

    /**
     * Get database size in MB.
     */
    public function getDatabaseSize(string $dbName): float
    {
        $result = DB::select("
            SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
            FROM information_schema.tables 
            WHERE table_schema = ?
            GROUP BY table_schema
        ", [$dbName]);
        
        return $result[0]->size_mb ?? 0;
    }

    /**
     * Get table count for a database.
     */
    public function getTableCount(string $dbName): int
    {
        $result = DB::select("
            SELECT COUNT(*) as count
            FROM information_schema.tables 
            WHERE table_schema = ?
        ", [$dbName]);
        
        return $result[0]->count ?? 0;
    }
}
