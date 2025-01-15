<?php
//// Everything after this line (2) is Core Functionality and no changes are permitted until after line (188).
// **
// USED TO DEFINE PLUGIN INFORMATION & CLASS
// **

// PLUGIN INFORMATION - This should match what is in plugin.json
$GLOBALS['plugins']['TrendVisionOne'] = [ // Plugin Name
    'name' => 'TrendVisionOne', // Plugin Name
    'author' => 'TinyTechLabUK', // Who wrote the plugin
    'category' => 'Anti Virus', // One to Two Word Description
    'link' => 'https://github.com/PHP-EF/plugin-trend-vision-one', // Link to plugin info
    'version' => '1.0.0.1', // SemVer of plugin
    'image' => 'logo.png', // 1:1 non transparent image for plugin
    'settings' => true, // does plugin need a settings modal?
    'api' => '/api/plugin/TrendVisionOne/settings', // api route for settings page, or null if no settings page
];

class trendvisionone extends phpef {
    private $trendvisiononepluginConfig;
    private $sql;

    public function __construct() {
        parent::__construct();
        $this->trendvisiononepluginConfig = $this->config->get('Plugins','TrendVisionOne') ?? [];
        
        // Initialize database connection
        if ($this->initializeDBConnection()) {
            $this->initializeDB();
        }
    }

