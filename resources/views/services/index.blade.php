@extends('layouts.app')

@section('title', 'Service Manager')
@section('page-title', 'Services Management')
@section('page-description', 'Manage system services')

@section('page-actions')
    <button class="btn btn-outline-primary" onclick="location.reload()">
        <i class="bi bi-arrow-clockwise me-2"></i> Refresh
    </button>
@endsection

@section('content')
<div class="container-fluid py-4">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(count($services) === 0)
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            No services found on this system.
        </div>
    @else
        <div class="row g-4">
            @foreach($services as $key => $service)
                <div class="col-lg-6 col-xl-4">
                    <div class="card h-100 shadow-sm service-card" data-service="{{ $key }}">
                        <div class="card-body">
                            <!-- Service Header -->
                            <div class="d-flex align-items-start justify-content-between mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="service-icon me-3">
                                        <i class="bi bi-{{ $service['icon'] ?? 'gear' }}"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title mb-1">{{ $service['name'] }}</h5>
                                        <small class="text-muted">{{ $key }}</small>
                                    </div>
                                </div>
                                <div>
                                    @if($service['running'] ?? false)
                                        <span class="badge badge-pastel-green">
                                            <i class="bi bi-check-circle me-1"></i> Running
                                        </span>
                                    @else
                                        <span class="badge badge-pastel-red">
                                            <i class="bi bi-x-circle me-1"></i> Stopped
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Service Info -->
                            <div class="service-info mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-power text-muted me-2"></i>
                                    <span class="text-muted me-2">Auto-start</span>
                                    @if($service['enabled'] ?? false)
                                        <span class="badge badge-pastel-green">Enabled</span>
                                    @else
                                        <span class="badge badge-pastel-yellow">Disabled</span>
                                    @endif
                                </div>

                                @if($service['running'] ?? false)
                                    <div class="small text-muted ps-4">
                                        <div><i class="bi bi-cpu me-1"></i> cpu: {{ $service['cpu'] ?? '0.0' }}%</div>
                                        <div><i class="bi bi-memory me-1"></i> ram: {{ $service['memory'] ?? '0.0' }}%</div>
                                        @if(!empty($service['pid']))
                                            <div class="mt-1"><i class="bi bi-hash me-1"></i> pid: {{ $service['pid'] }}</div>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <!-- Action Buttons -->
                            <div class="btn-group w-100 mb-2" role="group">
                                @if($service['running'] ?? false)
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="stopService('{{ $key }}', '{{ $service['name'] }}')">
                                        <i class="bi bi-stop-circle"></i> Stop
                                    </button>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="startService('{{ $key }}', '{{ $service['name'] }}')">
                                        <i class="bi bi-play-circle"></i> Start
                                    </button>
                                @endif

                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="restartService('{{ $key }}', '{{ $service['name'] }}')">
                                    <i class="bi bi-arrow-clockwise"></i> Restart
                                </button>

                                @if($service['supports_reload'])
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="reloadService('{{ $key }}', '{{ $service['name'] }}')">
                                        <i class="bi bi-arrow-repeat"></i> Reload
                                    </button>
                                @endif
                            </div>

                            <a href="{{ route('services.logs', ['service' => $key]) }}" class="btn btn-sm btn-outline-secondary w-100">
                                <i class="bi bi-file-text me-1"></i> View Logs
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@push('scripts')
<script>
// Hidden forms for service actions
const createForm = (action, service) => {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = action;

    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = '{{ csrf_token() }}';

    const serviceInput = document.createElement('input');
    serviceInput.type = 'hidden';
    serviceInput.name = 'service';
    serviceInput.value = service;

    form.appendChild(csrfInput);
    form.appendChild(serviceInput);
    document.body.appendChild(form);

    return form;
};

async function startService(service, name) {
    if (!confirm(`Start ${name}?`)) return;

    const form = createForm('{{ route("services.start") }}', service);
    form.submit();
}

async function stopService(service, name) {
    if (!confirm(`Stop ${name}? This may affect running applications.`)) return;

    const form = createForm('{{ route("services.stop") }}', service);
    form.submit();
}

async function restartService(service, name) {
    if (!confirm(`Restart ${name}?`)) return;

    const form = createForm('{{ route("services.restart") }}', service);
    form.submit();
}

async function reloadService(service, name) {
    if (!confirm(`Reload ${name} configuration?`)) return;

    const form = createForm('{{ route("services.reload") }}', service);
    form.submit();
}
</script>
@endpush
@endsection
