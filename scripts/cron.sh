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

# Find the interpreter that has this project's packages. cPanel's "Setup
# Python App" puts the virtualenv OUTSIDE the project (~/virtualenv/<app>/
# <version>/), so looking only inside the project silently falls back to the
# system python -- which has none of the dependencies and dies on import.
PYTHON=""
for candidate in \
    "$PROJECT_DIR/venv/bin/python" \
    "$PROJECT_DIR/.venv/bin/python" \
    "${VIRTUAL_ENV:-}/bin/python"
do
    [ -n "$candidate" ] && [ -x "$candidate" ] && PYTHON="$candidate" && break
done

if [ -z "$PYTHON" ]; then
    # cPanel layout: ~/virtualenv/<project folder>/<python version>/bin/python
    APP_NAME="$(basename "$PROJECT_DIR")"
    for candidate in "$HOME/virtualenv/$APP_NAME"/*/bin/python; do
        [ -x "$candidate" ] && PYTHON="$candidate" && break
    done
fi

if [ -z "$PYTHON" ]; then
    PYTHON="$(command -v python3 || command -v python)"
fi

if [ -z "${PYTHON:-}" ]; then
    echo "$(date '+%F %T') cron.sh: no python interpreter found" >&2
    exit 1
fi

# `cron.sh --print-cron` prints the two lines to paste into cPanel, already
# filled in with the real paths -- no guessing at usernames or folders.
if [ "${1:-}" = "--print-cron" ]; then
    echo "Project : $PROJECT_DIR"
    echo "Python  : $PYTHON"
    echo
    echo "Paste these into cPanel -> Advanced -> Cron Jobs:"
    echo
    echo "*/5  * * * * $PROJECT_DIR/scripts/cron.sh"
    echo "*/30 * * * * $PROJECT_DIR/scripts/cron.sh --slow"
    echo
    if [ ! -x "$PROJECT_DIR/scripts/cron.sh" ]; then
        echo "NOTE: run this first so cron may execute it:"
        echo "  chmod +x $PROJECT_DIR/scripts/cron.sh"
    fi
    exit 0
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
