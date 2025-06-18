<?php

/*
Created By : @Gunjan Sharma@04122024
Created for : This API is used to get Allowed Reels or batch no on basis of its status good
URL : https://flask.dfos.co/ITC/drilldown/allowedReel_list.php
Method      : POST
Description : This API fetches allowed reels or batch numbers based on the provided order ID, excluding those that have already been scanned.

*/

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection file
include '../connection/connection.php';

// Include the authorization file
// include '../../authorization/authorization.php';

// Set response headers
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

$result = [];
$code = 200; //success
// Check if the request method is POST
	try { 
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {  
			$order_id = $_POST['option_id']; // Order Id
			if ($order_id!='') {   
				
				$allowedReels = $con->query("
											SELECT 
												ans1.answer AS id
											FROM 
												form_via_form_main_audit_answers AS ans
											JOIN 
												form_via_form_main_audit_answers AS ans1 
												ON ans.fvf_main_audit_id = ans1.fvf_main_audit_id
											WHERE 
												ans.fvf_main_form_id = 18
												AND ans.answer = $order_id
												AND ans.deleted_at = '0000-00-00 00:00:00'
												AND ans1.fvf_main_field_id = 2703
												AND ans1.deleted_at = '0000-00-00 00:00:00'
										");
				$ids = []; 
				while ($row= mysqli_fetch_assoc($allowedReels)) { 
					$ids[] = $row['id']; // Append each ID to the array
					// $result[] = array(
					// 	"fvf_main_field_option_id" => $row['id'],
					// 	"fvf_main_field_type" => "DrillDown",
					// 	"fvf_main_field_option_name" => $row['id'],
					// 	"fvf_main_field_option_value" => $row['id']
					// ); 
					
				} 
				
				$alreadyScanedReels = $con->query("
											SELECT 
												ans1.answer AS id
											FROM 
												form_via_form_main_audit_answers AS ans
											JOIN 
												form_via_form_main_audit_answers AS ans1 
												ON ans.fvf_main_audit_id = ans1.fvf_main_audit_id
											WHERE 
												ans.fvf_main_form_id = 29
												AND ans.answer = $order_id
												AND ans.deleted_at = '0000-00-00 00:00:00'
												AND ans1.fvf_main_field_id = 3137
												AND ans1.deleted_at = '0000-00-00 00:00:00'
										");
				$scanedIds = []; 
				while ($row1= mysqli_fetch_assoc($alreadyScanedReels)) { 
					$scanedIds[] = $row1['id']; // Append each ID to the array
					
				} 
				// print_r($ids);
				// print_r($scanedIds);
				$uniqueScanedIds = array_unique($scanedIds);
				$uniqueIds = array_unique($ids);
				// echo '<pre>';print_r('uniqueIds');
				// echo '<pre>';print_r($uniqueIds);

				// echo '<pre>';print_r('uniqueScanedIds');
				// echo '<pre>';print_r($uniqueScanedIds);

				// Remove scanned IDs from allowed IDs
				$remainingIds = array_diff($uniqueIds, $uniqueScanedIds);//print_r($remainingIds);
				// echo '<pre>';print_r('form id 18');
				// echo '<pre>';print_r($remainingIds);

				//To fetch bacth acknoweledged form audit which are good in status
				$goodBatchNo = $con->query("SELECT 
												ans1.answer as id
											FROM 
												form_via_form_main_audit_answers AS ans
											JOIN 
												form_via_form_main_audit_answers AS ans1 
												ON ans.fvf_main_audit_id = ans1.fvf_main_audit_id
											WHERE 
												ans.fvf_main_form_id = 19
												AND ans.answer = 'Good'
												AND ans.deleted_at = '0000-00-00 00:00:00'
												AND ans1.fvf_main_field_id = 2712
												AND ans1.deleted_at = '0000-00-00 00:00:00'");
				$goodBatchedIds = []; 
				while ($row2= mysqli_fetch_assoc($goodBatchNo)) { 
					$goodBatchedIds[] = $row2['id']; // Append each ID to the array
					
				} 
				
				// echo '<pre>';print_r('form id 19');
				// echo '<pre>';print_r($goodBatchedIds);
				
				// // To get good batch no from form id 61 where hold batched are marked as good
				$newGoodBatchNo = $con->query("SELECT 
												ans1.answer as id
											FROM 
												form_via_form_main_audit_answers AS ans
											JOIN 
												form_via_form_main_audit_answers AS ans1 
												ON ans.fvf_main_audit_id = ans1.fvf_main_audit_id
											WHERE 
												ans.fvf_main_form_id = 61
												-- AND ans.answer = 'Good'
												AND ans.deleted_at = '0000-00-00 00:00:00'
												AND ans1.fvf_main_field_id = 3327
												AND ans1.deleted_at = '0000-00-00 00:00:00'");
				$newGoodBatchedIds = []; 
				while ($row3= mysqli_fetch_assoc($newGoodBatchNo)) { 
					$newGoodBatchedIds[] = $row3['id']; // Append each ID to the array
				} 

				// echo '<pre>';print_r('form id 61');
				// echo '<pre>';print_r(array_unique($newGoodBatchedIds));

				$filteredBatchNumbers = array_intersect($remainingIds,$goodBatchedIds);
				$additionalBatchNumbers = array_intersect($uniqueIds, $newGoodBatchedIds);
				
				$filteredBatchNumbers = array_merge($filteredBatchNumbers, $additionalBatchNumbers);
				$filteredBatchNumbers = array_unique($filteredBatchNumbers);
				
				// Remove values present in $uniqueScanedIds i.e remove already scanned batch ids from available batch ids
				$filteredBatchNumbers = array_diff($filteredBatchNumbers, $uniqueScanedIds);

				$commaSeparatedIds = implode(',', $filteredBatchNumbers);
				// echo '<pre>';print_r('common');
				// echo '<pre>';print_r($filteredBatchNumbers);

				//$commaSeparatedIds = implode(',', $remainingIds);
				
				$result[] = array(
					"fvf_main_field_option_id" => $commaSeparatedIds,
					"fvf_main_field_type" => "DrillDown",
					"fvf_main_field_option_name" => $commaSeparatedIds,
					"fvf_main_field_option_value" => $commaSeparatedIds
				);

				$records = $result;
				$response = [
					'status' => 'success',
					'message' => "Drill down Allowed Reels List",
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