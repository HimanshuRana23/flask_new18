<?php

/*
Created By : @Maulik@06062025
Created for : This API retrieves batch numbers that are marked as "Hold" in records associated with Form ID 19. It is used to identify and process batches that require further review, corrective action, or are temporarily restricted from proceeding in the workflow.
URL : https://flask.dfos.co/ITC/drilldown/getBatchNo.php 
Type : Get 
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
// Check if the request method is GET
	try { 
		if ($_SERVER['REQUEST_METHOD'] === 'GET') {  
			  
                $query = "
                            SELECT DISTINCT t1.fvf_main_audit_id AS id, t1.answer AS batchno
                            FROM form_via_form_main_audit_answers t1
                            JOIN form_via_form_main_audit_answers t2
                                ON t1.fvf_main_audit_id = t2.fvf_main_audit_id
                                AND t2.fvf_main_field_id = '2714'
                                AND t2.answer = 'Hold'
                                AND t2.deleted_at = '0000-00-00 00:00:00'
                            JOIN form_via_form_main_audit_answers t3
                                ON t3.answer = t1.answer
                                AND t3.fvf_main_form_id = '18'
                                AND t3.deleted_at = '0000-00-00 00:00:00'
                            WHERE t1.fvf_main_field_id = '2712'
                            AND t1.fvf_main_form_id = '19'
                            AND t1.deleted_at = '0000-00-00 00:00:00'
                            AND NOT EXISTS (
                                SELECT 1
                                FROM form_via_form_main_audit_answers a1
                                JOIN form_via_form_main_audit_answers a2
                                    ON a1.fvf_main_audit_id = a2.fvf_main_audit_id
                                    AND a2.fvf_main_field_id = '3329'
                                    AND a2.answer = 'Good'
                                    AND a2.deleted_at = '0000-00-00 00:00:00'
                                WHERE a1.fvf_main_field_id = '3327'
                                AND a1.answer = t1.answer
                                AND a1.fvf_main_form_id = '61'
                                AND a1.deleted_at = '0000-00-00 00:00:00'
                            )
                        ";

				$batchNo = $con->query($query);
				while ($row = mysqli_fetch_assoc($batchNo)) {  
					$result[] = $row;
				} 
				//print_r($row);
				http_response_code(200); //success
				echo json_encode($result);die;
			
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