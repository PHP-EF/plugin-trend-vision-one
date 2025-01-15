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
            console.log('Raw API response:', data);
            if (data.result === 'Success' && data.data && data.data.items) {
                updateDashboard(data.data);
            } else {
                console.error('Failed to load vulnerability data:', data);
                document.getElementById('total-vulnerabilities').textContent = '0';
                document.getElementById('high-risk-count').textContent = '0';
                document.getElementById('medium-risk-count').textContent = '0';
                document.getElementById('low-risk-count').textContent = '0';
            }
        })
        .catch(error => {
            console.error('Error loading vulnerability data:', error);
        });
}

function updateDashboard(data) {
    if (!data || !data.items || !Array.isArray(data.items)) {
        console.error('Invalid data structure:', data);
        return;
    }

    const items = data.items;
    
    // Update total count from API response
    document.getElementById('total-vulnerabilities').textContent = data.totalCount || items.length;
    
    // Count vulnerabilities by risk level
    const riskCounts = {
        'HIGH': 0,
        'MEDIUM': 0,
        'LOW': 0
    };

    items.forEach(item => {
        if (item.riskLevel) {
            const risk = item.riskLevel.toUpperCase();
            if (riskCounts.hasOwnProperty(risk)) {
                riskCounts[risk]++;
            }
        }
    });

    document.getElementById('high-risk-count').textContent = riskCounts['HIGH'];
    document.getElementById('medium-risk-count').textContent = riskCounts['MEDIUM'];
    document.getElementById('low-risk-count').textContent = riskCounts['LOW'];

    // Update table
    const tbody = document.querySelector('#vulnerabilities-table tbody');
    tbody.innerHTML = ''; // Clear existing rows

    items.forEach(item => {
        const row = document.createElement('tr');
        const riskLevel = item.riskLevel ? item.riskLevel.toUpperCase() : 'UNKNOWN';
        
        row.innerHTML = `
            <td>${escapeHtml(item.endpointName || item.displayName || '')}</td>
            <td><span class="risk-${riskLevel.toLowerCase()}">${escapeHtml(riskLevel)}</span></td>
            <td>${item.cvssScore || '-'}</td>
            <td>${escapeHtml(item.vulnerabilityId || '')}</td>
            <td>${escapeHtml(item.installedProductName || '')} ${escapeHtml(item.installedProductVersion || '')}</td>
            <td>${formatDate(item.lastDetected || item.lastUsedIp)}</td>
            <td>
                <button class="btn btn-sm btn-info" onclick="showDetails('${escapeHtml(item.agentGuid || '')}')">
                    Details
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function showDetails(agentGuid) {
    if (!agentGuid) {
        console.error('No agent GUID provided');
        return;
    }
    
    fetch(`/api/plugin/TrendVisionOne/getendpointdetails/${agentGuid}`)
        .then(response => response.json())
        .then(data => {
            if (data.result === 'Success' && data.data) {
                // TODO: Show modal with detailed information
                console.log('Endpoint details:', data.data);
            } else {
                console.error('Failed to load endpoint details:', data);
            }
        })
        .catch(error => {
            console.error('Error loading endpoint details:', error);
        });
}

function formatDate(dateString) {
    if (!dateString) return '-';
    try {
        return new Date(dateString).toLocaleString();
    } catch (e) {
        return dateString;
    }
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
</script>
