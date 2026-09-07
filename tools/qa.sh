#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PASS=0
check(){ echo "[CHECK] $1"; shift; "$@"; PASS=$((PASS+1)); }
check "JavaScript parses" node --check "$ROOT/public_html/assets/app.js"
check "All PHP files lint" bash -c "find '$ROOT' -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null"
check "No user-facing Sunday School wording" bash -c "! grep -RniE 'Sunday[[:space:]]+School' '$ROOT/public_html' '$ROOT/rccg_fife_private'"
check "Private SQLite exists outside public_html" test -f "$ROOT/rccg_fife_private/rccg.sqlite"
check "No SQLite inside public_html" bash -c "! find '$ROOT/public_html' -iname '*.sqlite' -o -iname '*.db' | grep -q ."
check "Donation bank values not hard-coded in public_html" bash -c "! grep -R -F '06000981' '$ROOT/public_html' >/dev/null && ! grep -R -F '80-12-71' '$ROOT/public_html' >/dev/null"
check "Church social/Zoom URLs not hard-coded in public_html" bash -c "! grep -R -F 'UC0j0DD8Rryt6h6OiLhJeWEQ' '$ROOT/public_html' >/dev/null && ! grep -R -F '8741131420' '$ROOT/public_html' >/dev/null"
check "Temporary robots block present" grep -q 'Disallow: /' "$ROOT/public_html/robots.txt"
check "PWA manifest present" test -f "$ROOT/public_html/site.webmanifest"
check "Service worker present" test -f "$ROOT/public_html/sw.js"
check "Calendar date picker present" grep -q 'id="lessonDate" type="date"' "$ROOT/public_html/index.php"
check "Student and Teacher manual present" bash -c "grep -q 'Student Manual' '$ROOT/public_html/index.php' && grep -q 'Teacher Manual' '$ROOT/public_html/index.php'"
check "Forms include name/email/phone/message" bash -c "grep -q 'Full Name' '$ROOT/public_html/assets/app.js' && grep -q 'Email Address' '$ROOT/public_html/assets/app.js' && grep -q 'Phone Number' '$ROOT/public_html/assets/app.js' && grep -q 'Message \\*' '$ROOT/public_html/assets/app.js'"
check "Share and bookmark controls present" bash -c "grep -q 'Bookmark' '$ROOT/public_html/assets/app.js' && grep -q 'Share' '$ROOT/public_html/assets/app.js'"
check "External links use separate-view attribute path" grep -q 'data-external' "$ROOT/public_html/assets/app.js"
check "Mobile, tablet and desktop breakpoints present" bash -c "grep -q '@media (min-width:700px)' '$ROOT/public_html/assets/app.css' && grep -q '@media (min-width:1024px)' '$ROOT/public_html/assets/app.css' && grep -q '@media (min-width:1280px)' '$ROOT/public_html/assets/app.css'"
check "44px interaction minimum present" grep -q 'min-height:44px' "$ROOT/public_html/assets/app.css"
check "Public navigation does not expose admin" bash -c "! grep -q 'api/leads.php' '$ROOT/public_html/index.php'"
check "Testing index renders noindex" bash -c "php '$ROOT/public_html/index.php' | grep -q 'noindex,nofollow,noarchive'"
check "Seed database contains future Discovery lessons" python - "$ROOT/rccg_fife_private/rccg.sqlite" <<'PY'
import sqlite3,sys,datetime
c=sqlite3.connect(sys.argv[1]); n=c.execute("select count(*) from discovery_lessons where lesson_date > date('now') and published=1").fetchone()[0]
assert n>0, n
PY
check "Seed database has all required church forms/dynamic tables" python - "$ROOT/rccg_fife_private/rccg.sqlite" <<'PY'
import sqlite3,sys
c=sqlite3.connect(sys.argv[1]); tables={r[0] for r in c.execute("select name from sqlite_master where type='table'")}
need={'settings','service_times','events','discovery_lessons','donation_refs','contacts','subscribers','ministries'}
assert need <= tables, need-tables
PY

echo "QA PASSED: $PASS checks"
