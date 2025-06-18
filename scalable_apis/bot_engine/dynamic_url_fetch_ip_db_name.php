<?php
header('Content-Type: application/json');

// Include the server mapping
require_once '../connection/dfosConnection_bot_engine.php';

// Get the URL parameter
$url = isset($_GET['url']) ? trim($_GET['url']) : '';

// Normalize URL if needed (e.g., remove trailing slash)
$normalized_url = rtrim($url, '/');

// Match exact URL or normalized version
$response = null;

// First try exact match
if (isset($servers[$url])) {
    $response = $servers[$url];
} elseif (isset($servers[$normalized_url . '/'])) {
    $response = $servers[$normalized_url . '/'];
} elseif (isset($servers[$normalized_url])) {
    $response = $servers[$normalized_url];
}

// Return the response
if ($response) {
    echo json_encode([
        'success' => true,
        'host' => $response['host'],
        'db_name' => $response['db_name']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'URL not found in server mapping.'
    ]);
}
?>
