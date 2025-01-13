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

// // Get Veeam Backup Jobs
// $app->get('/plugin/TrendVisionOne/jobs', function ($request, $response, $args) {
//     $TrendVisionOne = new TrendVisionOne();
//     $TrendVisionOne->getBackupJobs();
//     $response->getBody()->write(jsonE($GLOBALS['api']));
//     return $response
//         ->withHeader('Content-Type', 'application/json;charset=UTF-8')
//         ->withStatus($GLOBALS['responseCode']);
// });

// // Get Veeam Backup Jobs Status
// $app->get('/plugin/TrendVisionOne/jobsstatus', function ($request, $response, $args) {
//     $TrendVisionOne = new TrendVisionOne();
//     $TrendVisionOne->getJobStatus();
//     $response->getBody()->write(jsonE($GLOBALS['api']));
//     return $response
//         ->withHeader('Content-Type', 'application/json;charset=UTF-8')
//         ->withStatus(200);
// });

// // Get Veeam Backup Jobs Sessions
// $app->get('/plugin/TrendVisionOne/jobssessions', function ($request, $response, $args) {
//     $TrendVisionOne = new TrendVisionOne();
//     $TrendVisionOne->GetSessionsJobs();
    
//     $responseData = [
//         'result' => $GLOBALS['api']['result'],
//         'message' => $GLOBALS['api']['message'],
//         'data' => $GLOBALS['api']['data']
//     ];
    
//     $response->getBody()->write(json_encode($responseData));
//     return $response
//         ->withHeader('Content-Type', 'application/json')
//         ->withStatus(200);
// });