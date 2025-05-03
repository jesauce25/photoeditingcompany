# Cron Job Setup for Overdue Task Detection

This document provides instructions on how to set up the automatic overdue task detection cron job.

## Overview

The `check_overdue_tasks.php` script checks all graphic artists for overdue tasks and automatically:

1. Blocks users with overdue tasks
2. Locks their non-active tasks
3. Unblocks users who no longer have overdue tasks
4. Respects admin protection periods (when an admin manually unblocks a user)

## Cron Job Setup Instructions

### For Linux/Unix/Mac Systems:

1. Open your crontab for editing:

   ```
   crontab -e
   ```

2. Add the following line to run the script every day at midnight:

   ```
   0 0 * * * php /full/path/to/admin/cron/check_overdue_tasks.php >> /full/path/to/logs/overdue_check.log 2>&1
   ```

3. Save and exit the editor.

### For Windows Systems:

1. Open Task Scheduler
2. Create a new Basic Task
3. Set a name (e.g., "Check Overdue Tasks")
4. Set the trigger to Daily and select a time (recommended: 12:00 AM)
5. Set the action to "Start a program"
6. Program/script: `C:\path\to\php\php.exe`
7. Add arguments: `C:\full\path\to\admin\cron\check_overdue_tasks.php`
8. Finish the wizard

## Testing the Cron Job

To test if the cron job works correctly without waiting for the scheduled time:

1. Run the script manually:

   ```
   php /path/to/admin/cron/check_overdue_tasks.php
   ```

2. Check the output to verify it's working as expected.

## Logging

The cron job will output all its activities to the terminal or to the log file if redirected in the cron configuration.

## Troubleshooting

If the cron job doesn't appear to be working:

1. Check the log file for errors
2. Verify the PHP path is correct
3. Ensure the script has proper execute permissions
4. Test the script manually to ensure it works outside of cron
