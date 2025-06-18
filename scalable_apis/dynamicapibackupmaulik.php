<?php

/*
Created By : @Udit Prajapati - 06032025
Created for : Dynamic API
*/

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection file
include './connection/connection.php';

// Include the authorization file
//include '../authorization/authorization.php';

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
    // Validate required parameters
    if (!isset($_GET['table']) || !isset($_GET['columns'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing required parameters: table or columns']);
        die;
    }
    

    $table = $_GET['table'];
    $columns = isset($_GET['columns']) ? $_GET['columns'] : '*'; // Expected as comma-separated values
    $conditions = isset($_GET['conditions']) ? json_decode($_GET['conditions'], true) : [];
    $deleted_flag = isset($_GET['deleted_flag']) ? intval($_GET['deleted_flag']) : 0; // 1 = Include deleted_at condition
    $group_by = isset($_GET['group_by']) ? trim($_GET['group_by']) : ''; // 1 = Include deleted_at condition
    $created_at = isset($_GET['created_at']) ? trim($_GET['created_at']) : ''; // 1 = Include deleted_at condition
    // file_put_contents('log_new.txt', print_r($_POST,true), FILE_APPEND);

    // Validate table and columns (allow only alphanumeric and underscores to prevent SQL injection)
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table) || !preg_match('/^[a-zA-Z0-9_, ]+$/', $columns)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid table name or columns']);
        die;
    }

    // Split columns into array @maulik@17032025
    if ($columns !== '*') {
        $columnsArray = explode(',', $columns);
        $columnsArray = array_map('trim', $columnsArray);

        // Alias the first column as 'id' only if it's not already aliased
        if (isset($columnsArray[0]) && stripos($columnsArray[0], ' as ') === false) {
            $columnsArray[0] .= ' AS id';
        }

        $columns = implode(', ', $columnsArray);
    }
    // end@maulik
    
    // Start building the query
    $query = "SELECT distinct $columns FROM `$table`";
    $params = [];
    $types = '';
    if (!empty($conditions)) {
        $query .= " WHERE ";
        $conditionStrings = [];
        
        foreach ($conditions as $column => $value) {
            if (strpos($column, 'FIND_IN_SET:') === 0) {
                $actualColumn = str_replace('FIND_IN_SET:', '', $column);
                
                if (is_array($value)) {
                    $findParts = [];
                    foreach ($value as $v) {
                        $findParts[] = "FIND_IN_SET(?, `$actualColumn`)";
                        $params[] = $v;
                        $types .= 's';
                    }
                    $conditionStrings[] = '(' . implode(' OR ', $findParts) . ')';
                } else {
                    $conditionStrings[] = "FIND_IN_SET(?, `$actualColumn`)";
                    $params[] = $value;
                    $types .= 's';
                }
            } elseif (strpos($column, 'IN:') === 0 && is_array($value)) {
                $actualColumn = str_replace('IN:', '', $column);
                if (preg_match('/^[a-zA-Z0-9_]+$/', $actualColumn)) {
                    $placeholders = implode(', ', array_fill(0, count($value), '?'));
                    $conditionStrings[] = "`$actualColumn` IN ($placeholders)";
                    foreach ($value as $v) {
                        $params[] = $v;
                        $types .= 's'; // or use 'i' if values are always integers
                    }
                }
            } elseif (preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
                $conditionStrings[] = "`$column` = ?";
                $params[] = $value;
                $types .= 's';
            }
        }

        // Add all conditions
        $query .= implode(' AND ', $conditionStrings);
    }

    // Add deleted_at condition if flag is set
    if ($deleted_flag === 1) {
        $query .= !empty($conditions) ? " AND deleted_at ='0000-00-00 00:00:00'" : " WHERE deleted_at = '0000-00-00 00:00:00'";
    }
    if (!empty($created_at)) {
        $query .= !empty($conditions) ? " AND DATE(created_at) = '$created_at'" : " WHERE DATE(created_at) = '$created_at'" ;
    }
    if (!empty($group_by)) {
        $query .= " GROUP BY `$group_by`";
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
    // echo '<pre>';print_r($query);exit;

    // Debugging: Generate the full SQL query
    $finalQuery = getFinalQuery($query, $params);
    // echo $finalQuery;
    // die;
    
    // echo '<pre>';print_r($finalQuery);exit;
    // Prepare and execute the query
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        http_response_code(400);
        die(json_encode([
            'status' => 'error',
            'message' => 'Query execution failed:'.$conn->error
        ]));
    }
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    // echo '<pre>';print_r($data);exit;
    if(empty($data)){
        http_response_code(400);
        die(json_encode([
            'status' => 'error',
            'message' => 'No Data Found'
        ]));
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
