<?php

/*
Created By : @Gunjan Sharma@13022024
Created for : This API retrieves a list of Order IDs associated with a given Sale Order. It is used to fetch relevant order details based on a selected or provided Sale Order for further processing or display.
URL : https://flask.dfos.co/ITC/drilldown/order_list.php 
Type : Get
*/

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection file
include '../connection/connection.php';

// Include the authorization file
// include '../../authorization/authorization.php';

// Set response headers
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

$result = [];
$code = 200; //success
// Check if the request method is GET
	try { 
		if ($_SERVER['REQUEST_METHOD'] === 'GET') {  
			  
				//$orderId = $con->query("SELECT distinct answer as id FROM form_via_form_main_audit_answers where fvf_main_field_name='Sale Order' AND fvf_main_form_id=9428 AND deleted_at='0000-00-00 00:00:00'");
				$orderId = $con->query("SELECT distinct answer as id FROM form_via_form_main_audit_answers where fvf_main_field_id=2696 AND fvf_main_form_id=18 AND deleted_at='0000-00-00 00:00:00'");
				//$orderId = $con->query("SELECT distinct answer as id FROM form_via_form_main_audit_answers where fvf_main_field_id=2694 AND fvf_main_form_id=18 AND deleted_at='0000-00-00 00:00:00'");
				while ($row = mysqli_fetch_assoc($orderId)) {  

					if(!empty($row['id'])){ 
						 $result[] = $row;
					}
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