<?php
require_once 'NotificationBatcher.php';

/**
 * Specialized batch processors for different notification types
 * Extends the base NotificationBatcher with type-specific logic
 */
class NotificationBatchProcessors extends NotificationBatcher {
    
    /**
     * Process low stock notifications with intelligent batching
     */
    public function processLowStockNotifications($consumables_data) {
        $batched_notifications = [];
        
        foreach ($consumables_data as $consumable) {
            $office_admin_id = $this->getOfficeAdminId($consumable['office_id']);
            
            if (!$office_admin_id) continue;
            
            // Group by criticality level
            $urgency_level = $this->calculateLowStockUrgency($consumable);
            
            $notification_data = [
                'user_id' => $office_admin_id,
                'title' => $this->generateLowStockTitle($consumable, $urgency_level),
                'message' => $this->generateLowStockMessage($consumable, $urgency_level),
                'type' => 'warning',
                'priority' => $urgency_level['priority'],
                'related_id' => $consumable['id'],
                'related_type' => 'consumable',
                'batch_type' => $urgency_level['batch_type']
            ];
            
            $batched_notifications[] = $notification_data;
        }
        
        return $this->queueMultipleNotifications($batched_notifications, 'low_stock');
    }
    
    /**
     * Process new request notifications with smart batching
     */
    public function processNewRequestNotifications($requests_data) {
        $batched_notifications = [];
        
        // Group requests by type and urgency
        $grouped_requests = $this->groupRequestsByType($requests_data);
        
        foreach ($grouped_requests as $request_type => $requests) {
            if (count($requests) == 1) {
                // Single request - process individually
                $request = $requests[0];
                $notification_data = $this->createSingleRequestNotification($request, $request_type);
                $batched_notifications[] = $notification_data;
            } else {
                // Multiple requests - create summary notification
                $notification_data = $this->createRequestSummaryNotification($requests, $request_type);
                $batched_notifications[] = $notification_data;
            }
        }
        
        return $this->queueMultipleNotifications($batched_notifications, 'new_request');
    }
    
    /**
     * Process maintenance notifications with scheduling logic
     */
    public function processMaintenanceNotifications($maintenance_data) {
        $batched_notifications = [];
        
        // Group by maintenance urgency
        $by_urgency = [
            'overdue' => [],
            'due_soon' => [],
            'scheduled' => []
        ];
        
        foreach ($maintenance_data as $maintenance) {
            $urgency = $this->calculateMaintenanceUrgency($maintenance);
            $by_urgency[$urgency][] = $maintenance;
        }
        
        // Process each urgency group with different batching strategies
        foreach ($by_urgency as $urgency_level => $maintenances) {
            if (empty($maintenances)) continue;
            
            foreach ($maintenances as $maintenance) {
                $notification_data = [
                    'user_id' => $this->getOfficeAdminId($maintenance['office_id']),
                    'title' => $this->generateMaintenanceTitle($maintenance, $urgency_level),
                    'message' => $this->generateMaintenanceMessage($maintenance, $urgency_level),
                    'type' => $urgency_level === 'overdue' ? 'error' : 'warning',
                    'priority' => $this->getMaintenancePriority($urgency_level),
                    'related_id' => $maintenance['asset_id'],
                    'related_type' => 'asset',
                    'batch_type' => $urgency_level === 'overdue' ? 'immediate' : 'scheduled'
                ];
                
                $batched_notifications[] = $notification_data;
            }
        }
        
        return $this->queueMultipleNotifications($batched_notifications, 'maintenance');
    }
    
    /**
     * Process consumption notifications with aggregation logic
     */
    public function processConsumptionNotifications($consumption_data) {
        $batched_notifications = [];
        
        // Group consumptions by time period and user
        $grouped_consumptions = $this->groupConsumptionsByPeriod($consumption_data);
        
        foreach ($grouped_consumptions as $period => $consumptions) {
            if (count($consumptions) == 1) {
                // Single consumption
                $consumption = $consumptions[0];
                $notification_data = $this->createSingleConsumptionNotification($consumption);
                $batched_notifications[] = $notification_data;
            } else {
                // Multiple consumptions - create summary
                $notification_data = $this->createConsumptionSummaryNotification($consumptions, $period);
                $batched_notifications[] = $notification_data;
            }
        }
        
        return $this->queueMultipleNotifications($batched_notifications, 'consumption');
    }
    
