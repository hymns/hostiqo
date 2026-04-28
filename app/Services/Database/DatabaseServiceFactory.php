<?php

namespace App\Services\Database;

use InvalidArgumentException;

/**
 * Factory for creating database service instances.
 * 
 * Dynamically instantiates the appropriate database service
 * based on the database type (mysql, postgresql, mongodb).
 */
class DatabaseServiceFactory
{
    /**
     * Create a database service instance for the given type.
     *
     * @param string $type Database type (mysql, postgresql, mongodb)
     * @return AbstractDatabaseService
     * @throws InvalidArgumentException If the database type is not supported
     */
    public static function make(string $type): AbstractDatabaseService
    {
        return match($type) {
            'mysql' => new MySQLDatabaseService(),
            'postgresql' => new PostgreSQLDatabaseService(),
            'mongodb' => new MongoDBDatabaseService(),
            default => throw new InvalidArgumentException("Unsupported database type: {$type}"),
        };
    }
}
