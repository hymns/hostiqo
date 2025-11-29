@extends('layouts.app')

@section('title', 'Server Health - Git Webhook Manager')
@section('page-title', 'Server Health')
@section('page-description', 'Monitor your server performance metrics')

@section('content')
    @if($latestMetric)
        <!-- System Monitoring Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">CPU Cores</p>
                                <h3 class="mb-0">{{ $cpuCores }}</h3>
                                <small class="text-muted">Physical Cores</small>
                            </div>
                            <div class="text-primary" style="font-size: 2.5rem;">
                                <i class="bi bi-cpu"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">CPU Usage</p>
                                <h3 class="mb-0">{{ number_format($latestMetric->cpu_usage, 1) }}%</h3>
                                <small class="text-muted">&nbsp;</small>
                            </div>
                            <div class="text-{{ $latestMetric->cpu_usage > 80 ? 'danger' : ($latestMetric->cpu_usage > 60 ? 'warning' : 'success') }}" style="font-size: 2.5rem;">
                                <i class="bi bi-cpu-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Memory Usage</p>
                                <h3 class="mb-0">{{ number_format($latestMetric->memory_usage, 1) }}%</h3>
                                @if($latestMetric->memory_used && $latestMetric->memory_total)
                                    <small class="text-muted">{{ $latestMetric->formatBytes($latestMetric->memory_used) }} / {{ $latestMetric->formatBytes($latestMetric->memory_total) }}</small>
                                @endif
                            </div>
                            <div class="text-{{ $latestMetric->memory_usage > 80 ? 'danger' : ($latestMetric->memory_usage > 60 ? 'warning' : 'success') }}" style="font-size: 2.5rem;">
                                <i class="bi bi-memory"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Disk Usage</p>
                                <h3 class="mb-0">{{ number_format($latestMetric->disk_usage, 1) }}%</h3>
                                @if($latestMetric->disk_used && $latestMetric->disk_total)
                                    <small class="text-muted">{{ $latestMetric->formatBytes($latestMetric->disk_used) }} / {{ $latestMetric->formatBytes($latestMetric->disk_total) }}</small>
                                @endif
                            </div>
                            <div class="text-{{ $latestMetric->disk_usage > 80 ? 'danger' : ($latestMetric->disk_usage > 60 ? 'warning' : 'success') }}" style="font-size: 2.5rem;">
                                <i class="bi bi-hdd"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Monitoring Charts -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">System Performance (Last {{ config('monitoring.chart_hours', 6) }} Hours)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="systemMetricsChart" height="80"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Database Connection Monitoring Chart -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Database Connections (Last {{ config('monitoring.chart_hours', 6) }} Hours)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="dbConnectionsChart" height="80"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- I/O Monitoring Charts -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">I/O Performance (Last {{ config('monitoring.chart_hours', 6) }} Hours)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="ioMetricsChart" height="80"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monitoring Info -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Monitoring Configuration</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <p class="mb-2"><strong>Status:</strong></p>
                                <p>
                                    @if(config('monitoring.enabled', true))
                                        <span class="badge bg-success">Enabled</span>
                                    @else
                                        <span class="badge bg-secondary">Disabled</span>
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-2"><strong>Collection Interval:</strong></p>
                                <p>Every {{ config('monitoring.interval_minutes', 2) }} minutes</p>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-2"><strong>Data Retention:</strong></p>
                                <p>{{ config('monitoring.retention_hours', 24) }} hours</p>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-2"><strong>Last Update:</strong></p>
                                <p>{{ $latestMetric->recorded_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        <hr>
                        <p class="mb-0 text-muted">
                            <i class="bi bi-gear me-2"></i>
                            <small>Configure monitoring settings in your <code>.env</code> file using <code>MONITORING_*</code> variables.</small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- No Metrics Available -->
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            <strong>No metrics data available yet.</strong>
            <p class="mb-0 mt-2">
                The system monitoring job will collect metrics every {{ config('monitoring.interval_minutes', 2) }} minutes.
                Make sure the Laravel scheduler is running: <code>php artisan schedule:work</code>
            </p>
        </div>
    @endif
@endsection

@push('scripts')
@if($latestMetric && $systemMetrics->isNotEmpty())
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const metrics = @json($systemMetrics);
    
    // Prepare data for charts
    const labels = metrics.map(m => {
        const date = new Date(m.recorded_at);
        return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    });
    
    const cpuData = metrics.map(m => m.cpu_usage);
    const memoryData = metrics.map(m => m.memory_usage);
    const diskData = metrics.map(m => m.disk_usage);
    
    // Create chart
    const ctx = document.getElementById('systemMetricsChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'CPU Usage (%)',
                    data: cpuData,
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Memory Usage (%)',
                    data: memoryData,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Disk Usage (%)',
                    data: diskData,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y.toFixed(2) + '%';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                },
                x: {
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            }
        }
    });

    // I/O Metrics Chart with Rate Calculation
    const ioCtx = document.getElementById('ioMetricsChart').getContext('2d');
    
    // Calculate I/O rates for each data point
    const diskReadRates = [];
    const diskWriteRates = [];
    const networkRxRates = [];
    const networkTxRates = [];
    
    for (let i = 1; i < metrics.length; i++) {
        const curr = metrics[i];
        const prev = metrics[i - 1];
        const timeDiff = (new Date(curr.recorded_at) - new Date(prev.recorded_at)) / 1000; // seconds
        
        if (timeDiff > 0) {
            // Calculate bytes per second, then convert to MB/s
            diskReadRates.push(Math.max(0, (curr.disk_read_bytes - prev.disk_read_bytes) / timeDiff / 1024 / 1024));
            diskWriteRates.push(Math.max(0, (curr.disk_write_bytes - prev.disk_write_bytes) / timeDiff / 1024 / 1024));
            networkRxRates.push(Math.max(0, (curr.network_rx_bytes - prev.network_rx_bytes) / timeDiff / 1024 / 1024));
            networkTxRates.push(Math.max(0, (curr.network_tx_bytes - prev.network_tx_bytes) / timeDiff / 1024 / 1024));
        } else {
            diskReadRates.push(0);
            diskWriteRates.push(0);
            networkRxRates.push(0);
            networkTxRates.push(0);
        }
    }
    
    // Use labels starting from index 1 (since we calculate rates from diff)
    const ioLabels = labels.slice(1);
    
    new Chart(ioCtx, {
        type: 'line',
        data: {
            labels: ioLabels,
            datasets: [
                {
                    label: 'Disk Read (MB/s)',
                    data: diskReadRates,
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y'
                },
                {
                    label: 'Disk Write (MB/s)',
                    data: diskWriteRates,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y'
                },
                {
                    label: 'Network Download (MB/s)',
                    data: networkRxRates,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y'
                },
                {
                    label: 'Network Upload (MB/s)',
                    data: networkTxRates,
                    borderColor: 'rgb(255, 206, 86)',
                    backgroundColor: 'rgba(255, 206, 86, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y.toFixed(2) + ' MB/s';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toFixed(1) + ' MB/s';
                        }
                    }
                },
                x: {
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            }
        }
    });

    // Database Connections Chart
    const dbCtx = document.getElementById('dbConnectionsChart').getContext('2d');
    
    const dbConnectionsData = metrics.map(m => m.db_connections || 0);
    
    new Chart(dbCtx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Database Connections',
                    data: dbConnectionsData,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y + ' connections';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        callback: function(value) {
                            return Math.floor(value) + ' conn';
                        }
                    }
                },
                x: {
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            }
        }
    });
});
</script>
@endif
@endpush
