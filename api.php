<?php
// Get CMDB Plugin Settings
$app->get('/plugin/cmdb/settings', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPlugin();
	if ($cmdbPlugin->auth->checkAccess('ADMIN-CONFIG')) {
		$cmdbPlugin->api->setAPIResponseData($cmdbPlugin->_pluginGetSettings());
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// Get CMDB Layout (Used to build the tables & form)
$app->get('/plugin/cmdb/layout', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPlugin();
	if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','CMDB')['ACL-READ'] ?? null)) {
        $cmdbPlugin->api->setAPIResponseData($cmdbPlugin->getColumnsAndSections());
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// ** RECORDS ** //

// Get CMDB Records
$app->get('/plugin/cmdb/records', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPlugin();
	if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','CMDB')['ACL-READ'] ?? null)) {
        $cmdbPlugin->api->setAPIResponseData($cmdbPlugin->getAllRecords());
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// Create New CMDB Record
$app->post('/plugin/cmdb/record', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPlugin();
    if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','CMDB')['ACL-WRITE'] ?? null)) {
        $data = $cmdbPlugin->api->getAPIRequestData($request);
        // Create the CMDB record with the submitted data
        $cmdbPlugin->newRecord($data);
    }
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// Update CMDB Record
$app->patch('/plugin/cmdb/record/{id}', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPlugin();
    if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','CMDB')['ACL-WRITE'] ?? null)) {
        $data = $cmdbPlugin->api->getAPIRequestData($request);
        // Update the CMDB record values with the submitted data
        $cmdbPlugin->updateRecord($args['id'], $data);
    }

	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// Delete CMDB Record
$app->delete('/plugin/cmdb/record/{id}', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPlugin();
    if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','CMDB')['ACL-WRITE'] ?? null)) {
        // Delete the CMDB Record
        $cmdbPlugin->deleteRecord($args['id']);
    }

	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// ** SECTIONS ** //

// Get CMDB Sections
$app->get('/plugin/cmdb/sections', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPlugin();
	if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','CMDB')['ACL-ADMIN'] ?: 'ACL-ADMIN')) {
        $cmdbPlugin->api->setAPIResponseData($cmdbPlugin->getSections());
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// Create New CMDB Section
$app->post('/plugin/cmdb/sections', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPlugin();
    if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','CMDB')['ACL-ADMIN'] ?: 'ACL-ADMIN')) {
        $data = $cmdbPlugin->api->getAPIRequestData($request);
        // Create the CMDB Section with the submitted data
		if (isset($data["name"])) {
			$cmdbPlugin->addSection($data["name"]);
		}
    }
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// Update CMDB Section
$app->patch('/plugin/cmdb/section/{id}', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPlugin();
    if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','CMDB')['ACL-ADMIN'] ?: 'ACL-ADMIN')) {
        $data = $cmdbPlugin->api->getAPIRequestData($request);
        // Create the CMDB Section with the submitted data
		if (isset($data["name"])) {
			$cmdbPlugin->updateSection($args['id'],$data["name"]);
		}
    }
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// Delete CMDB Section
$app->delete('/plugin/cmdb/section/{id}', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPlugin();
    if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','CMDB')['ACL-ADMIN'] ?: 'ACL-ADMIN')) {
		$cmdbPlugin->removeSection($args["id"]);
    }
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// ** COLUMNS ** //

// Get CMDB Columns
$app->get('/plugin/cmdb/columns', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPlugin();
	if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','CMDB')['ACL-ADMIN'] ?: 'ACL-ADMIN')) {
		$data = $request->getQueryParams();
		if (isset($data['section'])) {
			$cmdbPlugin->api->setAPIResponseData($cmdbPlugin->getColumnDefinitionBySectionId($data['section']));
		} else {
			$cmdbPlugin->api->setAPIResponseData($cmdbPlugin->getColumnDefinitions());
		}
        
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// Create New CMDB Column
$app->post('/plugin/cmdb/columns', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPlugin();
    if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','CMDB')['ACL-ADMIN'] ?: 'ACL-ADMIN')) {
        $data = $cmdbPlugin->api->getAPIRequestData($request);
        // Create the CMDB Section with the submitted data
		$name = $data['name'] ?? null;
		$section = $data['section'] ?? null;
		$description = $data['description'] ?? null;
		$dataType = $data['dataType'] ?? null;
		$fieldType = $data['fieldType'] ?? null;
		$visible = $data['visible'] ?? null;
		$columnName = sanitizeInput($name);
		if ($name && $section && $dataType && $fieldType) {
			$cmdbPlugin->addColumnDefinition($columnName,$name,$description,$dataType,$fieldType,$section,$visible);
		} else {
			$cmdbPlugin->api->setAPIResponse('Error','Required information missing from request');
		}
    }
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// Update CMDB Column
$app->patch('/plugin/cmdb/column/{id}', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPlugin();
    if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','CMDB')['ACL-ADMIN'] ?: 'ACL-ADMIN')) {
        $data = $cmdbPlugin->api->getAPIRequestData($request);
        // Create the CMDB Section with the submitted data
		if (isset($data)) {
			$cmdbPlugin->updateColumnDefinition($args['id'],$data);
		}
    }
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// Update CMDB Column Weight
$app->patch('/plugin/cmdb/column/{id}/weight', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPlugin();
	if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','CMDB')['ACL-ADMIN'] ?: 'ACL-ADMIN')) {
		$data = $cmdbPlugin->api->getAPIRequestData($request);
		if (isset($data['weight'])) {
			if ($cmdbPlugin->updateColumnWeight($args['id'],$data['weight'])) {
				$cmdbPlugin->api->setAPIResponseMessage('Successfully updated column position');
			} else {
				$cmdbPlugin->api->setAPIResponse('Error','Failed to update column position');
			}
		} else {
			$cmdbPlugin->api->setAPIResponse('Error','Weight missing from request');
		}        
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// Delete CMDB Section
$app->delete('/plugin/cmdb/column/{id}', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPlugin();
    if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','CMDB')['ACL-ADMIN'] ?: 'ACL-ADMIN')) {
		$cmdbPlugin->removeColumnDefinition($args["id"]);
    }
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// Update CMDB Section Weight
$app->patch('/plugin/cmdb/section/{id}/weight', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPlugin();
	if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','CMDB')['ACL-ADMIN'] ?: 'ACL-ADMIN')) {
		$data = $cmdbPlugin->api->getAPIRequestData($request);
		if (isset($data['weight'])) {
			if ($cmdbPlugin->updateSectionWeight($args['id'],$data['weight'])) {
				$cmdbPlugin->api->setAPIResponseMessage('Successfully updated section position');
			} else {
				$cmdbPlugin->api->setAPIResponse('Error','Failed to update section position');
			}
		} else {
			$cmdbPlugin->api->setAPIResponse('Error','Weight missing from request');
		}        
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// ** DB REBUILD ** //

// Check If DB Rebuild Required
$app->get('/plugin/cmdb/dbRebuild', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPlugin();
	if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','CMDB')['ACL-ADMIN'] ?: 'ACL-ADMIN')) {
		if ($cmdbPlugin->rebuildRequired()) {
			$cmdbPlugin->api->setAPIResponse('Warning','Database rebuild required to remove old columns.');
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// Check If DB Rebuild Required
$app->post('/plugin/cmdb/dbRebuild/initiate', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPlugin();
	if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','CMDB')['ACL-ADMIN'] ?: 'ACL-ADMIN')) {
		$cmdbPlugin->updateCMDBColumns(true);
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// ** ANSIBLE ** //
//  Return list of Ansible Labels
$app->get('/plugin/cmdb/ansible/labels', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPluginAnsible();
	if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','CMDB')['ACL-ADMIN'] ?: 'ACL-ADMIN')) {
		$cmdbPlugin->GetAnsibleLabels();
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

//  Return list of Ansible Job Templates
$app->get('/plugin/cmdb/ansible/templates', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPluginAnsible();
	if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','CMDB')['ACL-JOB'] ?? null)) {
		$data = $request->getQueryParams();
		$Label = $data['label'] ?? null;
		$Id = $data['id'] ?? null;
		$cmdbPlugin->GetAnsibleJobTemplate($Id,$Label);
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

//  Return list of Ansible Jobs
$app->get('/plugin/cmdb/ansible/jobs', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPluginAnsible();
	if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','CMDB')['ACL-JOB'] ?? null)) {
		$cmdbPlugin->GetAnsibleJobs();
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// Submit Ansible Job
$app->post('/plugin/cmdb/ansible/job/{id}', function ($request, $response, $args) {
	$cmdbPlugin = new cmdbPluginAnsible();
    if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','CMDB')['ACL-JOB'] ?? null)) {
		$data = $cmdbPlugin->api->getAPIRequestData($request);
		$DataArray = array(
			"extra_vars" => array()
		);
		foreach ($data as $ReqVar => $ReqKey) {
			$DataArray['extra_vars'][$ReqVar] = $ReqKey;
		}
		$result = $cmdbPlugin->SubmitAnsibleJob($args['id'], $DataArray);
		$DebugArr = array(
			"request" => $data,
			"response" => $result
		);
		if (isset($result->job)) {
			$cmdbPlugin->logging->writeLog("Ansible","Submitted Ansible Job.","info",$DebugArr);
			$cmdbPlugin->api->setAPIResponseData($result);
		} else {
			$cmdbPlugin->api->setAPIResponse('Error','Error submitting ansible job. Check logs.');
			$cmdbPlugin->logging->writeLog("Ansible","Error submitting ansible Job.","error",$DebugArr);
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});