@extends('layouts.app')

@section('title', 'Supervisor Programs - Hostiqo')
@section('page-title', 'Supervisor Programs')
@section('page-description', 'Manage long-running processes and background workers')

@section('page-actions')
    <a href="{{ route('supervisor.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Create Program
    </a>
@endsection

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show">
        {{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card">
    <div class="card-body">
        @if($programs->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-terminal text-muted" style="font-size: 4rem;"></i>
                <h4 class="mt-4">No supervisor programs yet</h4>
                <p class="text-muted">Create your first supervisor program to manage background processes like queue workers and schedulers.</p>
                <a href="{{ route('supervisor.create') }}" class="btn btn-primary mt-3">
                    <i class="bi bi-plus-circle me-1"></i> Create Your First Program
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Program Name</th>
                            <th>Command</th>
                            <th>Directory</th>
                            <th>Processes</th>
                            <th>Status</th>
                            <th>Active</th>
                            <th width="200">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($programs as $program)
                            @php
                                $liveStatus = collect($livePrograms['programs'] ?? [])->firstWhere('name', $program->name);
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $program->name }}</strong>
                                    @if($program->description)
                                        <br><small class="text-muted">{{ Str::limit($program->description, 50) }}</small>
                                    @endif
                                </td>
                                <td><code>{{ Str::limit($program->command, 40) }}</code></td>
                                <td><code class="small">{{ Str::limit($program->directory, 30) }}</code></td>
                                <td>
                                    <span class="badge bg-secondary">{{ $program->numprocs }}</span>
                                </td>
                                <td>
                                    @if($liveStatus)
                                        @if($liveStatus['status'] === 'RUNNING')
                                            <span class="badge bg-success">
                                                <i class="bi bi-play-circle me-1"></i> Running
                                            </span>
                                        @elseif($liveStatus['status'] === 'STOPPED')
                                            <span class="badge bg-secondary">
                                                <i class="bi bi-stop-circle me-1"></i> Stopped
                                            </span>
                                        @elseif($liveStatus['status'] === 'STARTING')
                                            <span class="badge bg-info">
                                                <i class="bi bi-hourglass-split me-1"></i> Starting
                                            </span>
                                        @elseif($liveStatus['status'] === 'FATAL')
                                            <span class="badge bg-danger">
                                                <i class="bi bi-x-circle me-1"></i> Fatal
                                            </span>
                                        @else
                                            <span class="badge bg-warning">
                                                {{ $liveStatus['status'] }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="badge bg-secondary">Not Deployed</span>
                                    @endif
                                </td>
                                <td>
                                    @if($program->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('supervisor.show', $program) }}" class="btn btn-outline-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('supervisor.edit', $program) }}" class="btn btn-outline-secondary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('supervisor.destroy', $program) }}" method="POST" style="display: contents;" onsubmit="return confirm('Are you sure you want to delete this program?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@endsection
