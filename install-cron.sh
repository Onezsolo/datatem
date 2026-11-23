#!/bin/bash
# Add Laravel scheduler to crontab so it runs every minute.
# Run from project root: ./install-cron.sh
# Or: bash install-cron.sh

set -e
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CRON_LINE="* * * * * cd $DIR && php artisan schedule:run >> /dev/null 2>&1"

# Only skip if THIS project's scheduler is already in crontab (match our path)
if crontab -l 2>/dev/null | grep -q "data2buy.com.*schedule:run"; then
    echo "Cron entry for data2buy.com scheduler already exists. Current crontab:"
    crontab -l
    exit 0
fi

(crontab -l 2>/dev/null; echo "$CRON_LINE") | crontab -
echo "Cron entry added. The scheduler will run every minute."
echo "Current crontab:"
crontab -l
