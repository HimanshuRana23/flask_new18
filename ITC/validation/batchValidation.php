<?php

/*
Created By : @Gunjan Sharma - 03122024
Created for : This API validates the Batch Number during scanning. It checks whether the batch number exists, whether it has already been scanned or wether it is available
URL : https://flask.dfos.co/ITC/validation/batchValidation.php
Type : Validation
*/

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection file
include '../connection/connection.php';

// Include the authorization file
// include '../authorization/authorization.php';

// Set response headers
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");



$code = 200; //success
// Check if the request method is POST
	try { 
		if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
			
			$form_answer_json = $_POST["form_answer_json"]; 
			$fvf_main_form_id=$_POST['fvf_main_form_id'];//6446;//; 
			// file_put_contents('log.txt', $form_answer_json, FILE_APPEND);
            // file_put_contents('log2.txt', print_r($_POST,true), FILE_APPEND);
			//file_put_contents('log.txt', $fvf_main_form_id, FILE_APPEND); 
			
			$answerArray = json_decode($form_answer_json);  
            // echo '<pre>';print_r($answerArray);exit;
			// Extract question array
			// $fvf_section_id = $answerArray[0]->fvf_section_id;//27016
			$qnArray = $answerArray[0]->question_array[0];
			$batchNumber = $qnArray[0]->answer; //' ';//
            //$batchNumber = 0086217565;
            if($batchNumber){ 
                //$dataValue= $con->query("SELECT * FROM form_via_form_main_audit_answers WHERE answer=$batchNumber and fvf_main_form_id =9428 AND deleted_at='0000-00-00 00:00:00'");
                $dataValue= $con->query("SELECT * FROM form_via_form_main_audit_answers WHERE answer=$batchNumber AND deleted_at='0000-00-00 00:00:00'");
				if($dataValue->num_rows > 0){ 

                    $dataReScan= $con->query("SELECT * FROM form_via_form_main_audit_answers WHERE answer=$batchNumber and fvf_main_form_id =$fvf_main_form_id AND deleted_at='0000-00-00 00:00:00'");  
                    // $dataReScan= $con->query("SELECT * FROM form_via_form_main_audit_answers WHERE answer = '$batchNumber' AND fvf_main_form_id IN ($fvf_main_form_id) AND deleted_at='0000-00-00 00:00:00'");  
                    if($dataReScan->num_rows > 0){ 
                        $response = [
                            'message' => "This Batch No is already scanned.",
                            'status' => "error"
                        ]; 
                        http_response_code(200);// success 
                        echo json_encode($response);die;
                    }
                    $response = [
                        'message' => "Batch Number is available",
                        'status' => "success"
                    ]; 
                    http_response_code(200);// success 
                    echo json_encode($response);die;

                }else{
                    $response = [
                        'message' => "Invalid Batch No.",
                        'status' => "error"
                    ]; 
                    http_response_code(400);// success 
                    echo json_encode($response);die;
                } 
            }else{
				$response = [
					'message' => "Batch No is missing.",
					'status' => "success"
				]; 
				http_response_code(400);// success 
				echo json_encode($response);die;
			} 
			
			
		}else{ 
			$response = [
				'status' => 'error',
				'message' => "Method not allowed."
			]; 
			http_response_code(405);//Method not allowed
			echo json_encode($response);die;
		}	 
	}catch (Exception $e){ 
        $result = [
			'status' => 'error',
			'message' => $e->getMessage()
		]; 
        http_response_code(400); //error
        echo json_encode($response);die;
    }

?>