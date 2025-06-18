<?php

/*
Created By : @Udit Prajapati - 25032025
Created for : This API validates sheet weights and it should not be more then raw material weight and based on calculation the field is updated in product session form in sheet production module
URL : https://flask.dfos.co/ITC/validation/sheetProductionvalidation.php
Type : Validation
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



$code = 200; //success
// Check if the request method is POST
	try { 
		if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
    
          file_put_contents('log.txt', $_POST, FILE_APPEND);
   
          $linked_mainaudit_id = $_POST['linked_mainaudit_id'];
        //   echo '<pre>';print_r($linked_mainaudit_id);exit;

        $answerArray = json_decode($_POST['form_answer_json'],true); 

        $no_of_sheet = $answerArray[0]['question_array'][0][0]['answer']; 
        // echo '<pre>';print_r($no_of_sheet);exit;
        
          
        //   http_response_code(400);
        //   echo json_encode([
        //       'message' => "SELECT  answer
        //         FROM form_via_form_main_audit_answers 
        //         WHERE fvf_main_audit_id = $linked_mainaudit_id AND fvf_main_field_id = 3165
        //         AND deleted_at = '0000-00-00 00:00:00'",
        //       'status' => "error"
        //   ]);
        //   die;


            
            //$segments = explode('/', trim($path, '/'));
                $loadedQuantityWieght = '';
                // Get total weight
                $loadedQuantityWieghtQuery = "SELECT answer as loaded_qty_weight
                FROM form_via_form_main_audit_answers 
                WHERE fvf_main_audit_id = $linked_mainaudit_id AND fvf_main_field_id = 3315
                AND deleted_at = '0000-00-00 00:00:00'";

                $queryResult = $con->query($loadedQuantityWieghtQuery);
                if ($queryResult->num_rows > 0) {
                    $resNew = mysqli_fetch_assoc($queryResult);
                    $loadedQuantityWieght = $resNew['loaded_qty_weight'];
                }
                // echo '<pre>';print_r($loadedQuantityWieght);exit;
                $query = "SELECT  answer
                FROM form_via_form_main_audit_answers 
                WHERE fvf_main_audit_id = $linked_mainaudit_id AND fvf_main_field_id = 3165
                AND deleted_at = '0000-00-00 00:00:00'";

                $dataValue = $con->query($query);
                if ($dataValue->num_rows > 0) {  
                
                    $res = mysqli_fetch_assoc($dataValue);

                    $batchID = $res['answer'];
                    $batchIDs = explode(',',  $batchID);
                    $base_qty_recvd =0;
                    $material = '';
                    $actual_reel_width = '';
                    $width = '';
                    $length = '';

                    foreach($batchIDs as $batchID )
                    {

                        //2699 matirial GSM


                        // $query = "SELECT  fvf_main_audit_id,
                        // FROM form_via_form_main_audit_answers 
                        // WHERE  fvf_main_field_id = 2703 and answer = $batchID
                        // AND deleted_at = '0000-00-00 00:00:00'";

                        $query = "SELECT 
                        ans.fvf_main_audit_id AS auditId,
                        MAX(CASE WHEN ans1.fvf_main_field_id = 2699 THEN ans1.answer END) AS material,
                        MAX(CASE WHEN ans1.fvf_main_field_id = 2707 THEN ans1.answer END) AS actual_reel_width,
                        MAX(CASE WHEN ans1.fvf_main_field_id = 2708 THEN ans1.answer END) AS width,
                        MAX(CASE WHEN ans1.fvf_main_field_id = 2709 THEN ans1.answer END) AS length,
                        MAX(CASE WHEN ans1.fvf_main_field_id = 2705 THEN ans1.answer END) AS base_qty_recvd
                      FROM form_via_form_main_audit_answers AS ans
                      JOIN form_via_form_main_audit_answers AS ans1 
                        ON ans.fvf_main_audit_id = ans1.fvf_main_audit_id
                      WHERE ans.fvf_main_field_id = 2703 
                        AND ans.answer = '".$batchID."' 
                        AND (ans.deleted_at IS NULL OR ans.deleted_at='0000-00-00 00:00:00')
                      GROUP BY ans.fvf_main_audit_id";
                      
                    //   echo '<pre>';print_r($query);exit;
                                    

                        $dataValue = $con->query($query);
                        if ($dataValue->num_rows > 0) {
                            $res = mysqli_fetch_assoc($dataValue);
                            $material = $res['material'];
                            $actual_reel_width = $res['actual_reel_width'];
                            $width = $res['width'];
                            $length = $res['length'];
                            $base_qty_recvd = $base_qty_recvd + $res['base_qty_recvd'];
                        }

                    }

                 

                }
                // echo '<pre>';print_r($material);exit;
                if(!empty($base_qty_recvd) && !empty($material) && !empty($actual_reel_width) && !empty($width) && !empty($length))
                {

                    // Extract GSM (Remove first 5 characters from material)
                    $gsm = substr($material, 5);

                    // Remove leading '0' if present
                    if (!empty($gsm) && $gsm[0] === '0') {
                        $gsm = ltrim($gsm, '0');
                    }
                    // Length remains unchanged
                    $final_length = $length;

                    // Calculate breadth
                    $breadth = round($actual_reel_width / $width) * $width;
                    
                    // Dimension calculate
                    $dimension = $final_length * $breadth;
                    // Number of sheets (provided by user) $no_of_sheet

                    // Final Calculation
                    $final_value = ($gsm * $dimension * $no_of_sheet) / pow(10, 7); // final weight of sheet

                     
                    $message = "GSM: $gsm, Length: $final_length, Breadth: $breadth, Dimension(l*b): $dimension, Number of Sheets: $no_of_sheet, Final Weight of entered sheet: $final_value, base_qty_recvd = $base_qty_recvd";
                    // update total weight
                    $updatedWeight = (int) $loadedQuantityWieght - (int) $final_value;
                    // echo '<pre>';print_r($updatedWeight);exit;

                    // **Check if answer_remark is blank before updating**
                    $checkRemarkQuery = "SELECT answer_remark FROM form_via_form_main_audit_answers
                                        WHERE fvf_main_form_id = 36
                                        AND fvf_main_field_id = 3315
                                        AND answer = '$loadedQuantityWieght'
                                        AND fvf_main_audit_id = $linked_mainaudit_id
                                        AND deleted_at = '0000-00-00 00:00:00'";

                    $remarkResult = $con->query($checkRemarkQuery);

                    if ($remarkResult->num_rows > 0) {
                        $remarkRow = mysqli_fetch_assoc($remarkResult);

                        if (empty($remarkRow['answer_remark'])) {
                            $updateQuery = "UPDATE form_via_form_main_audit_answers 
                                SET answer_remark = '$loadedQuantityWieght', answer = '$updatedWeight'
                                WHERE fvf_main_form_id = 36
                                AND fvf_main_field_id = 3315
                                AND fvf_main_audit_id = $linked_mainaudit_id
                                AND answer = '$loadedQuantityWieght'
                                AND deleted_at = '0000-00-00 00:00:00'";

                            $con->query($updateQuery);
                        } else {    
                            $updateQuery = "UPDATE form_via_form_main_audit_answers 
                                SET answer = '$updatedWeight'
                                WHERE fvf_main_form_id = 36
                                AND fvf_main_field_id = 3315
                                AND fvf_main_audit_id = $linked_mainaudit_id
                                AND answer = '$loadedQuantityWieght'
                                AND deleted_at = '0000-00-00 00:00:00'";
            
                            $con->query($updateQuery);
                        }
                    }

                    

                    if($final_value >  $base_qty_recvd)
                     {
                        http_response_code(400);
                        echo json_encode([
                            'message' => "no of sheet weight ($final_value) can not be more than raw material weight ($base_qty_recvd)",
                            'status' => "error"
                        ]);
                    } else if ($final_value > $loadedQuantityWieght){
                        http_response_code(400);
                        echo json_encode([
                            'message' => "Raw materials has been consumed",
                            'status' => "error"
                        ]);
                    }
                    else
                    {
                        http_response_code(200);
                        echo json_encode([
                            'message' => $message,
                            'status' => "success"
                        ]);

                    }
                    die;

                } 
            

                   
            
        }
        else
        {
               // Additional condition: If package_type is not 'BP', check 3269 > 3270
       
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => "Method not allowed."]);
            die;
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