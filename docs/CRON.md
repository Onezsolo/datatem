# Cron setup for Laravel scheduler

The app uses Laravel’s task scheduler to run **order status checks** every minute. For that to run automatically, the server must have a **single cron entry** that runs every minute.

## 1. Add the cron entry

On the server, open your user’s crontab:

```bash
crontab -e
```

Add this line (replace `/var/www/datatem` with your project path):

```cron
* * * * * cd /var/www/datatem && php artisan schedule:run >> /dev/null 2>&1
```

Save and exit. This runs every minute and executes any scheduled tasks that are due.

## 2. Confirm it’s installed

List current crontab:

```bash
crontab -l
```

You should see the line above.

## 3. Verify the scheduler

- **List scheduled tasks and next run time:**
  ```bash
  cd /var/www/datatem && php artisan schedule:list
  ```
  You should see `orders:check-status` with “Every minute”.

- **Run the scheduler once by hand:**
  ```bash
  cd /var/www/datatem && php artisan schedule:run
  ```
  No output is normal when tasks run successfully.

- **Check the log:**  
  Each run is logged. In `storage/logs/laravel.log` you should see lines like:
  ```text
  Schedule orders:check-status ran {"processing_count":0}
  ```
  If you see that every minute (when you tail the log), the cron is running correctly.

## 4. Optional: log cron output

To capture output and errors from the scheduler into a file instead of discarding them:

```cron
* * * * * cd /var/www/datatem && php artisan schedule:run >> /var/www/datatem/storage/logs/schedule.log 2>&1
```

Then check `storage/logs/schedule.log` to confirm runs.

## Summary

| What                | Command / action |
|---------------------|-------------------|
| Cron entry          | `* * * * * cd /var/www/datatem && php artisan schedule:run >> /dev/null 2>&1` |
| List schedules      | `php artisan schedule:list` |
| Run once manually   | `php artisan schedule:run` |
| Confirm in app log  | `grep "orders:check-status" storage/logs/laravel.log` |

Without the cron entry, **nothing runs in the background**. Adding that single line is what makes the scheduler run every minute.
