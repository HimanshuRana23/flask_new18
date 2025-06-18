<?php

/*
Created By : @Udit Prajapati - 06032025
Created for : Dynamic API
*/

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection file
include './connection/connection.php';

// Include the authorization file
// include '../authorization/authorization.php';
//include '../authorization/authorization123.php';

// Set response headers
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

// Check if the request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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
    

    $table = $_GET['table'];
    $columns = $_GET['columns']; // Expected as comma-separated values
    $conditions = isset($_GET['conditions']) ? json_decode($_GET['conditions'], true) : [];
    $deleted_flag = isset($_GET['deleted_at']) ? intval($_GET['deleted_at']) : 0; // 1 = Include deleted_at condition
    $group_by = isset($_GET['group_by']) ? trim($_GET['group_by']) : ''; // 1 = Include deleted_at condition
    //file_put_contents('log_new.txt', print_r($_POST,true), FILE_APPEND);
    //print_r($conditions);
    // Validate table and columns (allow only alphanumeric and underscores to prevent SQL injection)
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table) || !preg_match('/^[a-zA-Z0-9_, ]+$/', $columns)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid table name or columns']);
        die;
    }

    
    // Split columns into array @maulik@17032025
    $columnsArray = explode(',', $columns);

    // Trim spaces from each column name
    $columnsArray = array_map('trim', $columnsArray);
    // Alias the first column
    // Alias the first column as 'id'
    if (isset($columnsArray[0])) {
        $columnsArray[0] = $columnsArray[0] . ' AS id';
    }

    // Rebuild the columns string
    $columns = implode(', ', $columnsArray); 
    // end@maulik
    
    // Start building the query
    $query = "SELECT distinct $columns FROM `$table`";
    // echo '<pre>';print_r($query);exit;
    $params = [];
    $types = '';

    if (!empty($conditions)) {
        $query .= " WHERE ";
        $conditionStrings = [];
        
            foreach ($conditions as $column => $value) {
                if (preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
                    // Handle IS NULL or IS NOT NULL
                    if (is_string($value) && strtolower($value) === 'is null') {
                        $conditionStrings[] = "$column IS NULL";
                        continue;
                    } elseif (is_string($value) && strtolower($value) === 'is not null') {
                        $conditionStrings[] = "$column IS NOT NULL";
                        continue;
                    } elseif (is_string($value) && (strtolower($value) === 'is blank' || strtolower($value) === 'is empty')) {
                        $conditionStrings[] = "$column = ''";
                        continue;
                    } elseif (is_string($value) && (strtolower($value) === 'is not blank' || strtolower($value) === 'is not empty')) {
                        $conditionStrings[] = "$column != ''";
                        continue;
                    }

                    if (in_array($column, ['user_modules','user_id','user_role','user_admin_modules','user_resources','employee_id','assigned_users'])) {
                        
                        if (strpos($value, ',') !== false) {
                            $valuesArray = explode(',', $value);

                            if (in_array($column, ['user_modules','user_admin_modules','user_resources','assigned_users'])) {
                                // Multiple FIND_IN_SET() joined with OR
                                $orConditions = [];
                                foreach ($valuesArray as $v) {
                                    $orConditions[] = "FIND_IN_SET(?, $column)";
                                    $params[] = trim($v);
                                    $types .= 's';
                                }
                                $conditionStrings[] = '(' . implode(' OR ', $orConditions) . ')';
                            } else {
                                // IN clause for normal values
                                $placeholders = implode(',', array_fill(0, count($valuesArray), '?'));
                                $conditionStrings[] = "$column IN ($placeholders)";
                                foreach ($valuesArray as $v) {
                                    $params[] = trim($v);
                                    $types .= 's';
                                }
                            }
                        } else {
                            // Single value
                            if (in_array($column, ['user_modules','user_admin_modules','user_resources','assigned_users'])) {
                                $conditionStrings[] = "FIND_IN_SET(?, $column)";
                            } else {
                                $conditionStrings[] = "$column = ?";
                            }
                            $params[] = $value;
                            $types .= 's';
                        }

                    } else if (in_array($column, ['created_at','updated_at'])) {
                        $conditionStrings[] = "DATE($column) = ?";
                        $params[] = $value;
                        $types .= 's';

                    } else {
                        $conditionStrings[] = "$column = ?";
                        $params[] = $value;
                        $types .= 's';
                    }
                }
            }
        // foreach ($conditions as $column => $value) {
        //     if (preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
        //         // if ($column === 'user_modules') {
        //         if (in_array($column, ['user_modules','user_id','user_role','user_admin_modules','user_resources','employee_id'])) {
        //             // Check if it's a comma-separated list
        //             if (strpos($value, ',') !== false) {
        //                 // IN clause
        //                 $valuesArray = explode(',', $value);
        //                 $placeholders = implode(',', array_fill(0, count($valuesArray), '?'));
        //                 $conditionStrings[] = "$column IN ($placeholders)";
        //                 foreach ($valuesArray as $v) {
        //                     $params[] = trim($v);
        //                     $types .= 's'; // Adjust type if needed
        //                 }
        //             } else {
        //                 // Single value: use FIND_IN_SET
        //                 $conditionStrings[] = "FIND_IN_SET(?, $column)";
        //                 $params[] = $value;
        //                 $types .= 's';
        //             }
        //         } else if (in_array($column, ['created_at','updated_at'])){
        //             $conditionStrings[] = "DATE($column) = ?";
        //             $params[] = $value;
        //             $types .= 's';

        //         } else {
        //             // Default equality condition
        //             $conditionStrings[] = "$column = ?";
        //             $params[] = $value;
        //             $types .= 's';
        //         }
        //     }
        // }
    
        $query .= implode(' AND ', $conditionStrings);
    }

    // Add deleted_at condition if flag is set
    if ($deleted_flag === 1) {
        $query .= !empty($conditions) ? " AND deleted_at ='0000-00-00 00:00:00'" : " WHERE deleted_at = '0000-00-00 00:00:00'";
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
    // echo $finalQuery;
    // die;
    
    // echo '<pre>';print_r($finalQuery);exit;
    // Prepare and execute the query
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        http_response_code(400);
        die(json_encode([
            'status' => 'error',
            'message' => 'Query execution failed:'.$conn->error
        ]));
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    // echo '<pre>';print_r($data);exit;
    if(empty($data)){
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'No Data Found..'
        ]);
        die;
    }
    http_response_code(200);
    echo json_encode($data);
    die;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
