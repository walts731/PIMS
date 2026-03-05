-- Create borrow_form_submissions table for PIMS borrowing system
CREATE TABLE IF NOT EXISTS `borrow_form_submissions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `guest_name` varchar(255) NOT NULL,
    `barangay` varchar(255) NOT NULL,
    `contact` varchar(100) NOT NULL,
    `date_borrowed` date NOT NULL,
    `schedule_return` date NOT NULL,
    `releasing_officer` varchar(255) NOT NULL,
    `approved_by` varchar(255) NOT NULL,
    `items` json NOT NULL,
    `status` enum('approved','returned') NOT NULL DEFAULT 'approved',
    `submitted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`),
    KEY `idx_date_borrowed` (`date_borrowed`),
    KEY `idx_submitted_at` (`submitted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for better performance
ALTER TABLE `borrow_form_submissions` 
ADD INDEX `idx_guest_name` (`guest_name`),
ADD INDEX `idx_barangay` (`barangay`);

-- Create trigger for updated_at if it doesn't exist
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `borrow_form_submissions_before_update`
BEFORE UPDATE ON `borrow_form_submissions`
FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END//
DELIMITER ;
