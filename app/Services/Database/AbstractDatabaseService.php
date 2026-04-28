<?php

namespace App\Services\Database;

use Exception;

/**
 * Abstract base class for database service implementations.
 * 
 * Provides a common interface for managing different database types
 * (MySQL, PostgreSQL, MongoDB) with CRUD operations, user management,
 * and statistics retrieval.
 */
abstract class AbstractDatabaseService
{
    /**
     * Check if the current user has permission to create databases and users.
     *
     * @return array{can_create: bool, has_create_db: bool, has_create_user: bool, has_grant_option: bool, current_user: string|null, grants: array, missing_privileges: array, message: string}
     */
    abstract public function canCreateDatabase(): array;
    
    /**
     * Create a new database with a dedicated user.
     *
     * @param string $name Database name
     * @param string $username Username for the database
     * @param string $password Password for the user
     * @param string $host Host from which the user can connect
     * @return void
     */
    abstract public function createDatabase(string $name, string $username, string $password, string $host): void;
    
    /**
     * Delete a database.
     *
     * @param string $name Database name
     * @return void
     */
    abstract public function deleteDatabase(string $name): void;
    
    /**
     * Check if a database exists.
     *
     * @param string $name Database name
     * @return bool
     */
    abstract public function databaseExists(string $name): bool;
    
    /**
     * Get the size of a database in megabytes.
     *
     * @param string $name Database name
     * @return float Size in MB
     */
    abstract public function getDatabaseSize(string $name): float;
    
    /**
     * Get the number of tables/collections in a database.
     *
     * @param string $name Database name
     * @return int Number of tables or collections
     */
    abstract public function getTableCount(string $name): int;
    
    /**
     * Create a new database user.
     *
     * @param string $username Username
     * @param string $password Password
     * @param string $host Host from which the user can connect
     * @return void
     */
    abstract public function createUser(string $username, string $password, string $host): void;
    
    /**
     * Delete a database user.
     *
     * @param string $username Username
     * @param string $host Host
     * @return void
     */
    abstract public function deleteUser(string $username, string $host): void;
    
    /**
     * Check if a user exists.
     *
     * @param string $username Username
     * @param string $host Host
     * @return bool
     */
    abstract public function userExists(string $username, string $host): bool;
    
    /**
     * Change a user's password.
     *
     * @param string $username Username
     * @param string $password New password
     * @param string $host Host
     * @return void
     */
    abstract public function changeUserPassword(string $username, string $password, string $host): void;
    
    /**
     * Get statistics for all databases.
     *
     * @return array<string, array{size_mb: float, table_count: int}> Database stats indexed by database name
     */
    abstract public function getAllDatabaseStats(): array;
    
    /**
     * Clear the permission cache.
     *
     * @return void
     */
    abstract public function clearPermissionCache(): void;
    
    /**
     * Read the root password from the credential file.
     *
     * @return string|null The password or null if file doesn't exist
     */
    protected function readRootPassword(): ?string
    {
        $credentialFile = $this->getCredentialFile();
        
        if (!file_exists($credentialFile)) {
            return null;
        }
        
        return trim(file_get_contents($credentialFile));
    }
    
    /**
     * Get the path to the credential file for this database type.
     *
     * @return string Full path to credential file
     */
    abstract protected function getCredentialFile(): string;
}
