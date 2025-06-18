<?php

/*
Created By : @Gunjan Sharma@14002025
Created for : This API retrieves the Packaging Style based on data provided in the Quality Card > Production Input form. It is used to dynamically fetch the appropriate packaging configuration associated with a specific production input, ensuring consistency between quality standards and production requirements.
URL : https://flask.dfos.co/ITC/qualityCheck/getPackagingStyle.php
Type : Get
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
// Check if the request method is GET
	try { 
		if ($_SERVER['REQUEST_METHOD'] === 'GET') {  
			  
				$auditId = $con->query("SELECT distinct answer as id FROM form_via_form_main_audit_answers
										 WHERE fvf_main_form_id=33 AND fvf_main_field_id=3197 AND deleted_at='0000-00-00 00:00:00'");
				while ($row = mysqli_fetch_assoc($auditId)) {  
					 $result[] = $row;
				} 
				//print_r($row);
				http_response_code(200); //success
				echo json_encode($result);die;
			
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