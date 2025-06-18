<?php

/*
Created By : @maulik - 20032025
Created for : Dynamic API for to fetch data based on get api
*/

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection file
include './connection/connection.php';

// Include the authorization file
//include '../authorization/authorization.php';

// Set response headers
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => "Method not allowed."]);
    die;
}

try {
    // Validate required parameters
    if (!isset($_GET['table']) || !isset($_GET['columns'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing required parameters: table or columns']);
        die;
    }
    
    $option_id = $_POST['option_id'] ?? '';
    $table = $_GET['table'];
    $match_column = $_GET['match_column'];
    // $columns = $_GET['columns']; // Expected as comma-separated values
    $conditions = isset($_GET['conditions']) ? json_decode($_GET['conditions'], true) : [];
    $deleted_flag = isset($_GET['deleted_flag']) ? intval($_GET['deleted_flag']) : 0; // 1 = Include deleted_at condition
    $group_by = isset($_GET['group_by']) ? trim($_GET['group_by']) : '';

    $columns = trim($_GET['columns']);
    $columns = preg_replace('/[{}]/', '', $columns); // Removes { and }
    $columns_array = array_map('trim', explode(',', $columns));
    $columns_for_sql = implode(',', $columns_array);

    // Split columns into array @maulik@17032025
    $columnsArray = explode(',', $columns);

    // Trim spaces from each column name
    $columnsArray = array_map('trim', $columnsArray);
    // Check if any column already has 'AS id' (case-insensitive)
    $hasAliasId = false;
    foreach ($columnsArray as $col) {
        if (stripos($col, ' as id') !== false) {
            $hasAliasId = true;
            break;
        }
    }

    // If no alias 'AS id' found, modify the first column
    if (!$hasAliasId && isset($columnsArray[0])) {
        $columnsArray[0] .= ' AS id';
    }

    // Rebuild the columns string
    $columns_for_sql = implode(', ', $columnsArray); 
    // end@maulik

    // Start building the query
    $query = "SELECT $columns_for_sql FROM `$table`";
    $params = [];
    $types = '';

    
    // if (!empty($conditions)) {
    //     $query .= " WHERE ";
    //     $conditionStrings = [];
        
    //     foreach ($conditions as $column => $value) {
    //         if (preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
    //             $conditionStrings[] = "$column = ?";
    //             $params[] = $value;
    //             $types .= 's'; // Assuming all values are strings, modify if needed
    //         }
    //     }

    //     // Add all conditions
    //     $query .= implode(' AND ', $conditionStrings);
    // }

    if (!empty($conditions)) {
        $query .= " WHERE ";
        $conditionStrings = [];
    
        foreach ($conditions as $column => $value) {
            if (preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
                $valuesToProcess = is_array($value) ? $value : [$value];

                foreach ($valuesToProcess as $val) {
                    // Normalize string
                    $val = is_string($val) ? trim($val) : $val;

                    // Handle IS NULL or IS NOT NULL
                    if (is_string($val) && strtolower($val) === 'is null') {
                        $conditionStrings[] = "$column IS NULL";
                        continue;
                    } elseif (is_string($val) && strtolower($val) === 'is not null') {
                        $conditionStrings[] = "$column IS NOT NULL";
                        continue;
                    } elseif (is_string($val) && (strtolower($val) === 'is blank' || strtolower($val) === 'is empty')) {
                        $conditionStrings[] = "$column = ''";
                        continue;
                    } elseif (is_string($val) && (strtolower($val) === 'is not blank' || strtolower($val) === 'is not empty')) {
                        $conditionStrings[] = "$column != ''";
                        continue;
                    }

                    // Handle specific columns
                    if (in_array($column, ['user_modules', 'user_id', 'user_role', 'user_admin_modules', 'user_resources', 'employee_id'])) {
                        if (strpos($val, ',') !== false) {
                            $valuesArray = explode(',', $val);
                            if (in_array($column, ['user_modules', 'user_admin_modules', 'user_resources'])) {
                                $orConditions = [];
                                foreach ($valuesArray as $v) {
                                    $orConditions[] = "FIND_IN_SET(?, $column)";
                                    $params[] = trim($v);
                                    $types .= 's';
                                }
                                $conditionStrings[] = '(' . implode(' OR ', $orConditions) . ')';
                            } else {
                                $placeholders = implode(',', array_fill(0, count($valuesArray), '?'));
                                $conditionStrings[] = "$column IN ($placeholders)";
                                foreach ($valuesArray as $v) {
                                    $params[] = trim($v);
                                    $types .= 's';
                                }
                            }
                        } else {
                            if (in_array($column, ['user_modules', 'user_admin_modules', 'user_resources'])) {
                                $conditionStrings[] = "FIND_IN_SET(?, $column)";
                            } else {
                                $conditionStrings[] = "$column = ?";
                            }
                            $params[] = $val;
                            $types .= 's';
                        }

                    } elseif (in_array($column, ['created_at', 'updated_at'])) {
                        $conditionStrings[] = "DATE($column) = ?";
                        $params[] = $val;
                        $types .= 's';

                    } else {
                        // Support for != and default =
                        if (is_string($val) && strpos($val, '!=') === 0) {
                            $actualValue = trim(substr($val, 2));
                            $conditionStrings[] = "$column != ?";
                            $params[] = $actualValue;
                            $types .= 's';
                        } else {
                            $conditionStrings[] = "$column = ?";
                            $params[] = $val;
                            $types .= 's';
                        }
                    }
                }
            }


            // if (preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
            //     // Handle IS NULL or IS NOT NULL
            //     if (is_string($value) && strtolower($value) === 'is null') {
            //         $conditionStrings[] = "$column IS NULL";
            //         continue;
            //     } elseif (is_string($value) && strtolower($value) === 'is not null') {
            //         $conditionStrings[] = "$column IS NOT NULL";
            //         continue;
            //     } elseif (is_string($value) && (strtolower($value) === 'is blank' || strtolower($value) === 'is empty')) {
            //         $conditionStrings[] = "$column = ''";
            //         continue;
            //     } elseif (is_string($value) && (strtolower($value) === 'is not blank' || strtolower($value) === 'is not empty')) {
            //         $conditionStrings[] = "$column != ''";
            //         continue;
            //     }

            //     if (in_array($column, ['user_modules','user_id','user_role','user_admin_modules','user_resources','employee_id'])) {
                    
            //         if (strpos($value, ',') !== false) {
            //             $valuesArray = explode(',', $value);

            //             if (in_array($column, ['user_modules','user_admin_modules','user_resources'])) {
            //                 // Multiple FIND_IN_SET() joined with OR
            //                 $orConditions = [];
            //                 foreach ($valuesArray as $v) {
            //                     $orConditions[] = "FIND_IN_SET(?, $column)";
            //                     $params[] = trim($v);
            //                     $types .= 's';
            //                 }
            //                 $conditionStrings[] = '(' . implode(' OR ', $orConditions) . ')';
            //             } else {
            //                 // IN clause for normal values
            //                 $placeholders = implode(',', array_fill(0, count($valuesArray), '?'));
            //                 $conditionStrings[] = "$column IN ($placeholders)";
            //                 foreach ($valuesArray as $v) {
            //                     $params[] = trim($v);
            //                     $types .= 's';
            //                 }
            //             }
            //         } else {
            //             // Single value
            //             if (in_array($column, ['user_modules','user_admin_modules','user_resources'])) {
            //                 $conditionStrings[] = "FIND_IN_SET(?, $column)";
            //             } else {
            //                 $conditionStrings[] = "$column = ?";
            //             }
            //             $params[] = $value;
            //             $types .= 's';
            //         }

            //     } else if (in_array($column, ['created_at','updated_at'])) {
            //         $conditionStrings[] = "DATE($column) = ?";
            //         $params[] = $value;
            //         $types .= 's';

            //     } else {
            //         // Support for != condition
            //         if (is_string($value) && strpos($value, '!=') === 0) {
            //             $actualValue = trim(substr($value, 2));
            //             $conditionStrings[] = "$column != ?";
            //             $params[] = $actualValue;
            //             $types .= 's';
            //         } else {
            //             $conditionStrings[] = "$column = ?";
            //             $params[] = $value;
            //             $types .= 's';
            //         }
            //     }
            // }
        }
        // foreach ($conditions as $column => $value) {
        //     if (preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
        //         if (is_string($value) && strtolower(trim($value)) === 'is null') {
        //             $conditionStrings[] = "$column IS NULL";
        //         } else {
        //             $conditionStrings[] = "$column = ?";
        //             $params[] = $value;
        //             $types .= 's'; // Adjust type as needed
        //         }
        //     }
        // }
    
        // Add all conditions
        $query .= implode(' AND ', $conditionStrings);
    }

    // Add deleted_at condition if flag is set
    if ($deleted_flag === 1) {
        $query .= !empty($conditions) ? " AND deleted_at = '0000-00-00 00:00:00'" : " WHERE deleted_at = '0000-00-00 00:00:00'";
    }
    if ($option_id) {
        $query .= ($deleted_flag) ? " AND $match_column = '" . $option_id . "'" : " WHERE $match_column = '" . $option_id . "'";
    }
    if (!empty($group_by)) {
        $query .= " GROUP BY `$group_by`";
    }

    // Function to replace ? placeholders with actual values
    function getFinalQuery($query, $params) {
        if (empty($params)) return $query;

        foreach ($params as $param) {
            // Escape single quotes for direct execution
            $value = is_numeric($param) ? $param : "'" . addslashes($param) . "'";
            $query = preg_replace('/\?/', $value, $query, 1);
        }

        return $query;
    }
    
    // Debugging: Generate the full SQL query
    $finalQuery = getFinalQuery($query, $params);
    // echo '<pre>';print_r($finalQuery);exit;

    // Prepare and execute the query
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        // Default id
        // echo '<pre>';print_r($row);
        // $option_id_value = $row['id'] ?? '';
        // echo '<pre>';print_r($option_id_value);
        // Dynamically build the option name
        $columns_array = array_map('trim', explode(',', $columns));
        $option_name_parts = [];
        foreach ($columns_array as $col) {
            // echo '<pre>';print_r($row[$col]);exit;
            if (isset($row[$col])) {
                $option_name_parts[] = $row[$col];
            }
        }
      
        $data[] = [
            // "fvf_main_field_option_id" => !empty($option_id_value) ? $option_id_value : 0,
            "fvf_main_field_option_id" => $row['id'] ?? 0,
            "fvf_main_field_type" => "DrillDown",
            "fvf_main_field_option_name" => isset($option_name_parts) ? implode(' ', $option_name_parts) : '',
            // "fvf_main_field_option_name" => $row['firstname'] . (isset($row['lastname']) ? ' '.$row['lastname'] : '') ?? '',
            "fvf_main_field_option_value" => 0
        ];
    }
    // Prepare response
    if(!empty($data)){
        $response = [
            'status' => 'success',
            'message' => "Drill down List",
            'data' => ['records' => $data]
        ];
    } else {
        $response = [
            'status' => 'error',
            'message' => "No data found",
            'data' => ['records' => []]
        ];
    }
    

    http_response_code(200);
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

