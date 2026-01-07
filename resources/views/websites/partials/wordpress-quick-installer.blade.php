<style>
/* Wizard Steps Progress */
.wizard-steps {
    display: flex;
    justify-content: space-between;
    margin-bottom: 2rem;
    padding: 0;
    list-style: none;
    counter-reset: step;
}
.wizard-step {
    flex: 1;
    text-align: center;
    position: relative;
}
.wizard-step::before {
    content: counter(step);
    counter-increment: step;
    width: 40px;
    height: 40px;
    border: 3px solid #dee2e6;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.5rem;
    background: white;
    font-weight: 700;
    color: #6c757d;
    transition: all 0.3s;
}
.wizard-step::after {
    content: '';
    position: absolute;
    width: calc(100% - 40px);
    height: 3px;
    background: #dee2e6;
    top: 18px;
    left: calc(50% + 20px);
    z-index: -1;
}
.wizard-step:last-child::after {
    display: none;
}
.wizard-step.active::before {
    border-color: #5865f2;
    background: #5865f2;
    color: white;
    transform: scale(1.1);
}
.wizard-step.completed::before {
    border-color: #198754;
    background: #198754;
    color: white;
    content: '✓';
}
.wizard-step.completed::after {
    background: #198754;
}
.wizard-step-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #6c757d;
}
.wizard-step.active .wizard-step-label {
    color: #5865f2;
}
.wizard-step.completed .wizard-step-label {
    color: #198754;
}

/* Wizard Content */
.wizard-content {
    min-height: 350px;
    position: relative;
}
.wizard-pane {
    display: none;
    animation: fadeIn 0.3s;
}
.wizard-pane.active {
    display: block;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.wp-wizard-section {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 2rem;
    border: 1px solid #e9ecef;
}
.wp-wizard-header {
    text-align: center;
    margin-bottom: 2rem;
}
.wp-wizard-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #5865f2 0%, #7289da 100%);
    border-radius: 15px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.75rem;
    margin-bottom: 1rem;
}
.wp-wizard-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 0.5rem;
}
.wp-wizard-desc {
    color: #6c757d;
    font-size: 0.95rem;
}
.wp-feature-card {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 1.25rem;
    transition: all 0.2s;
    cursor: pointer;
    height: 100%;
}
.wp-feature-card:hover {
    border-color: #5865f2;
    box-shadow: 0 2px 8px rgba(88, 101, 242, 0.1);
}
.wp-feature-card.active {
    border-color: #5865f2;
    background: #f0f2ff;
}
.wizard-nav {
    display: flex;
    justify-content: space-between;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 2px solid #dee2e6;
}
</style>

