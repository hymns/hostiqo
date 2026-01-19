@extends('layouts.app')

@section('title', 'PM2 Logs - ' . $appName . ' - Hostiqo')
@section('page-title', 'PM2 Logs')
@section('page-description')
    Application: <code>{{ $appName }}</code>
    @if($website)
        <span class="mx-2">|</span>
        Domain: <strong>{{ $website->domain }}</strong>
    @endif
@endsection

@section('page-actions')
    <a href="{{ route('pm2.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to PM2
    </a>
    @if($website)
        <a href="{{ route('websites.show', $website) }}" class="btn btn-outline-primary">
            <i class="bi bi-box-arrow-up-right me-1"></i> View Website
        </a>
    @endif
@endsection

@section('content')

    @if($error)
        <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Error:</strong> {{ $error }}
        </div>
    @endif

    <!-- Log Controls -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('pm2.logs', $appName) }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="lines" class="form-label">Number of Lines</label>
                    <select name="lines" id="lines" class="form-select" onchange="this.form.submit()">
                        <option value="50" {{ $lines == 50 ? 'selected' : '' }}>50 lines</option>
                        <option value="100" {{ $lines == 100 ? 'selected' : '' }}>100 lines</option>
                        <option value="200" {{ $lines == 200 ? 'selected' : '' }}>200 lines</option>
                        <option value="500" {{ $lines == 500 ? 'selected' : '' }}>500 lines</option>
                        <option value="1000" {{ $lines == 1000 ? 'selected' : '' }}>1000 lines</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="window.location.reload()">
                        <i class="bi bi-arrow-repeat me-1"></i> Auto Refresh
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Logs Display -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-terminal me-2"></i>Application Logs
            </h5>
            <div>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyLogs()">
                    <i class="bi bi-clipboard me-1"></i> Copy
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="downloadLogs()">
                    <i class="bi bi-download me-1"></i> Download
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            @if($logs)
                <pre id="logsContent" class="bg-dark text-light p-3 m-0" style="max-height: 600px; overflow-y: auto; font-size: 0.85rem; line-height: 1.4;"><code>{{ $logs }}</code></pre>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <p class="text-muted mt-3 mb-0">No logs available</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Log Information -->
    <div class="card mt-3">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-info-circle me-2"></i>Log Information
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Log Files Location:</h6>
                    <p class="small mb-2">
                        <strong>Output Log:</strong><br>
                        <code>/var/log/pm2/{{ $appName }}-out.log</code>
                    </p>
                    <p class="small mb-0">
                        <strong>Error Log:</strong><br>
                        <code>/var/log/pm2/{{ $appName }}-error.log</code>
                    </p>
                </div>
                <div class="col-md-6">
                    <h6>Log Management:</h6>
                    <ul class="small mb-0">
                        <li>Logs are automatically rotated by PM2</li>
                        <li>Both stdout and stderr are captured</li>
                        <li>Timestamps are in ISO 8601 format</li>
                        <li>Use "Refresh" to see latest logs</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    @if($website)
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-lightning me-2"></i>Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="btn-group" role="group">
                    <form action="{{ route('pm2.restart', $appName) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-arrow-clockwise me-1"></i> Restart Application
                        </button>
                    </form>
                    <form action="{{ route('pm2.stop', $appName) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-stop-circle me-1"></i> Stop Application
                        </button>
                    </form>
                </div>
                <p class="text-muted small mt-2 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Restart the application if you see errors or need to apply changes.
                </p>
            </div>
        </div>
    @endif
</div>

<script>
function copyLogs() {
    const logsContent = document.getElementById('logsContent');
    if (logsContent) {
        const text = logsContent.textContent;
        navigator.clipboard.writeText(text).then(() => {
            alert('Logs copied to clipboard!');
        }).catch(err => {
            console.error('Failed to copy logs:', err);
        });
    }
}

function downloadLogs() {
    const logsContent = document.getElementById('logsContent');
    if (logsContent) {
        const text = logsContent.textContent;
        const blob = new Blob([text], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = '{{ $appName }}-logs-{{ date("Y-m-d-His") }}.txt';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    }
}

// Auto-refresh every 5 seconds if enabled
let autoRefreshInterval = null;

function toggleAutoRefresh(button) {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
        button.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Auto Refresh';
        button.classList.remove('btn-success');
        button.classList.add('btn-outline-secondary');
    } else {
        autoRefreshInterval = setInterval(() => {
            window.location.reload();
        }, 5000);
        button.innerHTML = '<i class="bi bi-stop-circle me-1"></i> Stop Auto Refresh';
        button.classList.remove('btn-outline-secondary');
        button.classList.add('btn-success');
    }
}

// Scroll to bottom of logs
document.addEventListener('DOMContentLoaded', function() {
    const logsContent = document.getElementById('logsContent');
    if (logsContent) {
        logsContent.scrollTop = logsContent.scrollHeight;
    }
});
</script>
@endsection
