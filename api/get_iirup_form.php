<?php
header('Content-Type: application/json');
require_once '../config.php';

// Get form number from request
$form_number = isset($_GET['form_number']) ? trim($_GET['form_number']) : '';

if (empty($form_number)) {
    echo json_encode(['success' => false, 'message' => 'Form number required']);
    exit;
}

try {
    // Get form data
    $form_sql = "SELECT * FROM iirup_forms WHERE form_number = ?";
    $form_stmt = $conn->prepare($form_sql);
    $form_stmt->bind_param("s", $form_number);
    $form_stmt->execute();
    $form_result = $form_stmt->get_result();
    
    if ($form = $form_result->fetch_assoc()) {
        // Get items for this form
        $items_sql = "SELECT * FROM iirup_items WHERE form_id = ? ORDER BY item_order";
        $items_stmt = $conn->prepare($items_sql);
        $items_stmt->bind_param("i", $form['id']);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        
        $items = [];
        while ($item = $items_result->fetch_assoc()) {
            $items[] = $item;
        }
        
        echo json_encode([
            'success' => true,
            'form' => $form,
            'items' => $items
        ]);
        
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'IIRUP Form not found'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
