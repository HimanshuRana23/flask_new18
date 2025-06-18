<?php

/*
Created By : @Gunjan Sharma@30042025
Created For : Get All Form Fields by Form ID
Method      : POST
URL         : https://flask.dfos.co/scalable_apis/companyWiseData/getField.php
Description : This API retrieves all form fields associated with a given `form_id`.
              It processes optional parameters:
              - `selected_values`: JSON used to extract server ID.
              - `current_section_key`: Helps fetch the correct index from the selected values array.
              It returns a JSON list of field IDs and names for drill-downs.
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
        $form_id = $_POST['option_id']; // form Id
        $selected_values_json = $_POST['selected_values'] ?? '';
        $current_section_key = $_POST['current_section_key'] ?? ''; // @maulik@15/05/2025

        if (!empty($selected_values_json)) {
            $selected_values = json_decode($selected_values_json, true); // Decode JSON to PHP array
            // $server = $selected_values['0'][0]['option_value']; // server Id
            $server = $selected_values[$current_section_key][0]['option_value']; // server Id
        }

        if (!empty($form_id)) {   
            
			$fieldName = $con->query("SELECT fvf_main_field_id as id,fvf_main_field_name FROM form_via_form_main_fields WHERE
                                        fvf_main_form_id = $form_id  
                                        AND deleted_at='0000-00-00 00:00:00'");
			while ($row = mysqli_fetch_assoc($fieldName)) { 
                
                $result[] = array(
                    "fvf_main_field_option_id" => $row['id'],
                    "fvf_main_field_type" => "DrillDown",
                    "fvf_main_field_option_name" => $row['fvf_main_field_name'],
                    "fvf_main_field_option_value" => $server
                ); 
            } 	

           
                // Construct the response
                $response = [
                    'status' => 'success',
                    'message' => "Drill down All Fields",
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