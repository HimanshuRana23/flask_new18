<?php

/*
Created By : @udit prajapati@24042025
Modified for : This API is used to handle comma-separated batch IDs and concatenate multiple results
URL : https://flask.dfos.co/ITC/drilldown/batchDescription.php
Method      : POST
Description : This API fetches batch descriptions based on the provided batch IDs, concatenating results from multiple audits.
*/

error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../connection/connection.php';

// Include the authorization file
include '../../authorization/authorization.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

$result = [];
$code = 200;

try {
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {

		$batch_id_string = $_POST['option_id']; // comma-separated values
		if (!empty($batch_id_string)) {

			$batch_ids = array_map('trim', explode(',', $batch_id_string)); // explode and trim
			$batch_ids_filtered = array_filter($batch_ids, function ($id) {
				return is_numeric($id);
			});

			if (empty($batch_ids_filtered)) {
				throw new Exception("Invalid batch ID(s).");
			}

			$batch_ids_in = implode(',', $batch_ids_filtered);

			// Get unique audit_ids for the batch IDs
			$querydata = $con->query("
				SELECT 
					DISTINCT fvf_main_audit_id
				FROM 
					form_via_form_main_audit_answers AS ans
				WHERE 
					ans.fvf_main_form_id = 18
					AND ans.answer IN ($batch_ids_in)
					AND ans.fvf_main_field_id = 2703
					AND ans.deleted_at = '0000-00-00 00:00:00'
			");

			$textCollection = [];
$i=1;

			while ($row = mysqli_fetch_assoc($querydata)) {
				$audit_id = $row['fvf_main_audit_id'];

				$queryFields = $con->query("
					SELECT 
						fvf_main_field_id, answer
					FROM 
						form_via_form_main_audit_answers 
					WHERE 
						fvf_main_form_id = 18
						AND fvf_main_audit_id = $audit_id 
						AND deleted_at = '0000-00-00 00:00:00'
				");

				$textParts = [];
				$actual_reel_width = '';
				$width = '';
				
				while ($fieldRow = mysqli_fetch_assoc($queryFields)) {
					if (in_array($fieldRow['fvf_main_field_id'], ['2698', '2699', '2705', '2709'])) {
						$textParts[] = $fieldRow['answer'];
					}

					if ($fieldRow['fvf_main_field_id'] == '2707') {
						$actual_reel_width = $fieldRow['answer'];
					}
					if ($fieldRow['fvf_main_field_id'] == '2708') {
						$width = $fieldRow['answer'];
					}
				}

				$breadth = '';
				if (!empty($actual_reel_width) && !empty($width) && $width != 0) {
					$breadth = round($actual_reel_width / $width) * $width;
				}

				$text = implode('-', $textParts);
				$text .= '-' . $breadth . '-' . $actual_reel_width . '-' . $width;
                $count= count($batch_ids);

				if($i <= $count)
				{
					$textCollection[] = $text;
				}
				$i++;

			}
			

			if (!empty($textCollection)) {
				$finalText = implode(', ', $textCollection);
				$result[] = [
					"fvf_main_field_option_id" => $finalText,
					"fvf_main_field_type" => "DrillDown",
					"fvf_main_field_option_name" => $finalText,
					"fvf_main_field_option_value" => $finalText
				];

				$response = [
					'status' => 'success',
					'message' => "Drill down Batch description",
					'data' => ['records' => $result]
				];
				http_response_code(200);
			} else {
				$response = [
					'status' => 'error',
					'message' => "No matching audit records found",
					'data' => ['records' => []]
				];
				http_response_code(404);
			}
			echo json_encode($response);
			die;

		} else {
			throw new Exception("Invalid Parameter");
		}
	} else {
		throw new Exception("Method not allowed");
	}
} catch (Exception $e) {
	$response = [
		'status' => 'error',
		'message' => $e->getMessage()
	];
	http_response_code(500);
	echo json_encode($response);
	die;
}
?>
