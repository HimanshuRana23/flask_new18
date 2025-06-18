<?php

/*
Created By : @maulik@0603025
Created for : This api is used to fetch data from 2 tables using joins dynamically
URL : https://flask.dfos.co/scalable_apis/dynamicJoins.php?server=hero&table1=plants&table2=departments&on_condition=plant_id&t1_conditions={"site_id":6}&t2_conditions={"plant_id":9}&is_deleted=0&c1=plant_id&c2=department_name
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

$result = [];
$code = 200; //success

try { 
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $table1 = isset($_GET['table1']) ? $_GET['table1'] : '';
        $table2 = isset($_GET['table2']) ? $_GET['table2'] : '';
        $on_condition = isset($_GET['on_condition']) ? $_GET['on_condition'] : '';
        $t1_conditions = isset($_GET['t1_conditions']) ? json_decode($_GET['t1_conditions'], true) : [];
        $t2_conditions = isset($_GET['t2_conditions']) ? json_decode($_GET['t2_conditions'], true) : [];
        $is_deleted = isset($_GET['is_deleted']) ? $_GET['is_deleted'] : '';
        $columns1 = isset($_GET['c1']) ? $_GET['c1'] : '';
        $columns2 = isset($_GET['c2']) ? $_GET['c2'] : '';
    
        // Validate required parameters
        // if (empty($columns1) || empty($columns2) || empty($table1) || empty($table2) || empty($on_condition)) {
        //     die(json_encode(["status" => "error", "message" => "Missing required parameters.", "data" => ['records' => []]]));
        // }
    
        // Function to append alias to each column
        // function sanitize_columns($columns, $tableAlias) {
        //     if ($columns === '*') {
        //         return "$tableAlias.*";
        //     }
        //     $columnsArray = explode(',', $columns);
        //     $sanitizedColumns = array_map(function ($col) use ($tableAlias) {
        //         $col = trim($col);
        //         return "$tableAlias." . preg_replace('/[^a-zA-Z0-9_]/', '', $col);
        //     }, $columnsArray);
        //     return implode(', ', $sanitizedColumns);
        // }
        function sanitize_columns($columns, $tableAlias) {
            if ($columns === '*') {
                return "$tableAlias.*";
            }
            if ($columns === '') {
                return ''; // skip if empty
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
       $sanitizedColumns1 = sanitize_columns($columns1, 't1');
        $sanitizedColumns2 = sanitize_columns($columns2, 't2');

        // Parse original (unsanitized) input to get the first column name
        $columns1Array = array_values(array_filter(array_map('trim', explode(',', $columns1))));
        $columns2Array = array_values(array_filter(array_map('trim', explode(',', $columns2))));

        // Determine alias column
        if (!empty($columns1Array)) {
            // $aliasColumnValue = 't1.' . preg_replace('/[^a-zA-Z0-9_]/', '', $columns1Array[0]);
            $aliasColumnValue = preg_replace('/[^a-zA-Z0-9_]/', '', $columns1Array[0]);
            $aliasColumn = 't1.' . $aliasColumnValue;
            // Remove this alias column from $columns2Array so it doesn't appear twice
            $columns1Array = array_filter($columns1Array, function($col) use ($aliasColumnValue) {
                return trim($col) !== $aliasColumnValue;
            });
            $columns1Array = array_values($columns1Array); // reindex
        } elseif (!empty($columns2Array)) {
            $aliasColumnValue = preg_replace('/[^a-zA-Z0-9_]/', '', $columns2Array[0]);
            $aliasColumn = 't2.' . $aliasColumnValue;
            // Remove this alias column from $columns2Array so it doesn't appear twice
            $columns2Array = array_filter($columns2Array, function($col) use ($aliasColumnValue) {
                return trim($col) !== $aliasColumnValue;
            });
            $columns2Array = array_values($columns2Array); // reindex
        } else {
            $aliasColumn = 't1.id';
        }

        // Rebuild sanitized columns after alias adjustment
        $sanitizedColumns1 = '';
        $sanitizedColumns2 = '';

        if (!empty($columns1Array)) {
            $sanitizedColumns1 = implode(', ', array_map(function ($col) {
                return 't1.' . preg_replace('/[^a-zA-Z0-9_]/', '', trim($col));
            }, $columns1Array));
        }

        if (!empty($columns2Array)) {
            $sanitizedColumns2 = implode(', ', array_map(function ($col) {
                return 't2.' . preg_replace('/[^a-zA-Z0-9_]/', '', trim($col));
            }, $columns2Array));
        }

        // Build final SELECT
        $selectColumns = ["$aliasColumn AS id"];
        if (!empty($sanitizedColumns1)) $selectColumns[] = $sanitizedColumns1;
        if (!empty($sanitizedColumns2)) $selectColumns[] = $sanitizedColumns2;

        $finalSelect = implode(', ', $selectColumns);

        // Construct SQL query
        $query = "
            SELECT $finalSelect
            FROM $table1 t1
            JOIN $table2 t2
            ON t1.$on_condition = t2.$on_condition
        ";
    
        // Handling dynamic WHERE conditions
        $whereConditions = [];
    
        if (!empty($t1_conditions) && is_array($t1_conditions)) {
            foreach ($t1_conditions as $column => $value) {
                $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column); // Sanitize column name
                $value = mysqli_real_escape_string($conn, $value);
                $whereConditions[] = "t1.$column = '$value'";
            }
        }
        if (!empty($t2_conditions) && is_array($t2_conditions)) {
            foreach ($t2_conditions as $column => $value) {
                $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column); // Sanitize column name
                $value = mysqli_real_escape_string($conn, $value);
                $whereConditions[] = "t2.$column = '$value'";
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

        // echo '<pre>';print_r($query);exit;
        // Execute the query
        $dynamic_join = $conn->query($query);
    
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