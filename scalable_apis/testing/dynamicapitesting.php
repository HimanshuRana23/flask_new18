<?php

/*
Created By : @Udit Prajapati - 06032025
Created for : Dynamic API
*/

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection file
include '../connection/connection.php';

// Include the authorization file
include '../../authorization/authorization.php';

// Set response headers
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

// Check if the request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => "Method not allowed."]);
    die;
}

try {
    file_put_contents('log.txt', print_r($_GET,true), FILE_APPEND);

    // Validate required parameters
    if (!isset($_GET['table']) || !isset($_GET['columns'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing required parameters: table or columns']);
        die;
    }
    file_put_contents('log.txt', print_r($_GET,true), FILE_APPEND);

    $table = $_GET['table'];
    $columns = $_GET['columns']; // Expected as comma-separated values
    $conditions = isset($_GET['conditions']) ? json_decode($_GET['conditions'], true) : [];
    $deleted_flag = isset($_GET['deleted_flag']) ? intval($_GET['deleted_flag']) : 0; // 1 = Include deleted_at condition

    // Validate table and columns (allow only alphanumeric and underscores to prevent SQL injection)
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table) || !preg_match('/^[a-zA-Z0-9_, ]+$/', $columns)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid table name or columns']);
        die;
    }

    // Split columns into array @maulik@17032025
    $columnsArray = explode(',', $columns);

    // Trim spaces from each column name
    $columnsArray = array_map('trim', $columnsArray);

    // Alias the first column
    if (isset($columnsArray[0]) && strtolower($columnsArray[0]) == 'answer') {
        $columnsArray[0] = 'answer AS id';
    }

    // Rebuild the columns string
    $columns = implode(', ', $columnsArray);
    // end@maulik
    
    // Start building the query
    $query = "SELECT distinct $columns FROM `$table`";
    $params = [];
    $types = '';

    if (!empty($conditions)) {
        $query .= " WHERE ";
        $conditionStrings = [];
        
        foreach ($conditions as $column => $value) {
            if (preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
                $conditionStrings[] = "$column = ?";
                $params[] = $value;
                $types .= 's'; // Assuming all values are strings, modify if needed
            }
        }

        // Add all conditions
        $query .= implode(' AND ', $conditionStrings);
    }

    // Add deleted_at condition if flag is set
    if ($deleted_flag === 1) {
        $query .= !empty($conditions) ? " AND deleted_at ='0000-00-00 00:00:00'" : " WHERE deleted_at = '0000-00-00 00:00:00'";
    }

 

    // Function to replace ? placeholders with actual values
    function getFinalQuery($query, $params) {
        if (empty($params)) return $query;

        foreach ($params as $param) {
            // Escape single quotes for direct execution
            $value = is_numeric($param) ? $param : "'" . addslashes($param) . "'";
            $query = preg_replace('/\?/', $value, $query, 1);
        }

        return $query;
    }

    // Debugging: Generate the full SQL query
    $finalQuery = getFinalQuery($query, $params);
    // echo $finalQuery;
    // die;
    
    // echo '<pre>';print_r($finalQuery);exit;
    // Prepare and execute the query
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    http_response_code(200);
    echo json_encode($data);
    die;
    // echo json_encode([
    //     // 'status' => 'success',
    //  //   'query' => $finalQuery, // Return final query for debugging
    //    // 'parameters' => $params, 
    //     $data
    // ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
