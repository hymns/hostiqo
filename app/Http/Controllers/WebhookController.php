<?php

namespace App\Http\Controllers;

use App\Models\Webhook;
use App\Services\SshKeyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WebhookController extends Controller
{
    public function __construct(
        protected SshKeyService $sshKeyService
    ) {
    }

    /**
     * Display a listing of the webhooks.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $webhooks = Webhook::withCount('deployments')
            ->with(['latestDeployment', 'website'])
            ->latest()
            ->paginate(15);

        return view('webhooks.index', compact('webhooks'));
    }

    /**
     * Show the form for creating a new webhook.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('webhooks.create');
    }

    /**
     * Store a newly created webhook in storage.
     *
     * @param Request $request The HTTP request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255'],
            'git_provider' => ['required', Rule::in(['github', 'gitlab'])],
            'repository_url' => ['required', 'string'],
            'branch' => ['required', 'string', 'max:255'],
            'local_path' => ['required', 'string', 'max:500'],
            'deploy_user' => ['nullable', 'string', 'max:255', 'regex:/^[a-z_][a-z0-9_-]*$/'],
            'is_active' => ['nullable', 'boolean'],
            'pre_deploy_script' => ['nullable', 'string'],
            'post_deploy_script' => ['nullable', 'string'],
            'slack_webhook_url' => ['nullable', 'url', 'max:500'],
            'generate_ssh_key' => ['nullable', 'boolean'],
        ]);

        $validated['secret_token'] = Str::random(64);
        $validated['is_active'] = $request->boolean('is_active');

        $webhook = Webhook::create($validated);

        // Generate SSH key if requested
        if ($request->boolean('generate_ssh_key')) {
            $this->sshKeyService->generateKeyPair($webhook);
        }

        return redirect()
            ->route('webhooks.show', $webhook)
            ->with('success', 'Webhook created successfully!');
    }

    /**
     * Display the specified webhook.
     *
     * @param Webhook $webhook The webhook model
     * @return \Illuminate\View\View
     */
    public function show(Webhook $webhook)
    {
        $webhook->load(['sshKey', 'deployments' => function ($query) {
            $query->latest()->take(20);
        }]);

        return view('webhooks.show', compact('webhook'));
    }

    /**
     * Show the form for editing the specified webhook.
     *
     * @param Webhook $webhook The webhook model
     * @return \Illuminate\View\View
     */
    public function edit(Webhook $webhook)
    {
        return view('webhooks.edit', compact('webhook'));
    }

    /**
     * Update the specified webhook in storage.
     *
     * @param Request $request The HTTP request
     * @param Webhook $webhook The webhook model
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Webhook $webhook)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255'],
            'git_provider' => ['required', Rule::in(['github', 'gitlab'])],
            'repository_url' => ['required', 'string'],
            'branch' => ['required', 'string', 'max:255'],
            'local_path' => ['required', 'string', 'max:500'],
            'deploy_user' => ['nullable', 'string', 'max:255', 'regex:/^[a-z_][a-z0-9_-]*$/'],
            'is_active' => ['nullable', 'boolean'],
            'pre_deploy_script' => ['nullable', 'string'],
            'post_deploy_script' => ['nullable', 'string'],
            'slack_webhook_url' => ['nullable', 'url', 'max:500'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        // Check if repository URL changed
        $repositoryChanged = $webhook->repository_url !== $validated['repository_url'];

        $webhook->update($validated);

        // Update git remote origin if repository URL changed
        if ($repositoryChanged && file_exists($webhook->local_path . '/.git')) {
            try {
                $result = Process::path($webhook->local_path)
                    ->run('git remote set-url origin ' . escapeshellarg($validated['repository_url']));

                if ($result->successful()) {
                    return redirect()
                        ->route('webhooks.show', $webhook)
                        ->with('success', 'Webhook updated successfully! Git remote origin has been updated.');
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to update git remote origin', [
                    'webhook_id' => $webhook->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return redirect()
            ->route('webhooks.show', $webhook)
            ->with('success', 'Webhook updated successfully!');
    }

    /**
     * Remove the specified webhook from storage.
     *
     * @param Webhook $webhook The webhook model
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Webhook $webhook)
    {
        $webhook->delete();

        return redirect()
            ->route('webhooks.index')
            ->with('success', 'Webhook deleted successfully!');
    }

    /**
     * Generate or regenerate SSH key for webhook.
     *
     * @param Webhook $webhook The webhook model
     * @return \Illuminate\Http\RedirectResponse
     */
    public function generateSshKey(Webhook $webhook)
    {
        $this->sshKeyService->generateKeyPair($webhook);

        return redirect()
            ->route('webhooks.show', $webhook)
            ->with('success', 'SSH key generated successfully!');
    }

    /**
     * Toggle webhook active status.
     *
     * @param Webhook $webhook The webhook model
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggle(Webhook $webhook)
    {
        $webhook->update(['is_active' => !$webhook->is_active]);

        $status = $webhook->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->back()
            ->with('success', "Webhook {$status} successfully!");
    }
}
