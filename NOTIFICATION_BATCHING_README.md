# PIMS Notification Batching System

A comprehensive notification batching system for the PIMS Office Admin module that optimizes notification delivery through intelligent grouping, scheduling, and priority-based processing.

## Overview

The notification batching system addresses performance and user experience challenges when dealing with high volumes of notifications by:

- **Intelligent Grouping**: Automatically groups similar notifications based on type, priority, and timing
- **Priority-Based Processing**: Critical notifications are sent immediately, while others are batched
- **Configurable Rules**: Flexible batching rules that can be customized per office and notification type
- **Performance Monitoring**: Comprehensive metrics and logging for system optimization
- **Automated Processing**: Cron job integration for hands-off batch processing

## Features

### Core Functionality
- **Smart Batching**: Groups notifications by type (info, success, warning, error, system)
- **Priority Handling**: Critical/High priority notifications bypass batching when needed
- **Scheduled Processing**: Supports immediate, scheduled, and periodic batch types
- **Retry Mechanism**: Automatic retry for failed batch items with configurable attempts
- **Fallback System**: Graceful fallback to direct notification if batching fails

### Notification Type Processors
- **Low Stock Alerts**: Intelligent grouping based on stock levels and urgency
- **New Requests**: Summary notifications for multiple requests of same type
- **Maintenance Reminders**: Priority-based batching based on due dates
- **Consumption Tracking**: Hourly summaries of consumable usage
- **System Events**: Priority-based batching for system notifications

### Administrative Features
- **Dashboard**: Real-time monitoring of batch status and queue sizes
- **Batch Management**: View, process, and cancel individual batches
- **Rule Configuration**: Customize batching behavior per notification type
- **Performance Metrics**: 30-day analytics on processing performance
- **System Logs**: Comprehensive logging for debugging and monitoring

## Installation

### 1. Database Setup

Run the setup script to create the necessary database tables:

```bash
# Access via web browser
http://your-pims-domain/setup_notification_batching.php

# Or run SQL directly
mysql -u username -p database_name < database/notification_batching_tables.sql
```

### 2. Cron Job Configuration

Set up a cron job to run the batch processor every 5 minutes:

```bash
# Edit crontab
crontab -e

# Add this line (adjust path as needed)
*/5 * * * * php /path/to/your/pims/cron_notification_batch_processor.php

# For specific office only (optional)
*/5 * * * * php /path/to/your/pims/cron_notification_batch_processor.php --office_id=1
```

### 3. Verify Installation

1. Access the admin interface: `OFFICE_ADMIN/notification_batch_management.php`
2. Check that all database tables are created
3. Verify default batch rules are loaded
4. Test with sample notifications

## Configuration

### Batch Rules

The system includes default rules that can be customized:

| Rule Name | Type | Batch Size | Interval | Priority Threshold |
|-----------|------|------------|----------|-------------------|
| Default Info | info | 100 | 30 min | low |
| Default Warning | warning | 50 | 15 min | medium |
| Default Error | error | 25 | 10 min | high |
| Low Stock Alerts | low_stock | 20 | 10 min | high |
| New Requests | new_request | 30 | 15 min | medium |

### Priority Levels

- **Critical**: Immediate processing, bypasses batching
- **High**: Small batches, short intervals (5-10 min)
- **Medium**: Standard batching (15-30 min)
- **Low**: Large batches, longer intervals (30+ min)

## Usage

### Basic Integration

The batching system is automatically integrated into existing notification functions:

```php
// Existing functions now support batching
createLowStockNotification($office_id, $consumable_id, $name, $stock, $reorder);
createNewRequestNotification($office_id, $request_id, $type, $requester);
createMaintenanceNotification($office_id, $asset_id, $name, $due_date);
```

### Manual Batching

For custom notifications:

```php
// Create batcher instance
$batcher = new NotificationBatcher($office_id, $user_id);

// Queue notification (will be batched based on rules)
$queue_id = $batcher->queueNotification(
    $user_id,
    "Custom Title",
    "Custom message",
    'info',
    $related_id,
    'asset',
    'medium'
);

// Or force immediate delivery
$notification_id = createOfficeNotification(
    $user_id,
    "Urgent Title",
    "Urgent message",
    'error',
    $related_id,
    'system',
    'critical',
    false  // Disable batching
);
```

### Scheduled Batches

```php
// Create scheduler instance
$scheduler = new NotificationBatchScheduler($office_id, $user_id);

// Schedule batch for specific time
$result = $scheduler->createScheduledBatch(
    $notifications_data,
    '2026-03-12 09:00:00',
    'Morning Summary Batch'
);
```

## Administration

### Accessing the Admin Interface

1. Log in as Office Admin
2. Navigate to `OFFICE_ADMIN/notification_batch_management.php`
3. Use the tabs to manage different aspects:

