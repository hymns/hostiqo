@extends('layouts.app')

@section('title', 'Edit Database - ' . $database->name)
@section('page-title', 'Edit Database')
@section('page-description', 'Update database information')

@section('content')
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('databases.update', $database) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Info Alert -->
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Note:</strong> You can only update the description here. 
                            Database name and username cannot be changed. To change the password, 
                            <a href="{{ route('databases.change-password', $database) }}" class="alert-link">click here</a>.
                        </div>

                        <!-- Database Name (Read-only) -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Database Name</label>
                            <input 
                                type="text" 
                                class="form-control font-monospace bg-light" 
                                value="{{ $database->name }}" 
                                readonly
                            >
                        </div>

                        <!-- Username (Read-only) -->
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input 
                                type="text" 
                                class="form-control font-monospace bg-light" 
                                value="{{ $database->username }}" 
                                readonly
                            >
                        </div>

                        <!-- Host (Read-only) -->
                        <div class="mb-3">
                            <label for="host" class="form-label">Host</label>
                            <input 
                                type="text" 
                                class="form-control bg-light" 
                                value="{{ $database->host }}" 
                                readonly
                            >
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea 
                                class="form-control @error('description') is-invalid @enderror" 
                                id="description" 
                                name="description" 
                                rows="3"
                                placeholder="Optional description for this database"
                            >{{ old('description', $database->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('databases.show', $database) }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i> Update Database
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
