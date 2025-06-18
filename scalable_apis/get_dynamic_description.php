<?php

/*
Created By : @udit prajapati@01042025
Created for : Dynamic API for fetching descriptions based on Accept and Hold answers
URL : https://flask.dfos.co/scalable_apis/get_dynamic_description.php?table=form_via_form_main_audit_answers&field_id=377076&linked_field_id=377070&conditions={"token":"your_token","user_id":"your_user_id"}
Method      : GET
Description : This API fetches descriptions from the form_via_form_main_audit_answers table based on Accept and Hold answers, linked to a specific field ID. It allows dynamic retrieval of data based on provided conditions.
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
$code = 200; // Success

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get parameters from request
        $table = isset($_GET['table']) ? $_GET['table'] : 'form_via_form_main_audit_answers';
        $field_id = isset($_GET['field_id']) ? intval($_GET['field_id']) : 377076;
        $linked_field_id = isset($_GET['linked_field_id']) ? intval($_GET['linked_field_id']) : 377070;
        $conditions = isset($_GET['conditions']) ? json_decode($_GET['conditions'], true) : [];


         // Validate required parameters
    if (!isset($_GET['token']) || !isset($_GET['user_id'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing required parameters: token or user_id']);
        die;
    }

        // Get token and user_id from GET request
        $token = $_GET['token'];
        $user_id = $_GET['user_id'];

        // Prepare data for API request
        $apiUrl = "https://devn.dfos.co/dfos-admin/udit/api/v3/checkauth";
        $postData = json_encode(['token' => $token, 'user_id' => $user_id]);

        // Initialize cURL
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        // Execute cURL request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Decode API response
        $authResponse = json_decode($response, true);
        // Check authentication response
        if ($httpCode !== 200 || !isset($authResponse['status']) || $authResponse['status'] !== 'success') {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Authentication failed', 'api_response' => $authResponse]);
            die;
        }


        // Validate required parameters
        if (empty($table) || empty($field_id) || empty($linked_field_id)) {
            die(json_encode(["status" => "error", "message" => "Missing required parameters.", "data" => ['records' => []]]));
        }

        // Query to get linked_mainaudit_id
        $query = "SELECT linked_mainaudit_id FROM $table WHERE (answer = 'Accept' OR answer = 'Hold')
                  AND fvf_main_field_id = $field_id AND linked_mainaudit_id != 0
                  AND deleted_at = '0000-00-00 00:00:00'";

        // Execute query
        $siteIdsQ = $conn->query($query);
        
        while ($row = mysqli_fetch_assoc($siteIdsQ)) {
            $linked_mainaudit_id = $row['linked_mainaudit_id'];

            // Query to fetch the answer based on linked ID
            $getDataQuery = "SELECT fvf_main_audit_id as id, answer FROM $table
                             WHERE fvf_main_audit_id = '$linked_mainaudit_id' 
                             AND fvf_main_field_id = $linked_field_id
                             AND deleted_at = '0000-00-00 00:00:00'";
            
            $getData = $conn->query($getDataQuery);
            while ($row1 = mysqli_fetch_assoc($getData)) {
                $result[] = $row1;
            }
        }

        if (empty($result)) {
            http_response_code(404);
            echo json_encode([
                'status' => 'error',
                'message' => "No data found",
                'data' => ['records' => []]
            ]);
            die;
        }

        http_response_code(200);
        echo json_encode($result);
        die;
    } else {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => "Method not allowed", 'data' => ['records' => []]]);
        die;
    }
} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
    http_response_code(500);
    echo json_encode($response);
    die;
}

?>
