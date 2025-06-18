<?php

/*
Created By : @Gunjan Sharma@13082024
Created for : This API fetches Username from users table based on user id, It will combine firstname and lastname to make username
URL : https://flask.dfos.co/ITC/drilldown/user_list.php 
Type : Drilldown
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

// Initialize variables
$result = [];
$code = 200; // success

try {
    // Check if the request method is POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user_id = $_POST['option_id']; // User Id

        // Validate user_id
        if (!empty($user_id)) {   
            
			$userName = $con->query("SELECT user_id as id, firstname, lastname from users  WHERE user_id = $user_id ");
				

            // Check if the user was found
            if ($row = mysqli_fetch_assoc($userName)) {
                // Concatenate firstname and lastname correctly
                $username = $row['firstname'] . ' ' . $row['lastname'];

                // Prepare the response data
                $result[] = array(
                    "fvf_main_field_option_id" => $row['id'],
                    "fvf_main_field_type" => "DrillDown",
                    "fvf_main_field_option_name" => $username,
                    "fvf_main_field_option_value" => 0
                );

                // Construct the response
                $response = [
                    'status' => 'success',
                    'message' => "Drill down Username",
                    'data' => ['records' => $result]
                ];

                http_response_code(200); // success
            } else {
                // User not found
                $response = [
                    'status' => 'error',
                    'message' => "User not found",
                    'data' => ['records' => []]
                ];

                http_response_code(404); // not found
            }
           // $stmt->close();
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