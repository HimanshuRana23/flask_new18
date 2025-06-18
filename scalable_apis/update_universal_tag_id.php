<?php
/*
Created By : @udit perajapati - 21042025
Created for : API to update universal_tag_id for 3 tables based on conditions
Description : This API updates the universal_tag_id for three tables (form_via_form_main_fields, form_via_form_main_sections, form_via_form_main_forms) based on the provided form_id.
URL : https://flask.dfos.co/scalable_apis/update_universal_tag_id.php?form_id=12345
Method      : GET
*/

error_reporting(E_ALL);
ini_set('display_errors', 1);

include './connection/connection.php';

// Include the authorization file
include '../authorization/authorization.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => "Method not allowed."]);
    die;
}

try {
    // Tables and starting tag IDs
    $tables = [
        ['table' => 'form_via_form_main_fields', 'start_tag' => 10001, 'pk' => 'fvf_main_field_id'],
        ['table' => 'form_via_form_main_sections', 'start_tag' => 20001, 'pk' => 'fvf_section_id'],
        ['table' => 'form_via_form_main_forms', 'start_tag' => 30001, 'pk' => 'fvf_main_form_id']
    ];

    $responses = [];

    if (!isset($_GET['form_id']) || !is_numeric($_GET['form_id'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing or invalid form_id']);
        die;
    }

    $fvf_main_form_id = intval($_GET['form_id']);

    foreach ($tables as $info) {
        $table = $info['table'];
        $startTag = $info['start_tag'];
        $primaryKey = $info['pk'];
        $updatedCount = 0;

        // Fetch rows with blank universal_tag_id
       // $query = "SELECT $primaryKey, fvf_main_form_id FROM `$table` WHERE (universal_tag_id IS NULL OR universal_tag_id = '') AND fvf_main_form_id = $fvf_main_form_id ORDER BY $primaryKey ASC";
      
       $query = "SELECT  $primaryKey, fvf_main_form_id,universal_tag_id  FROM `$table` WHERE  fvf_main_form_id = $fvf_main_form_id";
    
        $result = $conn->query($query);
        // print_r( $query);
        // print_r( $result);
        // die;
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

              
                
                $id = $row[$primaryKey];
                if(!empty($row['universal_tag_id']))
                {
                    continue;
                }

                // Debug: show the query about to be executed
                $debugQuery = "UPDATE `$table` SET universal_tag_id = $startTag WHERE $primaryKey = $id AND fvf_main_form_id = $fvf_main_form_id;";
               // echo "Debug Query: " . $debugQuery . "<br>";  // Debugging line
                
                // Actual updates
                $updateQuery = "UPDATE `$table` SET universal_tag_id = ? WHERE $primaryKey = ? AND fvf_main_form_id = ?";
                $stmt = $conn->prepare($updateQuery);

                if ($stmt === false) {
                 //   echo "Error preparing query: " . $conn->error;
                    die;
                }

                $stmt->bind_param("iii", $startTag, $id, $fvf_main_form_id);
                $stmt->execute();

                if ($stmt->affected_rows > 0) {
                    $updatedCount++;
                    $startTag++;
                }
            }
        }

        $responses[] = [
            'table' => $table,
            'updated' => $updatedCount
        ];
    }

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Universal Tag IDs updated.', 'details' => $responses]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
