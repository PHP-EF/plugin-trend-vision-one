// Function to format date and time
function formatDateTime(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleString();
}

// Function to update the sessions table
function updateSessionsTable() {
    $.ajax({
        url: '/api/plugin/VeeamPlugin/jobssessions',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('API Response:', response);
            
            if (response && response.result === 'Success' && Array.isArray(response.data)) {
                const sessions = response.data;
                const tableBody = $('#veeamSessionsTable tbody');
                tableBody.empty();

                if (sessions.length === 0) {
                    tableBody.html(`
                        <tr>
                            <td colspan="8" class="text-center">
                                No sessions available
                            </td>
                        </tr>
                    `);
                    return;
                }

                sessions.forEach((session, index) => {
                    const row = $('<tr>');
                    console.log(`Processing session ${index}:`, session);

                    row.append(`<td>${session.sessionType || '-'}</td>`);
                    row.append(`<td>${session.platformName || '-'}</td>`);
                    row.append(`<td>${session.name || '-'}</td>`);
                    row.append(`<td>${formatDateTime(session.creationTime)}</td>`);
                    row.append(`<td>${formatDateTime(session.endTime)}</td>`);
                    
                    // Progress column with progress bar
                    const progressBar = `
                        <div class="progress">
                            <div class="progress-bar ${session.state === 'Working' ? 'progress-bar-striped progress-bar-animated' : ''} 
                                                    ${getProgressBarClass(session.state)}" 
                                 role="progressbar" 
                                 style="width: ${session.progressPercent || 0}%"
                                 aria-valuenow="${session.progressPercent || 0}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                ${session.progressPercent || 0}%
                            </div>
                        </div>`;
                    row.append(`<td>${progressBar}</td>`);

                    // Result column with badge
                    const resultStatus = session.result?.result || 'None';
                    const resultBadge = `<span class="${getStatusBadgeClass(resultStatus)}">${resultStatus}</span>`;
                    row.append(`<td>${resultBadge}</td>`);
                    
                    // Actions column with details button
                    const detailsButton = `
                        <button class="btn btn-sm btn-info" 
                                onclick='showSessionDetails("${session.id}", ${JSON.stringify(session.result || {}).replace(/'/g, "&apos;")})'>
                            <i class="fas fa-info-circle"></i> Details
                        </button>`;
                    row.append(`<td>${detailsButton}</td>`);
                    
                    tableBody.append(row);
                });
            } else {
                console.error('Invalid response structure:', response);
                $('#veeamSessionsTable tbody').html(`
                    <tr>
                        <td colspan="8" class="text-center">
                            ${response.message || 'Error loading sessions'}
                        </td>
                    </tr>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching sessions:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
            
            $('#veeamSessionsTable tbody').html(`
                <tr>
                    <td colspan="8" class="text-center text-danger">
                        <i class="fas fa-exclamation-triangle"></i> Error loading sessions data
                    </td>
                </tr>
            `);
        }
    });
}

// Function to get progress bar class based on state
function getProgressBarClass(state) {
    switch(state && state.toLowerCase()) {
        case 'working':
            return 'bg-info';
        case 'stopped':
            return 'bg-secondary';
        case 'failed':
            return 'bg-danger';
        case 'success':
            return 'bg-success';
        default:
            return 'bg-primary';
    }
}

// Initialize Bootstrap modal
let sessionModal;
$(document).ready(function() {
    sessionModal = new bootstrap.Modal(document.getElementById('sessionDetailsModal'));
});

// Function to show session details in modal
function showSessionDetails(sessionId, result) {
    try {
        // Safely handle the result object
        const resultObj = typeof result === 'string' ? JSON.parse(result) : result;
        
        // Update modal content with safe fallbacks
        $('#resultStatus')
            .text(resultObj?.result || 'None')
            .removeClass()
            .addClass(getStatusBadgeClass(resultObj?.result));
        
        $('#resultMessage').text(resultObj?.message || 'No message available');
        $('#resultCanceled').text(resultObj?.isCanceled ? 'Yes' : 'No');
        
        // Show the modal
        sessionModal.show();
    } catch (error) {
        console.error('Error showing session details:', error);
        alert('Error showing session details. Please try again.');
    }
}

// Function to get appropriate badge class based on status
function getStatusBadgeClass(status) {
    switch(status && status.toLowerCase()) {
        case 'success':
            return 'badge bg-success text-white';
        case 'failed':
            return 'badge bg-danger text-white';
        case 'warning':
            return 'badge bg-warning text-dark';
        case 'none':
            return 'badge bg-secondary text-white';
        default:
            return 'badge bg-info text-white';
    }
}

// Add custom styles
$('<style>')
    .text(`
        .progress {
            height: 20px;
            margin-bottom: 0;
        }
        .progress-bar {
            min-width: 2em;
        }
        .table td {
            vertical-align: middle;
        }
    `)
    .appendTo('head');

// Initial load of sessions data
$(document).ready(function() {
    updateSessionsTable();
    
    // Refresh data every 30 seconds
    setInterval(updateSessionsTable, 30000);
});