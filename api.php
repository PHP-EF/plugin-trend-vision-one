<?php
// **
// USED TO DEFINE API ENDPOINTS
// **

// Get TrendVisionOne Plugin Settings
$app->get('/plugin/TrendVisionOne/settings', function ($request, $response, $args) {
    $TrendVisionOne = new TrendVisionOne();
    if ($TrendVisionOne->auth->checkAccess('ADMIN-CONFIG')) {
        $TrendVisionOne->api->setAPIResponseData($TrendVisionOne->_pluginGetSettings());
    }
    $response->getBody()->write(jsonE($GLOBALS['api']));
    return $response
        ->withHeader('Content-Type', 'application/json;charset=UTF-8')
        ->withStatus($GLOBALS['responseCode']);
});

// Test TrendVisionOne Authentication
$app->get('/plugin/TrendVisionOne/test-url', function ($request, $response, $args) {
    $TrendVisionOne = new TrendVisionOne();
    $TrendVisionOne->getFullApiUrl();
    $response->getBody()->write(jsonE($GLOBALS['api']));
    return $response
        ->withHeader('Content-Type', 'application/json;charset=UTF-8')
        ->withStatus($GLOBALS['responseCode']);
});

// Test TrendVisionOne Authentication
$app->get('/plugin/TrendVisionOne/getfulldesktops', function ($request, $response, $args) {
    $TrendVisionOne = new TrendVisionOne();
    $TrendVisionOne->GetFullDesktops();
    $response->getBody()->write(jsonE($GLOBALS['api']));
    return $response
        ->withHeader('Content-Type', 'application/json;charset=UTF-8')
        ->withStatus($GLOBALS['responseCode']);
});

// Get endpoint details
$app->get('/plugin/TrendVisionOne/getendpointdetails/{id}', function ($request, $response, $args) {
    $TrendVisionOne = new TrendVisionOne();
    $TrendVisionOne->GetEndpointDetails($args['id']);
    $response->getBody()->write(jsonE($GLOBALS['api']));
    return $response
        ->withHeader('Content-Type', 'application/json;charset=UTF-8')
        ->withStatus($GLOBALS['responseCode']);
});

// Get Vulnerable Devices
$app->get('/plugin/TrendVisionOne/getvulnerabledevices', function ($request, $response, $args) {
    $TrendVisionOne = new TrendVisionOne();
    $TrendVisionOne->GetVulnerableDevices();
    $response->getBody()->write(jsonE($GLOBALS['api']));
    return $response
        ->withHeader('Content-Type', 'application/json;charset=UTF-8')
        ->withStatus($GLOBALS['responseCode']);
});

// Verify database structure
$app->get('/plugin/TrendVisionOne/verifydb', function ($request, $response, $args) {
    $plugin = new TrendVisionOne();
    $plugin->verifyDatabaseStructure();
    $response->getBody()->write(jsonE($GLOBALS['api']));
    return $response
        ->withHeader('Content-Type', 'application/json;charset=UTF-8')
        ->withStatus($GLOBALS['responseCode']);
});

// Sync vulnerability data
$app->get('/plugin/TrendVisionOne/syncvulnerabilities', function ($request, $response, $args) {
    $plugin = new TrendVisionOne();
    $plugin->syncVulnerabilityData();
    $response->getBody()->write(jsonE($GLOBALS['api']));
    return $response
        ->withHeader('Content-Type', 'application/json;charset=UTF-8')
        ->withStatus($GLOBALS['responseCode']);
});

// Get vulnerabilities data for table
$app->get('/plugin/TrendVisionOne/vulnerabilities', function ($request, $response, $args) {
    $plugin = new TrendVisionOne();
    if ($plugin->auth->checkAccess($plugin->config->get('Plugins','TrendVisionOne')['ACL-READ'] ?? null)) {
        try {
            // Get devices with their vulnerability counts and highest scores
            $query = "
                SELECT 
                    d.id,
                    d.device_name,
                    d.criticality,
                    GROUP_CONCAT(DISTINCT di.ip_address) as ip_addresses,
                    COUNT(DISTINCT dcm.cve_id) as cve_count,
                    MAX(cr.cvss_score) as highest_cvss,
                    MAX(cr.event_risk_level) as highest_risk
                FROM devices d
                LEFT JOIN device_ips di ON d.id = di.device_id
                LEFT JOIN device_cve_mapping dcm ON d.id = dcm.device_id
                LEFT JOIN cve_records cr ON dcm.cve_id = cr.id
                GROUP BY d.id, d.device_name, d.criticality";
                
            $stmt = $plugin->sql->query($query);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $plugin->api->setAPIResponseData($data);
        } catch (Exception $e) {
            $plugin->api->setAPIResponse('Error', $e->getMessage());
            $GLOBALS['responseCode'] = 500;
        }
    }
    $response->getBody()->write(jsonE($GLOBALS['api']));
    return $response
        ->withHeader('Content-Type', 'application/json;charset=UTF-8')
        ->withStatus($GLOBALS['responseCode']);
});

// Get vulnerabilities for specific device
$app->get('/plugin/TrendVisionOne/device/{id}/vulnerabilities', function ($request, $response, $args) {
    $plugin = new TrendVisionOne();
    if ($plugin->auth->checkAccess($plugin->config->get('Plugins','TrendVisionOne')['ACL-READ'] ?? null)) {
        try {
            $query = "
                SELECT cr.*
                FROM cve_records cr
                JOIN device_cve_mapping dcm ON cr.id = dcm.cve_id
                WHERE dcm.device_id = ?
                ORDER BY cr.cvss_score DESC";
                
            $stmt = $plugin->sql->prepare($query);
            $stmt->execute([$args['id']]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $plugin->api->setAPIResponseData($data);
        } catch (Exception $e) {
            $plugin->api->setAPIResponse('Error', $e->getMessage());
            $GLOBALS['responseCode'] = 500;
        }
    }
    $response->getBody()->write(jsonE($GLOBALS['api']));
    return $response
        ->withHeader('Content-Type', 'application/json;charset=UTF-8')
        ->withStatus($GLOBALS['responseCode']);
});

// Get last sync time
$app->get('/plugin/TrendVisionOne/lastsync', function ($request, $response, $args) {
    $plugin = new TrendVisionOne();
    if ($plugin->auth->checkAccess($plugin->config->get('Plugins','TrendVisionOne')['ACL-READ'] ?? null)) {
        try {
            $stmt = $plugin->sql->query("SELECT value FROM misc WHERE key = 'last_sync'");
            $lastSync = $stmt->fetchColumn();
            
            $plugin->api->setAPIResponseData($lastSync);
        } catch (Exception $e) {
            $plugin->api->setAPIResponse('Error', $e->getMessage());
            $GLOBALS['responseCode'] = 500;
        }
    }
    $response->getBody()->write(jsonE($GLOBALS['api']));
    return $response
        ->withHeader('Content-Type', 'application/json;charset=UTF-8')
        ->withStatus($GLOBALS['responseCode']);
});