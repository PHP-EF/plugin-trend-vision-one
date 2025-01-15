<?php
$pageData = [
    'title' => 'Trend Vision One - Vulnerabilities',
    'description' => 'View and manage vulnerable devices detected by Trend Vision One',
];
?>

<!-- Custom CSS for the vulnerabilities page -->
<style>
.vulnerability-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--surface-card);
    padding: 1rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-card h3 {
    margin: 0;
    color: var(--text-color);
    font-size: 1rem;
}

.stat-card .value {
    font-size: 2rem;
    font-weight: bold;
    color: var(--primary-color);
}

.vulnerability-table {
    width: 100%;
    margin-top: 1rem;
}

.risk-high {
    color: #dc3545;
}

.risk-medium {
    color: #ffc107;
}

.risk-low {
    color: #28a745;
}

.table th {
    cursor: pointer;
}

.table th:hover {
    background-color: rgba(0,0,0,0.05);
}
</style>

<!-- Main content -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2>Vulnerability Overview</h2>
        <div class="search-box">
            <input type="text" id="search-input" class="form-control" placeholder="Search vulnerabilities...">
        </div>
    </div>
    <div class="card-body">
        <!-- Statistics Cards -->
        <div class="vulnerability-stats">
            <div class="stat-card">
                <h3>Total Vulnerabilities</h3>
                <div class="value" id="total-vulnerabilities">-</div>
            </div>
            <div class="stat-card">
                <h3>High Risk</h3>
                <div class="value risk-high" id="high-risk-count">-</div>
            </div>
            <div class="stat-card">
                <h3>Medium Risk</h3>
                <div class="value risk-medium" id="medium-risk-count">-</div>
            </div>
            <div class="stat-card">
                <h3>Low Risk</h3>
                <div class="value risk-low" id="low-risk-count">-</div>
            </div>
        </div>

        <!-- Vulnerabilities Table -->
        <div class="table-responsive">
            <table class="table table-striped vulnerability-table" id="vulnerabilities-table">
                <thead>
                    <tr>
                        <th>Endpoint Name</th>
                        <th>Risk Level</th>
                        <th>CVSS Score</th>
                        <th>Vulnerability ID</th>
                        <th>Product</th>
                        <th>Last Detected</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data will be populated by JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="vulnerability-details-modal" tabindex="-1" aria-labelledby="vulnerabilityDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="vulnerabilityDetailsModalLabel">Vulnerability Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Content will be populated by JavaScript -->
                <div class="vulnerability-info">
                    <h6>Endpoint Information</h6>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Name:</strong> <span id="modal-endpoint-name"></span></p>
                            <p><strong>OS:</strong> <span id="modal-endpoint-os"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>IP:</strong> <span id="modal-endpoint-ip"></span></p>
                            <p><strong>Last Connected:</strong> <span id="modal-endpoint-last-connected"></span></p>
                        </div>
                    </div>
                    
                    <h6>Vulnerability Information</h6>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>ID:</strong> <span id="modal-vuln-id"></span></p>
                            <p><strong>Risk Level:</strong> <span id="modal-risk-level"></span></p>
                            <p><strong>CVSS Score:</strong> <span id="modal-cvss-score"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Product:</strong> <span id="modal-product"></span></p>
                            <p><strong>Version:</strong> <span id="modal-version"></span></p>
                            <p><strong>Last Detected:</strong> <span id="modal-last-detected"></span></p>
                        </div>
                    </div>
                    
                    <h6>Description</h6>
                    <div class="mb-3">
                        <p id="modal-description"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Include the vulnerabilities JavaScript -->
<script src="/plugins/TrendVisionOne/js/main-vulnerabilities.js"></script>
