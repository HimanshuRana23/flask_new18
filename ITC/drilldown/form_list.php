<?php

/*
Created By : @Gunjan Sharma@13082024
Created for : This API retrieves a list of forms associated with the provided module IDs. It is commonly used to dynamically display or filter forms that belong to specific modules within an application.
URL : https://flask.dfos.co/ITC/drilldown/form_list.php 
Method      : post
*/

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection file
include './connection/connection.php';

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
			$module_id = $_POST['option_id']; // Site Id
			if ($module_id!='') {   
				
				$formName= $con->query("SELECT fvf_main_form_id as id, fvf_main_form_name from form_via_form_main_forms where module_id IN ($module_id) AND deleted_at='0000-00-00 00:00:00'");
				while ($row = mysqli_fetch_assoc($formName)) { 
					$result[] = array(
						"fvf_main_field_option_id" => $row['id'],
						"fvf_main_field_type" => "DrillDown",
						"fvf_main_field_option_name" => $row['fvf_main_form_name'],
						"fvf_main_field_option_value" => 0
					); 
				} 
				$records = $result;
				$response = [
					'status' => 'success',
					'message' => "Drill down Form List",
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