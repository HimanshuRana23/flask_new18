<?php

/*
Created By : @Maulik@27052025
Created for : This API is use to get schedule json data based on schedule_id passed from get api
URL : https://flask.dfos.co/scalable_apis/getScheduleJSONDataDynamic.php?server=danone&key=Section&table=form_via_form_schedules&columns={fvf_schedule_id,schedule_json_data}&whereColumn=fvf_schedule_id
Method : POST
Description : This API fetches the schedule JSON data based on the provided schedule ID and key.
*/

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection file
include './connection/connection.php';

include '../authorization/authorization.php';

// Set response headers
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

$result = [];
$code = 200; //success
// Check if the request method is POST
	try { 
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {  
			$requestUri = $_SERVER['REQUEST_URI']; // full path after domain
			$segments = explode('/', $requestUri);
			$lastSegment = end($segments); // 'data=model_code'
			parse_str($lastSegment, $params);
			$dataValue = isset($params['key']) ? $params['key'] : null;			

			$fvf_schedule_id = isset($_POST['fvf_schedule_id']) ? $_POST['fvf_schedule_id'] : 0; 
			$table = $_GET['table'] ?? ''; 
			$columns = $_GET['columns'] ?? ''; 
			$whereColumn = $_GET['whereColumn'] ?? ''; 
			
        	// file_put_contents('log.txt', print_r($_POST,true), FILE_APPEND);exit;
			
			if ($fvf_schedule_id !='') {
                // Remove curly braces and sanitize input
                $columns = str_replace(['{', '}'], '', $columns);

                // Optional: Further security (allow only alphanumeric, underscore, comma)
                $columns = preg_replace('/[^a-zA-Z0-9_,]/', '', $columns);
				$query = "SELECT $columns FROM $table WHERE $whereColumn = '$fvf_schedule_id'";
				$resultData = mysqli_query($conn, $query);

				if ($resultData && mysqli_num_rows($resultData) > 0) {
					$row = mysqli_fetch_assoc($resultData); 
    				$jsonData = $row['schedule_json_data'];
					// echo '<pre>';print_r($jsonData);exit;
					// Decode the JSON
					$decodedData = json_decode($jsonData, true);
					// $data = null;

					
                    // Get the Data
					// Check if key exists in the JSON
                    if (isset($decodedData[$dataValue])) {
                        $data = $decodedData[$dataValue];

                        $result[] = array(
                            "fvf_main_field_option_id" => $row['fvf_schedule_id'],
                            "fvf_main_field_type" => "DrillDown",
                            "fvf_main_field_option_name" => $data,
                            "fvf_main_field_option_value" => 0
                        );
                    } else {
                        // If the key doesn't exist, show error
                        http_response_code(400); // Bad Request
                        echo json_encode([
                            'status' => 'error',
                            'message' => "Key '$dataValue' not found in schedule JSON data."
                        ]);
                        exit;
                    }
				} else {
                    $response = [
                        'status' => 'error',
                        'message' => "No schedule found",
                    ];
                    
                    http_response_code(400);
                    echo json_encode($response);die;
                }

				$response = [
					'status' => 'success',
					'message' => "Drill down data",
					'data' => ['records' => $result]
				];
				 
				http_response_code(200);
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