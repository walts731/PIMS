# Office Admin Notification Testing Guide

## Quick Test Steps

### 1. Test Page Method (Easiest)
1. Go to: `http://localhost/PIMS/OFFICE_ADMIN/test_notifications.php`
2. Fill out the form to create a test notification
3. Check if:
   - Success message appears
   - Notification count updates in topbar
   - Notification appears in dropdown

### 2. Real-world Testing

#### A. Consumable Usage (Creates Notifications)
1. Go to `office_consumables.php`
2. Use/consume any consumable
3. Check if notification appears:
   - "Consumable Used" notification
   - Low stock alert if quantity is low

#### B. Check Browser Console
1. Open browser developer tools (F12)
2. Go to Console tab
3. Click the notification bell
4. Look for:
   - "Notification count response: {unread_count: X}"
   - "Notifications response: {notifications: [...]}"

#### C. Check Network Tab
1. In developer tools, go to Network tab
2. Click notification bell
3. Verify these requests succeed:
   - `notifications_handler.php?action=get_count`
   - `notifications_handler.php?action=get_notifications`

### 3. Manual Database Check
```sql
-- Check if notifications are being created
SELECT * FROM notifications WHERE user_id = YOUR_USER_ID ORDER BY created_at DESC LIMIT 10;

-- Check unread count
SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = YOUR_USER_ID AND is_read = 0;
```

### 4. Expected Behaviors

#### Topbar Badge
- Shows number of unread notifications
- Updates every 30 seconds automatically
- Hides when no unread notifications

#### Dropdown
- Shows up to 5 recent notifications
- Loading spinner while fetching
- "New" badge for unread items
- Clicking notification marks it as read and navigates to related page

#### Notifications Page
- Full list of all notifications
- Filter by type (info, success, warning, error, system)
- Search functionality
- Mark as read/delete actions

### 5. Troubleshooting

#### If notifications don't appear:
1. Check browser console for JavaScript errors
2. Verify `notifications_handler.php` is accessible
3. Check database connection
4. Verify user session is valid

#### If badge doesn't update:
1. Check if `get_count` API call succeeds
2. Verify CSS is loading properly
3. Check if JavaScript is executing

#### If dropdown doesn't work:
1. Verify Bootstrap is loaded
2. Check if dropdown elements exist
3. Verify event listeners are attached

### 6. Test Different Notification Types
Use the test page to create:
- Info notifications (blue)
- Success notifications (green)  
- Warning notifications (yellow)
- Error notifications (red)
- System notifications (gray)

Each should display with appropriate colors and icons.
