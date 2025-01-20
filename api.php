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
    $TrendVisionOne->GetAllVulnerableDevices();
    $response->getBody()->write(jsonE($GLOBALS['api']));
    return $response
        ->withHeader('Content-Type', 'application/json;charset=UTF-8')
        ->withStatus($GLOBALS['responseCode']);
});

// Verify database structure
$app->get('/plugin/TrendVisionOne/verifydb', function ($request, $response, $args) {
    $trendvisiononeplugin = new TrendVisionOne();
    $trendvisiononeplugin->verifyDatabaseStructure();
    $response->getBody()->write(jsonE($GLOBALS['api']));
    return $response
        ->withHeader('Content-Type', 'application/json;charset=UTF-8')
        ->withStatus($GLOBALS['responseCode']);
});

// Sync vulnerability data
$app->get('/plugin/TrendVisionOne/syncvulnerabilities', function ($request, $response, $args) {
    $trendvisiononeplugin = new TrendVisionOne();
    $trendvisiononeplugin->syncVulnerabilityData();
    $response->getBody()->write(jsonE($GLOBALS['api']));
    return $response
        ->withHeader('Content-Type', 'application/json;charset=UTF-8')
        ->withStatus($GLOBALS['responseCode']);
});

// Get vulnerabilities data for table
$app->get('/plugin/TrendVisionOne/vulnerabilities', function ($request, $response, $args) {
    $trendvisiononeplugin = new trendvisionone();
    if ($trendvisiononeplugin->auth->checkAccess($trendvisiononeplugin->config->get('Plugins','TrendVisionOne')['ACL-READ'] ?? null)) {
        try {
            $data = $trendvisiononeplugin->getVulnerabilities();
            $trendvisiononeplugin->api->setAPIResponseData($data);
        } catch (Exception $e) {
            $trendvisiononeplugin->api->setAPIResponse('Error', $e->getMessage());
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
    $trendvisiononeplugin = new trendvisionone();
    if ($trendvisiononeplugin->auth->checkAccess($trendvisiononeplugin->config->get('Plugins','TrendVisionOne')['ACL-READ'] ?? null)) {
        try {
            error_log("[TrendVisionOne] API: Getting vulnerabilities for device: " . $args['id']);
            $data = $trendvisiononeplugin->getVulnerabilitiesForDevice($args['id']);
            if (!empty($data)) {
                $trendvisiononeplugin->api->setAPIResponseData($data);
            }
        } catch (Exception $e) {
            error_log("[TrendVisionOne] API Error: " . $e->getMessage());
            $trendvisiononeplugin->api->setAPIResponse('Error', $e->getMessage());
            $GLOBALS['responseCode'] = 500;
        }
    }
    $response->getBody()->write(jsonE($GLOBALS['api']));
    return $response
        ->withHeader('Content-Type', 'application/json;charset=UTF-8')
        ->withStatus($GLOBALS['responseCode']);
});

// Get vulnerabilities for specific device from database
$app->get('/plugin/TrendVisionOne/vulnerabilities/{device_id}', function ($request, $response, $args) {
    $trendvisiononeplugin = new trendvisionone();
    if ($trendvisiononeplugin->auth->checkAccess($trendvisiononeplugin->config->get('Plugins','TrendVisionOne')['ACL-READ'] ?? null)) {
        try {
            $data = $trendvisiononeplugin->getVulnerabilitiesForDevice($args['device_id']);
            if (!empty($data)) {
                $trendvisiononeplugin->api->setAPIResponseData($data);
            }
        } catch (Exception $e) {
            $trendvisiononeplugin->api->setAPIResponse('Error', $e->getMessage());
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
    $trendvisiononeplugin = new trendvisionone();
    if ($trendvisiononeplugin->auth->checkAccess($trendvisiononeplugin->config->get('Plugins','TrendVisionOne')['ACL-READ'] ?? null)) {
        try {
            $lastSync = $trendvisiononeplugin->getLastSync();
            $trendvisiononeplugin->api->setAPIResponseData($lastSync);
        } catch (Exception $e) {
            $trendvisiononeplugin->api->setAPIResponse('Error', $e->getMessage());
            $GLOBALS['responseCode'] = 500;
        }
    }
    $response->getBody()->write(jsonE($GLOBALS['api']));
    return $response
        ->withHeader('Content-Type', 'application/json;charset=UTF-8')
        ->withStatus($GLOBALS['responseCode']);
});

// Get vulnerability dashboard data
$app->get('/plugin/TrendVisionOne/dashboard', function ($request, $response, $args) {
    $trendvisiononeplugin = new trendvisionone();
    if ($trendvisiononeplugin->auth->checkAccess($trendvisiononeplugin->config->get('Plugins','TrendVisionOne')['ACL-READ'] ?? null)) {
        try {
            $data = $trendvisiononeplugin->getVulnerabilities();
            if (!empty($data)) {
                $trendvisiononeplugin->api->setAPIResponseData($data);
            }
        } catch (Exception $e) {
            error_log("[TrendVisionOne] API Error: " . $e->getMessage());
            $trendvisiononeplugin->api->setAPIResponse('Error', $e->getMessage());
            $GLOBALS['responseCode'] = 500;
        }
    }
    $response->getBody()->write(jsonE($GLOBALS['api']));
    return $response
        ->withHeader('Content-Type', 'application/json;charset=UTF-8')
        ->withStatus($GLOBALS['responseCode']);
});