<?php

/*
Created By : @Maulik@14052025
Created for : This api is used to get Resource List Dynamically based on department id which is passed from get api
URL : https://flask.dfos.co/scalable_apis/resource_listDynamic.php?server=hero&table=locations&columns=location_id as id,location_name,user_id&match_column=department_id&deleted_flag=1
Method      : POST
Description : This API dynamically fetches resource list based on the provided option_id, match_column, and other filters passed via POST. It's designed for flexible dropdown population using table, column, condition, and grouping parameters.
*/

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection file
include './connection/connection.php';

// Include the authorization file
include '../authorization/authorization.php';

// Set response headers
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

$result = [];
$code = 200; //success
// Check if the request method is POST
	try { 
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {  
			$resource_id = $_POST['option_id']; // Resource Id
            $table = $_GET['table'] ?? '';
            $columns = $_GET['columns'] ?? '';
            $match_column = $_GET['match_column'] ?? '';
            $deleted_flag = $_GET['deleted_flag'] ?? '';
			// $answer = $_POST['answer']; 
			// $resource = $con->query("SELECT department_id from departments where department_name='".$answer."'");
			// $resource = mysqli_fetch_assoc($resource);
			// $resource_id = $resource['department_id'];
			if ($resource_id!='') {   
				
                $query = "SELECT $columns FROM $table";

                if($match_column){
                    $query .= " WHERE $match_column IN ($resource_id)";
                }
                if($deleted_flag){
                    $query .= " AND deleted_at='0000-00-00 00:00:00'";
                }
                // echo '<pre>';print_r($query);exit;
				$resourceName= $conn->query($query);
				while ($row = mysqli_fetch_assoc($resourceName)) { 
					$result[] = array(
						"fvf_main_field_option_id" => $row['id'],
						"fvf_main_field_type" => "DrillDown",
						"fvf_main_field_option_name" => $row['location_name'],
						"fvf_main_field_option_value" => 0
					); 
				} 
				$records = $result;
				$response = [
					'status' => 'success',
					'message' => "Drill down Resource List",
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