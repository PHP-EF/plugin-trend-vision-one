<?php
//// Everything after this line (2) is Core Functionality and no changes are permitted until after line (188).
// **
// USED TO DEFINE PLUGIN INFORMATION & CLASS
// **
ini_set("memory_limit","512M");
// PLUGIN INFORMATION - This should match what is in plugin.json
$GLOBALS['plugins']['TrendVisionOne'] = [ // Plugin Name
    'name' => 'TrendVisionOne', // Plugin Name
    'author' => 'TinyTechLabUK', // Who wrote the plugin
    'category' => 'Anti Virus', // One to Two Word Description
    'link' => 'https://github.com/PHP-EF/plugin-trend_vision_one', // Link to plugin info
    'version' => '1.0.1', // SemVer of plugin
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
            // Create misc table for storing plugin metadata
            $this->sql->exec("CREATE TABLE IF NOT EXISTS misc (
                key TEXT PRIMARY KEY,
                value TEXT,
                created_at TEXT DEFAULT (datetime('now')),
                updated_at TEXT DEFAULT (datetime('now'))
            )");

            // Insert last_sync key if it doesn't exist
            $stmt = $this->sql->prepare("INSERT OR IGNORE INTO misc (key, value) VALUES ('last_sync', datetime('now'))");
            $stmt->execute();

            // Create devices table
            $this->sql->exec("CREATE TABLE IF NOT EXISTS devices (
                id TEXT PRIMARY KEY,
                device_name TEXT NOT NULL,
                criticality TEXT,
                created_at TEXT DEFAULT (datetime('now')),
                updated_at TEXT DEFAULT (datetime('now'))
            )");

            // Create device_ips table
            $this->sql->exec("CREATE TABLE IF NOT EXISTS device_ips (
                device_id TEXT,
                ip_address TEXT NOT NULL,
                created_at TEXT DEFAULT (datetime('now')),
                PRIMARY KEY (device_id, ip_address),
                FOREIGN KEY (device_id) REFERENCES devices(id)
            )");

            // Create cve_records table
            $this->sql->exec("CREATE TABLE IF NOT EXISTS cve_records (
                id TEXT PRIMARY KEY,
                cvss_score REAL DEFAULT 0.0,
                event_risk_level TEXT,
                description TEXT,
                global_exploit_activity_level TEXT,
                exploit_attempt_count INTEGER DEFAULT 0,
                mitigation_status TEXT,
                published_datetime TEXT,
                created_at TEXT DEFAULT (datetime('now')),
                updated_at TEXT DEFAULT (datetime('now'))
            )");

            // Create device_cve_mapping table
            $this->sql->exec("CREATE TABLE IF NOT EXISTS device_cve_mapping (
                device_id TEXT,
                cve_id TEXT,
                created_at TEXT DEFAULT (datetime('now')),
                PRIMARY KEY (device_id, cve_id),
                FOREIGN KEY (device_id) REFERENCES devices(id),
                FOREIGN KEY (cve_id) REFERENCES cve_records(id)
            )");

            // Create affected_components table
            $this->sql->exec("CREATE TABLE IF NOT EXISTS affected_components (
                cve_id TEXT,
                component_name TEXT NOT NULL,
                created_at TEXT DEFAULT (datetime('now')),
                PRIMARY KEY (cve_id, component_name),
                FOREIGN KEY (cve_id) REFERENCES cve_records(id)
            )");

            // Create linux_remediations table
            $this->sql->exec("CREATE TABLE IF NOT EXISTS linux_remediations (
                cve_id TEXT,
                package_name TEXT NOT NULL,
                minimum_patched_version TEXT,
                product_distribution TEXT,
                release_date TEXT,
                security_advisory_id TEXT,
                security_advisory_link TEXT,
                created_at TEXT DEFAULT (datetime('now')),
                PRIMARY KEY (cve_id, package_name),
                FOREIGN KEY (cve_id) REFERENCES cve_records(id)
            )");

            // Create indexes for better query performance
            $this->sql->exec('CREATE INDEX IF NOT EXISTS idx_device_ips_device_id ON device_ips(device_id);');
            $this->sql->exec('CREATE INDEX IF NOT EXISTS idx_device_cve_mapping_device_id ON device_cve_mapping(device_id);');
            $this->sql->exec('CREATE INDEX IF NOT EXISTS idx_device_cve_mapping_cve_id ON device_cve_mapping(cve_id);');
            $this->sql->exec('CREATE INDEX IF NOT EXISTS idx_affected_components_cve_id ON affected_components(cve_id);');
            $this->sql->exec('CREATE INDEX IF NOT EXISTS idx_linux_remediations_cve_id ON linux_remediations(cve_id);');

            // error_log("[TrendVisionOne] Database tables created/verified successfully");
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
        
        // error_log("Full API URL: " . $url);
        return $url;
    }

        //Protected function to define the TrendVisionOne URL to build the required URI for the TrendVisionOne Plugin
    private function getTrendVisionOneUrl() {
        if (!isset($this->trendvisiononepluginConfig['TrendVisionOne-URL']) || empty($this->trendvisiononepluginConfig['TrendVisionOne-URL'])) {
            throw new Exception("TrendVisionOne URL not configured. Please set 'TrendVisionOne-URL' in config.json");
        }
        // Remove trailing slash if present
        return rtrim($this->trendvisiononepluginConfig['TrendVisionOne-URL'], '/');
    }

        //Protected function to decrypt the password and build out a valid token for Veeam Plugin
    private function getAccessToken($force = false) {
        try {
            if (!isset($this->trendvisiononepluginConfig['TrendVisionOne-Api-Token'])) {
                throw new Exception("TrendVisionOne API Token not configured. Please set 'TrendVisionOne-Api-Token' in config.json");
            }

            try {
                $apiToken = decrypt($this->trendvisiononepluginConfig['TrendVisionOne-Api-Token'], $this->config->get('Security','salt'));
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
            if (!isset($this->trendvisiononepluginConfig['TrendVisionOne-URL']) || empty($this->trendvisiononepluginConfig['TrendVisionOne-URL'])) {
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
  
            if (in_array($Method, ["GET", "get"])) {
                $Result = $this->api->query->$Method($url, $headers);
            } else {
                $Result = $this->api->query->$Method($url, $Data, $headers);
            }

            // error_log("API Response: " . json_encode($Result));

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
            // print_r($url);
            $this->api->setAPIResponse('Success', 'API URL Retrieved');
            $this->api->setAPIResponseData(['url' => $url]);
            return true;
        } catch (Exception $e) {
            $this->api->setAPIResponse('Error', $e->getMessage());
            return false;
        }
    }

    public function GetVulnerableDevices($nextLinkPage = null) {
        try {
            if (!$this->auth->checkAccess($this->config->get("Plugins", "TrendVisionOne")['ACL-READ'] ?? "ACL-READ")) {
                throw new Exception("Access Denied - Missing READ permissions");
            }

            // Make API request
            if($nextLinkPage){
                $url = explode($this->getTrendVisionOneUrl()."/v3.0/",$nextLinkPage)[1] ?? "";
                $result = $this->makeApiRequest("GET", $url);   
            }else{
                // echo "hello got to line 302";
                $result = $this->makeApiRequest("GET", "asrm/vulnerableDevices");
                // print_r($result);
            }

            if ($result === false) {
                $this->api->setAPIResponse('Error', 'Failed to retrieve vulnerable devices - API request failed');
                return false;
            }

            // Just pass through the raw response
            // $this->api->setAPIResponse('Success', 'Retrieved raw device data');
            // $this->api->setAPIResponseData($result);
            return $result;

        } catch (Exception $e) {
            error_log("Error in GetVulnerableDevices: " . $e->getMessage());
            $this->api->setAPIResponse('Error', $e->getMessage());
            return false;
        }
    }

    public function GetAllVulnerableDevices () {
        $DevicesR = $this->GetVulnerableDevices();
        $DeviceResults = $DevicesR["items"];
    
        // $this->api->setAPIResponseData($DeviceResults);
        $count = 0;
        while (isset($DevicesR["nextLink"])) {
            $DevicesR = $this->GetVulnerableDevices($DevicesR["nextLink"]);
            $DeviceResults = array_merge($DeviceResults,$DevicesR["items"]);
            $count++;
            if ($count >= 10){break;} /// This limits to 1000 devices 
        }
        return $DeviceResults;
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
            // error_log("API Response structure: " . print_r($result, true));
            
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
                $data = $this->GetAllVulnerableDevices();

                if (empty($data)) {
                    throw new Exception("No vulnerability data received from API");
                }

                // Process each device
                foreach ($data as $device) {
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
        try {
            // First get the severity counts
            $severityQuery = "
                SELECT 
                    CASE 
                        WHEN cvss_score >= 7.0 THEN 'high'
                        WHEN cvss_score >= 4.0 THEN 'medium'
                        ELSE 'low'
                    END as severity,
                    COUNT(*) as count
                FROM cve_records
                GROUP BY severity
                ORDER BY severity";
            
            $stmt = $this->sql->query($severityQuery);
            $severityCounts = [
                'high' => 0,
                'medium' => 0,
                'low' => 0
            ];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $severityCounts[$row['severity']] = $row['count'];
            }

            // Get the main vulnerability data with all related information
            $query = "
                WITH cve_full_details AS (
                    SELECT 
                        cr.*,
                        GROUP_CONCAT(DISTINCT ac.component_name) as affected_components,
                        GROUP_CONCAT(
                            json_object(
                                'packageName', lr.package_name,
                                'minimumPatchedPackageVersion', lr.minimum_patched_version,
                                'productDistribution', lr.product_distribution,
                                'releaseDate', lr.release_date,
                                'securityAdvisoryId', lr.security_advisory_id,
                                'securityAdvisoryLink', lr.security_advisory_link
                            )
                        ) as linux_remediations
                    FROM cve_records cr
                    LEFT JOIN affected_components ac ON cr.id = ac.cve_id
                    LEFT JOIN linux_remediations lr ON cr.id = lr.cve_id
                    GROUP BY cr.id
                )
                SELECT 
                    d.id,
                    d.device_name,
                    d.criticality,
                    GROUP_CONCAT(DISTINCT di.ip_address) as ip_addresses,
                    COUNT(DISTINCT dcm.cve_id) as cve_count,
                    MAX(cfd.cvss_score) as highest_cvss,
                    MAX(cfd.event_risk_level) as highest_risk,
                    GROUP_CONCAT(
                        json_object(
                            'id', cfd.id,
                            'cvss_score', cfd.cvss_score,
                            'event_risk_level', cfd.event_risk_level,
                            'description', cfd.description,
                            'global_exploit_activity_level', cfd.global_exploit_activity_level,
                            'exploit_attempt_count', cfd.exploit_attempt_count,
                            'mitigation_status', cfd.mitigation_status,
                            'published_datetime', cfd.published_datetime,
                            'created_at', cfd.created_at,
                            'updated_at', cfd.updated_at,
                            'affectedComponents', json(CASE 
                                WHEN cfd.affected_components IS NULL THEN '[]'
                                ELSE json_array(cfd.affected_components) 
                            END),
                            'mitigationOption', json_object(
                                'linuxRemediations', CASE 
                                    WHEN cfd.linux_remediations IS NULL THEN '[]'
                                    ELSE json_array(cfd.linux_remediations)
                                END
                            )
                        )
                    ) as cve_details
                FROM devices d
                LEFT JOIN device_ips di ON d.id = di.device_id
                LEFT JOIN device_cve_mapping dcm ON d.id = dcm.device_id
                LEFT JOIN cve_full_details cfd ON dcm.cve_id = cfd.id
                GROUP BY d.id, d.device_name, d.criticality
                ORDER BY highest_cvss DESC";

            $stmt = $this->sql->query($query);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format the results
            foreach ($results as &$row) {
                // Format IP addresses
                $row['ip_addresses'] = $row['ip_addresses'] ? explode(',', $row['ip_addresses']) : [];
                
                // Parse and format CVE details
                if ($row['cve_details']) {
                    $cveDetails = array_map(function($cve) {
                        $cve = json_decode($cve, true);
                        // Convert string arrays back to arrays
                        if (isset($cve['affectedComponents'])) {
                            $cve['affectedComponents'] = array_filter(explode(',', $cve['affectedComponents']));
                        }
                        if (isset($cve['mitigationOption']['linuxRemediations'])) {
                            $remediations = json_decode($cve['mitigationOption']['linuxRemediations'], true);
                            $cve['mitigationOption']['linuxRemediations'] = array_filter($remediations);
                        }
                        return $cve;
                    }, explode(',', $row['cve_details']));
                    $row['cve_records'] = $cveDetails;
                } else {
                    $row['cve_records'] = [];
                }
                unset($row['cve_details']);
            }

            return [
                'severity_counts' => $severityCounts,
                'devices' => $results
            ];
        } catch (PDOException $e) {
            error_log("[TrendVisionOne] Error fetching vulnerabilities: " . $e->getMessage());
            return [];
        }
    }
    
    public function getLastSync() {
        try {
            // First check if the misc table exists
            $tableExists = $this->sql->query("SELECT name FROM sqlite_master WHERE type='table' AND name='misc'")->fetchColumn();
            if (!$tableExists) {
                $this->verifyDatabaseStructure();
            }

            // Get the last sync time
            $stmt = $this->sql->query("SELECT strftime('%s', value) as unix_timestamp FROM misc WHERE key = 'last_sync'");
            $lastSync = $stmt->fetchColumn();
            
            if (!$lastSync) {
                // Initialize last sync time if it doesn't exist
                $stmt = $this->sql->prepare("INSERT OR REPLACE INTO misc (key, value) VALUES ('last_sync', datetime('now'))");
                $stmt->execute();
                return strtotime('now');
            }
            
            return $lastSync;
            
        } catch (PDOException $e) {
            error_log("[TrendVisionOne] Error fetching last sync time: " . $e->getMessage());
            throw $e;
        }
    }

    public function getVulnerabilitiesForDevice($deviceId) {
        try {
            error_log("[TrendVisionOne] Getting vulnerabilities for device ID: " . $deviceId);
            
            $query = "
                SELECT 
                    cr.*,
                    GROUP_CONCAT(DISTINCT ac.component_name) as affected_components,
                    GROUP_CONCAT(DISTINCT lr.package_name || '|' || lr.minimum_patched_version || '|' || lr.product_distribution || '|' || 
                                         lr.release_date || '|' || lr.security_advisory_id || '|' || lr.security_advisory_link) as linux_remediations
                FROM devices d
                JOIN device_cve_mapping dcm ON d.id = dcm.device_id
                JOIN cve_records cr ON dcm.cve_id = cr.id
                LEFT JOIN affected_components ac ON cr.id = ac.cve_id
                LEFT JOIN linux_remediations lr ON cr.id = lr.cve_id
                WHERE d.id = ?
                GROUP BY cr.id
                ORDER BY cr.cvss_score DESC";
            
            $stmt = $this->sql->prepare($query);
            $stmt->execute([$deviceId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($results)) {
                error_log("[TrendVisionOne] No vulnerabilities found for device ID: " . $deviceId);
                $this->api->setAPIResponse('Error', 'No vulnerabilities found for device');
                return [];
            }
            
            // Format the results
            foreach ($results as &$row) {
                // Format basic fields
                $row['cvss_score'] = floatval($row['cvss_score'] ?? 0);
                $row['event_risk_level'] = $row['event_risk_level'] ?? 'None';
                $row['exploit_attempt_count'] = intval($row['exploit_attempt_count'] ?? 0);
                
                // Format affected components
                $row['affectedComponents'] = $row['affected_components'] ? explode(',', $row['affected_components']) : [];
                unset($row['affected_components']);
                
                // Format linux remediations
                $row['mitigationOption'] = ['linuxRemediations' => []];
                if (!empty($row['linux_remediations'])) {
                    $remediations = explode(',', $row['linux_remediations']);
                    foreach ($remediations as $remediation) {
                        list($package, $version, $dist, $date, $advisory, $link) = explode('|', $remediation);
                        $row['mitigationOption']['linuxRemediations'][] = [
                            'packageName' => $package,
                            'minimumPatchedPackageVersion' => $version,
                            'productDistribution' => $dist,
                            'releaseDate' => $date,
                            'securityAdvisoryId' => $advisory,
                            'securityAdvisoryLink' => $link
                        ];
                    }
                }
                unset($row['linux_remediations']);
            }
            
            $this->api->setAPIResponse('Success', 'Retrieved vulnerability data from database');
            return $results;
            
        } catch (PDOException $e) {
            error_log("[TrendVisionOne] Error fetching device vulnerabilities: " . $e->getMessage());
            throw $e;
        }
    }
}