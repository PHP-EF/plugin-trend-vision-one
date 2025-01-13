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
    public function __construct() {
        parent::__construct();
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
    private function getApiEndpoint($path) {
        $baseUrl = $this->getTrendVisionOneUrl();
        // Ensure path starts with /v3.0
        if (strpos($path, '/v3.0/') !== 0) {
            $path = '/v3.0/' . ltrim($path, '/');
        }
        $url = $baseUrl . $path;
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
            
            if (in_array($Method, ["GET", "get"])) {
                $Result = $this->api->query->$Method($url, $headers);
            } else {
                $Result = $this->api->query->$Method($url, $Data, $headers);
            }

            if (isset($Result->status_code)) {
                throw new Exception("API request failed with status code: " . $Result->status_code);
            }

            return $Result;

        } catch (Exception $e) {
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

//     public function getJobStatus() {
//         try {
//             if (!$this->auth->checkAccess($this->config->get("Plugins", "VeeamPlugin")['ACL-READ'] ?? "ACL-READ")) {
//                 throw new Exception("Access Denied - Missing READ permissions");
//             }

//             // Get all jobs sessions
//             $states = $this->makeApiRequest("GET", "v1/jobs/states");
            
//             // For debugging
//             echo "States Data Response:\n";
//             print_r($states);
            
//             if (!$states) {
//                 $this->api->setAPIResponse('Error', 'Failed to retrieve job states');
//                 return false;
//             }

//             $jobStates = [];
//             if (isset($states['data'])) {
//                 $jobStates = $states['data'];
//             }

//             $this->api->setAPIResponse('Success', 'Retrieved ' . count($jobStates) . ' job states');
//             $this->api->setAPIResponseData($jobStates);
//             return true;
//         } catch (Exception $e) {
//             $this->api->setAPIResponse('Error', $e->getMessage());
//             return false;
//         }
//     }

//     public function getBackupJobs() {
//         try {
//             if (!$this->auth->checkAccess($this->config->get("Plugins", "VeeamPlugin")['ACL-READ'] ?? "ACL-READ")) {
//                 throw new Exception("Access Denied - Missing READ permissions");
//             }

//             $jobsData = $this->makeApiRequest("GET","v1/jobs");
//             if (!$jobsData) {
//                 return false;
//             }
            
//             echo "Jobs Data Response:\n";
//             print_r($jobsData);
            
//             $jobs = [];
//             if (isset($jobsData->data)) {
//                 $jobs = $jobsData->data;
//                 echo "\nParsed Jobs:\n";
//                 print_r($jobs);
//             } 
            
//             // $formattedJobs = [];
//             // foreach ($jobs as $job) {
//             //     if (!is_array($job)) continue;
                
//             //     $formattedJob = [
//             //         'id' => $job->id ?? $job->Id ?? '',
//             //         'name' => $job->name ?? $job->Name ?? '',
//             //         'description' => $job->description ?? $job->Description ?? '',
//             //         'type' => $job->type ?? $job->Type ?? '',
//             //         'status' => $job->status ?? $job->Status ?? '',
//             //         'lastRun' => $job->lastRun ?? $job->LastRun ?? '',
//             //         'nextRun' => $job->nextRun ?? $job->NextRun ?? '',
//             //         'target' => $job->target ?? $job->Target ?? '',
//             //         'repository' => $job->repository ?? $job->Repository ?? '',
//             //         'enabled' => $job->enabled ?? $job->Enabled ?? false
//             //     ];
                
//             //     $formattedJobs[] = $formattedJob;
//             // }
            
//             $this->api->setAPIResponse('Success', 'Retrieved ' . count($jobs) . ' backup jobs');
//             $this->api->setAPIResponseData($jobs); //$formattedJobs
//             return true;
            
//         } catch (Exception $e) {
//             error_log("Error getting backup jobs: " . $e->getMessage());
//             $this->api->setAPIResponse('Error', $e->getMessage());
//             return false;
//         }
//     }

    public function GetFullDesktops() {
        try {
            if (!$this->auth->checkAccess($this->config->get("Plugins", "TrendVisionOne")['ACL-READ'] ?? "ACL-READ")) {
                throw new Exception("Access Denied - Missing READ permissions");
            }

            $sessions = $this->makeApiRequest("GET", "endpointSecurity/endpoints");
            $this->api->setAPIResponse('Success', 'Sessions retrieved');
            $this->api->setAPIResponseData($sessions['data']); // Just pass the data array directly
            return true;
        } catch (Exception $e) {
            $this->api->setAPIResponse('Error', $e->getMessage());
            return false;
        }
    }
}