<?php
require_once '../config.php';
require_once '../AICategorizer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    handleError('Invalid request method', 'index.php');
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Validate required fields
    $required_fields = ['title', 'description', 'contact_name', 'contact_phone'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            handleError("Field '$field' is required", '../submit.php');
            exit;
        }
    }
    
    // Validate department selection
    if (empty($_POST['departments']) || !is_array($_POST['departments'])) {
        handleError('Please select at least one department', '../submit.php');
        exit;
    }
    
    // Sanitize inputs
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $priority = sanitize($_POST['priority'] ?? 'normal');
    $location = sanitize($_POST['location'] ?? '');
    $contact_name = sanitize($_POST['contact_name']);
    $contact_phone = sanitize($_POST['contact_phone']);
    $contact_email = sanitize($_POST['contact_email'] ?? '');
    $departments = array_map('intval', $_POST['departments']);
    
    // Start transaction
    $db->beginTransaction();
    
    // 1. Create or get citizen record
    $citizen_stmt = $db->prepare("
        SELECT id FROM citizens WHERE phone = ?
    ");
    $citizen_stmt->execute([$contact_phone]);
    $citizen = $citizen_stmt->fetch();
    
    if ($citizen) {
        $citizen_id = $citizen['id'];
        // Update citizen info
        $update_citizen = $db->prepare("
            UPDATE citizens 
            SET full_name = ?, email = ?
            WHERE id = ?
        ");
        $update_citizen->execute([$contact_name, $contact_email, $citizen_id]);
    } else {
        // Insert new citizen
        $insert_citizen = $db->prepare("
            INSERT INTO citizens (full_name, email, phone)
            VALUES (?, ?, ?)
        ");
        $insert_citizen->execute([$contact_name, $contact_email, $contact_phone]);
        $citizen_id = $db->lastInsertId();
    }
    
    // 2. Get AI suggestions
    $categorizer = new AICategorizer();
    $ai_result = $categorizer->categorizeConcern($description);
    $ai_suggestions = json_encode($ai_result['suggested_departments']);
    
    // 3. Generate ticket number
    $ticket_number = generateTicketNumber();
    
    // 4. Insert ticket
    $insert_ticket = $db->prepare("
        INSERT INTO tickets (
            ticket_number, citizen_id, title, description, 
            priority, status, location, ai_suggested_depts
        ) VALUES (?, ?, ?, ?, ?, 'open', ?, ?)
    ");
    $insert_ticket->execute([
        $ticket_number,
        $citizen_id,
        $title,
        $description,
        $priority,
        $location,
        $ai_suggestions
    ]);
    $ticket_id = $db->lastInsertId();
    
    // 5. Assign departments
    $insert_dept = $db->prepare("
        INSERT INTO ticket_departments (ticket_id, department_id, is_primary)
        VALUES (?, ?, ?)
    ");
    
    foreach ($departments as $index => $dept_id) {
        $is_primary = ($index === 0) ? 1 : 0; // First department is primary
        $insert_dept->execute([$ticket_id, $dept_id, $is_primary]);
    }
    
    // 6. Create initial update
    $insert_update = $db->prepare("
        INSERT INTO ticket_updates (ticket_id, message, status, updated_by)
        VALUES (?, ?, 'open', 'System')
    ");
    $dept_names_stmt = $db->prepare("
        SELECT name FROM departments WHERE id IN (" . implode(',', array_fill(0, count($departments), '?')) . ")
    ");
    $dept_names_stmt->execute($departments);
    $dept_names = $dept_names_stmt->fetchAll(PDO::FETCH_COLUMN);
    $dept_list = implode(', ', $dept_names);
    
    $insert_update->execute([
        $ticket_id,
        "Ticket submitted and assigned to: {$dept_list}"
    ]);
    
    // Commit transaction
    $db->commit();
    
    // Success message
    handleSuccess(
        "Your concern has been submitted successfully! Ticket Number: <strong>{$ticket_number}</strong>. You can track your ticket status anytime.",
        "../track.php?ticket={$ticket_number}"
    );
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Ticket submission error: " . $e->getMessage());
    handleError('An error occurred while submitting your concern. Please try again.', '../submit.php');
}