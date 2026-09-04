#!/usr/bin/env bash
# Entry point for the server's scheduler (cPanel Cron Jobs).
#
# Works out its own location rather than relying on cron's working directory,
# which is NOT the project root -- that mismatch is what silently breaks .env
# loading and relative paths. Point cron at this file and nothing else needs
# to know where the project lives.
#
#   */5  * * * * /home/USER/jaraman_django/scripts/cron.sh
#   */30 * * * * /home/USER/jaraman_django/scripts/cron.sh --slow
set -uo pipefail

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_DIR" || exit 1

# Prefer the project's virtualenv; fall back to whatever python3 is on PATH.
if [ -x "$PROJECT_DIR/venv/bin/python" ]; then
    PYTHON="$PROJECT_DIR/venv/bin/python"
elif [ -x "$PROJECT_DIR/.venv/bin/python" ]; then
    PYTHON="$PROJECT_DIR/.venv/bin/python"
elif [ -n "${VIRTUAL_ENV:-}" ] && [ -x "$VIRTUAL_ENV/bin/python" ]; then
    PYTHON="$VIRTUAL_ENV/bin/python"
else
    PYTHON="$(command -v python3 || command -v python)"
fi

if [ -z "${PYTHON:-}" ]; then
    echo "$(date '+%F %T') cron.sh: no python interpreter found" >&2
    exit 1
fi

mkdir -p "$PROJECT_DIR/logs"
LOG="$PROJECT_DIR/logs/cron.log"

# A slow run must not overlap the next tick -- two dispatchers racing on the
# same order would offer it twice. flock is a no-op if unavailable.
LOCK="$PROJECT_DIR/logs/cron.lock"
if command -v flock >/dev/null 2>&1; then
    exec 9>"$LOCK"
    if ! flock -n 9; then
        echo "$(date '+%F %T') cron.sh: previous run still going, skipping" >> "$LOG"
        exit 0
    fi
fi

echo "$(date '+%F %T') cron.sh: starting ${*:-frequent}" >> "$LOG"
"$PYTHON" manage.py run_periodic_jobs "$@" >> "$LOG" 2>&1
STATUS=$?
echo "$(date '+%F %T') cron.sh: finished with status $STATUS" >> "$LOG"
exit $STATUS
