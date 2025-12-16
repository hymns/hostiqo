@extends('layouts.app')

@section('title', 'Create Alert Rule - Hostiqo')
@section('page-title', 'Alerts & Monitoring')
@section('page-description', 'Create a new alert rule')

@section('content')

<div class="card">
    <div class="card-body">
        <form action="{{ route('alerts.store') }}" method="POST">
            @csrf
            
            <div class="mb-3">
                <label class="form-label">Name *</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Metric *</label>
                <select name="metric" class="form-select @error('metric') is-invalid @enderror" required>
                    <option value="cpu">CPU Usage</option>
                    <option value="memory">Memory Usage</option>
                    <option value="disk">Disk Usage</option>
                    <option value="service">Service Status</option>
                </select>
                @error('metric')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Condition *</label>
                <select name="condition" class="form-select @error('condition') is-invalid @enderror" required>
                    <option value=">">Greater than (>)</option>
                    <option value="<">Less than (<)</option>
                    <option value="==">Equal to (==)</option>
                    <option value="!=">Not equal to (!=)</option>
                </select>
                @error('condition')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Threshold</label>
                <input type="number" name="threshold" class="form-control @error('threshold') is-invalid @enderror" value="{{ old('threshold', 80) }}" step="0.01">
                <small class="form-text text-muted">For CPU/Memory/Disk: percentage (e.g., 80)</small>
                @error('threshold')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Service Name (if Service metric)</label>
                <input type="text" name="service_name" class="form-control @error('service_name') is-invalid @enderror" value="{{ old('service_name') }}">
                <small class="form-text text-muted">e.g., nginx, mysql, apache2</small>
                @error('service_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Duration (minutes) *</label>
                <input type="number" name="duration" class="form-control @error('duration') is-invalid @enderror" value="{{ old('duration', 5) }}" required>
                <small class="form-text text-muted">Alert if condition persists for this many minutes</small>
                @error('duration')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Notification Channel *</label>
                <select name="channel" class="form-select @error('channel') is-invalid @enderror" required>
                    <option value="email">Email</option>
                    <option value="slack">Slack</option>
                    <option value="both">Both</option>
                </select>
                @error('channel')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Slack Webhook URL</label>
                <input type="url" name="slack_webhook" class="form-control @error('slack_webhook') is-invalid @enderror" value="{{ old('slack_webhook') }}" placeholder="https://hooks.slack.com/services/...">
                <div class="form-text">
                    <a class="text-decoration-none" data-bs-toggle="collapse" href="#slackHelp" role="button" aria-expanded="false">
                        <i class="bi bi-question-circle"></i> How to get Slack Webhook URL?
                    </a>
                    <div class="collapse mt-2" id="slackHelp">
                        <div class="card card-body bg-light small">
                            <ol class="mb-0 ps-3">
                                <li>Go to <a href="https://api.slack.com/apps" target="_blank">api.slack.com/apps</a></li>
                                <li>Create New App → From scratch</li>
                                <li>Go to <strong>Incoming Webhooks</strong> → Activate</li>
                                <li>Click <strong>Add New Webhook to Workspace</strong></li>
                                <li>Select channel and copy the Webhook URL</li>
                            </ol>
                        </div>
                    </div>
                </div>
                @error('slack_webhook')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Create Alert Rule
                </button>
                <a href="{{ route('alerts.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
