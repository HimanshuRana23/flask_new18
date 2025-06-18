<?php

/*
Created By : @Gunjan Sharma@14022025
Created for : This API retrieves the prompt message displayed in the drilldown view for Bundle Palletization, Total value is calculated based on ($length*2+30)*1200 formula
URL : https://flask.dfos.co/ITC/qualityCheck/bundlePalletizationPromptDrilldown.php
Type : post
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

                // $dataValue1= $con->query("SELECT answer as packgingstyle from form_via_form_main_audit_answers WHERE fvf_main_audit_id=$cutStackId AND fvf_main_field_id=3153");
                // $row1= mysqli_fetch_assoc($dataValue1); 
                
                
                    $orderId = $row['orderId']; //Order id
                    //$packgingstyle = $row1['packgingstyle']; //Packging style

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
                    
                    

                    $totalValue = ($length*2+30)*1200;

                    $message = " Procure film of size is".$totalValue;
                    
				
                $result[] = array(
                    "fvf_main_field_option_id" => $totalValue,
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