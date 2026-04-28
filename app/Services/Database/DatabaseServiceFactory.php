<?php

namespace App\Services\Database;

use InvalidArgumentException;

class DatabaseServiceFactory
{
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
