<?php
require_once '../config.php';
require_once '../AICategorizer.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['query']) || empty($input['query'])) {
    jsonResponse(false, 'Query is required');
}

try {
    $qa = new SmartQA();
    $query = $input['query'];
    
    // Search for answer
    $result = $qa->searchAnswer($query);
    
    if ($result) {
        // Found an answer
        $response_data = [
            'answer' => $result['answer'],
            'question' => $result['question'],
            'departments' => $result['department_names'] ?? []
        ];
        
        // Log the interaction
        $citizen_id = $_SESSION['citizen_id'] ?? null;
        $qa->logChat($citizen_id, $query, $result['answer'], $result['id']);
        
        jsonResponse(true, 'Answer found', $response_data);
    } else {
        // No answer found - suggest submitting a ticket
        $response_data = [
            'answer' => "I don't have specific information about that yet. Would you like to submit a formal concern ticket? Our staff will respond within 24-48 hours.\n\nYou can also try rephrasing your question or contact us directly at " . CONTACT_PHONE,
            'question' => null,
            'departments' => [],
            'suggest_ticket' => true
        ];
        
        // Log the interaction
        $citizen_id = $_SESSION['citizen_id'] ?? null;
        $qa->logChat($citizen_id, $query, 'No answer found', null);
        
        jsonResponse(true, 'No answer found', $response_data);
    }
    
} catch (Exception $e) {
    error_log("Chat error: " . $e->getMessage());
    jsonResponse(false, 'An error occurred while processing your query');
}