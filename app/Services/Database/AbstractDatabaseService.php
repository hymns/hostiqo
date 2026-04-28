<?php

namespace App\Services\Database;

use Exception;

abstract class AbstractDatabaseService
{
    abstract public function canCreateDatabase(): array;
    
    abstract public function createDatabase(string $name, string $username, string $password, string $host): void;
    
    abstract public function deleteDatabase(string $name): void;
    
    abstract public function databaseExists(string $name): bool;
    
    abstract public function getDatabaseSize(string $name): float;
    
    abstract public function getTableCount(string $name): int;
    
    abstract public function createUser(string $username, string $password, string $host): void;
    
    abstract public function deleteUser(string $username, string $host): void;
    
    abstract public function userExists(string $username, string $host): bool;
    
    abstract public function changeUserPassword(string $username, string $password, string $host): void;
    
    abstract public function getAllDatabaseStats(): array;
    
    abstract public function clearPermissionCache(): void;
    
    protected function readRootPassword(): ?string
    {
        $credentialFile = $this->getCredentialFile();
        
        if (!file_exists($credentialFile)) {
            return null;
        }
        
        return trim(file_get_contents($credentialFile));
    }
    
    abstract protected function getCredentialFile(): string;
}
