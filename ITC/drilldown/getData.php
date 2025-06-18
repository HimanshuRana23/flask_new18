<?php

/*
Created By : @maulik@03032025
Created for : This API retrieves material details, dimensions, and the number of sheets per cut stack based on the provided Cut Stack ID. 
URL : https://flask.dfos.co/ITC/drilldown/getData.php 
Type : Drilldown
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
    if ($_SERVER['REQUEST_METHOD'] === 'POST') { 

        $fvf_main_form_id = $_GET['fvf_main_form_id'] ?? '';
        $fvf_main_field_id = $_GET['fvf_main_field_id'] ?? '';
        $cut_stack_id = $_POST['option_id'] ?? '';
        
        // file_put_contents('log_new.txt', print_r($_POST,true), FILE_APPEND);exit;
        // $user_id = $_POST['user_id'];
        // $userQuery = "SELECT user_id FROM users WHERE user_id='$user_id'";
        // $isUserFound = $con->query($userQuery);
        
        // if (!$isUserFound || mysqli_num_rows($isUserFound) == 0) {
        //     $response = [
        //         'status' => 'error',
        //         'message' => "User not found.",
        //     ];
        //     http_response_code(400); // Bad Request
        //     echo json_encode($response);
        //     die;
        // }

        // if(true) {
            $query = '
                SELECT DISTINCT a2.answer as id
                FROM form_via_form_main_audit_answers AS a1
                JOIN form_via_form_main_audit_answers AS a2
                    ON a1.fvf_main_audit_id = a2.fvf_main_audit_id
                    AND a1.deleted_at = "0000-00-00 00:00:00"
                    AND a2.deleted_at = "0000-00-00 00:00:00"
            ';
            if ($fvf_main_form_id) {
                $query .= " WHERE a1.fvf_main_form_id = $fvf_main_form_id";
            }
            if ($fvf_main_field_id) {
                $query .= " AND a2.fvf_main_field_id = $fvf_main_field_id";
            }
            if ($cut_stack_id) {
                $query .= " AND a1.answer = '$cut_stack_id'";
            }
            // echo '<pre>';print_r($query);exit;
            // echo '<pre>';print_r($query);exit;
            $orderId = $con->query($query);
            
            while ($row = mysqli_fetch_assoc($orderId)) {  
                $result[] = array(
                    "fvf_main_field_option_id" => $row['id'],
                    "fvf_main_field_type" => "DrillDown",
                    "fvf_main_field_option_name" => $row['id'],
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
        // } else{
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