<form id="wordpressQuickInstallForm">
    @csrf

    <!-- Progress Steps -->
    <ul class="wizard-steps">
        <li class="wizard-step active" data-step="1">
            <div class="wizard-step-label">Website Info</div>
        </li>
        <li class="wizard-step" data-step="2">
            <div class="wizard-step-label">Database</div>
        </li>
        <li class="wizard-step" data-step="3">
            <div class="wizard-step-label">Admin Account</div>
        </li>
        <li class="wizard-step" data-step="4">
            <div class="wizard-step-label">Features</div>
        </li>
    </ul>

    <!-- Wizard Content -->
    <div class="wizard-content">

        <!-- Step 1: Website Information -->
        <div class="wizard-pane active" data-pane="1">
            <div class="wp-wizard-section">
                <div class="wp-wizard-header">
                    <div class="wp-wizard-icon">
                        <i class="bi bi-globe2"></i>
                    </div>
                    <h3 class="wp-wizard-title">Website Information</h3>
                    <p class="wp-wizard-desc">Basic details about your WordPress site</p>
                </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label for="name" class="form-label fw-semibold">
                    <i class="bi bi-tag me-1"></i> Website Name <span class="text-danger">*</span>
                </label>
                <input type="text"
                       class="form-control"
                       id="name"
                       name="name"
                       placeholder="My WordPress Site"
                       required>
                <div class="form-text"><small>Display name for your website</small></div>
            </div>

            <div class="col-md-6">
                <label for="domain" class="form-label fw-semibold">
                    <i class="bi bi-link-45deg me-1"></i> Domain <span class="text-danger">*</span>
                </label>
                <input type="text"
                       class="form-control"
                       id="domain"
                       name="domain"
                       placeholder="example.com"
                       pattern="[a-zA-Z0-9][a-zA-Z0-9-_.]*[a-zA-Z0-9]"
                       required>
                <div class="form-text"><small>Without http:// or https://</small></div>
            </div>

            <div class="col-md-6">
                <label for="php_version" class="form-label fw-semibold">
                    <i class="bi bi-code-square me-1"></i> PHP Version
                </label>
                <select class="form-select" id="php_version" name="php_version">
                    <option value="8.3" selected>PHP 8.3 (Recommended)</option>
                    <option value="8.2">PHP 8.2</option>
                    <option value="8.1">PHP 8.1</option>
                    <option value="8.0">PHP 8.0</option>
                </select>
            </div>
        </div>
            </div>
        </div>

        <!-- Step 2: Database Configuration -->
        <div class="wizard-pane" data-pane="2">
            <div class="wp-wizard-section">
                <div class="wp-wizard-header">
                    <div class="wp-wizard-icon">
                        <i class="bi bi-database"></i>
                    </div>
                    <h3 class="wp-wizard-title">Database Configuration</h3>
                    <p class="wp-wizard-desc">MySQL database credentials (auto-filled from domain)</p>
                </div>

        <div class="row g-3">
            <div class="col-md-4">
                <label for="db_name" class="form-label fw-semibold">
                    Database Name <span class="text-danger">*</span>
                </label>
                <input type="text"
                       class="form-control"
                       id="db_name"
                       name="db_name"
                       pattern="[a-zA-Z0-9_]+"
                       required>
            </div>

            <div class="col-md-4">
                <label for="db_user" class="form-label fw-semibold">
                    Database User <span class="text-danger">*</span>
                </label>
                <input type="text"
                       class="form-control"
                       id="db_user"
                       name="db_user"
                       pattern="[a-zA-Z0-9_]+"
                       maxlength="32"
                       required>
            </div>

            <div class="col-md-4">
                <label for="db_password" class="form-label fw-semibold">
                    Database Password <span class="text-danger">*</span>
                </label>
                <input type="password"
                       class="form-control"
                       id="db_password"
                       name="db_password"
                       minlength="8"
                       required>
            </div>
        </div>
            </div>
        </div>

        <!-- Step 3: WordPress Admin -->
        <div class="wizard-pane" data-pane="3">
            <div class="wp-wizard-section">
                <div class="wp-wizard-header">
                    <div class="wp-wizard-icon">
                        <i class="bi bi-person-lock"></i>
                    </div>
                    <h3 class="wp-wizard-title">WordPress Admin Account</h3>
                    <p class="wp-wizard-desc">Create your administrator credentials</p>
                </div>

        <div class="row g-3">
            <div class="col-md-4">
                <label for="admin_user" class="form-label fw-semibold">
                    Admin Username <span class="text-danger">*</span>
                </label>
                <input type="text"
                       class="form-control"
                       id="admin_user"
                       name="admin_user"
                       pattern="[a-zA-Z0-9_]+"
                       maxlength="60"
                       required>
            </div>

            <div class="col-md-4">
                <label for="admin_email" class="form-label fw-semibold">
                    Admin Email <span class="text-danger">*</span>
                </label>
                <input type="email"
                       class="form-control"
                       id="admin_email"
                       name="admin_email"
                       required>
            </div>

            <div class="col-md-4">
                <label for="admin_password" class="form-label fw-semibold">
                    Admin Password <span class="text-danger">*</span>
                </label>
                <input type="password"
                       class="form-control"
                       id="admin_password"
                       name="admin_password"
                       minlength="8"
                       required>
            </div>
        </div>
            </div>
        </div>

        <!-- Step 4: Optimization Features -->
        <div class="wizard-pane" data-pane="4">
            <div class="wp-wizard-section">
                <div class="wp-wizard-header">
                    <div class="wp-wizard-icon">
                        <i class="bi bi-rocket-takeoff"></i>
                    </div>
                    <h3 class="wp-wizard-title">Performance & Security</h3>
                    <p class="wp-wizard-desc">Optimize your WordPress installation</p>
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="wp-feature-card active" onclick="toggleFeature('enable_cache', this)">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="enable_cache" name="enable_cache" value="1" checked>
                                <label class="form-check-label fw-semibold d-block" for="enable_cache">
                                    <i class="bi bi-lightning-charge text-warning me-1"></i> FastCGI Cache
                                </label>
                                <small class="text-muted d-block mt-2">Nginx-level caching for blazing fast page loads. Recommended for all sites.</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="wp-feature-card active" onclick="toggleFeature('install_plugins', this)">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="install_plugins" name="install_plugins" value="1" checked>
                                <label class="form-check-label fw-semibold d-block" for="install_plugins">
                                    <i class="bi bi-shield-check text-success me-1"></i> Security Plugins
                                </label>
                                <small class="text-muted d-block mt-2">Essential security hardening plugins for WordPress protection.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div> <!-- End wizard-content -->

    <!-- Progress -->
    <div id="quickInstallProgress" class="mt-4" style="display: none;">
        <div class="alert alert-info">
            <div class="d-flex align-items-center">
                <div class="spinner-border spinner-border-sm me-3" role="status"></div>
                <div>
                    <strong>Deploying WordPress...</strong>
                    <div id="quickInstallStatus" class="small text-muted"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Wizard Navigation -->
    <div class="wizard-nav">
        <button type="button" class="btn btn-outline-secondary px-4" id="wizardPrevBtn" style="display: none;">
            <i class="bi bi-arrow-left me-2"></i> Previous
        </button>
        <div class="text-muted small align-self-center">
            <i class="bi bi-info-circle me-1"></i> Step <span id="currentStep">1</span> of 4
        </div>
        <button type="button" class="btn btn-primary px-4" id="wizardNextBtn">
            Next <i class="bi bi-arrow-right ms-2"></i>
        </button>
        <button type="submit" class="btn btn-success btn-lg px-4" id="quickInstallBtn" style="display: none;">
            <i class="bi bi-rocket-takeoff me-2"></i> Deploy Now
        </button>
    </div>
