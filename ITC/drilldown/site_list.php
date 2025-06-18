<?php

/*
Created By : @Gunjan Sharma@13082024
Created for : This API retrieves a list of site IDs and corresponding site names from the sites table, filtered by the provided company ID. 
URL : https://flask.dfos.co/ITC/drilldown/site_list.php 
Type : Get
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
			if ($company_id!='') {   
				$siteIdsQ = $con->query("SELECT site_id as id, site_name  from sites where company_id=$company_id AND deleted_at='0000-00-00 00:00:00'");
				while ($row = mysqli_fetch_assoc($siteIdsQ)) {  
					 $result[] = $row;
				} 
				//print_r($row);
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