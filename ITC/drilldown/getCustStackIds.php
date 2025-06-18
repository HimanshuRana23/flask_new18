<?php

/*
Created By : @maulik@28022025
Created for : This API retrieves a list of Cut Stack IDs based on the selected Order ID, ensuring that each returned cut stack matches the corresponding material and dimensions associated with the order. It is used to filter valid cut stacks for a specific order configuration.
URL : https://flask.dfos.co/ITC/drilldown/getCustStackIds.php
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

            // file_put_contents('log_new.txt', print_r($_POST,true), FILE_APPEND);
			$order_id = $_POST['option_id'];
			$fvf_main_form_id = $_GET['fvf_main_form_id'];
			$fvf_main_field_id = $_GET['fvf_main_field_id'];
			$field_id = isset($_GET['field_id']) ? $_GET['field_id'] : 3271;
            // $user_id = $_POST['user_id'];

            // $userQuery = "SELECT user_id FROM users WHERE user_id='$user_id'";
            // $isUserFound = $con->query($userQuery);
            
            // if (!$isUserFound || mysqli_num_rows($isUserFound) == 0) {
            //     $response = [
            //         'status' => 'error',
            //         'message' => "User not found.",
            //     ];
            //     http_response_code(400); // Bad Request
            //     echo json_encode($response);
            //     die;
            // }
            // file_put_contents('log_new.txt', print_r($isUserFound,true), FILE_APPEND);exit;

            // if(true) {
                if ($order_id != '' && $fvf_main_form_id != '' && $fvf_main_field_id != '') {   
                    $query = "SELECT ans1.answer AS dimension, ans2.answer as material
                                FROM form_via_form_main_audit_answers AS ans
                                JOIN form_via_form_main_audit_answers AS ans1
                                    ON ans.fvf_main_audit_id = ans1.fvf_main_audit_id
                                    AND ans1.fvf_main_field_id = 3259
                                    AND ans1.deleted_at = '0000-00-00 00:00:00'

                                JOIN form_via_form_main_audit_answers AS ans2
                                    ON ans.fvf_main_audit_id = ans2.fvf_main_audit_id
                                    AND ans2.fvf_main_field_id = 3252
                                    AND ans2.deleted_at = '0000-00-00 00:00:00'
                                WHERE ans.fvf_main_form_id = $fvf_main_form_id
                                    AND ans.answer = $order_id
                                    AND ans.deleted_at = '0000-00-00 00:00:00'";
                                    

                    $dataValue = $con->query($query);
                    $dimension = '';
                    $materials = '';

                    if ($row = mysqli_fetch_assoc($dataValue)) { 
                        $dimension = $row['dimension'];
                        $materials = $row['material'];
                    }
                    // echo '<pre>';print_r($materials);exit;
                    if ($dimension && $materials) {
                        // Fetch cust_stack_id where the order_id matches and also matches the dimension and material
                        $cust_stack_query = "
                                            SELECT distinct a1.answer AS id
                                            FROM form_via_form_main_audit_answers AS a1
                                            JOIN form_via_form_main_audit_answers AS a2 
                                                ON a1.fvf_main_audit_id = a2.fvf_main_audit_id
                                                AND a2.answer = '$order_id'
                                                AND a2.deleted_at = '0000-00-00 00:00:00'
                                            JOIN form_via_form_main_audit_answers AS a3 
                                                ON a1.fvf_main_audit_id = a3.fvf_main_audit_id
                                                AND a3.answer = '$dimension'
                                                AND a3.deleted_at = '0000-00-00 00:00:00'
                                            JOIN form_via_form_main_audit_answers AS a4 
                                                ON a1.fvf_main_audit_id = a4.fvf_main_audit_id
                                                AND a4.answer = '$materials'
                                                AND a4.deleted_at = '0000-00-00 00:00:00'
                                          JOIN form_via_form_main_audit_answers AS a5 
                                              ON a1.fvf_main_audit_id = a5.fvf_main_audit_id
                                              AND a5.fvf_main_field_id = $field_id
                                              AND a5.answer > 0
                                              AND a5.deleted_at = '0000-00-00 00:00:00'
                                            WHERE a1.fvf_main_form_id = $fvf_main_form_id 
                                                AND a1.fvf_main_field_id = $fvf_main_field_id 
                                                AND a1.deleted_at = '0000-00-00 00:00:00'
                                        ";
                                
                        // echo '<pre>';print_r($cust_stack_query);exit;
                        $cust_stack_result = $con->query($cust_stack_query);
                        $cust_stack_ids = [];
                    
                        while ($row2 = mysqli_fetch_assoc($cust_stack_result)) { 
                            $cust_stack_ids[] = array(
                                "fvf_main_field_option_id" => $row2['id'],
                                "fvf_main_field_type" => "DrillDown",
                                "fvf_main_field_option_name" => $row2['id'],
                                "fvf_main_field_option_value" => 0
                            );
                        } 
                        // echo '<pre>';print_r($cust_stack_ids);exit;
                        // Prepare response
                        $response = [
                            'status' => 'success',
                            'message' => "Drill down Cut Stack Id List",
                            'data' => ['records' => $cust_stack_ids]
                        ];
                    
                        http_response_code(200); //success
                        echo json_encode($response);
                        die;
                    }
                } else {
                    $response = [
                        'status' => 'error',
                        'message' => "Invalid Parameter",
                        'data' => ['records' => []]
                    ]; 
                    http_response_code(400); //Method not allowed
                    echo json_encode($response);die;
                }
            // } 
            // else{
            //     $response = [
            //         'status' => 'error',
            //         'message' => "It seems like your session has expired.",
            //     ];
            //     http_response_code(400); // Method Not Allowed
            //     echo json_encode($response);
            //     die;
            // }
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