    /**
     * Process system notifications with priority-based batching
     */
    public function processSystemNotifications($system_events) {
        $batched_notifications = [];
        
        foreach ($system_events as $event) {
            $notification_data = [
                'user_id' => $this->getOfficeAdminId($event['office_id']),
                'title' => $event['title'],
                'message' => $event['message'],
                'type' => 'system',
                'priority' => $event['priority'] ?? 'medium',
                'related_id' => $event['related_id'] ?? null,
                'related_type' => $event['related_type'] ?? 'system',
                'batch_type' => $this->getSystemBatchType($event['priority'])
            ];
            
            $batched_notifications[] = $notification_data;
        }
        
        return $this->queueMultipleNotifications($batched_notifications, 'system');
    }
    
    /**
     * Queue multiple notifications efficiently
     */
    private function queueMultipleNotifications($notifications, $batch_category) {
        $queued_count = 0;
        $errors = 0;
        
        foreach ($notifications as $notification) {
            try {
                $result = $this->queueNotification(
                    $notification['user_id'],
                    $notification['title'],
                    $notification['message'],
                    $notification['type'],
                    $notification['related_id'],
                    $notification['related_type'],
                    $notification['priority'],
                    $notification['batch_type']
                );
                
                if ($result) {
                    $queued_count++;
                } else {
                    $errors++;
                }
            } catch (Exception $e) {
                $errors++;
                error_log("Failed to queue notification: " . $e->getMessage());
            }
        }
        
        return [
            'queued' => $queued_count,
            'errors' => $errors,
            'total' => count($notifications)
        ];
    }
    
    /**
     * Calculate low stock urgency level
     */
    private function calculateLowStockUrgency($consumable) {
        $current_stock = $consumable['quantity'];
        $reorder_level = $consumable['reorder_level'];
        
        if ($current_stock == 0) {
            return [
                'level' => 'critical',
                'priority' => 'critical',
                'batch_type' => 'immediate',
                'percentage' => 0
            ];
        } elseif ($current_stock <= $reorder_level * 0.5) {
            return [
                'level' => 'critical',
                'priority' => 'high',
                'batch_type' => 'immediate',
                'percentage' => ($current_stock / $reorder_level) * 100
            ];
        } elseif ($current_stock <= $reorder_level) {
            return [
                'level' => 'warning',
                'priority' => 'medium',
                'batch_type' => 'scheduled',
                'percentage' => ($current_stock / $reorder_level) * 100
            ];
        } else {
            return [
                'level' => 'info',
                'priority' => 'low',
                'batch_type' => 'periodic',
                'percentage' => ($current_stock / $reorder_level) * 100
            ];
        }
    }
    
    /**
     * Generate low stock title
     */
    private function generateLowStockTitle($consumable, $urgency) {
        $percentage = round($urgency['percentage']);
        
        switch ($urgency['level']) {
            case 'critical':
                return "CRITICAL: {$consumable['description']} Out of Stock";
            default:
                return "Low Stock Alert: {$consumable['description']} ({$percentage}% remaining)";
        }
    }
    
    /**
     * Generate low stock message
     */
    private function generateLowStockMessage($consumable, $urgency) {
        $percentage = round($urgency['percentage']);
        
        switch ($urgency['level']) {
            case 'critical':
                return "{$consumable['description']} is completely out of stock. Immediate reordering required. Current: {$consumable['quantity']}, Reorder at: {$consumable['reorder_level']}";
            case 'warning':
                return "{$consumable['description']} is running low on stock ({$percentage}%). Consider reordering soon. Current: {$consumable['quantity']}, Reorder at: {$consumable['reorder_level']}";
            default:
                return "{$consumable['description']} stock level: {$consumable['quantity']} units (Reorder at: {$consumable['reorder_level']})";
        }
    }
    
    /**
     * Group requests by type
     */
    private function groupRequestsByType($requests) {
        $grouped = [];
        
        foreach ($requests as $request) {
            $type = $request['request_type'] ?? 'general';
            $grouped[$type][] = $request;
        }
        
        return $grouped;
    }
    
    /**
     * Create single request notification
     */
    private function createSingleRequestNotification($request, $request_type) {
        $office_admin_id = $this->getOfficeAdminId($request['office_id']);
        
        return [
            'user_id' => $office_admin_id,
            'title' => "New {$request_type} Request",
            'message' => "New {$request_type} request received from {$request['requester_name']}",
            'type' => 'info',
            'priority' => 'medium',
            'related_id' => $request['id'],
            'related_type' => 'request',
            'batch_type' => 'immediate'
        ];
    }
    
    /**
     * Create request summary notification
     */
    private function createRequestSummaryNotification($requests, $request_type) {
        $office_admin_id = $this->getOfficeAdminId($requests[0]['office_id']);
        $count = count($requests);
        
        return [
            'user_id' => $office_admin_id,
            'title' => "{$count} New {$request_type} Requests",
            'message' => "You have {$count} pending {$request_type} requests requiring your attention",
            'type' => 'info',
            'priority' => 'medium',
            'related_id' => null,
            'related_type' => 'request',
            'batch_type' => 'scheduled'
        ];
    }
    
