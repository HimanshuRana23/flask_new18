<?php

/*
Created By : @Maulik - 27052025
Created for : This dynamic get api is used to get schedule id
URL : https://flask.dfos.co/scalable_apis/getScheduleData.php?server=danone&schedule_id=17&fvf_main_form_id=125&fvf_main_field_id=1953
    Method : Get  
    Description : This API fetches the schedule ID based on the provided form and field IDs.


*/

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection file
include './connection/connection.php';

// Include the authorization file
include '../authorization/authorization.php';

// Set response headers
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

$result = [];
$code = 200; 

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => "Method not allowed."]);
    die;
}

try {
    $schedule_id = isset($_GET['schedule_id']) ? $_GET['schedule_id'] : 0;
    $fvf_main_form_id = isset($_GET['fvf_main_form_id']) ? $_GET['fvf_main_form_id'] : 0;
    $fvf_main_field_id = isset($_GET['fvf_main_field_id']) ? $_GET['fvf_main_field_id'] : 0;
    // echo '<pre>';print_r($schedule_id);exit;
    $query = "SELECT distinct answer AS id 
                FROM `form_via_form_main_audit_answers` 
                WHERE fvf_main_form_id = $fvf_main_form_id 
                AND fvf_main_field_id = $fvf_main_field_id
                AND answer = $schedule_id ";

    $scheduleData = $conn->query($query);
    while ($row = mysqli_fetch_assoc($scheduleData)) {  
        $result[] = $row;
    }
    if(empty($result)){
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Specified schedule id not found']);die;
    } 
    //print_r($row);
    http_response_code(200); //success
    echo json_encode($result);die;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

?>