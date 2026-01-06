<?php
require_once '../config.php';
require_once '../AICategorizer.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['description']) || empty($input['description'])) {
    jsonResponse(false, 'Description is required');
}

try {
    $categorizer = new AICategorizer();
    $result = $categorizer->categorizeConcern($input['description']);
    
    jsonResponse(true, 'Categorization successful', $result);
    
} catch (Exception $e) {
    error_log("Categorization error: " . $e->getMessage());
    jsonResponse(false, 'An error occurred during categorization');
}