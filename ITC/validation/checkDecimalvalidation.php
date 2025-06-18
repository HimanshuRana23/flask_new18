<?php

/*
Created By : @Udit Prajapati - 07032025
Created for : This PHP script validates batch packaging, ensuring requested sheets don’t exceed stock. It verifies inputs, prevents over-packing, updates inventory, and maintains an audit trail, ensuring accuracy and real-time stock tracking.
URL : https://flask.dfos.co/ITC/validation/checkDecimalvalidation.php/46
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



$code = 200; //success
// Check if the request method is POST
	try { 
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {   

            $requestUri = $_SERVER['REQUEST_URI'];
            $scriptName = $_SERVER['SCRIPT_NAME'];
            $path = str_replace($scriptName, '', $requestUri);
            $form_answer_json = json_decode($_POST['form_answer_json']);
            file_put_contents('log2.txt', json_encode($form_answer_json), FILE_APPEND);
            $target_field_id  = 3274;
            $target_field_id2 = 3281;
            $cut_id = 3276; 
            $answer_value1 = null;
            $answer_value2 = null;
            $answer =  3271;
            $section_id = 412;

            $segments = explode('/', trim($path, '/'));
            $form_id = explode('?', $segments[0])[0];

           

// file_put_contents('log.txt', json_encode($form_id), FILE_APPEND);
             

                $field_3269_values = [];
                $field_3270_values = [];
    
                foreach ($form_answer_json as $section) {
                    foreach ($section->question_array as $questions) {
                        foreach ($questions as $question) {
                            if ($question->fvf_main_field_id == "3279") {
                                $field_3269_values[] = (int) $question->answer;
                            }
                            if ($question->fvf_main_field_id == "3280") {
                                $field_3270_values[] = (int) $question->answer;
                            }
                        }
                    }
                }



                // $updateRemarkQuery = "UPDATE form_via_form_main_audit_answers 
                //                              SET answer = 20
                //                                WHERE fvf_main_form_id = 46
                //                                AND fvf_main_field_id = 3271";
                //          $con->query($updateRemarkQuery);
              
    
                // If any value from 3269 is greater than any value from 3270, return an error

                // print_r($field_3269_values);
                // print_r($field_3270_values);
                // die;
                foreach ($field_3269_values as $key => $value_3269) {
                        if ($value_3269 > $field_3270_values[$key]) {
                            http_response_code(400);
                            echo json_encode([
                                'message' => "You cannot Pack more than $field_3270_values[$key] bundles for $value_3269 sheets",
                                'status' => "error"
                            ]);
                            die;
                        }   
                }
            
            
            foreach ($form_answer_json as $section) { 
                foreach ($section->question_array as $questions) { 
                    foreach ($questions as $question) { 
                        if ($question->fvf_main_field_id == $target_field_id) {
                            $answer_value1 = $question->answer;
                        }
                        if ($question->fvf_main_field_id == $target_field_id2) {
                            $answer_value2 = $question->answer;
                        }
                        // Stop loop early if both values are found
                        if ($answer_value1 !== null && $answer_value2 !== null) {
                            break 3;
                        }
                    }
                }
            }
            
            // Ensure values are numeric
            if (!is_numeric($answer_value1) || !is_numeric($answer_value2)) {
                http_response_code(400);
                echo json_encode(["message" => "Invalid numeric values", "status" => "error"]);
                die;
            }
            
            // Convert to numbers
            $answer_value1 = floatval($answer_value1);
            $answer_value2 = floatval($answer_value2);
            
            // Check for division by zero
            if ($answer_value2 == 0) {
                http_response_code(400);
                echo json_encode(["message" => "Division by zero is not allowed", "status" => "error"]);
                die;
            }
            
            // Perform division
            $result = $answer_value1 / $answer_value2;
            
            // Check if the result is a decimal
            if ($result != floor($result)) { 
                http_response_code(400);
                echo json_encode(["message" => "You cannot Pack more than $answer_value1 bundles for $answer_value2 sheets", "status" => "error"]);
                die;
            }




                   // Additional condition: If package_type is not 'BP', check 3269 > 3270
            $field_x = "3274";
            $field_y = "3280";
            $field_z = "3279";
        
        
            $field_x_values = [];
            $field_y_values = [];
            $field_z_values = [];
                
                foreach ($form_answer_json as $section) {
                    foreach ($section->question_array as $questions) {
                        foreach ($questions as $question) {
                            if ($question->fvf_main_field_id == $field_x) {
                                $field_x_values[] = (int) $question->answer;
                            }
                            if ($question->fvf_main_field_id == $field_y) {
                                $field_y_values[] = (int) $question->answer;
                            }

                            if ($question->fvf_main_field_id == $field_z) {
                                $field_z_values[] = (int) $question->answer;
                            }
                        }
                    }
                }
                
              

               

            
                // If any value from field_y is greater than any value from field_x, return an error
                $totalyvalue = 0;
                $totalyvalue2 =0;
                foreach ($field_y_values as $key =>  $value_y) {
                    if ($value_y < $field_z_values[$key]) {
                        http_response_code(400);
                        echo json_encode([
                            'message' => "Invalid sheet Number, Kindly Pack Less than (Total Number of sheets of selected cut stack) or select another cut stacks $value_y  = $field_x_values[$key]",
                            'status' => "error"
                        ]);
                        die;
                    }
                    $totalyvalue = $totalyvalue + $value_y;
                    $totalyvalue2 = $totalyvalue2 + $field_z_values[$key];
                }


                

                if ( $answer_value1 !=  $totalyvalue2) {
                    http_response_code(400);
                    echo json_encode([
                        'message' => "Kindly Pack $answer_value1 Reams or Reduce Number of reams To pack, or choose another cut stack",
                        'status' => "error"
                    ]);
                    die;
                }

                foreach ($form_answer_json as $section) { 

                    if ($section->fvf_section_id == $section_id) { 
                    
                        
                        foreach ($section->question_array as $questions) { 
                         
                            foreach ($questions as $question) { 
               
                                if ($question->fvf_main_field_id == $cut_id) {
                                    $cut_stack_id = $question->answer;
                                    $query = "SELECT ans1.answer AS id ,ans1.fvf_main_audit_id
                                            FROM form_via_form_main_audit_answers AS ans
                                            JOIN form_via_form_main_audit_answers AS ans1
                                                ON ans.fvf_main_audit_id = ans1.fvf_main_audit_id
                                            WHERE ans.fvf_main_form_id = $form_id
                                                AND ans.answer = '$cut_stack_id'
                                                AND ans.deleted_at = '0000-00-00 00:00:00'
                                                AND ans1.fvf_main_field_id = $answer
                                                AND ans1.deleted_at = '0000-00-00 00:00:00'";

                                         
           
                                    $dataValue = $con->query($query);
                                    if ($dataValue->num_rows > 0) { 
                                        $res = mysqli_fetch_assoc($dataValue);
                                        if (!empty($res)) {
                                            $cut_stack_ids[$cut_stack_id] = $res['id']; // Store count per stack
    
                                            $fvf_main_audit_id[$cut_stack_id] = $res['fvf_main_audit_id']; // Store count per stack
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                

            // **Check if total available count is enough**
            $total_available = array_sum($cut_stack_ids);

          
            if ($answer_value1 > $total_available) {
                http_response_code(400);
                $response = [
                    'message' => "Select Less than the reams contained within the cut stack",
                    'status' => "error"
                ];
                echo json_encode($response);
                die;
            }

            // **Deduct sheets from stacks**
            $remaining_sheets = $answer_value1;
            $i=0;
            foreach ($cut_stack_ids as $stack_id => $available) {
                if ($remaining_sheets <= 0) break; // Stop once we've deducted all needed sheets                

                $fvf_id = $fvf_main_audit_id[$stack_id]; 
                $deduct = min($remaining_sheets, $available);
                $remaining_sheets -= $deduct;
                $new_value =  (int) $available -  (int) $field_y_values[$i]; // Calculate the new answer count
            
                
               
                // **Check if answer_remark is blank before updating**
                $checkRemarkQuery = "SELECT answer_remark FROM form_via_form_main_audit_answers
                                     WHERE fvf_main_form_id = $form_id
                                     AND fvf_main_field_id = $answer
                                     AND answer = '$available'
                                     AND fvf_main_audit_id = $fvf_id
                                     AND deleted_at = '0000-00-00 00:00:00'";
            
                $remarkResult = $con->query($checkRemarkQuery);
                if ($remarkResult->num_rows > 0) {
                    $remarkRow = mysqli_fetch_assoc($remarkResult);

                    if (empty($remarkRow['answer_remark'])) {
                        // **Update answer_remark with the original count**
                        $updateRemarkQuery = "UPDATE form_via_form_main_audit_answers 
                                              SET answer_remark = '$available'
                                              WHERE fvf_main_form_id = $form_id
                                              AND fvf_main_field_id = $answer
                                              AND fvf_main_audit_id = $fvf_id
                                              AND answer = '$available'
                                              AND deleted_at = '0000-00-00 00:00:00'";
                        $con->query($updateRemarkQuery);
                    }
                }

                
            
                // **Update the answer count after deduction**
                $updateQuery = "UPDATE form_via_form_main_audit_answers 
                                SET answer = $new_value
                                WHERE fvf_main_form_id = $form_id
                                AND fvf_main_field_id = $answer
                                AND fvf_main_audit_id = $fvf_id
                                AND answer = '$available'
                                AND deleted_at = '0000-00-00 00:00:00'";
            
                $con->query($updateQuery);

                $query = "SELECT ans1.answer AS id 
                FROM form_via_form_main_audit_answers AS ans
                JOIN form_via_form_main_audit_answers AS ans1
                    ON ans.fvf_main_audit_id = ans1.fvf_main_audit_id
                WHERE ans.fvf_main_form_id = $form_id
                    AND ans.answer = '$stack_id'
                    AND ans.deleted_at = '0000-00-00 00:00:00'
                    AND ans1.fvf_main_field_id = 3255
                    AND ans1.deleted_at = '0000-00-00 00:00:00'";
                     $dataValue = $con->query($query);
                     if ($dataValue->num_rows > 0) { 
                         $res = mysqli_fetch_assoc($dataValue);
                        //spr = sheet per ream
                         $spr= $res['id'];

                         $query = "SELECT ans1.answer ,ans1.fvf_main_audit_id
                            FROM form_via_form_main_audit_answers AS ans
                            JOIN form_via_form_main_audit_answers AS ans1
                                ON ans.fvf_main_audit_id = ans1.fvf_main_audit_id
                            WHERE ans.fvf_main_form_id = $form_id
                                AND ans.answer = '$stack_id'
                                AND ans.deleted_at = '0000-00-00 00:00:00'
                                AND ans1.fvf_main_field_id = 3254
                                AND ans1.deleted_at = '0000-00-00 00:00:00'";
                                $dataValue = $con->query($query);
                                if ($dataValue->num_rows > 0) { 
                                    $res = mysqli_fetch_assoc($dataValue);
                                    $totalnoofsheet= $res['answer'];
                                    $total =  $spr *  $field_y_values[$i];
                                    $aud_id= $res['fvf_main_audit_id'];
                                    if($totalnoofsheet >= $total )
                                    {
                                        $minustotalnoofsheet = $totalnoofsheet - $total;
                                        $updateQuery = "UPDATE form_via_form_main_audit_answers 
                                        SET answer = $minustotalnoofsheet
                                        WHERE fvf_main_form_id = $form_id
                                        AND fvf_main_field_id = $answer
                                        AND fvf_main_audit_id = $aud_id
                                        AND answer = '$totalnoofsheet'
                                        AND deleted_at = '0000-00-00 00:00:00'";
                                         $con->query($updateQuery);
                                    }
                                    else
                                    {
                                            http_response_code(400);
                                            $response = [
                                                'message' => "The total number of sheets ($totalnoofsheet) is not greater than the required total ($total)",
                                                'status' => "error"
                                            ];
                                            echo json_encode($response);
                                            die;
                                    }

                                }
                     }

            
                     $i++;

            }

            
            // If all checks pass, proceed with success response
            echo json_encode(["message" => "Calculation successful", "status" => "success", "result" => $result]);
            
        

        } else { 
          
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