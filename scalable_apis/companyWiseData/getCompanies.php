<?php

/*
Created By : @Gunjan Sharma@30042025
Created For : Get All Companies
Method      : POST
URL         : https://flask.dfos.co/scalable_apis/companyWiseData/getCompanies.php
Description : This API retrieves a list of companies from the `companies` table that have not 
              been soft-deleted (`deleted_at = '0000-00-00 00:00:00'`). It returns the list in 
              a format compatible with drill-down dropdowns. The `option_id` (server ID) received 
              in the request is passed back as part of each record.
*/

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set response headers
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

// Include the database connection file
include '../connection/dfosConnection1.php';

// Include the authorization file
//include '../../authorization/authorization.php';



// Initialize variables
$result = [];
$code = 200; // success

try {
    // Check if the request method is POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $server = $_POST['option_id']; // server Id
        
        if (!empty($server)) {   
            
			$companiesName = $con->query("SELECT company_id as id,company_name FROM companies WHERE
                                        deleted_at='0000-00-00 00:00:00'");
			while ($row = mysqli_fetch_assoc($companiesName)) { 
                
                $result[] = array(
                    "fvf_main_field_option_id" => $row['id'],
                    "fvf_main_field_type" => "DrillDown",
                    "fvf_main_field_option_name" => $row['company_name'],
                    "fvf_main_field_option_value" => $server
                ); 
            } 	

           
                // Construct the response
                $response = [
                    'status' => 'success',
                    'message' => "Drill down All Companies Names",
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