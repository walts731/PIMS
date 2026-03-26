<?php
require_once 'config.php';

// Add return_photo column to borrow_requests table
try {
    $alter_query = "ALTER TABLE borrow_requests 
                    ADD COLUMN return_photo VARCHAR(255) DEFAULT NULL 
                    AFTER return_notes";
    
    $result = $conn->query($alter_query);
    
    if ($result) {
        echo "Successfully added return_photo column to borrow_requests table\n";
    } else {
        echo "Error adding return_photo column: " . $conn->error . "\n";
    }
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

$conn->close();
?>
