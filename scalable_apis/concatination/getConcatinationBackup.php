<?php
/*
Created By : @Udit Prajapati@21042025
Created for : Dynamic Get app name with PONO only in first row
*/

error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../connection/connection.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $linked_mainaudit_id = isset($_GET['linked_mainaudit_id']) ? intval($_GET['linked_mainaudit_id']) : 0;
        $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
        $field_ids = isset($_GET['field_ids']) ? $_GET['field_ids'] : '';
        //file_put_contents('log_new.txt', 'linked_mainaudit_id = '.$linked_mainaudit_id, FILE_APPEND);

        if (!$form_id || !$field_ids || !$linked_mainaudit_id) {
            throw new Exception("Missing required parameters: form_id, field_ids, or linked_mainaudit_id");
        }

        $field_id_array = array_filter(array_map('trim', explode(',', $field_ids)), 'is_numeric');

        if (empty($field_id_array)) {
            throw new Exception("Invalid field_ids provided.");
        }

        $field_ids_sql = implode(',', $field_id_array);

        $query = "
            SELECT 
                fvf_main_field_id AS fieldId,
                answer
            FROM form_via_form_main_audit_answers
            WHERE fvf_main_form_id = $form_id  
              AND fvf_main_audit_id = $linked_mainaudit_id
              AND deleted_at = '0000-00-00 00:00:00'
              AND fvf_main_field_id IN ($field_ids_sql)
        ";

        $sqldata = $conn->query($query);

        $raw_answers = [];
        while ($row = mysqli_fetch_assoc($sqldata)) {
            $raw_answers[] = [
                'fieldId' => $row['fieldId'],
                'answer'  => $row['answer'],
            ];
        }

        // Group answers by fieldId
        $grouped_by_field = [];
        foreach ($raw_answers as $item) {
            $grouped_by_field[$item['fieldId']][] = $item['answer'];
        }

        // Get PONO from the first field
        $pono = isset($grouped_by_field[$field_id_array[0]][0]) ? $grouped_by_field[$field_id_array[0]][0] : '';
        $pono = explode('-',$pono);
        // Prepare answers from the remaining fields
        $result = [];
        $first_entry = true;

        for ($i = 0; $i < count($grouped_by_field[$field_id_array[1]] ?? []); $i++) {
            $value = isset($grouped_by_field[$field_id_array[1]][$i]) ? $grouped_by_field[$field_id_array[1]][$i] : '';

            //if ($first_entry && $pono) {
           
               
                $result[] = ['id' => $pono[0] . ' / ' . $value];
            //    $first_entry = false;
            // } else {
            //     $result[] = ['id' => $value];
            // }
        }

        echo json_encode($result);
        exit;

    } else {
        http_response_code(405);
        echo json_encode([
            'status' => 'error',
            'message' => "Method not allowed",
            'data' => []
        ]);
        exit;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    exit;
}
?>
