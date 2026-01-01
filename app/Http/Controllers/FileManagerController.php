<?php

namespace App\Http\Controllers;

use App\Exceptions\FileManagerException;
use App\Services\FileManagerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Exception;

class FileManagerController extends Controller
{
    protected FileManagerService $fileManager;

    public function __construct(FileManagerService $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    /**
     * File browser index
     */
    public function index(Request $request)
    {
        $path = $request->input('path', '/var/www');

        try {
            $items = $this->fileManager->listDirectory($path);
            $parentPath = dirname($path);

            return view('files.index', compact('path', 'items', 'parentPath'));

        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * View/edit file
     */
    public function edit(Request $request)
    {
        $path = $request->input('path');

        if (!$path) {
            return back()->with('error', 'File path is required');
        }

        try {
            $content = $this->fileManager->readFile($path);
            $fileInfo = $this->fileManager->getFileInfo($path);

            return view('files.edit', compact('path', 'content', 'fileInfo'));

        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Save file
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'path' => 'required|string',
            'content' => 'required|string',
        ]);

        try {
            $this->fileManager->writeFile($validated['path'], $validated['content']);

            return back()->with('success', 'File saved successfully');

        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete file or directory
     */
    public function destroy(Request $request)
    {
        $path = $request->input('path');
        $recursive = $request->boolean('recursive', false);

        if (!$path) {
            return back()->with('error', 'Path is required');
        }

        try {
            $this->fileManager->delete($path, $recursive);

            return back()->with('success', 'Deleted successfully');

        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Create directory
     */
    public function createDirectory(Request $request)
    {
        $validated = $request->validate([
            'path' => 'required|string',
            'name' => 'required|string|max:255',
        ]);

        $newPath = rtrim($validated['path'], '/') . '/' . $validated['name'];

        try {
            $this->fileManager->createDirectory($newPath);

            return back()->with('success', 'Directory created successfully');

        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Create file
     */
    public function createFile(Request $request)
    {
        $validated = $request->validate([
            'path' => 'required|string',
            'name' => 'required|string|max:255',
        ]);

        $newPath = rtrim($validated['path'], '/') . '/' . $validated['name'];

        try {
            $this->fileManager->writeFile($newPath, '');

            return redirect()
                ->route('files.edit', ['path' => $newPath])
                ->with('success', 'File created successfully');

        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Rename file or directory
     */
    public function rename(Request $request)
    {
        $validated = $request->validate([
            'path' => 'required|string',
            'new_name' => 'required|string|max:255',
        ]);

        $oldPath = $validated['path'];
        $newPath = dirname($oldPath) . '/' . $validated['new_name'];

        try {
            $this->fileManager->rename($oldPath, $newPath);

            return back()->with('success', 'Renamed successfully');

        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Change permissions
     */
    public function chmod(Request $request)
    {
        $validated = $request->validate([
            'path' => 'required|string',
            'permissions' => 'required|string|regex:/^[0-7]{3,4}$/',
        ]);

        try {
            $this->fileManager->chmod($validated['path'], $validated['permissions']);

            return back()->with('success', 'Permissions changed successfully');

        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Upload file
     */
    public function upload(Request $request)
    {
        // Security: Limit file upload to 10MB (10240 KB) to prevent DoS attacks
        // and memory exhaustion from large file uploads
        $validated = $request->validate([
            'path' => 'required|string',
            'file' => 'required|file|max:10240', // 10MB max - aligned with FileManagerService read limit
        ]);

        $file = $request->file('file');
        $targetPath = rtrim($validated['path'], '/') . '/' . $file->getClientOriginalName();

        try {
            // Additional safety check: Verify file size before reading into memory
            $fileSize = $file->getSize();
            if ($fileSize > 10485760) { // 10MB in bytes
                throw FileManagerException::fileTooLarge();
            }

            $content = file_get_contents($file->getRealPath());
            $this->fileManager->uploadFile($targetPath, $content);

            return back()->with('success', 'File uploaded successfully');

        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Download file
     */
    public function download(Request $request)
    {
        $path = $request->input('path');

        if (!$path) {
            return back()->with('error', 'File path is required');
        }

        try {
            $content = $this->fileManager->downloadFile($path);
            $filename = basename($path);

            return Response::make($content, 200, [
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Content-Length' => strlen($content),
            ]);

        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
