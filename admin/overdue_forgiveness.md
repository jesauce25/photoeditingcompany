# Overdue Task Forgiveness Feature

This document explains the "Overdue Forgiveness" feature that prevents artists from being re-blocked for the same overdue tasks after an admin unblocks them.

## How It Works

1. When an admin unblocks a graphic artist, the system now:

   - Marks all currently overdue tasks as "forgiven" (using the `forgiven_at` timestamp)
   - Sets the user's status to Active
   - Unlocks all tasks
   - Applies the regular protection period (1 minute for testing, normally 24 hours)

2. When the protection period ends:

   - The system will NOT re-block the user for the same overdue tasks that were forgiven
   - Only NEW overdue tasks (those that became overdue after unblocking) will trigger blocking

3. This creates a true "fresh start" for artists when they are unblocked by an admin

## Implementation

1. **Database Update Required**:
   Execute the SQL in `admin/db_update_forgiven.sql` to add the `forgiven_at` column to the `tbl_project_assignments` table:

   ```sql
   ALTER TABLE tbl_project_assignments ADD COLUMN forgiven_at TIMESTAMP NULL DEFAULT NULL;
   ```

2. **Code Changes**:
   - The code now marks tasks as forgiven during unblocking
   - The task block check ignores forgiven tasks
   - Everything works safely even if the column doesn't exist yet

## Testing

1. Create or identify a user with overdue tasks who is currently blocked
2. Unblock the user as admin
3. The system will mark the overdue tasks as forgiven
4. Wait for the protection period to expire (1 minute in test mode)
5. Verify that the user remains unblocked
6. Create a new overdue task to verify the user gets blocked again

## How to Switch Back to Normal Protection Time

After testing, change the protection period back to 24 hours by editing `admin/controllers/user_controller.php` and replacing:

```php
$protection_end = $now->add(new DateInterval('PT1M'));
```

with:

```php
$protection_end = $now->add(new DateInterval('PT24H'));
```

This will set the protection period back to 24 hours instead of 1 minute.
