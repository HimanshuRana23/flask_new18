<?php

/*
Created By : @Maulik@11062025
Created for : This API retrieves all Cut Stack IDs that are either marked as "Hold" or have a non-empty "Number of Sheets Rejected" value in Form ID 34 (QC Form).
URL : https://flask.dfos.co/ITC/drilldown/fetchHoldCutStack_ids.php
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
                        SELECT DISTINCT t1.answer as id
                        FROM form_via_form_main_audit_answers t1
                        JOIN form_via_form_main_audit_answers t2
                            ON t1.fvf_main_audit_id = t2.linked_mainaudit_id
                            AND t2.fvf_main_field_id = 3161
                            -- AND t2.answer = 'Hold'
                            AND t2.fvf_main_form_id = 34
                            AND t2.deleted_at = '0000-00-00 00:00:00'
                        JOIN form_via_form_main_audit_answers t3
                            ON t1.fvf_main_audit_id = t3.linked_mainaudit_id
                            AND t3.fvf_main_field_id = 3155
                            -- AND TRIM(t3.answer) != ''
                            AND t3.deleted_at = '0000-00-00 00:00:00'
                        WHERE t1.fvf_main_field_id = 3154
                        AND t1.fvf_main_form_id = 33
                        AND t1.deleted_at = '0000-00-00 00:00:00'
                        AND (
                            t2.answer = 'Hold'
                            OR t3.answer != ''
                        )
                    ";

            $holdCutstackList = $con->query($query);
            // echo '<pre>';print_r($holdCutstackList);exit;

            if (!$holdCutstackList) {
                die("Query Failed: " . $con->error . "\n" . $query);
            }

            while ($row = mysqli_fetch_assoc($holdCutstackList)) {  
                    $result[] = $row;
                }
                //print_r($row);
                http_response_code(200); //success
                echo json_encode($result);die;

            if($holdCutstackList->num_rows > 0){
                while ($row = mysqli_fetch_assoc($holdCutstackList)) {  
                    $result[] = $row;
                }
                //print_r($row);
                http_response_code(200); //success
                echo json_encode($result);die;
            } else {
                $response = [
                    'status' => 'error',
                    'message' => "No data found",
                    'data' => ['records' => $result]
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