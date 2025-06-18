<?php

/* 
Created By : @Udit prajapati - 08042025
Created for :This API validates batch numbers from form submissions. It checks if a batch exists, verifies if it's already scanned, and returns success or error messages. It also validates that rejected quantity should not more then the total no of sheets. Uses MySQL queries and responds in JSON format.
URL : https://flask.dfos.co/ITC/validation/qualityCard.php
Type : Validation
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

$code = 200; // success

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
       
        //$fvf_main_form_id = $_POST['fvf_main_form_id'];

           
        if(!empty($_POST["form_answer_json"]))
        {
     //       file_put_contents('log.txt', $_POST["form_answer_json"], FILE_APPEND);
        //file_put_contents('log.txt', $fvf_main_form_id, FILE_APPEND); 
        }
        
        $target_field_id = "3155";
        $rejected_sheet = null;
        $linked_mainaudit_id =   $_POST['linked_mainaudit_id'];
        $data = json_decode($_POST["form_answer_json"], true);
        // Loop through sections
        foreach ($data as $section) {
            foreach ($section['question_array'] as $question_group) {
                foreach ($question_group as $question) {
                    if ($question['fvf_main_field_id'] === $target_field_id) {
                        $rejected_sheet = $question['answer'];
                        break 3; // Exit all loops once found
                    }
                }
            }
        }

        $query = "SELECT  answer
        FROM form_via_form_main_audit_answers 
        WHERE fvf_main_audit_id = $linked_mainaudit_id AND fvf_main_field_id = 3152
        AND deleted_at = '0000-00-00 00:00:00'";
        $dataValue = $con->query($query);
        if ($dataValue->num_rows > 0) {  
            $res = mysqli_fetch_assoc($dataValue);
            $total_number_of_sheets = $res['answer'];

            if($rejected_sheet > $total_number_of_sheets)
            {
              $response = [
                    'message' => "Rejected sheet($rejected_sheet) can not be greater then Total number of sheets($total_number_of_sheets)",
                    'status' => "error"
                ]; 
                http_response_code(400);// success 
                echo json_encode($response);die;
            }
        
        }
       


    } else {
        $response = [
            'status' => 'error',
            'message' => "Method not allowed."
        ];
        http_response_code(405);
        echo json_encode($response);
    }
} catch (Exception $e) {
    $result = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
    http_response_code(400);
    echo json_encode($result);
}

?>
