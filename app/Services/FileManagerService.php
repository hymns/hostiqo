<?php

namespace App\Services;

use App\Exceptions\FileManagerException;
use Illuminate\Support\Facades\File;

class FileManagerService
{
    // Allowed base paths for safety
    protected array $allowedBasePaths = [
        '/var/www',
        '/home',
        '/opt',
        '/usr/local',
    ];

    // Restricted paths that should never be accessible
    protected array $restrictedPaths = [
        '/etc/shadow',
        '/etc/passwd',
        '/root/.ssh',
        '/etc/ssh',
    ];

    /**
     * List directory contents.
     *
     * @param string $path The directory path
     * @return array<int, array> List of directory items
     * @throws FileManagerException If directory cannot be listed
     */
    public function listDirectory(string $path = '/var/www'): array
    {
        // Sanitize and validate path
        $path = $this->sanitizePath($path);
        $this->validatePath($path);

        try {
            if (!File::isDirectory($path)) {
                throw FileManagerException::directoryNotFound($path);
            }

            if (!File::isReadable($path)) {
                throw FileManagerException::permissionDenied($path);
            }

            $items = [];
            // Use scandir to include hidden files (starting with .)
            $allFiles = scandir($path);

            foreach ($allFiles as $file) {
                // Skip . and ..
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $fullPath = rtrim($path, '/') . '/' . $file;
                $stat = @stat($fullPath);
                
                if ($stat === false) {
                    continue;
                }

                $items[] = [
                    'name' => $file,
                    'path' => $fullPath,
                    'type' => File::isDirectory($fullPath) ? 'directory' : 'file',
                    'permissions' => substr(sprintf('%o', $stat['mode']), -4),
                    'owner' => function_exists('posix_getpwuid') ? (posix_getpwuid($stat['uid'])['name'] ?? $stat['uid']) : $stat['uid'],
                    'group' => function_exists('posix_getgrgid') ? (posix_getgrgid($stat['gid'])['name'] ?? $stat['gid']) : $stat['gid'],
                    'size' => $this->formatBytes($stat['size']),
                    'modified' => date('M d H:i', $stat['mtime']),
                    'is_readable' => File::isReadable($fullPath),
                    'is_writable' => File::isWritable($fullPath),
                ];
            }

            // Sort: directories first, then files (both alphabetically)
            usort($items, function($a, $b) {
                if ($a['type'] !== $b['type']) {
                    return $a['type'] === 'directory' ? -1 : 1;
                }
                return strcasecmp($a['name'], $b['name']);
            });

            return $items;

        } catch (FileManagerException $e) {
            throw FileManagerException::operationFailed('list directory', $e->getMessage());
        }
    }

    /**
     * Read file contents.
     *
     * @param string $path The file path
     * @return string The file contents
     * @throws FileManagerException If file cannot be read
     */
    public function readFile(string $path): string
    {
        $path = $this->sanitizePath($path);
        $this->validatePath($path);

        try {
            if (!File::exists($path)) {
                throw FileManagerException::fileNotFound();
            }

            if (!File::isFile($path)) {
                throw FileManagerException::notAFile();
            }

            if (!File::isReadable($path)) {
                throw FileManagerException::fileNotReadable();
            }

            // Limit file size to 10MB for safety
            $filesize = File::size($path);
            if ($filesize > 10485760) {
                throw FileManagerException::fileTooLarge();
            }

            return File::get($path);

        } catch (FileManagerException $e) {
            throw FileManagerException::operationFailed('read file', $e->getMessage());
        }
    }

    /**
     * Write file contents.
     *
     * @param string $path The file path
     * @param string $content The content to write
     * @return bool True on success
     * @throws FileManagerException If file cannot be written
     */
    public function writeFile(string $path, string $content): bool
    {
        $path = $this->sanitizePath($path);
        $this->validatePath($path);

        try {
            // Check if directory exists, if not create it
            $directory = dirname($path);
            if (!File::isDirectory($directory)) {
                // 0755: Owner rwx, Group rx, Others rx - secure for directories
                File::makeDirectory($directory, 0755, true);
            }

            // Check if we can write (either file doesn't exist or is writable)
            if (File::exists($path) && !File::isWritable($path)) {
                throw FileManagerException::fileNotWritable();
            }

            // Write content
            File::put($path, $content);

            // Set permissions for new files
            // 0644: Owner rw, Group r, Others r - secure for files (no execute)
            File::chmod($path, 0644);

            return true;

        } catch (FileManagerException $e) {
            throw FileManagerException::operationFailed('write file', $e->getMessage());
        }
    }

    /**
     * Delete file or directory.
     *
     * @param string $path The path to delete
     * @param bool $recursive Whether to delete recursively
     * @return bool True on success
     * @throws FileManagerException If deletion fails
     */
    public function delete(string $path, bool $recursive = false): bool
    {
        $path = $this->sanitizePath($path);
        $this->validatePath($path);

        try {
            if (!File::exists($path)) {
                throw FileManagerException::pathNotFound();
            }

            if (File::isDirectory($path)) {
                File::deleteDirectory($path, $preserve = !$recursive);
            } else {
                File::delete($path);
            }

            return true;

        } catch (FileManagerException $e) {
            throw FileManagerException::operationFailed('delete', $e->getMessage());
        }
    }

