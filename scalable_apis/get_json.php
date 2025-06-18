<?php
/*
|--------------------------------------------------------------------------
| Script Name   : Static Form Answer JSON Handler
| Created By    : @udit perajapati
| Created Date  : 21-04-2025
| Description   : This script returns a static structure of form answer JSON 
|                 with all values blank or set to zero for initial form rendering.
| Method       : GET
| URL          : https://flask.dfos.co/scalable_apis/get_json.php
|--------------------------------------------------------------------------
*/

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set response headers
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

// Include the authorization file
include '../authorization/authorization.php';

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed. Please use POST request.'
    ]);
    exit;
}

// Static form structure with blank values
$form_answer_json = [
    [
        "fvf_section_id" => 203682,
        "fvf_section_name" => "Scheduling Initiation Form",
        "question_array" => [
            [
                [
                    "fvf_main_field_id" => "",
                    "fvf_main_field_name" => "",
                    "fvf_main_field_type" => "",
                    "answer" => "",
                    "is_nc_marked" =>"" ,
                    "answer_number_value" => "",
                    "answer_remark" => "",
                    "is_rnc" => "",
                    "is_na" => "",
                    "is_improvement" => "",
                    "is_nc_hold" => "",
                    "is_value" => "",
                    "fvf_main_field_option_id" => ""
                ]
            ]
        ]
    ]
];

// Respond with the static blank data
http_response_code(200);
echo json_encode([
    'status' => 'success',
    'message' => 'Static form data initialized.',
    'data' => $form_answer_json
]);
?>
