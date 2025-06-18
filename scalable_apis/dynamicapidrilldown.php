<?php

/*
Created By : @maulik@03032025
Description : This is drilldown API used for fetch data from 2 tables using joins based on option id from get api
URL : Eg. https://flask.dfos.co/scalable_apis/dynamicapidrilldown.php?server=heromotocop&table1=form_via_form_main_audit_answers&table2=form_via_form_main_audit_answers&on_condition=fvf_main_audit_id&fetch_column_t1={"fvf_main_audit_id"}&fetch_column_t2={"answer"}&conditions_t1={"fvf_main_form_id":64659}&conditions_t2={"fvf_main_field_id":4641415}&is_deleted=1
Type : Drilldown
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
    if ($_SERVER['REQUEST_METHOD'] === 'POST') { 

        // $option_id = $_POST['option_id'] ?? '';
        $option_id =  $_POST['answer'] ?? '';
        $conditions_t1 = isset($_GET['conditions_t1']) ? json_decode($_GET['conditions_t1'], true) : [];
        $conditions_t2 = isset($_GET['conditions_t2']) ? json_decode($_GET['conditions_t2'], true) : [];
        $on_condition = $_GET['on_condition'] ?? '';
        $is_deleted = $_GET['is_deleted'] ?? '';
        $table1 = $_GET['table1'] ?? '';
        $table2 = $_GET['table2'] ?? '';
        $fetch_column_t1 = $_GET['fetch_column_t1'] ?? '';
        $fetch_column_t2 = $_GET['fetch_column_t2'] ?? '';
        $group_by = $_GET['group_by'] ?? '';
        
        // file_put_contents('log_new.txt', print_r($_POST,true), FILE_APPEND);
         // Validate required parameters
        if (empty($table1) || empty($table2) || empty($on_condition)) {
            die(json_encode(["status" => "error", "message" => "Missing required parameters."]));
        }
        
        // Function to sanitize columns
        function sanitize_columns($columns, $tableAlias, $aliasForFirst = null) {
            $columnsArray = explode(',', $columns);
            $sanitizedColumns = [];
            foreach ($columnsArray as $index => $col) {
                $col = trim($col);
                $safeCol = preg_replace('/[^a-zA-Z0-9_]/', '', $col);
                if ($index === 0 && $aliasForFirst) {
                    $sanitizedColumns[] = "$tableAlias.$safeCol AS `$aliasForFirst`";
                } else {
                    $sanitizedColumns[] = "$tableAlias.$safeCol";
                }
            }
            return implode(', ', $sanitizedColumns);
        }

        // sanitize t1 columns & alias first column as id
        $columns1 = sanitize_columns($fetch_column_t1, 't1', 'id');

        // sanitize t2 columns & alias first column as sku
        $columns2 = sanitize_columns($fetch_column_t2, 't2', 'sku');

        // merge columns
        $finalColumns = $columns1 . ', ' . $columns2;

        $query = "
            SELECT $finalColumns
            FROM $table1 t1
            JOIN $table2 t2 
            ON t1.$on_condition = t2.$on_condition
        ";

        // Dynamic WHERE
        $whereConditions = [];

        if ($option_id) {
            $whereConditions[] = "t1.answer = '" . mysqli_real_escape_string($conn, $option_id) . "'";
        }
        
        if (!empty($conditions_t1) && is_array($conditions_t1)) {
            foreach ($conditions_t1 as $column => $value) {
                $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
                $value = mysqli_real_escape_string($conn, $value);
                $whereConditions[] = "t1.$column = '$value'";
            }
        }
        if (!empty($conditions_t2) && is_array($conditions_t2)) {
            foreach ($conditions_t2 as $column => $value) {
                $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
                $value = mysqli_real_escape_string($conn, $value);
                $whereConditions[] = "t2.$column = '$value'";
            }
        }

        if ($is_deleted == '1') {
            $whereConditions[] = 't1.deleted_at = "0000-00-00 00:00:00" AND t2.deleted_at = "0000-00-00 00:00:00"';
        }
    
        if (!empty($whereConditions)) {
            $query .= " WHERE " . implode(" AND ", $whereConditions);
        }
        if(!empty($group_by)){
            $query .= "GROUP BY t2.".$group_by;
        }
        
        // echo '<pre>';print_r($query);exit;

        $dynamic_join = $conn->query($query);
        // echo '<pre>';print_r($query);exit;
        while ($row = mysqli_fetch_assoc($dynamic_join)) {  
            $result[] = array(
                "fvf_main_field_option_id" => $row['id'],
                "fvf_main_field_type" => "DrillDown",
                "fvf_main_field_option_name" => $row['sku'],
                "fvf_main_field_option_value" => 0
            );
        } 
        $response = [
            'status' => 'success',
            'message' => "Data List Successfull",
            'data' => ['records' => $result]
        ]; 

        http_response_code(200); // success
        echo json_encode($response);
        die;

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
        // http_response_code(200); // success
        // echo json_encode($result);
        // die;

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
