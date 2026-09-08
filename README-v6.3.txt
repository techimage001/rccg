RCCG Open Heavens Fife WebApp — PUBLIC v6.3

Upload the contents of this package to public_html.
Keep the separate rccg_fife_private folder OUTSIDE public_html as supplied in the PRIVATE v6.3 package.

v6 changes:
- Discovery Class Teacher Manual only; other manual types are not shown until authorised content is supplied.
- Removed standalone Open Heavens/devotional, Bible reader, Hymns and House Fellowship content links.
- Actual Discovery Class Teacher Manual Table of Contents is filtered by All / Past / This Week / Future.
- Clickable mobile and desktop church logo returns Home.
- Added First-Time Worshipper form with 1–5 rating, prayer point, suggestion and contact permission.
- Added dedicated admin section for First-Time Worshippers with CSV export.
- Pastor buttons renamed clearly: Chat with Pastor Joseph / Send Pastor Joseph a Message.
- Cache bumped to v6.3.

Admin: /api/leads.php



v6.3 correction:
- No church service times, recurring schedule rules, event descriptions or service join links are hard-coded in public_html.
- Service Times, recurring What’s On entries and service Quick Access data are loaded from the private SQLite database.
- Website/Facebook/YouTube button styling from v6.2 is retained.
- Public code contains only generic recurrence/rendering logic.
