@extends('layouts.app')

@section('title', 'Edit Cron Job - Hostiqo')
@section('page-title', 'Cron Jobs')
@section('page-description', 'Edit scheduled task')

@section('content')

<div class="card">
    <div class="card-body">
        <form action="{{ route('cron-jobs.update', $cronJob) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="mb-3">
                <label class="form-label">Name *</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $cronJob->name) }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Command *</label>
                <input type="text" name="command" class="form-control @error('command') is-invalid @enderror" value="{{ old('command', $cronJob->command) }}" required>
                @error('command')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Schedule (Cron Format) *</label>
                <input type="text" name="schedule" class="form-control @error('schedule') is-invalid @enderror" value="{{ old('schedule', $cronJob->schedule) }}" required>
                @error('schedule')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Run as User *</label>
                <input type="text" name="user" class="form-control @error('user') is-invalid @enderror" value="{{ old('user', $cronJob->user) }}" required>
                @error('user')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Update Cron Job
                </button>
                <a href="{{ route('cron-jobs.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
