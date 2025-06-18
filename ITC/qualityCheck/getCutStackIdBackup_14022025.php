<?php

/*
Created By : @Gunjan Sharma@17012025
Created for : Get audit id as per 9846 form id
*/

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection file
include '../connection/connection.php';

// Set response headers
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

$result = [];
$code = 200; //success
// Check if the request method is GET
	try { 
		if ($_SERVER['REQUEST_METHOD'] === 'GET') {  
			  
				$auditId = $con->query("SELECT distinct fvf_main_audit_id as id FROM form_via_form_main_audits
										 WHERE fvf_main_form_id=9846 AND deleted_at='0000-00-00 00:00:00'");
				// $auditId = $con->query("SELECT answer as id FROM form_via_form_main_audit_answers
				// 						 WHERE fvf_main_form_id=9846 AND fvf_main_field_id=164356 AND deleted_at='0000-00-00 00:00:00'");
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