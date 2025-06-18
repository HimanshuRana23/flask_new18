<?php 
/*
Created By : @udit prajapati - 28042025
Created for : API for get data from material_types using module_id
url : https://flask.dfos.co/scalable_apis/get_material_types.php?module_id=1
Method      : GET
Description : This API retrieves data from the material_types table where the given module_id is found in the module_id column (comma-separated values).
It returns:

id → material type ID

answer → material type name

Only records with deleted_at = '0000-00-00 00:00:00' are included.
Used for populating dropdowns or selection fields based on module-specific material types.

*/

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection file
include './connection/connection.php';

// Include the authorization file
include '../authorization/authorization.php';

// Set response headers
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");


    $code = 200; //success
	$result = []; 
	try { 
		if ($_SERVER['REQUEST_METHOD'] === 'GET') {  
		    $module_id = $_GET['module_id'];
            $getMaterialsQ = $conn->query("SELECT  materialtype_id, material_type FROM material_types WHERE FIND_IN_SET($module_id, module_id) AND deleted_at='0000-00-00 00:00:00'"); 
          
            while ($row = mysqli_fetch_assoc($getMaterialsQ)) { 
                $result[] = array(
                    "id" => $row['materialtype_id'],
                    "answer" => $row['material_type']
                );  	
            } 
		}else{
			$response = [
				'status' => 'error',
				'message' => "Method not allowed",
				'data' => ['records' => []]
			];
			$code = 405; //Method not allowed
		}			
	}catch(Exception $e){ 
		$result = [
			'status' => 'error',
			'message' => $e->getMessage()
		];
		$code = 400; //error
	}
	http_response_code($code);
	echo json_encode($result);



?>