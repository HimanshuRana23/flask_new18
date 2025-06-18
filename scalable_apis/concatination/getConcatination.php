<?php
/*
Created By : @Udit Prajapati@21042025
Created for : Dynamic Get app name with PONO only in first row
*/

error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../connection/connection.php';

// Include the authorization file
// include '../../authorization/authorization.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
$result = [];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $linked_mainaudit_id = isset($_GET['linked_mainaudit_id']) ? intval($_GET['linked_mainaudit_id']) : 0;
        $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
        $field_ids = isset($_GET['field_ids']) ? $_GET['field_ids'] : '';
   //     file_put_contents('log_new.txt', 'linked_mainaudit_id = '.$linked_mainaudit_id, FILE_APPEND);

        if (!$form_id || !$field_ids) {
            throw new Exception("Missing required parameters: form_id, field_ids");
        }

        $field_id_array = array_filter(array_map('trim', explode(',', $field_ids)), 'is_numeric');

        if (empty($field_id_array)) {
            throw new Exception("Invalid field_ids provided.");
        }

        $field_ids_sql = implode(',', $field_id_array);

        $query = "
            SELECT fvf_main_audit_id AS id, fvf_main_field_id AS fieldId, answer
            FROM form_via_form_main_audit_answers
            WHERE fvf_main_form_id = $form_id  
            AND deleted_at = '0000-00-00 00:00:00'
            AND fvf_main_field_id IN ($field_ids_sql)
        ";

        // Conditionally add audit ID filter
        if ($linked_mainaudit_id > 0) {
            $query .= " AND fvf_main_audit_id = $linked_mainaudit_id";
        }

        // echo '<pre>';print_r($query);exit;
        $sqldata = $conn->query($query);
        $grouped_answers = [];

        while ($row = mysqli_fetch_assoc($sqldata)) {
            $auditId = $row['id'];
            $fieldId = $row['fieldId'];
            $answer = trim($row['answer']);

            // Group answers by audit ID
            if (!isset($grouped_answers[$auditId])) {
                $grouped_answers[$auditId] = [];
            }
            $grouped_answers[$auditId][$fieldId] = $answer;
        }
        $result = [];

        foreach ($grouped_answers as $auditId => $answersByField) {
            $answers = [];
            foreach ($field_id_array as $fid) {
                $answers[] = $answersByField[$fid] ?? '';
            }

            $result[] = [
                'id' => $auditId,
                'answer' => implode(' - ', $answers),
            ];
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
