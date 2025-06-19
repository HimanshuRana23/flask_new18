<?php

/*
Created By : @maulik@2802025
Created for : This API retrieves a list of Order IDs based on the specified packing type, such as BP, RPT, or BDL. It is useful for filtering and processing orders that follow a particular packing method.
URL : https://flask.dfos.co/ITC/drilldown/getOrderId.php 
Type : Get
*/

/*This endpoint returns distinct order IDs that match a given packing type
and optional filters. Clients may supply any of these GET parameters:

 - `packing_type`       – packaging style such as BP, RPT or BDL
 - `fvf_main_form_id`   – form identifier containing the order values
 - `fvf_main_field_id`  – field identifier containing the order values
 - `from_date` and `to_date` – limit records to this date range

The script joins audit answers to locate order IDs that meet the provided
criteria. Results are returned as a JSON array or an error message when no
records are found.
URL   : https://flask.dfos.co/ITC/drilldown/getOrderId.php
Method: GET
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

$result = [];
$code = 200; //success

try { 
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // $user_id = $_GET['user_id'] ?? '';
        // $user_id = trim($_GET['user_id'] ?? '');
        $packing_type = $_GET['packing_type'] ?? '';
        $fvf_main_form_id = $_GET['fvf_main_form_id'] ?? '';
        $fvf_main_field_id = $_GET['fvf_main_field_id'] ?? '';
        $from_date = $_GET['from_date'] ?? '';
        $to_date = $_GET['to_date'] ?? '';

        // file_put_contents('log_new.txt', print_r($_GET,true), FILE_APPEND);exit;

        // if(true){
            $query = '
                SELECT distinct a1.answer AS id
                FROM form_via_form_main_audit_answers AS a1
                JOIN form_via_form_main_audit_answers AS a2
                    ON a1.fvf_main_audit_id = a2.fvf_main_audit_id
                WHERE a1.deleted_at = "0000-00-00 00:00:00"
                AND a2.deleted_at = "0000-00-00 00:00:00"
                ';
       
            if (!empty($packing_type)) {
                $query .= " AND a2.answer = '$packing_type'";
            }
            if($fvf_main_form_id){
                $query .= " AND a1.fvf_main_form_id = $fvf_main_form_id";
            }
            if($fvf_main_field_id){
                $query .= " AND a1.fvf_main_field_id = $fvf_main_field_id";
            }
            if($from_date && $to_date){
                $query .= " AND a1.created_at BETWEEN '$from_date' AND '$to_date'";
            }
            // echo '<pre>';print_r($query);exit;
            
            $orderId = $con->query($query);
            
            while ($row = mysqli_fetch_assoc($orderId)) {  
                $result[] = $row;
            }

            if(empty($result)){
                $response = [
                    'status' => 'error',
                    'message' => "No data found",
                    'data' => ['records' => $result]
                ];
                http_response_code(405); // Method Not Allowed
                echo json_encode($response);
                die;
            }
            // file_put_contents('log_new.txt', print_r($result,true), FILE_APPEND);exit;
            http_response_code(200); // success
            echo json_encode($result);
            die;
        // } else {
        //     $response = [
        //         'status' => 'error',
        //         'message' => "It seems like your session has expired.",
        //     ];
        //     http_response_code(400); // Method Not Allowed
        //     echo json_encode($response);
        //     die;
        // }
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

?>
