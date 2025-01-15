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

class TrendVisionOne extends phpef {
    private $pluginConfig;
    private $sql;

    public function __construct() {
        parent::__construct();
        $this->pluginConfig = $this->config->get('Plugins','TrendVisionOne') ?? [];
        // Create or open the SQLite database
        $dbFile = dirname(__DIR__,2). DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'trendvisionone.db';
        $this->sql = new PDO("sqlite:$dbFile");
        $this->sql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->hasDB();
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

    // Check if Database & Tables Exist
    private function hasDB() {
        if ($this->sql) {
            try {
                // Query to check if all required tables exist
                $result = $this->sql->query("SELECT name FROM sqlite_master WHERE type='table' AND name IN ('endpoints', 'vulnerabilities', 'endpoint_vulnerabilities', 'misc')");
                $tables = $result->fetchAll(PDO::FETCH_COLUMN);
            
                if (count($tables) === 4) {
                    return true;
                } else {
                    $this->createTables();
                }
            } catch (PDOException $e) {
                $this->api->setAPIResponse("Error", $e->getMessage());
                return false;
            }
        } else {
            $this->api->setAPIResponse("Error", "Database Not Initialized");
            return false;
        }
    }

    // Create Database Tables
    private function createTables() {
        // Create Endpoints Table
        $this->sql->exec("CREATE TABLE IF NOT EXISTS endpoints (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            agentGuid TEXT UNIQUE,
            endpointName TEXT,
            displayName TEXT,
            hostname TEXT,
            description TEXT,
            ip TEXT,
            mac TEXT,
            osName TEXT,
            osVersion TEXT,
            lastSeen DATETIME,
            lastUpdated DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Create Vulnerabilities Table
        $this->sql->exec("CREATE TABLE IF NOT EXISTS vulnerabilities (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            vulnerabilityId TEXT UNIQUE,
            cveId TEXT,
            description TEXT,
            riskLevel TEXT,
            cvssScore REAL,
            productName TEXT,
            productVersion TEXT,
            lastDetected DATETIME,
            status TEXT,
            lastUpdated DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Create Endpoint-Vulnerabilities Relationship Table
        $this->sql->exec("CREATE TABLE IF NOT EXISTS endpoint_vulnerabilities (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            endpointId INTEGER,
            vulnerabilityId INTEGER,
            detectedDate DATETIME,
            status TEXT,
            lastUpdated DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (endpointId) REFERENCES endpoints(id),
            FOREIGN KEY (vulnerabilityId) REFERENCES vulnerabilities(id),
            UNIQUE(endpointId, vulnerabilityId)
        )");

        // Create Misc Table for Settings and Status
        $this->sql->exec("CREATE TABLE IF NOT EXISTS misc (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            key TEXT UNIQUE,
            value TEXT
        )");

        // Insert default misc values
        $this->sql->exec('INSERT OR IGNORE INTO misc (key, value) VALUES ("lastSync", "0")');
        $this->sql->exec('INSERT OR IGNORE INTO misc (key, value) VALUES ("syncInterval", "300")');
    }

    // Get Last Sync Time
    public function getLastSync() {
        $stmt = $this->sql->prepare('SELECT value FROM misc WHERE key = :key');
        $stmt->execute([':key' => 'lastSync']);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['value'] : '0';
    }

    // Update Last Sync Time
    public function updateLastSync() {
        $time = time();
        $stmt = $this->sql->prepare('UPDATE misc SET value = :value WHERE key = :key');
        $stmt->execute([':key' => 'lastSync', ':value' => $time]);
        return $time;
    }

    // Get Sync Interval
    public function getSyncInterval() {
        $stmt = $this->sql->prepare('SELECT value FROM misc WHERE key = :key');
        $stmt->execute([':key' => 'syncInterval']);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? intval($result['value']) : 300;
    }

    // Update or Insert Endpoint
    public function upsertEndpoint($data) {
        $stmt = $this->sql->prepare('
            INSERT INTO endpoints 
            (agentGuid, endpointName, displayName, hostname, description, ip, mac, osName, osVersion, lastSeen)
            VALUES (:agentGuid, :endpointName, :displayName, :hostname, :description, :ip, :mac, :osName, :osVersion, :lastSeen)
            ON CONFLICT(agentGuid) DO UPDATE SET
            endpointName=:endpointName,
            displayName=:displayName,
            hostname=:hostname,
            description=:description,
            ip=:ip,
            mac=:mac,
            osName=:osName,
            osVersion=:osVersion,
            lastSeen=:lastSeen,
            lastUpdated=CURRENT_TIMESTAMP
        ');
        
        return $stmt->execute([
            ':agentGuid' => $data['agentGuid'],
            ':endpointName' => $data['endpointName'] ?? null,
            ':displayName' => $data['displayName'] ?? null,
            ':hostname' => $data['hostname'] ?? null,
            ':description' => $data['description'] ?? null,
            ':ip' => $data['ip'] ?? null,
            ':mac' => $data['mac'] ?? null,
            ':osName' => $data['osName'] ?? null,
            ':osVersion' => $data['osVersion'] ?? null,
            ':lastSeen' => $data['lastSeen'] ?? null
        ]);
    }

    // Update or Insert Vulnerability
    public function upsertVulnerability($data) {
        $stmt = $this->sql->prepare('
            INSERT INTO vulnerabilities 
            (vulnerabilityId, cveId, description, riskLevel, cvssScore, productName, productVersion, lastDetected, status)
            VALUES (:vulnerabilityId, :cveId, :description, :riskLevel, :cvssScore, :productName, :productVersion, :lastDetected, :status)
            ON CONFLICT(vulnerabilityId) DO UPDATE SET
            cveId=:cveId,
            description=:description,
            riskLevel=:riskLevel,
            cvssScore=:cvssScore,
            productName=:productName,
            productVersion=:productVersion,
            lastDetected=:lastDetected,
            status=:status,
            lastUpdated=CURRENT_TIMESTAMP
        ');
        
        return $stmt->execute([
            ':vulnerabilityId' => $data['vulnerabilityId'],
            ':cveId' => $data['cveId'] ?? null,
            ':description' => $data['description'] ?? null,
            ':riskLevel' => $data['riskLevel'] ?? null,
            ':cvssScore' => $data['cvssScore'] ?? null,
            ':productName' => $data['productName'] ?? null,
            ':productVersion' => $data['productVersion'] ?? null,
            ':lastDetected' => $data['lastDetected'] ?? null,
            ':status' => $data['status'] ?? 'Active'
        ]);
    }

    // Link Endpoint and Vulnerability
    public function linkEndpointVulnerability($endpointId, $vulnerabilityId, $detectedDate) {
        $stmt = $this->sql->prepare('
            INSERT INTO endpoint_vulnerabilities 
            (endpointId, vulnerabilityId, detectedDate, status)
            VALUES (:endpointId, :vulnerabilityId, :detectedDate, :status)
            ON CONFLICT(endpointId, vulnerabilityId) DO UPDATE SET
            detectedDate=:detectedDate,
            status=:status,
            lastUpdated=CURRENT_TIMESTAMP
        ');
        
        return $stmt->execute([
            ':endpointId' => $endpointId,
            ':vulnerabilityId' => $vulnerabilityId,
            ':detectedDate' => $detectedDate,
            ':status' => 'Active'
        ]);
    }

    // Get All Vulnerabilities with Endpoint Details
    public function getAllVulnerabilities() {
        $query = "
            SELECT 
                v.*,
                e.endpointName,
                e.agentGuid,
                e.osName,
                e.osVersion,
                ev.detectedDate
            FROM vulnerabilities v
            JOIN endpoint_vulnerabilities ev ON v.id = ev.vulnerabilityId
            JOIN endpoints e ON ev.endpointId = e.id
            WHERE ev.status = 'Active'
            ORDER BY v.lastDetected DESC
        ";
        
        $stmt = $this->sql->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get Vulnerability Statistics
    public function getVulnerabilityStats() {
        $query = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN riskLevel = 'HIGH' THEN 1 ELSE 0 END) as high,
                SUM(CASE WHEN riskLevel = 'MEDIUM' THEN 1 ELSE 0 END) as medium,
                SUM(CASE WHEN riskLevel = 'LOW' THEN 1 ELSE 0 END) as low
            FROM vulnerabilities v
            JOIN endpoint_vulnerabilities ev ON v.id = ev.vulnerabilityId
            WHERE ev.status = 'Active'
        ";
        
        $stmt = $this->sql->query($query);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

        //// Everything after this line (188) is features and is permitted to be edited to build out the plugin features

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

    // Get Vision One Vulnerabilities and store in database
    public function getVulnerableDevices() {
        try {
            // Check if we need to sync (based on sync interval)
            $lastSync = intval($this->getLastSync());
            $syncInterval = $this->getSyncInterval();
            $currentTime = time();
            
            if (($currentTime - $lastSync) < $syncInterval) {
                // Return data from database if within sync interval
                $vulnerabilities = $this->getAllVulnerabilities();
                $stats = $this->getVulnerabilityStats();
                
                return [
                    'result' => 'Success',
                    'data' => [
                        'items' => $vulnerabilities,
                        'stats' => $stats
                    ]
                ];
            }

            // Get fresh data from Vision One API
            $response = $this->queryVisionOne('GET', '/v3.0/vulnerabilities/hosts');
            
            if ($response && isset($response['items'])) {
                // Begin transaction
                $this->sql->beginTransaction();
                
                try {
                    foreach ($response['items'] as $item) {
                        // Process endpoint data
                        $endpointData = [
                            'agentGuid' => $item['agentGuid'],
                            'endpointName' => $item['endpointName'],
                            'displayName' => $item['displayName'] ?? null,
                            'hostname' => $item['hostname'] ?? null,
                            'ip' => $item['ip'] ?? null,
                            'mac' => $item['mac'] ?? null,
                            'osName' => $item['osName'] ?? null,
                            'osVersion' => $item['osVersion'] ?? null,
                            'lastSeen' => $item['lastSeen'] ?? null
                        ];
                        
                        // Insert/Update endpoint
                        $this->upsertEndpoint($endpointData);
                        
                        // Get endpoint ID
                        $stmt = $this->sql->prepare('SELECT id FROM endpoints WHERE agentGuid = :agentGuid');
                        $stmt->execute([':agentGuid' => $item['agentGuid']]);
                        $endpointId = $stmt->fetchColumn();
                        
                        // Process vulnerabilities
                        if (isset($item['vulnerabilities'])) {
                            foreach ($item['vulnerabilities'] as $vuln) {
                                // Process vulnerability data
                                $vulnData = [
                                    'vulnerabilityId' => $vuln['id'],
                                    'cveId' => $vuln['cveId'] ?? null,
                                    'description' => $vuln['description'] ?? null,
                                    'riskLevel' => $this->mapRiskLevel($vuln['cvssScore'] ?? 0),
                                    'cvssScore' => $vuln['cvssScore'] ?? null,
                                    'productName' => $vuln['productName'] ?? null,
                                    'productVersion' => $vuln['productVersion'] ?? null,
                                    'lastDetected' => $vuln['lastDetected'] ?? null,
                                    'status' => 'Active'
                                ];
                                
                                // Insert/Update vulnerability
                                $this->upsertVulnerability($vulnData);
                                
                                // Get vulnerability ID
                                $stmt = $this->sql->prepare('SELECT id FROM vulnerabilities WHERE vulnerabilityId = :vulnerabilityId');
                                $stmt->execute([':vulnerabilityId' => $vuln['id']]);
                                $vulnerabilityId = $stmt->fetchColumn();
                                
                                // Link endpoint to vulnerability
                                $this->linkEndpointVulnerability($endpointId, $vulnerabilityId, $vuln['lastDetected'] ?? null);
                            }
                        }
                    }
                    
                    // Update last sync time
                    $this->updateLastSync();
                    
                    // Commit transaction
                    $this->sql->commit();
                    
                    // Return fresh data from database
                    $vulnerabilities = $this->getAllVulnerabilities();
                    $stats = $this->getVulnerabilityStats();
                    
                    return [
                        'result' => 'Success',
                        'data' => [
                            'items' => $vulnerabilities,
                            'stats' => $stats
                        ]
                    ];
                    
                } catch (Exception $e) {
                    // Roll back transaction on error
                    $this->sql->rollBack();
                    throw $e;
                }
            }
            
            return ['result' => 'Error', 'message' => 'No vulnerability data received from Vision One'];
            
        } catch (Exception $e) {
            return ['result' => 'Error', 'message' => $e->getMessage()];
        }
    }

    // Helper function to map CVSS score to risk level
    private function mapRiskLevel($cvssScore) {
        $score = floatval($cvssScore);
        if ($score >= 7.0) return 'HIGH';
        if ($score >= 4.0) return 'MEDIUM';
        return 'LOW';
    }

    // Get endpoint details from database
    public function getEndpointDetails($agentGuid) {
        try {
            $stmt = $this->sql->prepare('
                SELECT 
                    e.*,
                    COUNT(DISTINCT ev.vulnerabilityId) as vulnerability_count,
                    MAX(v.lastDetected) as last_vulnerability_detected
                FROM endpoints e
                LEFT JOIN endpoint_vulnerabilities ev ON e.id = ev.endpointId
                LEFT JOIN vulnerabilities v ON ev.vulnerabilityId = v.id
                WHERE e.agentGuid = :agentGuid
                GROUP BY e.id
            ');
            
            $stmt->execute([':agentGuid' => $agentGuid]);
            $endpoint = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($endpoint) {
                // Get vulnerabilities for this endpoint
                $stmt = $this->sql->prepare('
                    SELECT v.*
                    FROM vulnerabilities v
                    JOIN endpoint_vulnerabilities ev ON v.id = ev.vulnerabilityId
                    JOIN endpoints e ON ev.endpointId = e.id
                    WHERE e.agentGuid = :agentGuid
                    AND ev.status = "Active"
                    ORDER BY v.lastDetected DESC
                ');
                
                $stmt->execute([':agentGuid' => $agentGuid]);
                $vulnerabilities = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $endpoint['vulnerabilities'] = $vulnerabilities;
                
                return ['result' => 'Success', 'data' => $endpoint];
            }
            
            return ['result' => 'Error', 'message' => 'Endpoint not found'];
            
        } catch (Exception $e) {
            return ['result' => 'Error', 'message' => $e->getMessage()];
        }
    }
}