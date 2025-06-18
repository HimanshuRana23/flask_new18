<?php

/*
Created By : @Gunjan@13082024 
Created for : This api is used to get user Id list
URL : https://flask.dfos.co/ITC/drilldown/employeeId-list.php 
Method      : GET
Description : This API fetches the user IDs from the users table where the deleted_at field is set to '0000-00-00 00:00:00'. It returns a JSON response containing the user IDs.

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
// Check if the request method is GET
	try { 
		if ($_SERVER['REQUEST_METHOD'] === 'GET') {  
			
				$userId = $con->query("SELECT user_id as id, user_id from users where deleted_at='0000-00-00 00:00:00'");
				while ($row = mysqli_fetch_assoc($userId)) { 
					 $result[] = $row;
				} 
				 
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