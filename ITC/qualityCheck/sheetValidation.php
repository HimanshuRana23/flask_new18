<?php

/*
Created By : @Gunjan Sharma - 14022025
Created for : This API is used to validate whether the entered total number of sheets falls within the acceptable range defined for a given Cut Stack ID.
URL : https://flask.dfos.co/ITC/qualityCheck/sheetValidation.php
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



$code = 200; //success
// Check if the request method is POST
	try { 
		if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
			
			$form_answer_json = $_POST["form_answer_json"]; 
			$fvf_main_form_id=$_POST['fvf_main_form_id'];//6446;//; 
			//  file_put_contents('log.txt', $form_answer_json, FILE_APPEND);
			//  file_put_contents('log.txt', $fvf_main_form_id, FILE_APPEND); 
			
			$answerArray = json_decode($form_answer_json);  
			//print_r($answerArray[0]->question_array[0]);
			// Extract question array
			// $fvf_section_id = $answerArray[0]->fvf_section_id;//27016
			$qnArray = $answerArray[0]->question_array[0];
            $noOfSheets = $qnArray[1]->answer; // No of sheets
			$cutStackId = $qnArray[2]->answer; //audit id
           
            if($cutStackId){ 
                $dataValue= $con->query("SELECT answer from form_via_form_main_audit_answers WHERE fvf_main_audit_id=$cutStackId AND fvf_main_field_id=3152");
				// if($dataValue->num_rows > 0){ 
                    $row= mysqli_fetch_assoc($dataValue);
                    $totalNoOfSheets = $row['answer']; //Total No of Sheets
                    if($noOfSheets>$totalNoOfSheets)
                    {
                        $response = [
                            'message' => "You cannot Pack More than '".$totalNoOfSheets."' sheets",
                            'status' => "error"
                        ]; 
                        http_response_code(200);// success 
                        echo json_encode($response);die;
                    }
                    else{
                        $response = [
                                'message' => "Total no of sheets are available",
                                'status' => "success"
                            ]; 
                            http_response_code(200);// success 
                            echo json_encode($response);die;
                    }
                    
            }else{
				$response = [
					'message' => "Cut stack id is missing.",
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