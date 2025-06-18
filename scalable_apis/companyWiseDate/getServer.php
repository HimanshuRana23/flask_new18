<?php

/*
Created By : @Gunjan Sharma@17082024
Created for : Get Plant List for PQCS Site
*/

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection file
include '../connection/dfosConnection.php';

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
			$site_id = 6;
			//$site_id = $_GET['site_id'];
			if ($site_id!='') {   
				$plantIdsQ = $con->query("SELECT id , company_name from database_details where (database_name is not null or database_name!='') ");
				while ($row = mysqli_fetch_assoc($plantIdsQ)) { 
					 $result[] = $row;
				} 
				 
				http_response_code(200); //success
				echo json_encode($result);die;
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