RCCG Open Heavens Fife WebApp — v9
==================================

INSTALL
-------
Upload the single ZIP to the folder that CONTAINS public_html (your account home,
one level above public_html) and extract it there. It writes two folders:

  rccg_fife_private/     PRIVATE  — never served to the public
  public_html/           PUBLIC   — the WebApp itself

Both replace the v8 versions completely. Nothing else needs moving.

After uploading, open the WebApp once and hard-refresh (or close and reopen the
installed app) so the new service worker takes over. The cache name is
rccg-fife-v9 and the CSS/JS are requested as ?v=9, so returning visitors get the
new build automatically on their next load.

WHAT LIVES WHERE
----------------
Everything the church edits stays OUTSIDE public_html, in rccg_fife_private:

  - All 52 Discovery Class Teacher Manual lessons + the manifest
  - All service times
  - All recurring What's On schedules
  - All form definitions and dropdown options (forms.json)
  - The Sunday fellowship invitation text
  - Church contact details, social links and the Zoom link
  - Quick Access tiles and Contact & Join cards
  - All form submissions and subscribers (rccg.sqlite)

public_html contains only the interface, the generic renderers and the API
endpoints that read and write the private data. No church content, no times and
no links are hard-coded in public files.

CHANGED IN v9
-------------
  1. Wednesday Prayer Meeting now shows a closing time: 19:00-20:00.
     (Daily Prayer already showed Monday-Friday 19:00-19:30.)
  2. The Daily Prayer tile on the home page opens What's On instead of Zoom.
  3. "Next at RCCG" on the home page now opens What's On instead of a raw link.
     The Zoom join button still appears inside What's On, where you join from.
  4. Facebook, YouTube and Zoom addresses are no longer printed on screen. The
     buttons still work and still open in a separate window, so the WebApp stays
     open behind them.
  5. New clickable Email Us card: Info@kirkcaldyrccg.co.uk
  6. Every main form has a small x close button, top right.
       - Nothing typed yet  -> closes straight away
       - Partly filled in   -> asks "Keep Editing" or "Discard Form"
  7. The floating "Chat with Pastor Joseph" and "Prayer Request" buttons are
     hidden while a form is open, so they cannot cover the form controls.
  8. Sunday services now show the fellowship invitation, which is stored in the
     private database, not in public code.
  9. PWA cache bumped to v9.

Everything else from v8 is unchanged: donations, ministries, submissions,
subscribers, the Discovery Class reader, read-aloud, bookmarks, offline saving
and the admin leads page.
