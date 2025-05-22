<?php
// Simple placeholder for client-side logging
// This ensures the 404 error is resolved

// Get input from the request
$jsonInput = file_get_contents('php://input');
$logData = json_decode($jsonInput, true);

// Log to server error log
if ($logData) {
    $message = "[CLIENT LOG] ";
    if (isset($logData['action'])) {
        $message .= $logData['action'];
    }
    if (isset($logData['data'])) {
        $message .= ": " . json_encode($logData['data']);
    }
    error_log($message);
}

// Return success response
header('Content-Type: application/json');
echo json_encode(['status' => 'success']);