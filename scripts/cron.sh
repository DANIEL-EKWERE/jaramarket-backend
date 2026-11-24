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
# <version>/), so looking only inside the project finds nothing.
#
# The interpreter MUST be the one holding Django. Picking the system python
# instead fails on import every single run, and because cron discards output
# that looks exactly like "the cron never ran". So each candidate is tested by
# importing Django, not merely by being executable, and an unresolved
# interpreter is a loud error rather than a fallback.
PYTHON=""
TRIED=""

has_django() {
    [ -x "$1" ] && "$1" -c "import django" >/dev/null 2>&1
}

# An explicit override wins: set JARA_PYTHON in the cron entry, or drop the
# path into .python_path, when the search below cannot find the venv.
OVERRIDE="${JARA_PYTHON:-}"
if [ -z "$OVERRIDE" ] && [ -f "$PROJECT_DIR/.python_path" ]; then
    OVERRIDE="$(tr -d '[:space:]' < "$PROJECT_DIR/.python_path")"
fi

APP_NAME="$(basename "$PROJECT_DIR")"
for candidate in \
    "$OVERRIDE" \
    "$PROJECT_DIR/venv/bin/python" \
    "$PROJECT_DIR/.venv/bin/python" \
    "${VIRTUAL_ENV:+$VIRTUAL_ENV/bin/python}" \
    "$HOME/virtualenv/$APP_NAME"/*/bin/python \
    "$HOME/virtualenv"/*/*/bin/python \
    "$(command -v python3 2>/dev/null)" \
    "$(command -v python 2>/dev/null)"
do
    # Unmatched globs come through as the literal pattern -- skip those.
    case "$candidate" in ""|*"*"*) continue ;; esac
    TRIED="$TRIED  $candidate"$'\n'
    if has_django "$candidate"; then
        PYTHON="$candidate"
        break
    fi
done

# `cron.sh --print-cron` prints the two lines to paste into cPanel, already
# filled in with the real paths -- no guessing at usernames or folders.
if [ "${1:-}" = "--print-cron" ]; then
    echo "Project : $PROJECT_DIR"
    echo "Python  : ${PYTHON:-NOT FOUND}"
    echo
    if [ -z "$PYTHON" ]; then
        echo "No interpreter with Django installed. Tried:"
        printf '%s' "$TRIED"
        echo
        echo "Activate the venv, run 'which python', then:"
        echo "  echo /that/path > $PROJECT_DIR/.python_path"
        echo
    fi
    echo "Paste these into cPanel -> Advanced -> Cron Jobs:"
    echo
    echo "*/5  * * * * $PROJECT_DIR/scripts/cron.sh"
    echo "*/30 * * * * $PROJECT_DIR/scripts/cron.sh --slow"
    echo
    if [ ! -x "$PROJECT_DIR/scripts/cron.sh" ]; then
        echo "NOTE: run this first so cron may execute it:"
        echo "  chmod +x $PROJECT_DIR/scripts/cron.sh"
    fi
    [ -n "$PYTHON" ] && exit 0 || exit 1
fi

mkdir -p "$PROJECT_DIR/logs"
LOG="$PROJECT_DIR/logs/cron.log"

if [ -z "$PYTHON" ]; then
    {
        echo "$(date '+%F %T') cron.sh: starting ${*:-frequent}"
        echo "cron.sh: FATAL - no interpreter with Django installed. Tried:"
        printf '%s' "$TRIED"
        echo "cron.sh: fix by writing the venv python path into $PROJECT_DIR/.python_path"
        echo "$(date '+%F %T') cron.sh: finished with status 1"
    } >> "$LOG"
    exit 1
fi

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

# Record the interpreter: when a run fails, the first question is always which
# python it used, and the answer has to be in the log already.
echo "$(date '+%F %T') cron.sh: starting ${*:-frequent} [$PYTHON]" >> "$LOG"
"$PYTHON" manage.py run_periodic_jobs "$@" >> "$LOG" 2>&1
STATUS=$?
echo "$(date '+%F %T') cron.sh: finished with status $STATUS" >> "$LOG"
exit $STATUS
