<?php

/*
Created By : @maulik@0603025
Created for : Dynamic api for joins
*/

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection file
include './connection/connection.php';

// Set response headers
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

$result = [];
$code = 200; //success

try { 
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $table1 = isset($_GET['table1']) ? $_GET['table1'] : '';
        $table2 = isset($_GET['table2']) ? $_GET['table2'] : '';
        $on_condition = isset($_GET['on_condition']) ? $_GET['on_condition'] : '';
        $conditions = isset($_GET['conditions']) ? json_decode($_GET['conditions'], true) : [];
        $is_deleted = isset($_GET['is_deleted']) ? $_GET['is_deleted'] : '';
        $columns1 = isset($_GET['c1']) ? $_GET['c1'] : '*';
        $columns2 = isset($_GET['c2']) ? $_GET['c2'] : '*';
    
        // Validate required parameters
        if (empty($columns1) || empty($columns2) || empty($table1) || empty($table2) || empty($on_condition)) {
            die(json_encode(["status" => "error", "message" => "Missing required parameters.", "data" => ['records' => []]]));
        }
    
        // Function to append alias to each column
        function sanitize_columns($columns, $tableAlias) {
            if ($columns === '*') {
                return "$tableAlias.*";
            }
            $columnsArray = explode(',', $columns);
            $sanitizedColumns = array_map(function ($col) use ($tableAlias) {
                $col = trim($col);
                return "$tableAlias." . preg_replace('/[^a-zA-Z0-9_]/', '', $col);
            }, $columnsArray);
            return implode(', ', $sanitizedColumns);
        }
    
        // Determine first column for aliasing
        $firstColumn1 = explode(',', $columns1)[0] ?? 'id';
        $aliasColumn = trim($firstColumn1);
    
        // Sanitize column names
        $columns1 = sanitize_columns($columns1, 't1');
        $columns2 = sanitize_columns($columns2, 't2');
    
        // Construct SQL query
        $query = "
                    SELECT $columns1 AS id, $columns2 
                    FROM $table1 t1 
                    JOIN $table2 t2 
                    ON t1.$on_condition = t2.$on_condition
                ";
    
        // Handling dynamic WHERE conditions
        $whereConditions = [];
    
        if (!empty($conditions) && is_array($conditions)) {
            foreach ($conditions as $column => $value) {
                $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column); // Sanitize column name
                $value = mysqli_real_escape_string($con, $value);
                $whereConditions[] = "t1.$column = '$value'";
            }
        }
    
        // Handling is_deleted filter
        if ($is_deleted === '' || $is_deleted == '0') {
            $whereConditions[] = 't1.deleted_at = "0000-00-00 00:00:00" AND t2.deleted_at = "0000-00-00 00:00:00"';
        }
    
        // Append WHERE conditions if any
        if (!empty($whereConditions)) {
            $query .= " WHERE " . implode(" AND ", $whereConditions);
        }
        // Execute the query
        $dynamic_join = $con->query($query);
    
        while ($row = mysqli_fetch_assoc($dynamic_join)) {
            $result[] = $row;
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
    http_response_code(500); // Internal Server Error
    echo json_encode($response);
    die;
}

?>