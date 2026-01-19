@extends('layouts.app')

@section('title', 'Systemd Services - Hostiqo')
@section('page-title', 'Systemd Services')
@section('page-description', 'Manage system daemon services')

@section('page-actions')
    <a href="{{ route('systemd.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Create Service
    </a>
@endsection

@section('content')
    @if($services->isEmpty())
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            No systemd services found. Create your first service to get started.
        </div>
    @else
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>User</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($services as $service)
                                <tr>
                                    <td>
                                        <strong>{{ $service->name }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $service->service_file_name }}</small>
                                    </td>
                                    <td>{{ $service->description }}</td>
                                    <td>
                                        <span class="badge bg-{{ $service->status_badge }}">
                                            {{ ucfirst($service->status ?? 'unknown') }}
                                        </span>
                                    </td>
                                    <td>{{ $service->user }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            @if($service->status === 'active')
                                                <form action="{{ route('systemd.stop', $service) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-warning" title="Stop">
                                                        <i class="bi bi-stop-circle"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('systemd.restart', $service) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-info" title="Restart">
                                                        <i class="bi bi-arrow-clockwise"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('systemd.start', $service) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success" title="Start">
                                                        <i class="bi bi-play-circle"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            
                                            <a href="{{ route('systemd.edit', $service) }}" class="btn btn-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            
                                            <form action="{{ route('systemd.destroy', $service) }}" method="POST" class="d-inline" 
                                                  onsubmit="return confirm('Are you sure you want to delete this service?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger" title="Delete">
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
            </div>
        </div>
    @endif
@endsection
