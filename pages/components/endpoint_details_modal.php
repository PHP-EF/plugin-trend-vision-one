<!-- Endpoint Details Modal -->
<div class="modal fade" id="endpointDetailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Endpoint Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 class="mb-3">General Information</h6>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">Agent GUID:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext" id="agentGuid">-</p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">Display Name:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext" id="displayName">-</p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">OS Name:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext" id="osName">-</p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">OS Version:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext" id="osVersion">-</p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">IP Addresses:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext" id="ipAddresses">-</p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">Last Connected:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext" id="lastConnectedDateTime">-</p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">Status:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext"><span id="endpointStatus" class="badge">-</span></p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">Endpoint Group:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext" id="endpointGroup">-</p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">Protection Manager:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext" id="protectionManager">-</p>
                    </div>
                </div>

                <h6 class="mt-4 mb-3">EPP Information</h6>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">Policy Name:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext" id="eppPolicyName">-</p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">Status:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext"><span id="eppStatus" class="badge">-</span></p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">Last Connected:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext" id="eppLastConnected">-</p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">Version:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext" id="eppVersion">-</p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">Component Version:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext"><span id="eppComponentVersion" class="badge">-</span></p>
                    </div>
                </div>

                <h6 class="mt-4 mb-3">EDR Information</h6>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">Connectivity:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext"><span id="edrConnectivity" class="badge">-</span></p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">Last Connected:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext" id="edrLastConnected">-</p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">Version:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext" id="edrVersion">-</p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">Status:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext"><span id="edrStatus" class="badge">-</span></p>
                    </div>
                </div>
                <div class="mb-2 row">
                    <label class="col-sm-4 col-form-label">Advanced Risk Telemetry:</label>
                    <div class="col-sm-8">
                        <p class="form-control-plaintext"><span id="edrAdvancedRiskTelemetry" class="badge">-</span></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
