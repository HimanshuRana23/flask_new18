<?php

/*
Created By : @Udit Prajapati - 07032025
Created for : This API facilitates batch validation and sheet packaging management in a manufacturing or printing process. It ensures that the number of sheets to be packed does not exceed the available stock while preventing duplicate selections of the same cut stack.
URL : https://flask.dfos.co/ITC/validation/validateSheettoPack.php
Type : Validation
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


          //  print_r( $form_answer_json);
           // die;

          file_put_contents('log.txt', json_encode($form_answer_json), FILE_APPEND);

        //   http_response_code(400);
        //             echo json_encode([
        //                 'message' => "Invalid sheet Number, Kindly Pack Less than (Total Number of sheets of selected cut stack) or select another cut stacks 6666",
        //                 'status' => "error"
        //             ]);
        //             die;

            
            $segments = explode('/', trim($path, '/'));
            $form_id = $segments[0];
            $package_type = explode('?', $segments[1])[0];
            $cut_id = $package_type == 'BP' ?  "3248" : 3266; 
            $target_field_id = $package_type == 'BP' ? "3232" : "3264"; // Field ID for "Enter Number of sheets to pack"
            $answer = $package_type == 'BP' ? 3254 : 3271;
            $section_id = $package_type == 'BP' ? 408 : 410;

            $answer_value = null;
            $field_3270 = null;
            $field_3269 = null;
        
            $answer_value = null;
            foreach ($form_answer_json as $section) { 
                foreach ($section->question_array as $questions) { 
                    foreach ($questions as $question) { 
                        if ($question->fvf_main_field_id == $target_field_id) {
                            $answer_value = $question->answer;
                            break 3;
                        }
                    }
                }
            }

         

            
            
            
               // Additional condition: If package_type is not 'BP', check 3269 > 3270
         if ($package_type == 'BP') {
                $field_x = "3309";
                $field_y = "3310";
            } else {
                $field_x = "3270";
                $field_y = "3269";
            }
            
            $field_x_values = [];
            $field_y_values = [];
            
            foreach ($form_answer_json as $section) {
                foreach ($section->question_array as $questions) {
                    foreach ($questions as $question) {
                        if ($question->fvf_main_field_id == $field_x) {
                            $field_x_values[] = (int) $question->answer;
                        }
                        if ($question->fvf_main_field_id == $field_y) {
                            $field_y_values[] = (int) $question->answer;
                        }
                    }
                }
            }

          
            
            // If any value from field_y is greater than any value from field_x, return an error
            $totalyvalue = 0;
            foreach ($field_y_values as $key => $value_y) {
                // if Pick Sheets from Cut stack is  greater then total Sheets Per cut stack
                if ($value_y > $field_x_values[$key]) {
                    http_response_code(400);
                    echo json_encode([
                        'message' => "Select Less than the reams contained within the cut stack",
                        'status' => "error"
                    ]);
                    die;
                }
                $totalyvalue = $totalyvalue + $value_y;
            }

            
            // if Enter Number of sheets to pack is  greater then  Pick Sheets from Cut stack total sum
            if ( $answer_value != $totalyvalue) {

                $msg = "Kindly Pack $answer_value Reams or Reduce Number of reams To pack, or choose another cut stack ";
                if ($package_type == 'BP') {
                
                 $msg = "Kindly Pack $answer_value Sheets or Reduce Number of Sheets To pack, or choose another cut stack ";
                
                }
                http_response_code(400);
                echo json_encode([
                    'message' => $msg,
                    'status' => "error"
                ]);

                die;
            }


            // $updateRemarkQuery = "UPDATE form_via_form_main_audit_answers 
            //                                   SET answer = 500
            //                                   WHERE fvf_main_form_id = 46
            //                                   AND fvf_main_field_id = 3255";
            //             $con->query($updateRemarkQuery);
        
            $cut_stack_ids = []; // Array to hold cut stack IDs and their counts
            $fvf_main_audit_id = []; // Array to hold cut stack IDs and their counts

            // echo '<pre>';
            //  print_r($form_answer_json);
            //  die;
        
            $processed_cut_stack_ids = [];
            // Fetch stack IDs and their available counts
            foreach ($form_answer_json as $section) { 

                if ($section->fvf_section_id == $section_id) { 
                
                    
                    foreach ($section->question_array as $questions) { 
                     
                                foreach ($questions as $question) { 
                    
                                if ($question->fvf_main_field_id == $cut_id) {
                                            $cut_stack_id = $question->answer;

                                            // Check if the cut_stack_id has already been processed
                                    if (in_array($cut_stack_id, $processed_cut_stack_ids)) {
                                        http_response_code(400);
                                        echo json_encode([
                                            'message' => "Kindly Pick {$cut_stack_id} once",
                                            'status' => "error"
                                        ]);
                                        die;
                                    }

                                // Store cut_stack_id to track duplicates
                               $processed_cut_stack_ids[] = $cut_stack_id;

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


            // if (true) {
            //     http_response_code(400);
            //     $response = [
            //         'message' => "total_available=".$query ,
            //         'status' => "error"
            //     ];
            //     echo json_encode($response);
            //     die;
            // }
           
            
           
            if ($answer_value > $total_available) {
                http_response_code(400);
                $response = [
                    'message' => "Invalid sheet Number, Kindly Pack Less than (Total Number of sheets of selected cut stack) or select another cut stack. 222",
                    'status' => "error"
                ];
                echo json_encode($response);
                die;
            }

            // **Deduct sheets from stacks**
            $remaining_sheets = $answer_value;
            $i=0;

          

            foreach ($cut_stack_ids as $stack_id => $available) {
//                if ($remaining_sheets <= 0) break; // Stop once we've deducted all needed sheets

                

                $fvf_id = $fvf_main_audit_id[$stack_id];               
                $deduct = min($remaining_sheets, $available);
    
                $remaining_sheets -= $deduct;
    
                  if($package_type != 'BP')
                  {

                  
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
                                                    'message' => "The total number of sheets ($totalnoofsheet) is not greater than the required total ($total) audit id = $aud_id",
                                                    'status' => "error"
                                                ];
                                                echo json_encode($response);
                                                die;
                                        }
                                        
                                    }
                         }

                  }
              
                 
                $new_value =  (int) $available -  (int) $field_y_values[$i]; // Calculate the new answer count
             //   print_r($new_value);
               
            
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
                $i++;
            }
        
            // **Success response**
            http_response_code(200);
            $response = [
                'message' => "Record updated successfully",
                'status' => "success"
            ];
            echo json_encode($response);
            die;
        
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