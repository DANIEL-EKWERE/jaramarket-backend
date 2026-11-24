#!/usr/bin/env bash
# "Is the cron actually running?" -- answers from the log the runs write,
# so it reflects reality rather than whether an entry exists in cPanel.
#
# Two failure modes look identical from cPanel and have to be told apart:
# the scheduler not firing at all, and the scheduler firing into a run that
# dies every time. So this reports both liveness AND exit statuses, and
# prints the last failing run's output -- the error itself, not just a code.
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LOG="$PROJECT_DIR/logs/cron.log"

echo "Log: $LOG"
if [ ! -f "$LOG" ]; then
    echo
    echo "NOT RUNNING — the log does not exist, so cron.sh has never run."
    echo "  Run it by hand first:  bash $PROJECT_DIR/scripts/cron.sh"
    exit 1
fi

LAST_EPOCH=$(date -r "$LOG" +%s 2>/dev/null || stat -c %Y "$LOG")
AGE=$(( $(date +%s) - LAST_EPOCH ))
RUNS=$(grep -c "cron.sh: starting" "$LOG" || true)
OKS=$(grep -c "finished with status 0$" "$LOG" || true)
FAILS=$(grep -cE "finished with status [1-9][0-9]*$" "$LOG" || true)
SKIPS=$(grep -c "previous run still going" "$LOG" || true)

FIRST_TS=$(grep -m1 "cron.sh: starting" "$LOG" | cut -d' ' -f1-2)
LAST_TS=$(grep "cron.sh: starting" "$LOG" | tail -1 | cut -d' ' -f1-2)

echo "Runs recorded : $RUNS   (first $FIRST_TS, last $LAST_TS)"
echo "Succeeded     : $OKS"
echo "Failed        : $FAILS"
echo "Skipped (lock): $SKIPS"
echo "Last activity : $((AGE / 60)) min ago"
echo

# The frequent job is on a 5-minute schedule, so anything past ~12 minutes
# means the scheduler is not firing -- not just that it had nothing to do.
if [ "$AGE" -lt 720 ]; then
    echo "SCHEDULER: FIRING — a run wrote to the log within the last 12 minutes."
else
    echo "SCHEDULER: NOT FIRING — nothing written for $((AGE / 60)) min."
    # A log full of history means the entry once worked and later stopped,
    # which is a different problem from one that never ran: hosts disable
    # cron entries that fail constantly.
    if [ "${RUNS:-0}" -gt 10 ]; then
        echo "  It HAS run before (see dates below), so the entry was removed or"
        echo "  disabled -- not mistyped. Check cPanel -> Advanced -> Cron Jobs."
    else
        echo "  The cPanel cron entry is missing, has the wrong path, or cron.sh is not executable."
    fi
fi

# More invocations than the schedule allows means duplicate entries; heavy
# lock-skipping means runs are outlasting their interval. Both distort the
# counts above, so say so rather than leaving them to be misread.
if [ "${SKIPS:-0}" -gt $(( ${RUNS:-0} / 4 )) ] && [ "${SKIPS:-0}" -gt 20 ]; then
    echo
    echo "NOTE: $SKIPS invocations were skipped because the previous run was"
    echo "  still going. Runs are overlapping their interval, or more than one"
    echo "  cron entry points at this script. Expect at most 336 runs/day from"
    echo "  the two documented entries (*/5 plus */30)."
fi

# A firing scheduler whose every run dies is worse than no cron at all: it
# looks configured and does nothing. Call it out separately.
if [ "${FAILS:-0}" -gt 0 ] && [ "${FAILS:-0}" -ge "${OKS:-0}" ]; then
    echo
    echo "JOBS: FAILING — $FAILS of $RUNS runs exited non-zero."
    echo "  The schedule is fine; the run itself is broken. See the error below."
elif [ "${FAILS:-0}" -gt 0 ]; then
    echo
    echo "JOBS: $FAILS past failure(s), $OKS success(es)."
fi

echo
echo "--- exit statuses ---"
grep -o "finished with status [0-9]*" "$LOG" | sort | uniq -c | sort -rn

echo
echo "--- runs per day (last 10 logged days) ---"
grep "cron.sh: starting" "$LOG" | cut -d' ' -f1 | sort | uniq -c | tail -10

# Grouping failures by their last line of output separates the one-off from
# the systemic. The MOST RECENT failure is often an unrepresentative one-off
# (a bad flag typed by hand), so the tally is what to read first.
echo
echo "--- why runs failed (grouped by last line of output) ---"
awk '
  /cron\.sh: starting/ { cap = 1; sig = ""; next }
  cap && /cron\.sh: finished with status/ {
      if (($NF + 0) != 0) { cnt[sig == "" ? "(no output at all)" : sig]++ }
      cap = 0; next
  }
  cap { if ($0 ~ /[^[:space:]]/) sig = substr($0, 1, 110) }
  END { for (s in cnt) printf "%7d  %s\n", cnt[s], s }
' "$LOG" | sort -rn | head -8

echo
echo "--- last run that failed with status 1, in full ---"
awk '
  /cron\.sh: starting/ { buf = $0 ORS; cap = 1; next }
  cap && /cron\.sh: finished with status/ {
      buf = buf $0 ORS
      if (($NF + 0) == 1) { last = buf }
      cap = 0; next
  }
  cap { buf = buf $0 ORS }
  END { printf "%s", (last == "" ? "  (none -- no run exited 1)\n" : last) }
' "$LOG" | tail -40

echo
echo "--- last 8 log lines ---"
tail -8 "$LOG"
