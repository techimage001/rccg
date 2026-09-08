RCCG Open Heavens Fife WebApp — PUBLIC v6.4

PURPOSE OF THIS UPDATE
This release fixes the private-data connection failure that caused the live WebApp to show 0 lessons, empty What's On, empty contact cards and other missing private content.

WHAT CHANGED
- api/bootstrap.php now locates rccg_fife_private correctly for both public_html/api and nested public_html/public_html/api Hostinger layouts.
- The API now returns a clear JSON error instead of silently opening/using a missing database path.
- index.php uses the same multi-level private config lookup for environment settings.
- assets/app.js no longer presents a failed private-data connection as genuine zero lessons/events. It displays a clear data-connection error instead.
- Service-worker cache bumped to v6.4 so the corrected JavaScript is fetched.

WHAT DID NOT CHANGE
- No lesson text, lesson titles, dates, service schedules, church links, forms, styling, donation data, ministry data or other content was changed by this correction.
- The private database and 52 Discovery Class Teacher lesson JSON files are unchanged.

DEPLOYMENT
Upload/replace the contents of this PUBLIC ZIP in the actual live WebApp public folder.
Keep rccg_fife_private OUTSIDE public_html. Do NOT overwrite the live rccg.sqlite or config.php.

Expected private placement for the current nested Hostinger layout:
  account-root/rccg_fife_private/
  account-root/public_html/public_html/index.php
  account-root/public_html/public_html/api/

After upload, refresh/reopen the site so service-worker cache v6.4 activates.

Admin: /api/leads.php
