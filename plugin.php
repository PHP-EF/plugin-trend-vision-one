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

    public function __construct() {
        parent::__construct();
        $this->pluginConfig = $this->config->get('Plugins','TrendVisionOne') ?? [];
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
    // private function refreshAuth() {
    //     // Refresh authentication logic here
    //     // For now, just reset the access token
    //     $this->accessToken = null;
    //     $this->tokenExpiration = null;
    // }

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

            // Set query parameters according to API v3 spec
            $params = [
                'top' => 50,          // Number of records per page (default 50, max 200)
                'skip' => 0,          // Number of records to skip
                'orderBy' => 'cvssScore desc',  // Order by CVSS score descending
                'select' => implode(',', [
                    'endpointName',
                    'ip',
                    'mac',
                    'os',
                    'agentGuid',
                    'cvssScore',
                    'riskLevel',
                    'vulnerabilityId',
                    'vulnerabilityType',
                    'detectedBy',
                    'installedProductName',
                    'installedProductVersion',
                    'fixedProductVersion',
                    'description',
                    'recommendation',
                    'firstDetected',
                    'lastDetected'
                ])
            ];

            // Make API request with parameters to v3.0 endpoint
            $result = $this->makeApiRequest("GET", "v3.0/asrm/vulnerableDevices?" . http_build_query($params));
            
            if ($result === false) {
                $this->api->setAPIResponse('Error', 'Failed to retrieve vulnerable devices - API request failed');
                return false;
            }

            if (empty($result)) {
                $this->api->setAPIResponse('Error', 'Failed to retrieve vulnerable devices - Empty response');
                return false;
            }

            // Debug the response structure
            error_log("API Response structure: " . print_r($result, true));
            
            // Handle the response according to v3 API structure
            $items = json_decode(json_encode($result), true);
            
            if (isset($items['items']) && is_array($items['items'])) {
                $responseData = [
                    'totalCount' => $items['totalCount'] ?? count($items['items']),
                    'count' => count($items['items']),
                    'items' => array_map(function($item) {
                        return [
                            'endpointName' => $item['endpointName'] ?? '',
                            'ip' => $item['ip'] ?? '',
                            'mac' => $item['mac'] ?? '',
                            'os' => $item['os'] ?? '',
                            'agentGuid' => $item['agentGuid'] ?? '',
                            'cvssScore' => $item['cvssScore'] ?? 0,
                            'riskLevel' => $item['riskLevel'] ?? '',
                            'vulnerabilityId' => $item['vulnerabilityId'] ?? '',
                            'vulnerabilityType' => $item['vulnerabilityType'] ?? '',
                            'detectedBy' => $item['detectedBy'] ?? '',
                            'installedProductName' => $item['installedProductName'] ?? '',
                            'installedProductVersion' => $item['installedProductVersion'] ?? '',
                            'fixedProductVersion' => $item['fixedProductVersion'] ?? '',
                            'description' => $item['description'] ?? '',
                            'recommendation' => $item['recommendation'] ?? '',
                            'firstDetected' => $item['firstDetected'] ?? '',
                            'lastDetected' => $item['lastDetected'] ?? ''
                        ];
                    }, $items['items'])
                ];
                
                $this->api->setAPIResponse('Success', 'Retrieved ' . $responseData['count'] . ' vulnerable devices');
                $this->api->setAPIResponseData($responseData);
                return true;
            } else {
                error_log("Unexpected response format: " . gettype($result));
                $this->api->setAPIResponse('Error', 'Unexpected API response structure');
                return false;
            }
            
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
}