</form>

@push('scripts')
<script>
var currentWizardStep = 1;
var totalSteps = 4;

// Wizard Navigation
function updateWizardUI() {
    // Update step indicators
    $('.wizard-step').each(function(index) {
        var stepNum = index + 1;
        $(this).removeClass('active completed');

        if (stepNum < currentWizardStep) {
            $(this).addClass('completed');
        } else if (stepNum === currentWizardStep) {
            $(this).addClass('active');
        }
    });

    // Update panes
    $('.wizard-pane').each(function(index) {
        $(this).removeClass('active');
        if (index + 1 === currentWizardStep) {
            $(this).addClass('active');
        }
    });

    // Update buttons
    $('#currentStep').text(currentWizardStep);
    $('#wizardPrevBtn').toggle(currentWizardStep !== 1);
    $('#wizardNextBtn').toggle(currentWizardStep !== totalSteps);
    $('#quickInstallBtn').toggle(currentWizardStep === totalSteps);
}

$('#wizardNextBtn').on('click', function() {
    if (currentWizardStep < totalSteps) {
        currentWizardStep++;
        updateWizardUI();
    }
});

$('#wizardPrevBtn').on('click', function() {
    if (currentWizardStep > 1) {
        currentWizardStep--;
        updateWizardUI();
    }
});

// Toggle feature card active state
function toggleFeature(checkboxId, card) {
    var $checkbox = $('#' + checkboxId);
    $checkbox.prop('checked', !$checkbox.prop('checked'));
    $(card).toggleClass('active', $checkbox.prop('checked'));
}

