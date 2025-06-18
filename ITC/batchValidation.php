<?php

/* 
Created By  : @Gunjan Sharma - 03/12/2024
Created For : Batch Validation on Form Submission
Method      : POST
URL         : https://flask.dfos.co/ITC/batchValidation.php
Description : This API validates batch numbers from form submissions. It checks:
              - If the batch number exists in the master form (form ID = 18)
              - If the batch number is already scanned in the current form
              - If the batch number was selected from the batch selection list
              Responds with success or error messages accordingly, using MySQL queries
              and returns structured JSON responses.
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

$code = 200; // success

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $form_answer_json = $_POST["form_answer_json"];
        $fvf_main_form_id = $_POST['fvf_main_form_id'];

        	file_put_contents('log.txt', $form_answer_json, FILE_APPEND);
			//file_put_contents('log.txt', $fvf_main_form_id, FILE_APPEND); 

        $answerArray = json_decode($form_answer_json);
        $batchErrors = [];
        $batchSuccess = [];
        $comaselectedbatches = $answerArray[0]->question_array[0][1]->answer;
        $selectedbatches =[];
        if($comaselectedbatches)
        {
            $selectedbatches = explode(',',$comaselectedbatches); 
        }
        
        foreach ($answerArray as $section) {
            if ($section->fvf_section_name === "BATCH SELECTION") {
                foreach ($section->question_array as $qnArray) {
                    $batchNumber = $qnArray[0]->answer;

                    
                    if ($batchNumber) {
                        $dataValue = $con->query("SELECT * FROM form_via_form_main_audit_answers WHERE answer='$batchNumber' AND fvf_main_form_id=18 AND deleted_at='0000-00-00 00:00:00'");
                        if ($dataValue->num_rows > 0 && in_array($batchNumber, $selectedbatches)) {
                            $dataReScan = $con->query("SELECT * FROM form_via_form_main_audit_answers WHERE answer='$batchNumber' AND fvf_main_form_id='$fvf_main_form_id' AND deleted_at='0000-00-00 00:00:00'");
                            
                            if ($dataReScan->num_rows > 0) {
                                $response = [
                                    'message' => "This Batch No ($batchNumber) is already scanned.",
                                    'status' => "error"
                                ]; 
                                http_response_code(200);// success 
                                echo json_encode($response);die; 
                            } else {
                                $batchSuccess[] = [
                                    'batch_number' => $batchNumber,
                                    'message' => "Batch Number is available",
                                    'status' => "success"
                                ];
                            }
                        } else {
                          

                            $response = [
                                'message' => "Invalid Batch No ($batchNumber)",
                                'status' => "error"
                            ]; 
                            http_response_code(400);// success 
                            echo json_encode($response);die;
                        }
                    } else {
                        $response = [
                            'message' => "Batch No is missing.",
                            'status' => "success"
                        ]; 
                        http_response_code(400);// success 
                        echo json_encode($response);die;
                    }
                }
            }
        }

        if (!empty($batchErrors)) {
            http_response_code(400);
            echo json_encode(['errors' => $batchErrors]);
        } else {
            http_response_code(200);
            echo json_encode(['success' => $batchSuccess]);
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
