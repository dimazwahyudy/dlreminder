#!/usr/bin/env bash
# Wrapper to run the PHP notification script from project root
set -e
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
php "$PROJECT_ROOT/app/Controllers/send_notification.php" >/dev/null 2>&1
