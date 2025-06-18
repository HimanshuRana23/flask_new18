<?php

/*
Created By : @Maulik - 11062025
Created for : This validation API is designed to validate the Status Form based on the selected Sheet Weight and Sheet Quantity. It performs internal calculations to ensure the input values are within acceptable parameters, and based on the result, it updates relevant fields in the Cut Stack Master Form accordingly.
URL : https://flask.dfos.co/ITC/validation/validateStatusForm.php
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
            // file_put_contents('log.txt', $_POST, FILE_APPEND);
   
            //   echo '<pre>';print_r($linked_mainaudit_id);exit;
            $form_answer_json = $_POST["form_answer_json"];
            
            $answerArray = json_decode($form_answer_json); 
            // echo '<pre>';print_r($answerArray);exit;
            $enteredWeight = '';
            $enteredQty = '';
            $material = '';
            $dimension = '';
            $old_rejected_sheet = '';
            $auditId = '';

            $selectedCustStactId = $answerArray[0]->question_array[0][0]->answer;
            $dataType = $answerArray[0]->question_array[0][2]->answer;
            
    
            $query = "SELECT 
                ans.fvf_main_audit_id AS auditId,
                MAX(CASE WHEN ans1.fvf_main_field_id = 3252 THEN ans1.answer END) AS material,
                MAX(CASE WHEN ans1.fvf_main_field_id = 3259 THEN ans1.answer END) AS dimension,
                MAX(CASE WHEN ans1.fvf_main_field_id = 3254 THEN ans1.answer END) AS total_no_sheets,
                MAX(CASE WHEN ans1.fvf_main_field_id = 3258 THEN ans1.answer END) AS cut_stack_weight,
                MAX(CASE WHEN ans1.fvf_main_field_id = 3257 THEN ans1.answer END) AS quality_loss
                FROM form_via_form_main_audit_answers AS ans
                JOIN form_via_form_main_audit_answers AS ans1 
                ON ans.fvf_main_audit_id = ans1.fvf_main_audit_id
                WHERE ans.fvf_main_field_id = 3253 
                AND ans.answer = '$selectedCustStactId' 
                AND (ans.deleted_at IS NULL OR ans.deleted_at='0000-00-00 00:00:00')
                GROUP BY ans.fvf_main_audit_id";
                
            //   echo '<pre>';print_r($query);exit;
                            

            $dataValue = $con->query($query);
            if ($dataValue->num_rows > 0) {
                $res = mysqli_fetch_assoc($dataValue);
                $material = $res['material'];
                $dimension = $res['dimension'];
                $total_no_sheets = $res['total_no_sheets'];
                $cut_stack_weight = $res['cut_stack_weight'];
                $quality_loss = $res['quality_loss'];
                $auditId = $res['auditId'];
                // echo '<pre>';print_r($dimension);exit;
            }
            
            // fetch old rejected sheet value from form id 34 of selected cust stack id
            $oldrejectedSheets = "SELECT t2.answer AS rejected_sheet
                        FROM form_via_form_main_audit_answers t1
                        JOIN form_via_form_main_audit_answers t2 
                        ON t1.fvf_main_audit_id = t2.linked_mainaudit_id
                        WHERE t1.fvf_main_form_id = 33
                        AND t1.answer = '$selectedCustStactId'
                        AND t1.deleted_at = '0000-00-00 00:00:00'
                        AND t2.fvf_main_form_id = 34
                        AND t2.fvf_main_field_id = 3155
                        AND t2.deleted_at = '0000-00-00 00:00:00'";

            $oldRejectedSheets = $con->query($oldrejectedSheets);
            if ($oldRejectedSheets->num_rows > 0) {
                $res = mysqli_fetch_assoc($oldRejectedSheets);
                $old_rejected_sheet = $res['rejected_sheet'];
                // echo '<pre>';print_r($old_rejected_sheet);exit;
            }


            if(!empty($material) && !empty($dimension) && !empty($dataValue))
            {
                // Extract GSM (Remove first 6 characters from material)
                $GSM = substr($material, 6);
                $enteredValue = $answerArray[0]->question_array[0][2]->option_question_answer[0]->fvf_main_op_quest_answer;
                list($width, $height) = explode('x', str_replace(' ', '', $dimension));
                $dimension = floatval($width) * floatval($height);

                // $finalAmt should not be more then rejected value i.e either weight or quantity
                $finalAmt = $cut_stack_weight + $quality_loss;
                // echo '<pre>';print_r($finalAmt);exit;
 
                if($dataType == 'Sheet Wieght')
                {
                    if($enteredValue > $finalAmt){
                        http_response_code(400);
                        $response = [
                            'message' => "Entered weight ($enteredValue) should not be more than cust stack weight($cut_stack_weight) + quality loss($quality_loss) = ($finalAmt)",
                            'status' => "error"
                        ];
                        echo json_encode($response);
                        die;
                    }
                    
                    // echo '<pre>';print_r(1);exit;
                    // Final Calculation
                    $noOfSheets = ($enteredValue * pow(10, 7)) / ($GSM * $dimension) ;
                    // echo '<pre>';print_r($noOfSheets);exit;
                    
                    $finalSheets = round(($total_no_sheets + $old_rejected_sheet) - $noOfSheets);

                    $message = "GSM: $GSM, Dimension(l*b): $dimension, Number of Sheets: $noOfSheets, Final sheets after calculation: $finalSheets";
                    // echo '<pre>';print_r($message);exit;

                    // **Check if answer_remark is blank before updating**
                    $checkRemarkQuery = "SELECT fvf_main_ans_id, answer_remark FROM form_via_form_main_audit_answers
                                        WHERE fvf_main_form_id = 46
                                        AND fvf_main_field_id  = 3254
                                        AND answer = '$total_no_sheets'
                                        AND fvf_main_audit_id = $auditId
                                        AND deleted_at = '0000-00-00 00:00:00'";

                    $remarkResult = $con->query($checkRemarkQuery);
                    // echo '<pre>';print_r($remarkResult);exit;
                    if ($remarkResult->num_rows > 0) {
                        $remarkRow = mysqli_fetch_assoc($remarkResult);
                        // echo '<pre>';print_r($remarkRow);exit;

                        if (empty($remarkRow['answer_remark'])) {
                            $updateQuery = "UPDATE form_via_form_main_audit_answers 
                                SET answer_remark = '$total_no_sheets', answer = '$finalSheets'
                                WHERE fvf_main_form_id = 46
                                AND fvf_main_field_id = 3254
                                AND fvf_main_audit_id = $auditId
                                AND answer = '$total_no_sheets'
                                AND deleted_at = '0000-00-00 00:00:00'";

                            $con->query($updateQuery);
 
                        } else {    
                            $updateQuery = "UPDATE form_via_form_main_audit_answers 
                                SET answer = '$finalSheets'
                                WHERE fvf_main_form_id = 46
                                AND fvf_main_field_id = 3254
                                AND fvf_main_audit_id = $auditId
                                AND answer = '$total_no_sheets'
                                AND deleted_at = '0000-00-00 00:00:00'";
            
                            $con->query($updateQuery); 
                        }
                    }

                    // --------------------------------------------------------------------------------
                    // **Check if answer_remark is blank before updating field 3257 quality loss**
                    $checkRemarkQuery3257 = "SELECT fvf_main_ans_id, answer_remark FROM form_via_form_main_audit_answers
                                        WHERE fvf_main_form_id = 46
                                        AND fvf_main_field_id  = 3257
                                        AND answer = '$quality_loss'
                                        AND fvf_main_audit_id = $auditId
                                        AND deleted_at = '0000-00-00 00:00:00'";

                    $remarkResult3257 = $con->query($checkRemarkQuery3257);
                    // echo '<pre>';print_r($remarkResult);exit;
                    if ($remarkResult->num_rows > 0) {
                        $remarkRow3257 = mysqli_fetch_assoc($remarkResult3257);
                        // echo '<pre>';print_r($remarkRow);exit;

                        if (empty($remarkRow3257['answer_remark'])) {
                            $updateQuery3257 = "UPDATE form_via_form_main_audit_answers 
                                SET answer_remark = '$quality_loss', answer = '$enteredValue'
                                WHERE fvf_main_form_id = 46
                                AND fvf_main_field_id = 3257
                                AND fvf_main_audit_id = $auditId
                                
                                AND deleted_at = '0000-00-00 00:00:00'";

                            $con->query($updateQuery3257);
 
                        } else {    
                            $updateQuery3257 = "UPDATE form_via_form_main_audit_answers 
                                SET answer = '$enteredValue'
                                WHERE fvf_main_form_id = 46
                                AND fvf_main_field_id = 3257
                                AND fvf_main_audit_id = $auditId
                                
                                AND deleted_at = '0000-00-00 00:00:00'";
            
                            $con->query($updateQuery3257); 
                        }
                    }
                } else if($dataType == 'Sheet Quantity')
                {
                    // echo '<pre>';print_r($enteredValue > $finalAmt);exit;
                    if($enteredValue > $finalAmt){
                        http_response_code(400);
                        $response = [
                            'message' => "Entered Quantity ($enteredValue) should not be more than cust stack weight($cut_stack_weight) + quality loss($quality_loss) = ($finalAmt)",
                            'status' => "error"
                        ];
                        echo json_encode($response);
                        die;
                    } 
                    // echo '<pre>';print_r(444);exit;
                    // Final Calculation
                    $custStackWeight = ($GSM * $dimension * $enteredValue) / pow(10, 7);
                    $qualityLoss = $quality_loss - $custStackWeight;
                    $finalWeight = (int) $cut_stack_weight + (int) $qualityLoss;
                    // echo '<pre>';print_r($qualityLoss);exit;
                    $message = "GSM: $GSM, Dimension(l*b): $dimension, Enter qty: $enteredValue, Final weight after calculation: $custStackWeight, Quality Loss: $qualityLoss";
                    // echo '<pre>';print_r($message);exit;


                    $checkRemarkQuery = "SELECT fvf_main_ans_id, answer_remark FROM form_via_form_main_audit_answers
                                        WHERE fvf_main_form_id = 46
                                        AND fvf_main_field_id = 3258
                                        AND answer = '$cut_stack_weight'
                                        AND fvf_main_audit_id = $auditId
                                        AND deleted_at = '0000-00-00 00:00:00'";

                    $remarkResult = $con->query($checkRemarkQuery);
                    // echo '<pre>';print_r($remarkResult);exit;
                    if ($remarkResult->num_rows > 0) {
                        $remarkRow = mysqli_fetch_assoc($remarkResult);
                        // echo '<pre>';print_r($remarkRow);exit;

                        if (empty($remarkRow['answer_remark'])) {
                            $updateQuery = "UPDATE form_via_form_main_audit_answers 
                                SET answer_remark = '$cut_stack_weight', answer = '$finalWeight'
                                WHERE fvf_main_form_id = 46
                                AND fvf_main_field_id = 3258
                                AND fvf_main_audit_id = $auditId
                                -- AND answer = '$cut_stack_weight'
                                AND deleted_at = '0000-00-00 00:00:00'";

                            $con->query($updateQuery);

                        } else {    
                            $updateQuery = "UPDATE form_via_form_main_audit_answers 
                                SET answer = '$finalWeight'
                                WHERE fvf_main_form_id = 46
                                AND fvf_main_field_id = 3258
                                AND fvf_main_audit_id = $auditId
                                -- AND answer = '$cut_stack_weight'
                                AND deleted_at = '0000-00-00 00:00:00'";
            
                            $con->query($updateQuery);
                        }
                    }
                    // ------------------------check answer_remark for field_id 3257-----------------------------------------
                    $checkRemarkQuery3257 = "SELECT fvf_main_ans_id, answer_remark FROM form_via_form_main_audit_answers
                                        WHERE fvf_main_form_id = 46
                                        AND fvf_main_field_id = 3257
                                        -- AND answer = '$cut_stack_weight'
                                        AND fvf_main_audit_id = $auditId
                                        AND deleted_at = '0000-00-00 00:00:00'";

                    $remarkResult3257 = $con->query($checkRemarkQuery3257);
                    // echo '<pre>';print_r($remarkResult3257);exit;
                    if ($remarkResult3257->num_rows > 0) {
                        $remarkRow3257 = mysqli_fetch_assoc($remarkResult3257);
                        // echo '<pre>';print_r($remarkRow);exit;
                       
                        if (empty($remarkRow3257['answer_remark'])) {
                            $updateQuery3257 = "UPDATE form_via_form_main_audit_answers 
                                SET answer_remark = '$quality_loss', answer = '$qualityLoss'
                                WHERE fvf_main_form_id = 46
                                AND fvf_main_field_id = 3257
                                AND fvf_main_audit_id = $auditId
                                -- AND answer = '$cut_stack_weight'
                                AND deleted_at = '0000-00-00 00:00:00'";

                            $con->query($updateQuery3257);

                        } else {    
                            $updateQuery3257 = "UPDATE form_via_form_main_audit_answers 
                                SET answer = '$qualityLoss'
                                WHERE fvf_main_form_id = 46
                                AND fvf_main_field_id = 3257
                                AND fvf_main_audit_id = $auditId
                                -- AND answer = '$cut_stack_weight'
                                AND deleted_at = '0000-00-00 00:00:00'";
            
                            $con->query($updateQuery3257);
                        }
                    }
                }

                http_response_code(200);
                echo json_encode([
                    'message' => $message,
                    'status' => "success"
                ]);
                die;
            }     
        }
        else
        {
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