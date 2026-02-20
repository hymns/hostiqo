@extends('layouts.app')

@section('title', 'Banned IPs - Fail2ban - Hostiqo')
@section('page-title', 'Banned IPs')
@section('page-description', 'View and manage all banned IP addresses')

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="mb-3">
    <a href="{{ route('fail2ban.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Fail2ban
    </a>
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-slash-circle me-2"></i> Currently Banned IPs
        <span class="badge bg-danger ms-2">{{ count($bannedIps) }}</span>
    </div>
    <div class="card-body p-0">
        @if(count($bannedIps) > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>IP Address</th>
                            <th>Jail</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bannedIps as $banned)
                            <tr>
                                <td>
                                    <code class="text-danger">{{ $banned['ip'] }}</code>
                                </td>
                                <td>
                                    <a href="{{ route('fail2ban.jail', $banned['jail']) }}" class="text-decoration-none">
                                        {{ $banned['jail'] }}
                                    </a>
                                </td>
                                <td class="text-end">
                                    <form action="{{ route('fail2ban.unban') }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="jail" value="{{ $banned['jail'] }}">
                                        <input type="hidden" name="ip" value="{{ $banned['ip'] }}">
                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Unban {{ $banned['ip'] }}?')">
                                            <i class="bi bi-unlock"></i> Unban
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5 text-muted">
                <i class="bi bi-check-circle display-4 text-success"></i>
                <p class="mt-2">No IPs are currently banned.</p>
            </div>
        @endif
    </div>
</div>

@endsection
