<?php

/*
Created By : @Gunjan Sharma - 03122024
Created for : This API is used to validate the combination of an order ID and batch number to ensure they are correct and exist in the system. It helps prevent processing of invalid or mismatched data by verifying the provided inputs against existing records.
URL : https://flask.dfos.co/ITC/drilldown/scanReel.php
Type : Validation Api 
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

/**
 * Send JSON response.
 *
 * @param int    $statusCode HTTP status code.
 * @param string $message    Response message.
 * @param string $status     Response status ('success' or 'error').
 * @param array  $data       Additional data for the response (optional).
 */
function sendResponse($statusCode, $message, $status, $data = [])
{
    http_response_code($statusCode);
    echo json_encode([
        'message' => $message,
        'status' => $status,
        'data' => $data
    ]);
    exit;
}

try {
    // Validate HTTP method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(405, "Method not allowed.", "error");
    }

    // Retrieve and validate input parameters
    $formAnswerJson = $_POST["form_answer_json"] ?? null;
    $fvfMainFormId = $_POST["fvf_main_form_id"] ?? null;
    // file_put_contents('log.txt', $formAnswerJson, FILE_APPEND);
	// file_put_contents('log.txt', $fvfMainFormId, FILE_APPEND); 

    if (!$formAnswerJson || !$fvfMainFormId) {
        sendResponse(400, "Missing required parameters.", "error");
    }

    // Decode JSON input
    $answerArray = json_decode($formAnswerJson);
    if (!is_array($answerArray) || empty($answerArray)) {
        sendResponse(400, "Invalid JSON input.", "error");
    }

    // Extract main order and batch number
    $orderId = $answerArray[0]->question_array[0][0]->answer ?? null;
    $batchNumber = $answerArray[0]->question_array[0][1]->answer ?? null;

    if (!$orderId || !$batchNumber) {
        sendResponse(400, "Order ID and Batch Number are required.", "error");
    }

    // Convert batchNumber string to an array
    $batchNumberArray = array_map('trim', explode(',', $batchNumber));

    // Check if question array exists
    if (isset($answerArray[1]->question_array)) {
        foreach ($answerArray[1]->question_array as $questionSet) { //print_r($questionSet[0]->answer);
            $batchNOAnswer = $questionSet[0]->answer; 
            foreach ($questionSet as $question) {
                $fieldId = $question->fvf_main_field_id ?? null;
                $fieldName = $question->fvf_main_field_name ?? null; 
                $answer = $question->answer ?? null;
//print_r($answer);
                // Check if the answer exists in the batch number array
                if (in_array($batchNOAnswer, $batchNumberArray)) {
                    // sendResponse(200, "Valid Batch Number.", "success", [
                    //     'field_id' => $fieldId,
                    //     'field_name' => $fieldName,
                    //     'answer' => $answer
                    // ]);
                }
                else {
                    sendResponse(400, "Invalid Batch Number: $answer", "error");
                    // sendResponse(400, "Invalid Batch Number: $answer", "error", [
                    //     'invalid_batch_number' => $answer
                    // ]);
                }
            }
        }
        //sendResponse(400, "No valid answers found in batch numbers.", "error");
    } else {
        sendResponse(400, "No data found.", "error");
    }
} catch (Exception $e) {
    // Handle unexpected errors
    sendResponse(500, "An error occurred: " . $e->getMessage(), "error");
}

?>