    private function initializeDBConnection() {
        try {
            $dbFile = dirname(__DIR__,2). DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'trendvisionone.db';
            $this->sql = new PDO("sqlite:$dbFile");
            $this->sql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Log initial foreign keys state
            $initialState = $this->sql->query('PRAGMA foreign_keys;')->fetch(PDO::FETCH_COLUMN);
            error_log("[TrendVisionOne] Initial foreign_keys state: " . ($initialState ? 'ON' : 'OFF'));
            
            // Enable foreign key support
            $this->sql->exec('PRAGMA foreign_keys = ON;');
            
            // Verify foreign keys are enabled
            $foreignKeysEnabled = $this->sql->query('PRAGMA foreign_keys;')->fetch(PDO::FETCH_COLUMN);
            error_log("[TrendVisionOne] Foreign keys enabled: " . ($foreignKeysEnabled ? 'YES' : 'NO'));
            
            if (!$foreignKeysEnabled) {
                error_log("[TrendVisionOne] Warning: Foreign keys could not be enabled");
            } else {
                error_log("[TrendVisionOne] Database connection established with foreign keys enabled");
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("[TrendVisionOne] Database connection error: " . $e->getMessage());
            return false;
        }
    }

    // Initialize database and create tables if they don't exist
    public function initializeDB() {
        try {
            // Create devices table
            $this->sql->exec("CREATE TABLE IF NOT EXISTS devices (
                id VARCHAR(36) PRIMARY KEY,
                device_name VARCHAR(255),
                criticality VARCHAR(50),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            // Create device_ips table
            $this->sql->exec("CREATE TABLE IF NOT EXISTS device_ips (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                device_id VARCHAR(36),
                ip_address VARCHAR(45),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (device_id) REFERENCES devices(id)
            )");

            // Create cve_records table
            $this->sql->exec("CREATE TABLE IF NOT EXISTS cve_records (
                id VARCHAR(50) PRIMARY KEY,
                cvss_score FLOAT,
                event_risk_level VARCHAR(50),
                global_exploit_activity_level VARCHAR(50),
                exploit_attempt_count INTEGER,
                mitigation_status VARCHAR(50),
                published_datetime DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            // Create device_cve_mapping table
            $this->sql->exec("CREATE TABLE IF NOT EXISTS device_cve_mapping (
                device_id VARCHAR(36),
                cve_id VARCHAR(50),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (device_id, cve_id),
                FOREIGN KEY (device_id) REFERENCES devices(id),
                FOREIGN KEY (cve_id) REFERENCES cve_records(id)
            )");

            // Create affected_components table
            $this->sql->exec("CREATE TABLE IF NOT EXISTS affected_components (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                cve_id VARCHAR(50),
                component_name VARCHAR(255),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (cve_id) REFERENCES cve_records(id)
            )");

            // Create linux_remediations table
            $this->sql->exec("CREATE TABLE IF NOT EXISTS linux_remediations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                cve_id VARCHAR(50),
                package_name VARCHAR(255),
                minimum_patched_version VARCHAR(255),
                product_distribution VARCHAR(255),
                release_date DATE,
                security_advisory_id VARCHAR(50),
                security_advisory_link TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (cve_id) REFERENCES cve_records(id)
            )");

            // Create misc table for storing last sync time and other metadata
            $this->sql->exec("CREATE TABLE IF NOT EXISTS misc (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                key TEXT UNIQUE,
                value TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            // Initialize last sync time if not exists
            $this->sql->exec('INSERT OR IGNORE INTO misc (key, value) VALUES ("last_sync", "0")');

            // Create indexes for better query performance
            $this->sql->exec('CREATE INDEX IF NOT EXISTS idx_device_ips_device_id ON device_ips(device_id);');
            $this->sql->exec('CREATE INDEX IF NOT EXISTS idx_device_cve_mapping_device_id ON device_cve_mapping(device_id);');
            $this->sql->exec('CREATE INDEX IF NOT EXISTS idx_device_cve_mapping_cve_id ON device_cve_mapping(cve_id);');
            $this->sql->exec('CREATE INDEX IF NOT EXISTS idx_affected_components_cve_id ON affected_components(cve_id);');
            $this->sql->exec('CREATE INDEX IF NOT EXISTS idx_linux_remediations_cve_id ON linux_remediations(cve_id);');
            $this->sql->exec('CREATE INDEX IF NOT EXISTS idx_cve_records_cvss ON cve_records(cvss_score);');
            $this->sql->exec('CREATE INDEX IF NOT EXISTS idx_cve_records_risk ON cve_records(event_risk_level);');

            error_log("[TrendVisionOne] Database tables created/verified successfully");
            return true;
        } catch (PDOException $e) {
            error_log("[TrendVisionOne] Database Error: " . $e->getMessage());
            return false;
        }
    }

        //Protected function to define the settings for this plugin
    public function _pluginGetSettings() {
        return array(
            'Plugin Settings' => array(
                $this->settingsOption('auth', 'ACL-READ', ['label' => 'TrendVisionOne B&R Read ACL']),
                $this->settingsOption('auth', 'ACL-WRITE', ['label' => 'TrendVisionOne B&R Write ACL']),
                $this->settingsOption('auth', 'ACL-ADMIN', ['label' => 'TrendVisionOne B&R Admin ACL']),
                $this->settingsOption('auth', 'ACL-JOB', ['label' => 'Grants access to use TrendVisionOne Integration'])
            ),
            'TrendVisionOne Settings' => array(
                $this->settingsOption('url', 'TrendVisionOne-URL', [
                    'label' => 'TrendVisionOne URL',
                    'description' => 'The URL of your TrendVisionOne (e.g., https://api.eu.xdr.trendmicro.com/). Uses port 443 for HTTPS.'
                ]),
                $this->settingsOption('password', 'TrendVisionOne-Api-Token', [
                    'label' => 'TrendVisionOne API Token',
                    'description' => 'API Token for TrendVisionOne authentication'
                ])
            )
        );
    }

        //Protected function to define the api and build the required api for the plugin
    private function getApiEndpoint($path, $params = []) {
        $baseUrl = $this->getTrendVisionOneUrl();
        // Ensure path starts with /v3.0
        if (strpos($path, '/v3.0/') !== 0) {
            $path = '/v3.0/' . ltrim($path, '/');
        }
        $url = $baseUrl . $path;
        
        // Add query parameters if they exist
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        error_log("Full API URL: " . $url);
        return $url;
    }

        //Protected function to define the TrendVisionOne URL to build the required URI for the TrendVisionOne Plugin
    private function getTrendVisionOneUrl() {
        if (!isset($this->pluginConfig['TrendVisionOne-URL']) || empty($this->pluginConfig['TrendVisionOne-URL'])) {
            throw new Exception("TrendVisionOne URL not configured. Please set 'TrendVisionOne-URL' in config.json");
        }
        // Remove trailing slash if present
        return rtrim($this->pluginConfig['TrendVisionOne-URL'], '/');
    }

        //Protected function to decrypt the password and build out a valid token for Veeam Plugin
    private function getAccessToken($force = false) {
        try {
            if (!isset($this->pluginConfig['TrendVisionOne-Api-Token'])) {
                throw new Exception("TrendVisionOne API Token not configured. Please set 'TrendVisionOne-Api-Token' in config.json");
            }

            try {
                $apiToken = decrypt($this->pluginConfig['TrendVisionOne-Api-Token'], $this->config->get('Security','salt'));
            } catch (Exception $e) {
                $this->api->setAPIResponse('Error', 'Unable to decrypt TrendVisionOne API Token');
                $this->logging->writeLog('TrendVisionOne-Api-Token', 'Unable to decrypt TrendVisionOne API Token', 'error');
                return false;
            }

            return $apiToken;

        } catch (Exception $e) {
            error_log("Error getting access token: " . $e->getMessage());
            throw $e;
        }
    }
    

        //Protected function to for making API Request to Veeam for Get/Post/Put/Delete
    public function makeApiRequest($Method, $Uri, $Data = "") {
        try {
            if (!isset($this->pluginConfig['TrendVisionOne-URL']) || empty($this->pluginConfig['TrendVisionOne-URL'])) {
                throw new Exception("TrendVisionOne URL not configured");
            }

            $apiToken = $this->getAccessToken();
            if (!$apiToken) {
                throw new Exception("Failed to get TrendVisionOne API Token");
            }

            $headers = array(
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $apiToken
            );

            $url = $this->getApiEndpoint($Uri);
            error_log("Making request to URL: " . $url);
            error_log("Headers: " . json_encode($headers));
            if ($Data) {
                error_log("Request Data: " . json_encode($Data));
            }
            
            if (in_array($Method, ["GET", "get"])) {
                $Result = $this->api->query->$Method($url, $headers);
            } else {
                $Result = $this->api->query->$Method($url, $Data, $headers);
            }

            error_log("API Response: " . json_encode($Result));

            if (isset($Result->status_code) && $Result->status_code >= 400) {
                throw new Exception("API request failed with status code: " . $Result->status_code . ", Response: " . json_encode($Result));
            }

            return $Result;

        } catch (Exception $e) {
            error_log("TrendVisionOne API Error: " . $e->getMessage());
            $this->api->setAPIResponse('Error', $e->getMessage());
            return false;
        }
    }

    //// Everything after this line (221) is features and is permitted to be edited to build out the plugin features

    public function getFullApiUrl() {
        try {
            $url = $this->getApiEndpoint("endpointSecurity/endpoints");
            print_r($url);
            $this->api->setAPIResponse('Success', 'API URL Retrieved');
            $this->api->setAPIResponseData(['url' => $url]);
            return true;
        } catch (Exception $e) {
            $this->api->setAPIResponse('Error', $e->getMessage());
            return false;
        }
    }

    public function GetVulnerableDevices() {
        try {
            if (!$this->auth->checkAccess($this->config->get("Plugins", "TrendVisionOne")['ACL-READ'] ?? "ACL-READ")) {
                throw new Exception("Access Denied - Missing READ permissions");
            }

            // Make API request
            $result = $this->makeApiRequest("GET", "asrm/vulnerableDevices");
            
            if ($result === false) {
                $this->api->setAPIResponse('Error', 'Failed to retrieve vulnerable devices - API request failed');
                return false;
            }

            // Just pass through the raw response
            $this->api->setAPIResponse('Success', 'Retrieved raw device data');
            $this->api->setAPIResponseData($result);
            return true;

        } catch (Exception $e) {
            error_log("Error in GetVulnerableDevices: " . $e->getMessage());
            $this->api->setAPIResponse('Error', $e->getMessage());
            return false;
        }
    }

    public function GetFullDesktops() {
        try {
            if (!$this->auth->checkAccess($this->config->get("Plugins", "TrendVisionOne")['ACL-READ'] ?? "ACL-READ")) {
                throw new Exception("Access Denied - Missing READ permissions");
            }

            // Set query parameters for 1000 records
            $params = [
                'top' => 1000,
                'skip' => 0
            ];

            // Make API request with parameters
            $result = $this->makeApiRequest("GET", "endpointSecurity/endpoints?" . http_build_query($params));
            
            if ($result === false) {
                $this->api->setAPIResponse('Error', 'Failed to retrieve endpoints - API request failed');
                return false;
            }

            if (empty($result)) {
                $this->api->setAPIResponse('Error', 'Failed to retrieve endpoints - Empty response');
                return false;
            }

            // Debug the response structure
            error_log("API Response structure: " . print_r($result, true));
            
            // Check if we have a valid response with totalCount
            if (isset($result->totalCount)) {
                $responseData = [
                    'totalCount' => $result->totalCount,
                    'count' => $result->count,
                    'items' => $result->items ?? []
                ];
                $this->api->setAPIResponse('Success', 'Retrieved ' . ($result->count) . ' endpoints');
                $this->api->setAPIResponseData($responseData);
            } else {
                // Handle direct array response
                $items = json_decode(json_encode($result), true);
                if (is_array($items)) {
                    $responseData = [
                        'totalCount' => count($items),
                        'count' => count($items),
                        'items' => $items
                    ];
                    $this->api->setAPIResponse('Success', 'Retrieved ' . count($items) . ' endpoints');
                    $this->api->setAPIResponseData($responseData);
                } else {
                    error_log("Unexpected response format: " . gettype($result));
                    $this->api->setAPIResponse('Error', 'Unexpected API response structure');
                    return false;
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error in GetFullDesktops: " . $e->getMessage());
            $this->api->setAPIResponse('Error', $e->getMessage());
            return false;
        }
    }

    public function GetEndpointDetails($endpointId = null) {
        try {
            if (!$this->auth->checkAccess($this->config->get("Plugins", "TrendVisionOne")['ACL-READ'] ?? "ACL-READ")) {
                throw new Exception("Access Denied - Missing READ permissions");
            }

            // Check if endpoint ID is provided
            if (empty($endpointId)) {
                throw new Exception("Endpoint ID is required");
            }

            $endpoint = "endpointSecurity/endpoints/" . urlencode($endpointId);
            $result = $this->makeApiRequest("GET", $endpoint);
            
            if ($result === false) {
                $this->api->setAPIResponse('Error', 'Failed to retrieve endpoint details - API request failed');
                return false;
            }

            if (empty($result)) {
                $this->api->setAPIResponse('Error', 'Failed to retrieve endpoint details - Empty response');
                return false;
            }

            // Debug the response structure
            error_log("API Response structure for endpoint $endpointId: " . print_r($result, true));
            
            // Convert response to array if it's an object
            $details = json_decode(json_encode($result), true);
            
            if (is_array($details)) {
                $this->api->setAPIResponse('Success', 'Retrieved endpoint details');
                $this->api->setAPIResponseData($details);
            } else {
                error_log("Unexpected response format: " . gettype($result));
                $this->api->setAPIResponse('Error', 'Unexpected API response structure');
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error in GetEndpointDetails: " . $e->getMessage());
            $this->api->setAPIResponse('Error', $e->getMessage());
            return false;
        }
    }

    public function verifyDatabaseStructure() {
        try {
            $tables = [
                'devices', 'device_ips', 'cve_records', 'device_cve_mapping',
                'affected_components', 'linux_remediations', 'misc'
            ];
            
            foreach ($tables as $table) {
                $query = $this->sql->query("PRAGMA table_info(" . $table . ")");
                $columns = $query->fetchAll(PDO::FETCH_ASSOC);
                
                error_log("[TrendVisionOne] Table '{$table}' structure:");
                foreach ($columns as $col) {
                    error_log("  - {$col['name']} ({$col['type']})");
                }
                
                // Check if table exists by trying to count rows
                $count = $this->sql->query("SELECT COUNT(*) as count FROM " . $table)->fetch(PDO::FETCH_ASSOC);
                error_log("  Total rows: {$count['count']}");
                error_log("-------------------");
            }
            
            $this->api->setAPIResponse('Success', 'Database structure verified');
            return true;
        } catch (PDOException $e) {
            error_log("[TrendVisionOne] Database verification error: " . $e->getMessage());
            $this->api->setAPIResponse('Error', 'Failed to verify database structure');
            return false;
        }
    }

    public function syncVulnerabilityData() {
        try {
            if (!$this->auth->checkAccess($this->config->get("Plugins", "TrendVisionOne")['ACL-READ'] ?? "ACL-READ")) {
                throw new Exception("Access Denied - Missing READ permissions");
            }

            // Check last sync time
            $stmt = $this->sql->prepare("SELECT value FROM misc WHERE key = 'last_sync'");
            $stmt->execute();
            $lastSync = $stmt->fetchColumn();
            
            if ($lastSync) {
                $lastSyncTime = strtotime($lastSync);
                $timeSinceLastSync = time() - $lastSyncTime;
                if ($timeSinceLastSync < 3600) { // 3600 seconds = 60 minutes
                    $this->api->setAPIResponse('Warning', 'Data was synced less than 60 minutes ago. Next sync available in ' . (3600 - $timeSinceLastSync) . ' seconds.');
                    return false;
                }
            }

            // Start transaction
            $this->sql->beginTransaction();

            try {
                // Get data from API
                if (!$this->GetVulnerableDevices()) {
                    throw new Exception("Failed to retrieve vulnerability data from API");
                }

                $data = $GLOBALS['api'];
                if (empty($data) || empty($data['data']) || empty($data['data']['items'])) {
                    throw new Exception("No vulnerability data received from API");
                }

                // Process each device
                foreach ($data['data']['items'] as $device) {
                    // Insert device
                    $stmt = $this->sql->prepare("INSERT OR REPLACE INTO devices (id, device_name, criticality, created_at) VALUES (?, ?, ?, datetime('now'))");
                    $stmt->execute([$device['id'], $device['deviceName'], $device['criticality']]);

                    // Insert IPs
                    if (!empty($device['ip'])) {
                        $stmt = $this->sql->prepare("INSERT OR REPLACE INTO device_ips (device_id, ip_address, created_at) VALUES (?, ?, datetime('now'))");
                        foreach ($device['ip'] as $ip) {
                            $stmt->execute([$device['id'], $ip]);
                        }
                    }

                    // Process CVEs
                    if (!empty($device['cveRecords'])) {
                        foreach ($device['cveRecords'] as $cve) {
                            // Insert CVE record
                            $stmt = $this->sql->prepare("INSERT OR REPLACE INTO cve_records 
                                (id, cvss_score, event_risk_level, global_exploit_activity_level, exploit_attempt_count, mitigation_status, published_datetime, created_at) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'))");
                            $stmt->execute([
                                $cve['id'],
                                $cve['cvssScore'],
                                $cve['eventRiskLevel'],
                                $cve['globalExploitActivityLevel'],
                                $cve['exploitAttemptCount'],
                                $cve['mitigationStatus'],
                                $cve['publishedDateTime']
                            ]);

                            // Map device to CVE
                            $stmt = $this->sql->prepare("INSERT OR REPLACE INTO device_cve_mapping (device_id, cve_id, created_at) VALUES (?, ?, datetime('now'))");
                            $stmt->execute([$device['id'], $cve['id']]);

                            // Insert affected components
                            if (!empty($cve['affectedComponents'])) {
                                $stmt = $this->sql->prepare("INSERT OR REPLACE INTO affected_components (cve_id, component_name, created_at) VALUES (?, ?, datetime('now'))");
                                foreach ($cve['affectedComponents'] as $component) {
                                    $stmt->execute([$cve['id'], $component]);
                                }
                            }

                            // Insert Linux remediations if they exist
                            if (!empty($cve['mitigationOption']) && !empty($cve['mitigationOption']['linuxRemediations'])) {
                                $stmt = $this->sql->prepare("INSERT OR REPLACE INTO linux_remediations 
                                    (cve_id, package_name, minimum_patched_version, product_distribution, release_date, security_advisory_id, security_advisory_link, created_at) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'))");
                                foreach ($cve['mitigationOption']['linuxRemediations'] as $remediation) {
                                    $stmt->execute([
                                        $cve['id'],
                                        $remediation['packageName'],
                                        $remediation['minimumPatchedPackageVersion'],
                                        $remediation['productDistribution'],
                                        $remediation['releaseDate'],
                                        $remediation['securityAdvisoryId'],
                                        $remediation['securityAdvisoryLink']
                                    ]);
                                }
                            }
                        }
                    }
                }

                // Update last sync time
                $stmt = $this->sql->prepare("UPDATE misc SET value = datetime('now'), updated_at = datetime('now') WHERE key = 'last_sync'");
                $stmt->execute();

                // Commit transaction
                $this->sql->commit();
                error_log("[TrendVisionOne] Successfully synced vulnerability data");
                $this->api->setAPIResponse('Success', 'Vulnerability data synced successfully');
                return true;

            } catch (Exception $e) {
                // Rollback on error
                $this->sql->rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            error_log("[TrendVisionOne] Sync error: " . $e->getMessage());
            $this->api->setAPIResponse('Error', $e->getMessage());
            return false;
        }
    }

    public function getVulnerabilities() {
        $query = "
            SELECT d.id, d.device_name, d.criticality, COUNT(cr.id) AS cve_count
            FROM devices d
            LEFT JOIN cve_records cr ON d.cve_id = cr.id
            GROUP BY d.id, d.device_name, d.criticality";
        
        $stmt = $this->sql->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getLastSync() {
        $stmt = $this->sql->query("SELECT value FROM misc WHERE key = 'last_sync'");
        return $stmt->fetchColumn();
    }

    public function getVulnerabilitiesForDevice($deviceId) {
        $query = "
            SELECT cr.*
            FROM device_cve_mapping dcm
            LEFT JOIN cve_records cr ON dcm.cve_id = cr.id
            WHERE dcm.device_id = ?
            ORDER BY cr.cvss_score DESC";
        
        $stmt = $this->sql->prepare($query);
        $stmt->execute([$deviceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}