// Auto-fill database name and user based on domain
$('#domain').on('input', function() {
    var domain = $(this).val().replace(/[^a-zA-Z0-9]/g, '_');
    if (domain) {
        $('#db_name').val(domain + '_wp');
        $('#db_user').val(domain.substring(0, 20) + '_user');
    }
});

$('#wordpressQuickInstallForm').on('submit', function(e) {
    e.preventDefault();

    var $form = $(this);
    var $submitBtn = $('#quickInstallBtn');
    var $progressDiv = $('#quickInstallProgress');
    var $statusDiv = $('#quickInstallStatus');

    // Disable form
    $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Deploying...');
    $form.find('input, select, button').prop('disabled', true);
    $progressDiv.show();

    // Prepare data
    var data = {};
    $form.serializeArray().forEach(function(item) {
        data[item.name] = item.value;
    });

    $statusDiv.text('Creating website configuration...');

    // Step 1: Create website
    $.ajax({
        url: '{{ route("websites.store") }}',
        method: 'POST',
        contentType: 'application/json',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        data: JSON.stringify({
            name: data.name,
            domain: data.domain,
            project_type: 'php',
            php_version: data.php_version || '8.3',
            root_path: '/var/www/' + data.domain,
            framework: 'wordpress',
            status: 'pending'
        })
    }).done(function(websiteData) {
        var websiteId = websiteData.id;

        $statusDiv.text('Installing WordPress...');

        // Step 2: Install WordPress
        $.ajax({
            url: '/websites/' + websiteId + '/wordpress/install',
            method: 'POST',
            contentType: 'application/json',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: JSON.stringify({
                db_name: data.db_name,
                db_user: data.db_user,
                db_password: data.db_password,
                db_host: 'localhost',
                db_prefix: 'wp_',
                admin_user: data.admin_user,
                admin_password: data.admin_password,
                admin_email: data.admin_email,
                site_title: data.name,
                enable_cache: data.enable_cache === '1',
                install_plugins: data.install_plugins === '1'
            })
        }).done(function(installResult) {
            if (installResult.success) {
                $progressDiv.html(
                    '<div class="alert alert-success">' +
                        '<h5 class="alert-heading">' +
                            '<i class="bi bi-check-circle me-2"></i> WordPress Deployed Successfully!' +
                        '</h5>' +
                        '<hr>' +
                        '<p><strong>Site:</strong> ' + data.name + ' (' + data.domain + ')</p>' +
                        '<p><strong>Admin URL:</strong> <a href="' + installResult.data.admin_url + '" target="_blank">' + installResult.data.admin_url + '</a></p>' +
                        '<p class="mb-0"><strong>Admin Username:</strong> ' + installResult.data.admin_user + '</p>' +
                        '<hr>' +
                        '<p class="text-muted mb-0"><small>Redirecting to deployment list...</small></p>' +
                    '</div>'
                );

                setTimeout(function() {
                    window.location.href = '{{ route("websites.index", ["type" => "deployment"]) }}';
                }, 2000);
            } else {
                throw new Error(installResult.message || 'Installation failed');
            }
        }).fail(function(xhr) {
            var message = xhr.responseJSON ? xhr.responseJSON.message : 'Installation failed';
            showDeployError(message, $form, $submitBtn, $progressDiv);
        });
    }).fail(function(xhr) {
        var message = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to create website';
        showDeployError(message, $form, $submitBtn, $progressDiv);
    });
});

function showDeployError(message, $form, $submitBtn, $progressDiv) {
    console.error('Deployment error:', message);
    $progressDiv.html(
        '<div class="alert alert-danger">' +
            '<h5 class="alert-heading"><i class="bi bi-x-circle me-2"></i> Deployment Failed</h5>' +
            '<p class="mb-0">' + message + '</p>' +
        '</div>'
    );

    $form.find('input, select, button').prop('disabled', false);
    $submitBtn.prop('disabled', false).html('<i class="bi bi-rocket-takeoff me-2"></i> Retry Deployment');
}
</script>
@endpush