    /**
     * Calculate maintenance urgency
     */
    private function calculateMaintenanceUrgency($maintenance) {
        $today = new DateTime();
        $due_date = new DateTime($maintenance['due_date']);
        $interval = $today->diff($due_date);
        
        if ($due_date < $today) {
            return 'overdue';
        } elseif ($interval->days <= 7) {
            return 'due_soon';
        } else {
            return 'scheduled';
        }
    }
    
    /**
     * Generate maintenance title
     */
    private function generateMaintenanceTitle($maintenance, $urgency_level) {
        $asset_name = $maintenance['asset_name'];
        
        switch ($urgency_level) {
            case 'overdue':
                return "OVERDUE: Maintenance Required for {$asset_name}";
            case 'due_soon':
                return "Maintenance Due Soon for {$asset_name}";
            default:
                return "Maintenance Scheduled for {$asset_name}";
        }
    }
    
    /**
     * Generate maintenance message
     */
    private function generateMaintenanceMessage($maintenance, $urgency_level) {
        $asset_name = $maintenance['asset_name'];
        $due_date = date('M j, Y', strtotime($maintenance['due_date']));
        $today = new DateTime();
        $due_date_obj = new DateTime($maintenance['due_date']);
        $overdue_days = $due_date_obj->diff($today)->days;
        
        switch ($urgency_level) {
            case 'overdue':
                return "Maintenance for {$asset_name} was due on {$due_date} ({$overdue_days} days overdue). Immediate attention required.";
            case 'due_soon':
                return "Maintenance for {$asset_name} is due on {$due_date}. Please schedule maintenance.";
            default:
                return "Maintenance for {$asset_name} is scheduled for {$due_date}.";
        }
    }
    
    /**
     * Get maintenance priority
     */
    private function getMaintenancePriority($urgency_level) {
        switch ($urgency_level) {
            case 'overdue':
                return 'high';
            case 'due_soon':
                return 'medium';
            default:
                return 'low';
        }
    }
    
    /**
     * Group consumptions by time period
     */
    private function groupConsumptionsByPeriod($consumptions) {
        $grouped = [];
        
        foreach ($consumptions as $consumption) {
            $period = date('Y-m-d H:00:00', strtotime($consumption['consumed_at']));
            $grouped[$period][] = $consumption;
        }
        
        return $grouped;
    }
    
    /**
     * Create single consumption notification
     */
    private function createSingleConsumptionNotification($consumption) {
        $office_admin_id = $this->getOfficeAdminId($consumption['office_id']);
        
        return [
            'user_id' => $office_admin_id,
            'title' => "Consumable Used",
            'message' => "{$consumption['quantity']} units of '{$consumption['description']}' consumed. Remaining: {$consumption['remaining_stock']}",
            'type' => 'info',
            'priority' => 'low',
            'related_id' => $consumption['consumable_id'],
            'related_type' => 'consumable',
            'batch_type' => 'periodic'
        ];
    }
    
    /**
     * Create consumption summary notification
     */
    private function createConsumptionSummaryNotification($consumptions, $period) {
        $office_admin_id = $this->getOfficeAdminId($consumptions[0]['office_id']);
        $total_consumed = array_sum(array_column($consumptions, 'quantity'));
        $period_label = date('g A', strtotime($period));
        
        return [
            'user_id' => $office_admin_id,
            'title' => "Consumption Summary - {$period_label}",
            'message' => "Total of {$total_consumed} consumables used in the last hour across {$this->getUniqueConsumablesCount($consumptions)} items",
            'type' => 'info',
            'priority' => 'low',
            'related_id' => null,
            'related_type' => 'consumable',
            'batch_type' => 'periodic'
        ];
    }
    
    /**
     * Get unique consumables count
     */
    private function getUniqueConsumablesCount($consumptions) {
        $unique_ids = array_unique(array_column($consumptions, 'consumable_id'));
        return count($unique_ids);
    }
    
    /**
     * Get system batch type based on priority
     */
    private function getSystemBatchType($priority) {
        switch ($priority) {
            case 'critical':
            case 'high':
                return 'immediate';
            case 'medium':
                return 'scheduled';
            default:
                return 'periodic';
        }
    }
    
    /**
     * Get office admin ID
     */
    private function getOfficeAdminId($office_id) {
        $sql = "SELECT id FROM users WHERE role = 'office_admin' AND office = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $office_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $row['id'];
        }
        
        return null;
    }
}
?>
