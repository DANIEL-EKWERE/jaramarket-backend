#!/usr/bin/env bash
# "Is the cron actually running?" -- answers from the log the runs write,
# so it reflects reality rather than whether an entry exists in cPanel.
#
# Three things have to be told apart, because from cPanel they look alike:
#   1. the scheduler not firing at all
#   2. the scheduler firing into a run that dies every time
#   3. a healthy cron carrying the scars of a bug that is already fixed
# (3) is why health is judged on the last 48 hours and not on lifetime
# totals: one bad fortnight would otherwise read as broken forever.
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

# The recent window: today and yesterday, matched on the date each line
# already carries, so it needs no state and survives log rotation.
TODAY=$(date '+%F')
YDAY=$(date -d yesterday '+%F' 2>/dev/null || date -v-1d '+%F' 2>/dev/null || echo "$TODAY")
WINDOW="^($TODAY|$YDAY) "
R_OKS=$(grep -cE "$WINDOW.*finished with status 0$" "$LOG" || true)
R_FAILS=$(grep -cE "$WINDOW.*finished with status [1-9][0-9]*$" "$LOG" || true)
R_SKIPS=$(grep -cE "$WINDOW.*previous run still going" "$LOG" || true)
R_RUNS=$(( R_OKS + R_FAILS ))

echo "Lifetime : $RUNS runs since $FIRST_TS  ($OKS ok, $FAILS failed, $SKIPS lock-skipped)"
echo "Last 48h : $R_RUNS runs  ($R_OKS ok, $R_FAILS failed, $R_SKIPS lock-skipped)"
echo "Last run : $LAST_TS  ($((AGE / 60)) min ago)"
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
    if [ "$RUNS" -gt 10 ]; then
        echo "  It HAS run before (see dates below), so the entry was removed or"
        echo "  disabled -- not mistyped. Check cPanel -> Advanced -> Cron Jobs."
    else
        echo "  The entry is missing, has the wrong path, or cron.sh is not executable."
    fi
fi

# Judged on the window only. A firing scheduler whose every run dies is worse
# than no cron at all -- it looks configured and does nothing.
echo
if [ "$R_RUNS" -eq 0 ]; then
    echo "JOBS: NO DATA — no run has completed in the last 48h."
elif [ "$R_FAILS" -eq 0 ]; then
    echo "JOBS: HEALTHY — all $R_RUNS run(s) in the last 48h succeeded."
elif [ "$R_FAILS" -ge "$R_OKS" ]; then
    echo "JOBS: FAILING — $R_FAILS of $R_RUNS runs in the last 48h exited non-zero."
    echo "  The schedule is fine; the run itself is broken. See the error below."
else
    echo "JOBS: INTERMITTENT — $R_FAILS of $R_RUNS runs in the last 48h failed."
fi

# Historical failures are forensics, not an alarm -- say which they are so a
# healthy cron is not read as broken.
if [ "$FAILS" -gt 0 ] && [ "$R_FAILS" -eq 0 ]; then
    echo "  ($FAILS older failure(s) in the log, none recent. History, not a fault.)"
fi

# Heavy lock-skipping means runs are outlasting their interval or more than
# one entry points here. Only worth raising if it is happening NOW.
if [ "$R_SKIPS" -gt 5 ] && [ "$R_SKIPS" -gt "$R_RUNS" ]; then
    echo
    echo "NOTE: $R_SKIPS invocations in the last 48h were skipped because the"
    echo "  previous run was still going. Runs are overlapping their interval,"
    echo "  or more than one cron entry points at this script. The two"
    echo "  documented entries (*/5 plus */30) give at most 336 runs/day."
fi

echo
echo "--- exit statuses (lifetime) ---"
grep -o "finished with status [0-9]*" "$LOG" | sort | uniq -c | sort -rn

echo
echo "--- runs per day (last 10 logged days) ---"
grep "cron.sh: starting" "$LOG" | cut -d' ' -f1 | sort | uniq -c | tail -10

# Grouping failures by their last line of output separates the one-off from
# the systemic. The MOST RECENT failure is often an unrepresentative one-off
# (a bad flag typed by hand), so the tally is what to read first.
echo
echo "--- why runs failed, grouped (lifetime) ---"
awk '
  /cron\.sh: starting/ { cap = 1; sig = ""; next }
  cap && /cron\.sh: finished with status/ {
      if (($NF + 0) != 0) { cnt[sig == "" ? "(no output at all)" : sig]++ }
      cap = 0; next
  }
  # Skip lines are written by a DIFFERENT invocation while this one runs,
  # so they interleave and would otherwise masquerade as the cause.
  cap { if ($0 ~ /[^[:space:]]/ && $0 !~ /previous run still going/) sig = substr($0, 1, 110) }
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