- **Dashboard**: Overview of system status and recent activity
- **Batches**: View and manage individual notification batches
- **Rules**: Configure batching behavior per notification type
- **Logs**: View system logs and debug information
- **Metrics**: Analyze performance over time

### Common Administrative Tasks

#### Processing Pending Batches
```php
// Manual trigger (also available in admin interface)
$scheduler = new NotificationBatchScheduler($office_id);
$results = $scheduler->runScheduler(true);
```

#### Adjusting Batch Rules
1. Go to Rules tab in admin interface
2. Click "Add Rule" or edit existing rules
3. Configure batch size, intervals, and thresholds
4. Save changes

#### Monitoring Performance
1. Check Metrics tab for 30-day performance data
2. Monitor success rates and processing times
3. Adjust rules if performance issues are detected

## API Reference

### NotificationBatcher Class

#### Methods

- `queueNotification($user_id, $title, $message, $type, $related_id, $related_type, $priority, $batch_type)`
  - Queues a notification for batch processing
  - Returns queue ID or notification ID for immediate delivery

- `processPendingBatches($max_batches = 10)`
  - Processes pending batches up to the specified limit
  - Returns array with processed count and errors

- `getBatchStatistics($days = 7)`
  - Returns performance statistics for the specified number of days

### NotificationBatchScheduler Class

#### Methods

- `runScheduler($force_run = false)`
  - Main scheduler runner that processes all batch types
  - Returns comprehensive results array

- `createScheduledBatch($notifications_data, $schedule_time, $batch_name)`
  - Creates a batch scheduled for specific time
  - Returns batch ID and queued count

- `getSchedulerStatus()`
  - Returns current scheduler status and metrics

## Troubleshooting

### Common Issues

#### Batches Not Processing
1. Check if cron job is running properly
2. Verify database tables exist and are accessible
3. Check scheduler status in admin interface
4. Review system logs for errors

#### High Queue Size
1. Check if scheduler is running frequently enough
2. Review batch rules for appropriate intervals
3. Consider increasing batch sizes for non-critical notifications
4. Monitor database performance

#### Failed Batches
1. Check batch logs for specific error messages
2. Verify notification data integrity
3. Check database connection and permissions
4. Review retry configuration

### Debug Mode

Enable verbose logging in cron job:

```bash
php cron_notification_batch_processor.php --verbose
```

Or check logs in admin interface under the "Logs" tab.

## Performance Optimization

### Database Optimization

The system creates optimized indexes for common queries:

```sql
-- Key indexes for performance
CREATE INDEX idx_notifications_batch_processing ON notifications (user_id, is_read, priority, created_at);
CREATE INDEX idx_batch_status_priority ON notification_batches (status, priority_weight DESC);
CREATE INDEX idx_queue_status_priority ON notification_queue (status, priority_score DESC);
```

### Recommended Settings

Based on office size and notification volume:

| Office Size | Batch Size | Interval | Max Batches/Hour |
|-------------|------------|----------|------------------|
| Small (<50 users) | 25-50 | 15-30 min | 5-8 |
| Medium (50-200 users) | 50-100 | 10-20 min | 8-15 |
| Large (200+ users) | 100-200 | 5-15 min | 15-25 |

### Monitoring Metrics

Key performance indicators to monitor:

- **Processing Time**: Should be under 10 seconds per batch
- **Success Rate**: Should be above 95%
- **Queue Size**: Should not exceed 500 items
- **Failed Batches**: Should be minimal (<5% daily)

## Security Considerations

### Access Control
- Only system administrators can access setup script
- Office admins can only manage their own office's batches
- All database operations use prepared statements

### Data Protection
- Sensitive notification data is encrypted in database
- Audit logging tracks all batch operations
- Automatic cleanup of old logs and metrics

## Future Enhancements

### Planned Features
- **Email Batching**: Extend batching to email notifications
- **Push Notifications**: Mobile app notification batching
- **Machine Learning**: Intelligent batch timing based on user behavior
- **Multi-Office Batching**: Cross-office notification optimization
- **API Integration**: REST API for external system integration

### Extensibility
The system is designed to be easily extended:

1. **New Notification Types**: Add processors in `NotificationBatchProcessors`
2. **Custom Rules**: Implement rule logic in database configuration
3. **Additional Metrics**: Extend metrics collection and reporting
4. **Integration Points**: Hook into existing notification creation functions

## Support

For issues and questions:

1. Check the admin interface logs for error details
2. Review this documentation for common solutions
3. Verify cron job configuration and database connectivity
4. Contact system administrator for database-level issues

## Version History

- **v1.0.0**: Initial release with core batching functionality
- Database tables: 5 (batches, queue, rules, logs, metrics)
- Supported notification types: 5 (info, success, warning, error, system)
- Admin interface: Complete management dashboard
- Cron integration: Automated batch processing
