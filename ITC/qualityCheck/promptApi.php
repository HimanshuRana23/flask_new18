<?php

/*
Created By : @Gunjan Sharma - 23012025
Created for : This API retrieves a prompt message based on the selected Cut Stack ID, It is used to display context-specific information, warnings, or instructions to the user during cut stack-related operations such as quality checks, palletization, or production entry.
URL : https://flask.dfos.co/ITC/qualityCheck/promptApi.php
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
			// file_put_contents('log.txt', $form_answer_json, FILE_APPEND);
			// file_put_contents('log.txt', $fvf_main_form_id, FILE_APPEND); 
			
			$answerArray = json_decode($form_answer_json);  
			//print_r($answerArray[0]->question_array[0]);
			// Extract question array
			// $fvf_section_id = $answerArray[0]->fvf_section_id;//27016
			$qnArray = $answerArray[0]->question_array[0];
			$cutStackId = $qnArray[0]->answer; //audit id
            
            if($cutStackId){ 
                echo "SELECT answer from form_via_form_main_audit_answers WHERE fvf_main_audit_id=$cutStackId AND fvf_main_field_id=158876";
                $dataValue= $con->query("SELECT answer from form_via_form_main_audit_answers WHERE fvf_main_audit_id=$cutStackId AND fvf_main_field_id=158876");
				// if($dataValue->num_rows > 0){ 
                    $row= mysqli_fetch_assoc($dataValue);
                    $orderId = $row['answer']; //order id
                    $masterForm= $con->query("SELECT ans1.fvf_main_audit_id,
                                                MAX(CASE WHEN ans1.fvf_main_field_id = 157668 THEN ans1.answer END) AS 'width',
                                                MAX(CASE WHEN ans1.fvf_main_field_id = 157669 THEN ans1.answer END) AS 'length'
                                                
                                            FROM 
                                                form_via_form_main_audit_answers as ans
                                            JOIN
                                                form_via_form_main_audit_answers AS ans1
                                            ON
                                                ans.fvf_main_audit_id = ans1.fvf_main_audit_id 
                                            WHERE 
                                                ans.fvf_main_form_id = 9428 and ans.answer = $orderId and ans.fvf_main_field_id=157656
                                                order by ans.fvf_main_ans_id asc limit 1
                                            ");  
                    $masterFormValue = mysqli_fetch_assoc($masterForm);print_r($masterFormValue);
                    $width = $masterFormValue['width'];
                    $length = $masterFormValue['length'];
                    
                    echo $miniumValue = min($width, $length);
                    echo 'min=='.$smallerValue = ($miniumValue*2)+ 15; echo '     ';
                    echo $maximumValue = max($width, $length);
                    echo 'max=='.$largerValue = ($maximumValue)+20;

                    if($largerValue > $smallerValue)
                    {

                        if ($smallerValue <= 100 && $largerValue <= 140) {
                            $message = "Wrapper size: '100x140'";
                        } elseif ($smallerValue <= 115 && $largerValue <= 165) {
                            $message = "Wrapper size: '115x165'";
                        } elseif ($smallerValue <= 135 && $largerValue <= 175) {
                            $message = "Wrapper size: '135x175'";
                        } elseif ($smallerValue <= 140 && $largerValue <= 200) {
                            $message = "Wrapper size: '150x200'";
                        } elseif ($smallerValue <= 150 && $largerValue <= 200) {
                            $message = "Wrapper size: '1 FULL SHEET & 1 HALF SHEET OF 100X140'";
                        } elseif ($smallerValue <= 163 && $largerValue <= 238) {
                            $message = "Wrapper size: '1 FULL SHEET & 1 HALF SHEET OF 115X165'";
                        } elseif ($smallerValue <= 190 && $largerValue <= 250) {
                            $message = "Wrapper size: '1 FULL SHEET & 1 HALF SHEET OF 135X175'";
                        } elseif ($smallerValue <= 215 && $largerValue <= 290) {
                            $message = "Wrapper size: '1 FULL SHEET & 1 HALF SHEET OF 150X200'";
                        } else {
                            $message = "Wrapper size: 'USE TWO WRAPER SHEETS'";
                        }

                    }
                    else{
                        $message = "Wrapper size: 'USE TWO WRAPER SHEETS'";
                    }

                    
                        $response = [
                            'message' => $message,
                            'status' => "error"
                        ]; 
                        http_response_code(200);// success 
                        echo json_encode($response);die;
                    
                    // $response = [
                    //     'message' => "Batch Number is available",
                    //     'status' => "success"
                    // ]; 
                    // http_response_code(200);// success 
                    // echo json_encode($response);die;

                // }else{
                //     $response = [
                //         'message' => "Invalid Batch No.",
                //         'status' => "error"
                //     ]; 
                //     http_response_code(400);// success 
                //     echo json_encode($response);die;
                // } 
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