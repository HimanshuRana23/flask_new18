<?php

/*
Created By : @Udit Prajapati - 06032025
Created for : Dynamic API
*/

// -----------------------------------------------------------------------------
// This script exposes a single endpoint that can query any table from the
// database. The table to query, columns to select and optional conditions are
// supplied as GET parameters. The result set is returned in JSON format. It is
// intended as a generic API that can power multiple reports or UI widgets
// without the need to write a dedicated API for each use case.
// -----------------------------------------------------------------------------

// -----------------------------------------------------------------------------
// Enable error reporting during development. These lines ensure that PHP will
// output any runtime warnings or errors directly in the response, which is
// helpful while testing the API. In production you may want to disable this.
// -----------------------------------------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', 1);

// -----------------------------------------------------------------------------
// Bootstrap application dependencies. The connection file sets up the MySQL
// connection and assigns it to $conn which is later used for prepared
// statements.
// -----------------------------------------------------------------------------
include './connection/connection.php';

// Optional authorization middleware can be included here. Commented out in
// this example but shown to illustrate where token/role checks would live.
// include '../authorization/authorization.php';
//include '../authorization/authorization123.php';

// Standard JSON response headers. CORS is open to allow requests from any
// domain. Adjust Access-Control-Allow-Origin to restrict in real deployments.
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

// The API only supports GET requests. Any other method will receive a 405
// response. This simplifies the endpoint to read-only operations.
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => "Method not allowed."]);
    die;
}

