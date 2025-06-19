<?php

/*
Created By : @udit prajapati@01042025
Created for : Dynamic API for fetching descriptions based on Accept and Hold answers

This script returns the textual description linked to a particular field in the
`form_via_form_main_audit_answers` table.  A client supplies the table name,
field identifier and the linked field identifier.  The API validates the
requester's token by calling an authentication endpoint before querying the
database.  Only rows with answers of either "Accept" or "Hold" are considered.
The response contains the description from the linked record or a 404 error if
none are found.

Example usage:
https://flask.dfos.co/scalable_apis/get_dynamic_description.php?table=form_via_form_main_audit_answers&field_id=377076&linked_field_id=377070&token=TOKEN&user_id=ID
*/

// -----------------------------------------------------------------------------
// Enable verbose error output during development.  These settings should be
// adjusted in production environments.
// -----------------------------------------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', 1);

// -----------------------------------------------------------------------------
// Bootstrap application dependencies: database connection and optional
// authorization middleware.
// -----------------------------------------------------------------------------
// Include the database connection file
include './connection/connection.php';

// Include the authorization file
include '../authorization/authorization.php';

// -----------------------------------------------------------------------------
// Response headers - output is JSON and CORS is wide open for demonstration
// purposes.
// -----------------------------------------------------------------------------
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

$result = [];
$code = 200; // Success

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // -----------------------------------------------------------------
        // Retrieve parameters. Defaults are provided for table and IDs so the
        // endpoint can be invoked without specifying them explicitly.
        // -----------------------------------------------------------------
        $table          = isset($_GET['table']) ? $_GET['table'] : 'form_via_form_main_audit_answers';
        $field_id       = isset($_GET['field_id']) ? intval($_GET['field_id']) : 377076;
        $linked_field_id = isset($_GET['linked_field_id']) ? intval($_GET['linked_field_id']) : 377070;
        // Optional future extension for additional filters
        $conditions     = isset($_GET['conditions']) ? json_decode($_GET['conditions'], true) : [];

        // -----------------------------------------------------------------
        // Authenticate the caller by verifying token and user_id with an
        // external service. Both values are required.
        // -----------------------------------------------------------------
        if (!isset($_GET['token']) || !isset($_GET['user_id'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing required parameters: token or user_id']);
            die;
        }

        $token   = $_GET['token'];
        $user_id = $_GET['user_id'];

        $apiUrl   = "https://devn.dfos.co/dfos-admin/udit/api/v3/checkauth";
        $postData = json_encode(['token' => $token, 'user_id' => $user_id]);

        // -------------------------------------------------------------
        // Verify the credentials by posting them to the auth endpoint.
        // -------------------------------------------------------------
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Decode API response
        $authResponse = json_decode($response, true);
        // Check authentication response
        if ($httpCode !== 200 || !isset($authResponse['status']) || $authResponse['status'] !== 'success') {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Authentication failed', 'api_response' => $authResponse]);
            die;
        }


        // Ensure essential parameters are present before querying
        if (empty($table) || empty($field_id) || empty($linked_field_id)) {
            die(json_encode([
                'status'  => 'error',
                'message' => 'Missing required parameters.',
                'data'    => ['records' => []]
            ]));
        }

        // -------------------------------------------------------------
        // First fetch all linked audit IDs that have an answer of Accept/Hold
        // for the specified field.
        // -------------------------------------------------------------
        $query = "SELECT linked_mainaudit_id FROM $table WHERE (answer = 'Accept' OR answer = 'Hold')
                  AND fvf_main_field_id = $field_id AND linked_mainaudit_id != 0
                  AND deleted_at = '0000-00-00 00:00:00'";

        $siteIdsQ = $conn->query($query);
        
        while ($row = mysqli_fetch_assoc($siteIdsQ)) {
            $linked_mainaudit_id = $row['linked_mainaudit_id'];

            // ---------------------------------------------------------
            // For each linked audit ID fetch the corresponding description
            // from the same table using the linked field id.
            // ---------------------------------------------------------
            $getDataQuery = "SELECT fvf_main_audit_id as id, answer FROM $table
                             WHERE fvf_main_audit_id = '$linked_mainaudit_id'
                             AND fvf_main_field_id = $linked_field_id
                             AND deleted_at = '0000-00-00 00:00:00'";

            $getData = $conn->query($getDataQuery);
            while ($row1 = mysqli_fetch_assoc($getData)) {
                $result[] = $row1;
            }
        }

        // If no related descriptions were found return a 404 response
        if (empty($result)) {
            http_response_code(404);
            echo json_encode([
                'status'  => 'error',
                'message' => 'No data found',
                'data'    => ['records' => []]
            ]);
            die;
        }

        http_response_code(200);
        echo json_encode($result);
        die;
    } else {
        // Request method other than GET
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed', 'data' => ['records' => []]]);
        die;
    }
} catch (Exception $e) {
    // Unexpected error: return message for debugging
    $response = [
        'status'  => 'error',
        'message' => $e->getMessage()
    ];
    http_response_code(500);
    echo json_encode($response);
    die;
}

?>
