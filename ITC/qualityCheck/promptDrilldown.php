<?php

/*
Created By : @Gunjan Sharma@14022025
Created for : This API is used to get Prompt message on basis of selected cut stack id
URL : https://flask.dfos.co/ITC/qualityCheck/promptDrilldown.php
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
// Check if the request method is POST
	try { 
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {  
			$cutStackId = $_POST['option_id']; // Cut stack ID
			if ($cutStackId!='') {   
				
				$dataValue= $con->query("SELECT answer as orderId from form_via_form_main_audit_answers WHERE fvf_main_audit_id=$cutStackId AND fvf_main_field_id=3149");
                $row= mysqli_fetch_assoc($dataValue);

                $dataValue1= $con->query("SELECT answer as packgingstyle from form_via_form_main_audit_answers WHERE fvf_main_audit_id=$cutStackId AND fvf_main_field_id=3197");
                $row1= mysqli_fetch_assoc($dataValue1); 
                
                
                    $orderId = $row['orderId']; //Order id
                    $packgingstyle = $row1['packgingstyle']; //Packging style

                    $masterForm= $con->query("SELECT ans1.fvf_main_audit_id,
                                                MAX(CASE WHEN ans1.fvf_main_field_id = 2708 THEN ans1.answer END) AS 'width',
                                                MAX(CASE WHEN ans1.fvf_main_field_id = 2709 THEN ans1.answer END) AS 'length'
                                                
                                            FROM 
                                                form_via_form_main_audit_answers as ans
                                            JOIN
                                                form_via_form_main_audit_answers AS ans1
                                            ON
                                                ans.fvf_main_audit_id = ans1.fvf_main_audit_id 
                                            WHERE 
                                                ans.fvf_main_form_id = 18 and ans.answer = $orderId and ans.fvf_main_field_id=2696
                                                order by ans.fvf_main_ans_id asc limit 1
                                            ");  
                    $masterFormValue = mysqli_fetch_assoc($masterForm);//print_r($masterFormValue);
                    $width = $masterFormValue['width'];
                    $length = $masterFormValue['length'];
                    
                    $miniumValue = min($width, $length);
                    $smallerValue = ($miniumValue*2)+ 15; 
                    $maximumValue = max($width, $length);
                    $largerValue = ($maximumValue)+20;

                    if($packgingstyle=='RPT')
                    {
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
                    }
                    else if($packgingstyle == 'BP')
                    {
                        $message = $cutStackId." Procure 2 units of 'FILM SHRINK LDPE 100mu SHEET 900MM' for BULK PACKING";
                    }
                    // else
                    // { }
				
                $result[] = array(
                    "fvf_main_field_option_id" => $message,
                    "fvf_main_field_type" => "DrillDown",
                    "fvf_main_field_option_name" => $message,
                    "fvf_main_field_option_value" => 0
                ); 
				 
				$records = $result;
				$response = [
					'status' => 'success',
					'message' => "Drill down prompt message",
					'data' => ['records' => $records]
				];
				 
				http_response_code(200); //success
				echo json_encode($response);die;
			}else{
				$response = [
					'status' => 'error',
					'message' => "Invalid Parameter",
					'data' => ['records' => []]
				]; 
				http_response_code(400); //Method not allowed
				echo json_encode($response);die;
			}
		}else{
			$response = [
				'status' => 'error',
				'message' => "Method not allowed",
				'data' => ['records' => $result]
			]; 
			http_response_code(400); //Method not allowed
			echo json_encode($response);die;
		} 
	}catch (Exception $e){ 
        $response = [
			'status' => 'error',
			'message' => $e->getMessage()
		]; 
        http_response_code(400); //error
        echo json_encode($response);die;
    }

?>