# RCCG Open Heavens Fife — Testing Release

This release is designed for a temporary Hostinger domain first, then a permanent domain later.

## Important folder placement

Upload/clone the **repository root** so these two folders are siblings:

```text
/home/<hostinger-account>/
├── rccg_fife_private/   <- MUST stay outside public_html
└── public_html/         <- web root
```

Do **not** place `rccg_fife_private` inside `public_html`.

If your Hostinger Git deployment target is fixed to `public_html`, deploy `public_html` from Git as usual, then place `rccg_fife_private` one level above it once through Hostinger File Manager. Future public deployments will not erase the private database.

## Before first live test

1. Open `rccg_fife_private/config.php`.
2. Keep `environment` as `testing` while using the temporary domain.
3. Change `admin_password` and `site_salt`.
4. Add the Hostinger SMTP mailbox details for email verification and church form notifications.
5. Confirm Hostinger PHP has **PDO SQLite** enabled.
6. Ensure `rccg_fife_private/rccg.sqlite` is writable by PHP.
7. Open `/api/leads.php` and sign in with the admin password.
8. Submit one test form and one test email verification.

## Temporary-domain protection

The testing release ships with:

- `robots.txt` -> `Disallow: /`
- `index.php` -> `noindex,nofollow,noarchive` while `environment=testing`

This prevents the temporary test domain from becoming the indexed version.

## Moving to the permanent domain

1. Point the permanent domain to the same Hostinger app.
2. Set `environment` to `production` in `rccg_fife_private/config.php`.
3. Set `site_url` to the final HTTPS domain.
4. Replace the testing `robots.txt` with the production version you want to index.
5. Keep the same `site_salt` and private SQLite database so subscribers and verification links are not lost.

## Admin-editable without rebuilding the ZIP

Stored in `rccg_fife_private/rccg.sqlite`:

- Service Times
- What's On / events
- Wednesday Zoom URL
- Phone, address, website, Facebook, YouTube
- Donation sort code, account number and reference options
- Discovery Class Student & Teacher manual lessons, including future lessons
- Ministries
- Contact/pastor/prayer/suggestion/ministry/sponsorship submissions
- Subscribers and verification state

## Discovery Class

User-facing wording is **Discovery Class** throughout. It includes:

- Student Manual
- Teacher Manual
- date picker
- past lessons
- current lesson
- future published lessons
- read aloud
- bookmark
- share
- save offline
- personal notes
- explicit back navigation to Discovery Class and Home

## External links

Facebook, YouTube, website and Zoom open in a separate browser tab/view (`target=_blank`, `noopener noreferrer`) so the WebApp remains open.

## Donation

Donation is visible throughout the app. The app only displays bank-transfer details and reference options. It does **not** collect or process bank-card details.

## Email verification

The 5-second prompt uses a passwordless verification link. SMTP must be configured before this can work on the temporary domain.

## Admin URL

`/api/leads.php`

The admin link is intentionally not displayed in the public navigation.
