<?php
/*
Created By : @Maulik@19052025
Created for : Dynamic Get app name and module id concatinate with PONO only in first row
*/

error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../connection/connection.php';

// Include the authorization file
// include '../../authorization/authorization.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $option_id = $_POST['answer'] ?? $_POST['option_id'] ?? '';
        $pono = '';

        if (!empty($option_id)) {
            // Split by ' - ' (space-hyphen-space)
            $parts = explode(' - ', $option_id);

            if (count($parts) > 0) {
                $pono = trim($parts[0]); // Take only the left part before ' - '
            }
        }

        // echo '<pre>';print_r($pono);exit;
        // $optionIdArray = explode('-', $option_id);
        // $pono = trim($optionIdArray[0]);

        $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
        $main_form_id = isset($_GET['main_form_id']) ? intval($_GET['main_form_id']) : 0;
        $field_ids = $_GET['field_ids'] ?? '';

        if (!$form_id || !$field_ids) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Missing required parameters: form_id, field_ids',
                'data' => []
            ]);
            exit;
        }

        $field_id_array = array_filter(array_map('trim', explode(',', $field_ids)), 'is_numeric');
        if (empty($field_id_array)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid field_ids provided.',
                'data' => []
            ]);
            exit;
        }

        $field_ids_sql = implode(',', $field_id_array);

        // JOIN query to fetch related answers based on PONO match (field ID 379650)
        $query = "
            SELECT a.fvf_main_ans_id as ansId,
                a.fvf_main_field_id AS fieldId,
                a.answer
            FROM form_via_form_main_audit_answers AS a
            JOIN (
                SELECT fvf_main_audit_id,fvf_main_ans_id as aId
                FROM form_via_form_main_audit_answers as ans
                WHERE ans.deleted_at = '0000-00-00 00:00:00'
                AND ans.answer LIKE '$pono%'
                AND ans.fvf_main_form_id = $main_form_id
                ORDER BY ans.fvf_main_audit_id desc
                LIMIT 1
            ) AS po_match ON a.linked_mainaudit_id = po_match.fvf_main_audit_id
            WHERE a.fvf_main_form_id = $form_id  
            AND a.deleted_at = '0000-00-00 00:00:00'
            AND a.fvf_main_field_id IN ($field_ids_sql)
        ";

        // echo '<pre>';print_r($query);exit;
        $sqldata = $conn->query($query);
        $result = [];
        $answerParts = [];

        while ($row = mysqli_fetch_assoc($sqldata)) {
            $fieldId = $row['fieldId'];
            $ansId = $row['ansId'];
            $answer = trim($row['answer'] ?? '');

            if (!isset($answerParts[$fieldId])) {
                $answerParts[$fieldId] = [];
            }

            $answerParts[$fieldId][] = $answer;
        }
        $fieldAnswers = !empty($answerParts) ? array_values($answerParts) : [];
        $maxLength = !empty($fieldAnswers) ? max(array_map('count', $fieldAnswers)) : 0;

        if (!empty($maxLength)) {
            for ($i = 0; $i < $maxLength; $i++) {
                $combined = [];
                foreach ($fieldAnswers as $answers) {
                    $combined[] = $answers[$i] ?? '';
                }
                // echo '<pre>';print_r($combined);
                $result[] = [
                    "fvf_main_field_option_id" => $ansId,
                    "fvf_main_field_type" => "DrillDown",
                    "fvf_main_field_option_name" => implode(' - ', $combined),
                    "fvf_main_field_option_value" => 0
                ];
            }
        }

        $response = [
            'status' => empty($result) ? 'error' : 'success',
            'message' => empty($result) ? 'No Data Found' : 'Application And Module Listing',
            'data' => ['records' => $result]
        ];
        http_response_code(200);
        echo json_encode($response);
        exit;

    } else {
        http_response_code(405);
        echo json_encode([
            'status' => 'error',
            'message' => 'Method not allowed',
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
