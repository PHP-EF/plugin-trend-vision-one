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