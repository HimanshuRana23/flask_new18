<?php

/*
Created By : @Gunjan Sharma@15022025
Created for : This API is used to get audit id as per Quality check form submission on behalf of selected packaging style
URL : https://flask.dfos.co/ITC/qualityCheck/getCutStackId.php
*/

/*
Created By : @Gunjan Sharma@15022025

This endpoint returns the audit IDs recorded when quality checks were performed
for a given packaging style. It expects a POST parameter `option_id` containing
the desired packaging style and performs the following steps:

1. Query form `33` for audits where the answer matches the packaging style.
2. Collect the `fvf_main_audit_id` for each matching entry.

The response is a JSON object with `status`, a `message`, and the array of
audit IDs under `data.records`.
URL : https://flask.dfos.co/ITC/qualityCheck/getCutStackId.php
Method      : POST

*/

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection file
include '../connection/connection.php';

// Include the authorization file
include '../../authorization/authorization.php';

// Set response headers
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

$result = [];
$code = 200; //success
// Check if the request method is POST
	try { 
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {  
			$PackagingStyle = $_POST['option_id']; // Packaging Style
			if ($PackagingStyle!='') {   
				
				$auditId= $con->query("SELECT distinct fvf_main_audit_id as id FROM form_via_form_main_audit_answers
										 WHERE fvf_main_form_id=33 and answer='".$PackagingStyle."' AND deleted_at='0000-00-00 00:00:00'");
				while ($row = mysqli_fetch_assoc($auditId)) { 
					$result[] = array(
						"fvf_main_field_option_id" => $row['id'],
						"fvf_main_field_type" => "DrillDown",
						"fvf_main_field_option_name" => $row['id'],
						"fvf_main_field_option_value" => 0
					); 
				} 
				$records = $result;
				$response = [
					'status' => 'success',
					'message' => "Drill down Cut Stack Id List",
					'data' => ['records' => $records]
				];
				 
				http_response_code(200); //success
				echo json_encode($response);die;
			}else{
				$response = [
					'status' => 'error',
					'message' => "Invalid Parameter",
					'data' => ['records' => []]
				]; 
				http_response_code(400); //Method not allowed
				echo json_encode($response);die;
			}
		}else{
			$response = [
				'status' => 'error',
				'message' => "Method not allowed",
				'data' => ['records' => $result]
			]; 
			http_response_code(400); //Method not allowed
			echo json_encode($response);die;
		} 
	}catch (Exception $e){ 
        $response = [
			'status' => 'error',
			'message' => $e->getMessage()
		]; 
        http_response_code(400); //error
        echo json_encode($response);die;
    }

?>
