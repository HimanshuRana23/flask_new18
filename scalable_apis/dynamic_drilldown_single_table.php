<?php

/*
Created By : @maulik - 20032025
Created for : This is dynamic API to fetch data from single table based on option id passed from get api
URL : Eg https://flask.dfos.co/scalable_apis/dynamic_drilldown_single_table.php?server=heromotocop&table=form_via_form_main_audit_answers&columns={fvf_main_field_option_id as id,answer}&conditions={"fvf_main_field_type":"User","fvf_main_field_id":"!=4641410"}&deleted_flag=1&match_column=fvf_main_audit_id
Description : This API dynamically fetches drill-down data from a single table based on the provided option_id, match_column, and other filters passed via GET. It's designed for flexible dropdown population using table, column, condition, and grouping parameters.
Method      : POST

*/

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection file
include './connection/connection.php';

// Include the authorization file
// include '../authorization/authorization.php';

// Set response headers
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => "Method not allowed."]);
    die;
}

try {
    // Validate required parameters
    if (!isset($_GET['table']) || !isset($_GET['columns'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing required parameters: table or columns']);
        die;
    }
    
    $option_id = $_POST['option_id'] ?? '';
    $table = $_GET['table'];
    $match_column = $_GET['match_column'];
    // $columns = $_GET['columns']; // Expected as comma-separated values
    $conditions = isset($_GET['conditions']) ? json_decode($_GET['conditions'], true) : [];
    $deleted_flag = isset($_GET['deleted_flag']) ? intval($_GET['deleted_flag']) : 0; // 1 = Include deleted_at condition
    $group_by = isset($_GET['group_by']) ? trim($_GET['group_by']) : '';

    $columns = trim($_GET['columns']);
    $columns = preg_replace('/[{}]/', '', $columns); // Removes { and }
    $columns_array = array_map('trim', explode(',', $columns));
    $columns_for_sql = implode(',', $columns_array);

    // Start building the query
    $query = "SELECT $columns_for_sql FROM `$table`";
    $params = [];
    $types = '';

    
    if (!empty($conditions)) {
        $query .= " WHERE ";
        $conditionStrings = [];
        
        foreach ($conditions as $column => $value) {
            if (preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
                if (is_string($value) && strpos($value, '!=') === 0) { // to metch '!=' condition
                    $actualValue = trim(substr($value, 2));
                    $conditionStrings[] = "$column != ?";
                    $params[] = $actualValue;
                    $types .= 's';
                } else {
                    $conditionStrings[] = "$column = ?";
                    $params[] = $value;
                    $types .= 's'; // Assuming all values are strings, modify if needed
                }
            }
        }

        // Add all conditions
        $query .= implode(' AND ', $conditionStrings);
    }

    // Add deleted_at condition if flag is set
    if ($deleted_flag === 1) {
        $query .= !empty($conditions) ? " AND deleted_at = '0000-00-00 00:00:00'" : " WHERE deleted_at = '0000-00-00 00:00:00'";
    }
    if ($option_id) {
        $query .= ($deleted_flag) ? " AND $match_column = '" . $option_id . "'" : " WHERE $match_column = '" . $option_id . "'";
    }
    if (!empty($group_by)) {
        $query .= " GROUP BY `$group_by`";
    }

    // echo '<pre>';print_r($query);exit;
    // Prepare and execute the query
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        // Default id
        $option_id_value = $row['id'] ?? '';
        // echo '<pre>';print_r($option_id_value);
        // Dynamically build the option name
        $columns_array = array_map('trim', explode(',', $columns));
        $option_name_parts = [];
        foreach ($columns_array as $col) {
            // echo '<pre>';print_r($row[$col]);exit;
            if (isset($row[$col])) {
                $option_name_parts[] = $row[$col];
            }
        }
      
        $data[] = [
            "fvf_main_field_option_id" => !empty($option_id_value) ? $option_id_value : 0,
            "fvf_main_field_type" => "DrillDown",
            "fvf_main_field_option_name" => isset($option_name_parts) ? implode(' ', $option_name_parts) : '',
            "fvf_main_field_option_value" => 0
        ];
    }
    // Prepare response
    if(!empty($data)){
        $response = [
            'status' => 'success',
            'message' => "Drill down List",
            'data' => ['records' => $data]
        ];
    } else {
        $response = [
            'status' => 'error',
            'message' => "No data found",
            'data' => ['records' => []]
        ];
    }
    

    http_response_code(200);
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