    /**
     * Create directory.
     *
     * @param string $path The directory path to create
     * @return bool True on success
     * @throws FileManagerException If creation fails
     */
    public function createDirectory(string $path): bool
    {
        $path = $this->sanitizePath($path);
        $this->validatePath($path);

        try {
            if (File::exists($path)) {
                throw FileManagerException::pathAlreadyExists();
            }

            // 0755: Owner rwx, Group rx, Others rx - secure for directories
            File::makeDirectory($path, 0755, true);

            return true;

        } catch (FileManagerException $e) {
            throw FileManagerException::operationFailed('create directory', $e->getMessage());
        }
    }

    /**
     * Rename/move file or directory.
     *
     * @param string $oldPath The source path
     * @param string $newPath The destination path
     * @return bool True on success
     * @throws FileManagerException If rename fails
     */
    public function rename(string $oldPath, string $newPath): bool
    {
        $oldPath = $this->sanitizePath($oldPath);
        $newPath = $this->sanitizePath($newPath);
        $this->validatePath($oldPath);
        $this->validatePath($newPath);

        try {
            if (!File::exists($oldPath)) {
                throw FileManagerException::sourceNotFound();
            }

            if (File::exists($newPath)) {
                throw FileManagerException::destinationExists();
            }

            File::move($oldPath, $newPath);

            return true;

        } catch (FileManagerException $e) {
            throw FileManagerException::operationFailed('rename', $e->getMessage());
        }
    }

    /**
     * Change file permissions.
     *
     * @param string $path The file path
     * @param string $permissions The permissions in octal format (e.g., '0755')
     * @return bool True on success
     * @throws FileManagerException If chmod fails
     */
    public function chmod(string $path, string $permissions): bool
    {
        $path = $this->sanitizePath($path);
        $this->validatePath($path);

        // Validate permissions format (octal)
        if (!preg_match('/^[0-7]{3,4}$/', $permissions)) {
            throw FileManagerException::invalidPermissionsFormat();
        }

        try {
            if (!File::exists($path)) {
                throw FileManagerException::fileNotFound();
            }

            // Convert string octal to integer
            $mode = octdec($permissions);
            
            File::chmod($path, $mode);

            return true;

        } catch (FileManagerException $e) {
            throw FileManagerException::operationFailed('change permissions', $e->getMessage());
        }
    }

    /**
     * Get file info.
     *
     * @param string $path The file path
     * @return array{path: string, size: int, permissions: string, owner: string|int, group: string|int, modified: string}
     * @throws FileManagerException If file info cannot be retrieved
     */
    public function getFileInfo(string $path): array
    {
        $path = $this->sanitizePath($path);

        try {
            if (!File::exists($path)) {
                throw FileManagerException::fileNotFound();
            }

            $stat = stat($path);
            
            if ($stat === false) {
                throw FileManagerException::failedToGetFileInfo();
            }

            return [
                'path' => $path,
                'size' => File::size($path),
                'permissions' => substr(sprintf('%o', $stat['mode']), -4),
                'owner' => function_exists('posix_getpwuid') ? (posix_getpwuid($stat['uid'])['name'] ?? $stat['uid']) : $stat['uid'],
                'group' => function_exists('posix_getgrgid') ? (posix_getgrgid($stat['gid'])['name'] ?? $stat['gid']) : $stat['gid'],
                'modified' => date('Y-m-d H:i:s', File::lastModified($path)),
            ];

        } catch (FileManagerException $e) {
            throw FileManagerException::operationFailed('get file info', $e->getMessage());
        }
    }

    /**
     * Upload file content.
     *
     * @param string $targetPath The target file path
     * @param string $content The content to upload
     * @return bool True on success
     */
    public function uploadFile(string $targetPath, string $content): bool
    {
        return $this->writeFile($targetPath, $content);
    }

    /**
     * Download file.
     *
     * @param string $path The file path
     * @return string The file contents
     */
    public function downloadFile(string $path): string
    {
        return $this->readFile($path);
    }

    /**
     * Sanitize path.
     *
     * @param string $path The path to sanitize
     * @return string The sanitized path
     */
    protected function sanitizePath(string $path): string
    {
        // Remove any dangerous characters
        $path = str_replace(['..', '~'], '', $path);
        
        // Ensure absolute path
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        return $path;
    }

    /**
     * Validate path is in allowed locations.
     *
     * @param string $path The path to validate
     * @return void
     * @throws FileManagerException If path is not allowed
     */
    protected function validatePath(string $path): void
    {
        // Check if path is in restricted list
        foreach ($this->restrictedPaths as $restricted) {
            if (str_starts_with($path, $restricted)) {
                throw FileManagerException::accessDeniedRestricted();
            }
        }

        // Check if path is in allowed base paths
        $isAllowed = false;
        foreach ($this->allowedBasePaths as $allowed) {
            if (str_starts_with($path, $allowed)) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
            throw FileManagerException::accessDeniedOutsideAllowed();
        }
    }

    /**
     * Format bytes to human readable format.
     *
     * @param int $bytes The bytes to format
     * @param int $precision The decimal precision
     * @return string The formatted string
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'K', 'M', 'G', 'T'];
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . $units[$pow];
    }
}
