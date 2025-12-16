@extends('layouts.app')

@section('title', 'Create Cron Job - Hostiqo')
@section('page-title', 'Cron Jobs')
@section('page-description', 'Create a new scheduled task')

@section('content')

<div class="card">
    <div class="card-body">
        <form action="{{ route('cron-jobs.store') }}" method="POST">
            @csrf
            
            <div class="mb-3">
                <label class="form-label">Name *</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Command *</label>
                <input type="text" name="command" class="form-control @error('command') is-invalid @enderror" value="{{ old('command') }}" required>
                <small class="form-text text-muted">Full command to execute (e.g., /usr/bin/php /var/www/html/artisan schedule:run)</small>
                @error('command')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Schedule (Cron Format) *</label>
                <input type="text" name="schedule" class="form-control @error('schedule') is-invalid @enderror" value="{{ old('schedule', '* * * * *') }}" required>
                <small class="form-text text-muted">
                    Format: minute hour day month weekday<br>
                    Examples: <code>* * * * *</code> (every minute), <code>0 * * * *</code> (every hour), <code>0 0 * * *</code> (daily at midnight)
                </small>
                @error('schedule')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Run as User *</label>
                <input type="text" name="user" class="form-control @error('user') is-invalid @enderror" value="{{ old('user', 'www-data') }}" required>
                @error('user')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Create Cron Job
                </button>
                <a href="{{ route('cron-jobs.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
