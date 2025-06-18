<?php

/*
Created By : @Gunjan Sharma@30042025
Created for : Get Main form name on behalf od module id
Method      : POST
URL         : https://flask.dfos.co/scalable_apis/companyWiseData/mainForm_list.php
Description : This API fetches the list of Main Forms associated with a given Module ID
              from the `form_via_form_main_forms` table. Only non-deleted, non-linked forms are returned.
*/

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection file
include '../connection/dfosConnection.php';

// Include the authorization file
include '../../authorization/authorization.php';

// Set response headers
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

// Initialize variables
$result = [];
$code = 200; // success

try {
    // Check if the request method is POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $module_id = $_POST['option_id']; // User Id
        $selected_values_json = $_POST['selected_values'] ?? '';

        if (!empty($selected_values_json)) {
            $selected_values = json_decode($selected_values_json, true); // Decode JSON to PHP array
            $server = $selected_values['0'][0]['option_value']; // server Id
        }
        

        if (!empty($module_id)) {   
            
			$mainFormName = $con->query("SELECT fvf_main_form_id as id, fvf_main_form_name FROM form_via_form_main_forms WHERE module_id=$module_id 
                                        AND (linked_main_form = 0 OR linked_main_form IS NULL) 
                                        AND deleted_at='0000-00-00 00:00:00'");
			while ($row = mysqli_fetch_assoc($mainFormName)) { 
                
                $result[] = array(
                    "fvf_main_field_option_id" => $row['id'],
                    "fvf_main_field_type" => "DrillDown",
                    "fvf_main_field_option_name" => $row['fvf_main_form_name'],
                    "fvf_main_field_option_value" => $server
                ); 
            } 	

           
                // Construct the response
                $response = [
                    'status' => 'success',
                    'message' => "Drill down Main Form Name",
                    'data' => ['records' => $result]
                ];

                http_response_code(200); // success
            
        } else {
            // Invalid user_id
            $response = [
                'status' => 'error',
                'message' => "Invalid Parameter",
                'data' => ['records' => []]
            ];

            http_response_code(400); // bad request
        }
    } else {
        // Invalid request method
        $response = [
            'status' => 'error',
            'message' => "Method not allowed",
            'data' => ['records' => $result]
        ];

        http_response_code(405); // method not allowed
    }

    // Output the response as JSON
    echo json_encode($response);
} catch (Exception $e) {
    // Handle exceptions
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];

    http_response_code(500); // internal server error
    echo json_encode($response);
    die;
}
?>