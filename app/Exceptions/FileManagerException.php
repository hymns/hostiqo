<?php

namespace App\Exceptions;

use Exception;

/**
 * Custom exception for file manager operations.
 *
 * Provides specific exception types for various file system operations
 * to improve error handling and maintainability.
 */
class FileManagerException extends Exception
{
    /**
     * Create exception for directory not found.
     *
     * @param string $path The directory path that was not found
     * @return self
     */
    public static function directoryNotFound(string $path): self
    {
        return new self("Directory not found: {$path}");
    }

    /**
     * Create exception for permission denied.
     *
     * @param string $path The path where permission was denied
     * @return self
     */
    public static function permissionDenied(string $path): self
    {
        return new self("Permission denied: {$path}");
    }

    /**
     * Create exception for file not found.
     *
     * @return self
     */
    public static function fileNotFound(): self
    {
        return new self("File not found");
    }

    /**
     * Create exception when path is not a file.
     *
     * @return self
     */
    public static function notAFile(): self
    {
        return new self("Path is not a file");
    }

    /**
     * Create exception for file not readable.
     *
     * @return self
     */
    public static function fileNotReadable(): self
    {
        return new self("File not readable");
    }

    /**
     * Create exception for file too large.
     *
     * Thrown when file exceeds the 10MB size limit.
     *
     * @return self
     */
    public static function fileTooLarge(): self
    {
        return new self("File too large (max 10MB)");
    }

    /**
     * Create exception for file not writable.
     *
     * @return self
     */
    public static function fileNotWritable(): self
    {
        return new self("File is not writable");
    }

    /**
     * Create exception for path not found.
     *
     * Generic exception for when a file or directory doesn't exist.
     *
     * @return self
     */
    public static function pathNotFound(): self
    {
        return new self("File or directory not found");
    }

    /**
     * Create exception for path already exists.
     *
     * @return self
     */
    public static function pathAlreadyExists(): self
    {
        return new self("Path already exists");
    }

    /**
     * Create exception for source path not found.
     *
     * Used in rename/move operations when source doesn't exist.
     *
     * @return self
     */
    public static function sourceNotFound(): self
    {
        return new self("Source path not found");
    }

    /**
     * Create exception for destination already exists.
     *
     * Used in rename/move operations when destination already exists.
     *
     * @return self
     */
    public static function destinationExists(): self
    {
        return new self("Destination path already exists");
    }

    /**
     * Create exception for invalid permissions format.
     *
     * Thrown when permissions string doesn't match octal format (e.g., '0755').
     *
     * @return self
     */
    public static function invalidPermissionsFormat(): self
    {
        return new self("Invalid permissions format");
    }

    /**
     * Create exception for failed to get file info.
     *
     * @return self
     */
    public static function failedToGetFileInfo(): self
    {
        return new self("Failed to get file info");
    }

    /**
     * Create exception for access denied to restricted path.
     *
     * Thrown when attempting to access system-critical paths like /etc/shadow.
     *
     * @return self
     */
    public static function accessDeniedRestricted(): self
    {
        return new self("Access denied: Restricted path");
    }

    /**
     * Create exception for access denied outside allowed locations.
     *
     * Thrown when path is outside configured allowed base paths.
     *
     * @return self
     */
    public static function accessDeniedOutsideAllowed(): self
    {
        return new self("Access denied: Path outside allowed locations");
    }

    /**
     * Create exception for generic operation failure.
     *
     * Wrapper exception that includes the operation name and original error message.
     *
     * @param string $operation The operation that failed (e.g., 'read file', 'delete')
     * @param string $message The original error message
     * @return self
     */
    public static function operationFailed(string $operation, string $message): self
    {
        return new self("Failed to {$operation}: {$message}");
    }
}
