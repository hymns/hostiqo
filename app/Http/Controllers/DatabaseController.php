<?php

namespace App\Http\Controllers;

use App\Models\Database;
use App\Services\Database\DatabaseServiceFactory;
use App\Services\Database\AbstractDatabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Exception;

/**
 * Controller for managing databases across different database types.
 * 
 * Supports MySQL, PostgreSQL, and MongoDB database management
 * with CRUD operations, user management, and statistics.
 */
class DatabaseController extends Controller
{
    /**
     * Get the database service for the specified type.
     *
     * @param string $type Database type (mysql, postgresql, mongodb)
     * @return AbstractDatabaseService
     */
    protected function getService(string $type): AbstractDatabaseService
    {
        return DatabaseServiceFactory::make($type);
    }
    
    /**
     * Get the database type from the request or default to mysql.
     *
     * @param Request $request
     * @return string
     */
    protected function getType(Request $request): string
    {
        return $request->route('type') ?? 'mysql';
    }

    /**
     * Clear permission cache and recheck.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function recheckPermissions(Request $request)
    {
        $type = $this->getType($request);
        $service = $this->getService($type);
        
        $service->clearPermissionCache();
        
        return redirect()
            ->route("databases.{$type}.index")
            ->with('success', 'Permissions rechecked successfully!');
    }

    /**
     * Display a listing of the databases.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $type = $this->getType($request);
        $service = $this->getService($type);
        
        $databases = Database::where('type', $type)->latest()->paginate(15);
        
        // Prefetch all database stats in single query
        $dbStats = $service->getAllDatabaseStats();
        
        // Enhance database records with database info
        foreach ($databases as $database) {
            $database->exists_in_db = isset($dbStats[$database->name]);
            if ($database->exists_in_db) {
                $database->size_mb = $dbStats[$database->name]['size_mb'];
                $database->table_count = $dbStats[$database->name]['table_count'];
            }
        }
        
        // Check if user has permission to create databases
        $permissions = $service->canCreateDatabase();
        
        return view('databases.index', compact('databases', 'permissions', 'type'));
    }

    /**
     * Show the form for creating a new database.
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function create(Request $request)
    {
        $type = $this->getType($request);
        $service = $this->getService($type);
        
        // Check if user has permission to create databases
        $permissions = $service->canCreateDatabase();
        
        if (!$permissions['can_create']) {
            return redirect()
                ->route("databases.{$type}.index")
                ->withErrors([
                    'permission' => $permissions['message']
                ]);
        }
        
        return view('databases.create', compact('permissions', 'type'));
    }

    /**
     * Store a newly created database in storage.
     *
     * @param Request $request The HTTP request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $type = $this->getType($request);
        $service = $this->getService($type);
        
        // Check permissions before proceeding
        $permissions = $service->canCreateDatabase();
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
        
        // Get default port for database type
        $port = match($type) {
            'mysql' => 3306,
            'postgresql' => 5432,
            'mongodb' => 27017,
            default => null,
        };

        try {
            // Check if database already exists
            if ($service->databaseExists($validated['name'])) {
                return back()
                    ->withInput()
                    ->withErrors(['name' => "Database already exists in {$type}."]);
            }

            // Create database and user
            $service->createDatabase(
                $validated['name'],
                $validated['username'],
                $validated['password'],
                $host
            );

            // Save to tracking table
            $database = Database::create([
                'name' => $validated['name'],
                'type' => $type,
                'username' => $validated['username'],
                'host' => $host,
                'port' => $port,
                'description' => $validated['description'] ?? null,
            ]);

            return redirect()
                ->route("databases.{$type}.index")
                ->with('success', 'Database and user created successfully!');
        } catch (Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create database: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified database.
     *
     * @param Request $request
     * @param Database $database The database model
     * @return \Illuminate\View\View
     */
    public function show(Request $request, Database $database)
    {
        $type = $database->type ?? 'mysql';
        $service = $this->getService($type);
        
        $database->exists_in_db = $service->databaseExists($database->name);
        if ($database->exists_in_db) {
            $database->size_mb = $service->getDatabaseSize($database->name);
            $database->table_count = $service->getTableCount($database->name);
        }
        
        return view('databases.show', compact('database', 'type'));
    }

    /**
     * Show the form for editing the specified database.
     *
     * @param Request $request
     * @param Database $database The database model
     * @return \Illuminate\View\View
     */
    public function edit(Request $request, Database $database)
    {
        $type = $database->type ?? 'mysql';
        return view('databases.edit', compact('database', 'type'));
    }

    /**
     * Update the specified database in storage (metadata only).
     *
     * @param Request $request The HTTP request
     * @param Database $database The database model
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Database $database)
    {
        $type = $database->type ?? 'mysql';
        
        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $database->update($validated);

        return redirect()
            ->route("databases.{$type}.show", $database)
            ->with('success', 'Database information updated successfully!');
    }

    /**
     * Show form to change user password.
     *
     * @param Request $request
     * @param Database $database The database model
     * @return \Illuminate\View\View
     */
    public function showChangePasswordForm(Request $request, Database $database)
    {
        $type = $database->type ?? 'mysql';
        return view('databases.change-password', compact('database', 'type'));
    }

    /**
     * Change password for database user.
     *
     * @param Request $request The HTTP request
     * @param Database $database The database model
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changePassword(Request $request, Database $database)
    {
        $type = $database->type ?? 'mysql';
        $service = $this->getService($type);
        
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $service->changeUserPassword(
                $database->username,
                $validated['password'],
                $database->host
            );

            return redirect()
                ->route("databases.{$type}.show", $database)
                ->with('success', 'Password changed successfully!');
        } catch (Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to change password: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified database from storage.
     *
     * @param Request $request
     * @param Database $database The database model
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request, Database $database)
    {
        $type = $database->type ?? 'mysql';
        $service = $this->getService($type);
        
        try {
            // Delete database
            $service->deleteDatabase($database->name);
            
            // Delete user
            $service->deleteUser($database->username, $database->host);
            
            // Delete from tracking table
            $database->delete();

            return redirect()
                ->route("databases.{$type}.index")
                ->with('success', 'Database and user deleted successfully!');
        } catch (Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to delete database: ' . $e->getMessage()]);
        }
    }
}
