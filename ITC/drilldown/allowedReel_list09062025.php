<?php

/*
Created By : @Gunjan Sharma@04122024
Created for : Get Allowed Reels No
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
				// Remove scanned IDs from allowed IDs
				$remainingIds = array_diff($uniqueIds, $uniqueScanedIds);//print_r($remainingIds);

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
				$filteredBatchNumbers = array_intersect($remainingIds,$goodBatchedIds);
				$commaSeparatedIds = implode(',', $filteredBatchNumbers);

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