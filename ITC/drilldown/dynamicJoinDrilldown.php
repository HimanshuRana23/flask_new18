<?php

/*
Created By : @maulik@03032025
Created for : Get material, dimension, no of sheets per cut stack based on cus stack id
*/

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection file
include '../connection/connection.php';

// Set response headers
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

$result = [];
$code = 200; //success

try { 
    if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
        $option_id = $_GET['option_id'] ?? '';
        $conditions = isset($_GET['conditions']) ? json_decode($_GET['conditions'], true) : [];
        $on_condition = $_GET['on_condition'] ?? '';
        $is_deleted = $_GET['is_deleted'] ?? '';
        $table1 = $_GET['table1'] ?? '';
        $table2 = $_GET['table2'] ?? '';
        $columns1 = $_GET['c1'] ?? '';
        $columns2 = $_GET['c2'] ?? '';

         // Validate required parameters
         if (empty($table1) || empty($table2) || empty($on_condition) || empty($conditions)) {
            die(json_encode(["status" => "error", "message" => "Missing required parameters.", "data" => ['records' => $result]]));
        }
        
        // function to append alias on each columns
        function sanitize_columns($columns, $tableAlias) {
            $columnsArray = explode(',', $columns); // Split columns by comma
            $sanitizedColumns = array_map(function ($col) use ($tableAlias) {
                $col = trim($col); // Remove whitespace
                return $tableAlias . '.' . preg_replace('/[^a-zA-Z0-9_]/', '', $col); // Add table alias
            }, $columnsArray);
            return implode(', ', $sanitizedColumns); // Convert back to comma-separated string
        }

        // Determine which column to alias as 'id' (e.g., first column of table1)
        $firstColumn1 = explode(',', $_GET['c1'])[0] ?? ''; // Get first column from c1
        $aliasColumn = !empty($firstColumn1) ? trim($firstColumn1) : 'id'; // Choose the first column or default

        // Apply function to columns1 with 't1.' prefix
        $columns1 = ($columns1 !== '*') ? sanitize_columns($columns1, 't1', $aliasColumn) : '';

        // Apply function to columns2 with 't2.' prefix
        $columns2 = ($columns2 !== '') ? sanitize_columns($columns2, 't2') : '';

        // Add a comma **only if** $columns2 is not empty else remove if no c2 is present
        $columns2 = (!empty($columns2)) ? ', ' . $columns2 : '';
        // Construct SQL query dynamically
        $query = "
                SELECT $columns1 as id $columns2
                FROM $table1 t1
                JOIN $table2 t2 
                ON t1.$on_condition = t2.$on_condition
            ";

        // Handling dynamic WHERE conditions
        $whereConditions = [];

        if($option_id){
            $whereConditions[] = "t2.answer='$option_id'";
        }
        
        if (!empty($conditions) && is_array($conditions)) {
            foreach ($conditions as $column => $value) {
                $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column); // Sanitize column name
                $value = mysqli_real_escape_string($con, $value);
                $whereConditions[] = "t1.$column = '$value'";
            }
        }

        if ($is_deleted === '' || $is_deleted == '0') {
            $whereConditions[] = 't1.deleted_at = "0000-00-00 00:00:00" AND t2.deleted_at = "0000-00-00 00:00:00"';
        }

        // Append WHERE conditions if any
        if (!empty($whereConditions)) {
            $query .= " WHERE " . implode(" AND ", $whereConditions);
        }
        
        // echo '<pre>';print_r($query);exit;

        $dynamic_join = $con->query($query);
        // echo '<pre>';print_r($query);exit;
        while ($row = mysqli_fetch_assoc($dynamic_join)) {  
            $result[] = array(
                "fvf_main_field_option_id" => $row['id'],
                "fvf_main_field_type" => "DrillDown",
                "fvf_main_field_option_name" => $row['id'],
                "fvf_main_field_option_value" => 0
            );
        } 

        if (empty($result)) {
            $response = [
                'status' => 'error',
                'message' => "No data found",
                'data' => ['records' => []]
            ];
            http_response_code(404);
            echo json_encode($response);
            die;
        }
        http_response_code(200); // success
        echo json_encode($result);
        die;

    } else {
        $response = [
            'status' => 'error',
            'message' => "Method not allowed",
            'data' => ['records' => $result]
        ];
        http_response_code(405); // Method Not Allowed
        echo json_encode($response);
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