try {
    // All input validation and database operations are wrapped in a try/catch
    // block so that any unexpected error results in a clean JSON error
    // response.
    // -----------------------------------------------------------------
    // Validate required parameters. 'table' specifies which DB table to
    // query and 'columns' lists the columns to return. If either is missing
    // we return a 400 Bad Request.
    // -----------------------------------------------------------------
    if (!isset($_GET['table']) || !isset($_GET['columns'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing required parameters: table or columns']);
        die;
    }
    

    // Requested table name
    $table = $_GET['table'];
    // Comma separated list of columns to return
    $columns = $_GET['columns'];
    // Optional JSON encoded conditions array where key is column and value is
    // the match criteria
    $conditions = isset($_GET['conditions']) ? json_decode($_GET['conditions'], true) : [];
    // When deleted_flag is 1 the API automatically filters out logically
    // deleted rows
    $deleted_flag = isset($_GET['deleted_at']) ? intval($_GET['deleted_at']) : 0;
    // Optional column to group the results by
    $group_by = isset($_GET['group_by']) ? trim($_GET['group_by']) : '';
    // Sanity check table and column names. Only allow alphanumeric characters,
    // underscores and commas so that arbitrary SQL cannot be injected via
    // these parameters.
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table) || !preg_match('/^[a-zA-Z0-9_, ]+$/', $columns)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid table name or columns']);
        die;
    }

    
    // Convert the comma separated column list into an array and remove any
    // whitespace around the names.
    $columnsArray = explode(',', $columns);
    $columnsArray = array_map('trim', $columnsArray);
    // The first column is treated as the primary identifier for the row so we
    // alias it as 'id'. This makes the response consistent across tables.
    if (isset($columnsArray[0])) {
        $columnsArray[0] = $columnsArray[0] . ' AS id';
    }

    // Rebuild the columns string
    $columns = implode(', ', $columnsArray); 
    
    // Build the base SELECT statement using the sanitized table and column
    // list. DISTINCT is used to avoid duplicate rows when joins produce
    // repetition.
    $query = "SELECT distinct $columns FROM `$table`";
    // $params will hold values for prepared statement placeholders and $types
    // contains the corresponding type information used by mysqli.
    $params = [];
    $types = '';

    // -----------------------------------------------------------------
    // Build dynamic WHERE clause. Each entry in $conditions is processed and
    // converted into a parameterized expression. Special handling exists for
    // comma separated values and null/blank checks.
    // -----------------------------------------------------------------
    if (!empty($conditions)) {
        $query .= " WHERE ";
        $conditionStrings = [];

            // Iterate over each filter condition supplied by the client. Keys
            // represent column names and values specify the filter to apply.
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

                    // Some columns store comma separated lists of IDs or need
                    // special handling. For these we build conditions using
                    // FIND_IN_SET or IN clauses depending on whether multiple
                    // values were supplied.
                    if (in_array($column, ['user_modules','user_id','user_role','user_admin_modules','user_resources','employee_id','assigned_users'])) {
                        
                        // Check for multiple comma separated values
                        if (strpos($value, ',') !== false) {
                            $valuesArray = explode(',', $value);

                            if (in_array($column, ['user_modules','user_admin_modules','user_resources','assigned_users'])) {
                                // For list type columns we use FIND_IN_SET so
                                // that each value can match anywhere in the
                                // stored CSV.
                                $orConditions = [];
                                foreach ($valuesArray as $v) {
                                    $orConditions[] = "FIND_IN_SET(?, $column)";
                                    $params[] = trim($v);
                                    $types .= 's';
                                }
                                $conditionStrings[] = '(' . implode(' OR ', $orConditions) . ')';
                            } else {
                                // Standard IN clause when the column contains
                                // a single value and multiple options were
                                // supplied by the client.
                                $placeholders = implode(',', array_fill(0, count($valuesArray), '?'));
                                $conditionStrings[] = "$column IN ($placeholders)";
                                foreach ($valuesArray as $v) {
                                    $params[] = trim($v);
                                    $types .= 's';
                                }
                            }
                        } else {
                            // Only a single filter value provided. Use either
                            // FIND_IN_SET or a simple equality check depending
                            // on the column type.
                            if (in_array($column, ['user_modules','user_admin_modules','user_resources','assigned_users'])) {
                                $conditionStrings[] = "FIND_IN_SET(?, $column)";
                            } else {
                                $conditionStrings[] = "$column = ?";
                            }
                            $params[] = $value;
                            $types .= 's';
                        }

                    } else if (in_array($column, ['created_at','updated_at'])) {
                        // For timestamp columns we ignore the time portion and
                        // match on the date only.
                        $conditionStrings[] = "DATE($column) = ?";
                        $params[] = $value;
                        $types .= 's';

                    } else {
                        // Default handling: equality check with a parameter
                        $conditionStrings[] = "$column = ?";
                        $params[] = $value;
                        $types .= 's';
                    }
                }
            }
    
        // Join all generated conditions with AND to form the WHERE clause
        $query .= implode(' AND ', $conditionStrings);
    }

    // Optionally filter out soft deleted rows. Many tables mark deletions by
    // setting a 'deleted_at' timestamp; when the flag is provided only rows
    // where this field is zero are returned.
    if ($deleted_flag === 1) {
        $query .= !empty($conditions) ? " AND deleted_at ='0000-00-00 00:00:00'" : " WHERE deleted_at = '0000-00-00 00:00:00'";
    }
    // Grouping can be requested via the 'group_by' parameter
    if (!empty($group_by)) {
        $query .= " GROUP BY `$group_by`";
    }

 

    // Utility function used purely for debugging. It expands a prepared
    // statement by substituting the supplied parameters so that the final SQL
    // string can be inspected in logs.
    function getFinalQuery($query, $params) {
        if (empty($params)) return $query;

        foreach ($params as $param) {
            // Escape single quotes for direct execution
            $value = is_numeric($param) ? $param : "'" . addslashes($param) . "'";
            // Replace the first occurrence of '?' with the parameter value
            $query = preg_replace('/\?/', $value, $query, 1);
        }

        return $query;
    }
    
    // Debugging: Generate the full SQL query
    $finalQuery = getFinalQuery($query, $params);
    // Uncomment the next lines to output the generated SQL for troubleshooting
    // echo $finalQuery;
    // die;

    // Prepare and execute the query
    // Use prepared statements to avoid SQL injection. If preparation fails we
    // send a 400 response with the database error.
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        http_response_code(400);
        die(json_encode([
            'status' => 'error',
            'message' => 'Query execution failed:'.$conn->error
        ]));
    }

    if (!empty($params)) {
        // Bind any collected parameters to the statement using the generated
        // type string.
        $stmt->bind_param($types, ...$params);
    }
    
    // Execute the statement and fetch all resulting rows into an array
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        // Collect each row as an associative array
        $data[] = $row;
    }
    if(empty($data)){
        // No rows matched the query - notify the caller
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'No Data Found..'
        ]);
        die;
    }
    // Return the resulting data set as JSON
    http_response_code(200);
    echo json_encode($data);
    die;

} catch (Exception $e) {
    // Unexpected failure - return the exception message for debugging
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
