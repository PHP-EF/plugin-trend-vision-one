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
</style>

<!-- Main content -->
<div class="card">
    <div class="card-header">
        <h2>Vulnerability Overview</h2>
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

<!-- JavaScript for handling the data -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    loadVulnerabilityData();
});

function loadVulnerabilityData() {
    fetch('/api/plugin/TrendVisionOne/getvulnerabledevices')
        .then(response => response.json())
        .then(data => {
            if (data.result === 'Success') {
                updateDashboard(data.data);
            } else {
                console.error('Failed to load vulnerability data:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading vulnerability data:', error);
        });
}

function updateDashboard(data) {
    if (!data || !data.items) return;

    const items = data.items;
    
    // Update statistics
    document.getElementById('total-vulnerabilities').textContent = items.length;
    
    const riskCounts = items.reduce((acc, item) => {
        acc[item.riskLevel] = (acc[item.riskLevel] || 0) + 1;
        return acc;
    }, {});

    document.getElementById('high-risk-count').textContent = riskCounts['HIGH'] || 0;
    document.getElementById('medium-risk-count').textContent = riskCounts['MEDIUM'] || 0;
    document.getElementById('low-risk-count').textContent = riskCounts['LOW'] || 0;

    // Update table
    const tbody = document.querySelector('#vulnerabilities-table tbody');
    tbody.innerHTML = ''; // Clear existing rows

    items.forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${escapeHtml(item.endpointName)}</td>
            <td><span class="risk-${item.riskLevel.toLowerCase()}">${escapeHtml(item.riskLevel)}</span></td>
            <td>${item.cvssScore}</td>
            <td>${escapeHtml(item.vulnerabilityId)}</td>
            <td>${escapeHtml(item.installedProductName)} ${escapeHtml(item.installedProductVersion)}</td>
            <td>${formatDate(item.lastDetected)}</td>
            <td>
                <button class="btn btn-sm btn-info" onclick="showDetails('${escapeHtml(item.vulnerabilityId)}')">
                    Details
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function showDetails(vulnerabilityId) {
    // Implement vulnerability details modal
    console.log('Show details for:', vulnerabilityId);
}

function formatDate(dateString) {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString();
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
</script>
