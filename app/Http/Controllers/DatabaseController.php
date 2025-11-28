<?php

namespace App\Http\Controllers;

use App\Models\Database;
use App\Services\DatabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Exception;

class DatabaseController extends Controller
{
    public function __construct(
        protected DatabaseService $databaseService
    ) {
    }

    /**
     * Clear permission cache and recheck.
     */
    public function recheckPermissions()
    {
        $this->databaseService->clearPermissionCache();
        
        return redirect()
            ->route('databases.index')
            ->with('success', 'Permissions rechecked successfully!');
    }

    /**
     * Display a listing of the databases.
     */
    public function index()
    {
        $databases = Database::latest()->paginate(15);
        
        // Get MySQL databases list
        $mysqlDatabases = $this->databaseService->listDatabases();
        
        // Enhance database records with MySQL info
        foreach ($databases as $database) {
            $database->exists_in_mysql = in_array($database->name, $mysqlDatabases);
            if ($database->exists_in_mysql) {
                $database->size_mb = $this->databaseService->getDatabaseSize($database->name);
                $database->table_count = $this->databaseService->getTableCount($database->name);
            }
        }
        
        // Check if user has permission to create databases
        $permissions = $this->databaseService->canCreateDatabase();
        
        return view('databases.index', compact('databases', 'permissions'));
    }

    /**
     * Show the form for creating a new database.
     */
    public function create()
    {
        // Check if user has permission to create databases
        $permissions = $this->databaseService->canCreateDatabase();
        
        if (!$permissions['can_create']) {
            return redirect()
                ->route('databases.index')
                ->withErrors([
                    'permission' => $permissions['message']
                ]);
        }
        
        return view('databases.create', compact('permissions'));
    }

    /**
     * Store a newly created database in storage.
     */
    public function store(Request $request)
    {
        // Check permissions before proceeding
        $permissions = $this->databaseService->canCreateDatabase();
        if (!$permissions['can_create']) {
            return back()
                ->withInput()
                ->withErrors(['permission' => $permissions['message']]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_]+$/', 'unique:databases,name'],
            'username' => ['required', 'string', 'max:32', 'regex:/^[a-zA-Z0-9_]+$/'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'host' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $host = $validated['host'] ?? 'localhost';

        try {
            // Check if database already exists in MySQL
            if ($this->databaseService->databaseExists($validated['name'])) {
                return back()
                    ->withInput()
                    ->withErrors(['name' => 'Database already exists in MySQL.']);
            }

            // Create database and user in MySQL
            $this->databaseService->createDatabase(
                $validated['name'],
                $validated['username'],
                $validated['password'],
                $host
            );

            // Save to tracking table
            $database = Database::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'host' => $host,
                'description' => $validated['description'] ?? null,
            ]);

            return redirect()
                ->route('databases.index')
                ->with('success', 'Database and user created successfully!');
        } catch (Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create database: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified database.
     */
    public function show(Database $database)
    {
        $database->exists_in_mysql = $this->databaseService->databaseExists($database->name);
        if ($database->exists_in_mysql) {
            $database->size_mb = $this->databaseService->getDatabaseSize($database->name);
            $database->table_count = $this->databaseService->getTableCount($database->name);
        }
        
        return view('databases.show', compact('database'));
    }

    /**
     * Show the form for editing the specified database.
     */
    public function edit(Database $database)
    {
        return view('databases.edit', compact('database'));
    }

    /**
     * Update the specified database in storage (metadata only).
     */
    public function update(Request $request, Database $database)
    {
        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $database->update($validated);

        return redirect()
            ->route('databases.show', $database)
            ->with('success', 'Database information updated successfully!');
    }

    /**
     * Show form to change user password.
     */
    public function showChangePasswordForm(Database $database)
    {
        return view('databases.change-password', compact('database'));
    }

    /**
     * Change password for database user.
     */
    public function changePassword(Request $request, Database $database)
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $this->databaseService->changeUserPassword(
                $database->username,
                $validated['password'],
                $database->host
            );

            return redirect()
                ->route('databases.show', $database)
                ->with('success', 'Password changed successfully!');
        } catch (Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to change password: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified database from storage.
     */
    public function destroy(Database $database)
    {
        try {
            // Delete database from MySQL
            if ($this->databaseService->databaseExists($database->name)) {
                $this->databaseService->deleteDatabase($database->name);
            }

            // Delete user from MySQL
            if ($this->databaseService->userExists($database->username, $database->host)) {
                $this->databaseService->deleteUser($database->username, $database->host);
            }

            // Delete from tracking table
            $database->delete();

            return redirect()
                ->route('databases.index')
                ->with('success', 'Database and user deleted successfully!');
        } catch (Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to delete database: ' . $e->getMessage()]);
        }
    }
}
