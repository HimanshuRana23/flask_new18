<?php
/*
Created By : @udit perajapati - 23042025
Updated for : Dynamic table field updater with query print for debugging
Created for : API to update a specific field in a table based on conditions
URL : https://flask.dfos.co/scalable_apis/update_value.php?table=your_table&field=your_field&value=your_value&condField=condition_field&condValue=condition_value
Method      : GET
Description : This API updates a specific field in a specified table based on the provided condition. It allows dynamic updates to any table and field, with error handling and debugging output.
*/

error_reporting(E_ALL);
ini_set('display_errors', 1);

include './connection/connection.php';

include '../authorization/authorization.php';


header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => "Method not allowed."]);
    exit;
}

try {
    $table     = $_GET['table']     ?? null;
    $field     = $_GET['field']     ?? null;
    $value     = $_GET['value']     ?? null;
    $condField = $_GET['condField'] ?? null;
    $condValue = $_GET['condValue'] ?? null;

    // Validate required inputs
    if (!$table || !$field || !$value || !$condField || !$condValue) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing required parameters.']);
        exit;
    }

    // Sanitize table/field names
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table) || !preg_match('/^[a-zA-Z0-9_]+$/', $field) || !preg_match('/^[a-zA-Z0-9_]+$/', $condField)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid table or field name.']);
        exit;
    }

    // Prepare and execute query
    $query = "UPDATE `$table` SET `$field` = ? WHERE `$condField` = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("SQL prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ss", $value, $condValue);
    $stmt->execute();

    // Debug SQL Query Print
    $debugQuery = "UPDATE `$table` SET `$field` = $value WHERE `$condField` = '$condValue'";
    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'status' => 'success',
            'message' => "$field updated successfully.",
            'query' => $debugQuery
        ]);
    } else {
        echo json_encode([
            'status' => 'warning',
            'message' => 'No rows updated or value already set.',
            'query' => $debugQuery
        ]);
    }

    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
