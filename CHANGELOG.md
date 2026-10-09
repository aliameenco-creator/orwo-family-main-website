# Changelog

## 0.2.2 — 2026-10-09

- Footer copyright now always shows the current year (2026), updated automatically every year instead of the old site's fixed "2025".

## 0.2.1 — 2026-10-09

- Fixed the page being wider than the screen on narrower phones (about 375px and below, e.g. iPhone SE and many Android phones): the footer columns no longer force extra width, and the page can never scroll sideways.
- Removed the scroll-mouse icon from the bottom of the hero.

## 0.2.0 — 2026-10-09

Design refinement after the first review on the live site.

- Phone menu fixed: it now always opens as a full black screen under the header, also after scrolling (the header's blur effect was trapping the menu panel).
- Black throughout, with lighter, quieter typography (Roboto 200–400, no heavy condensed bold).
- Clients now appear right under the numbers as a moving strip of white logos on black (replacing the text ticker).
- The ORWO Family companies are shown as 26 large individual logos (cut from the original collage) instead of one small image.
- Images move into the WordPress Media Library with one click (Complete ORWO Family setup): descriptive file names and alt text for SEO, responsive image sizes, and alt text you edit in the Media Library is used on the website. Re-running is safe and never creates duplicates.
- Phones: parallax switched off for smoother scrolling, larger tap targets, refined two-column layouts.

## 0.1.0 — 2026-10-09

First release of the ORWO Family theme, built from the archived orwo.family (Strikingly) site.

- Landing page at `/` with every visible section of the original one-page site, in the original order and with the original anchors (`#home`, `#about`, `#history`, `#gallery`, `#innovation`, `#racing`, `#innovation-2`, `#clients`, `#online-shop`, `#110-years`), so the original menu links keep working.
- Original copy only (with its original bold/italic emphasis), original logo, favicon and photos. Hidden Strikingly sections (Clothing, The Board, Social feed, Blog), template filler and the hidden COVID footer note are not shown.
- Motion: film-grain hero with slow zoom, moving sprocket edges, light leak and logo reveal; count-up numbers; moving capability ticker; film-strip gallery with lightbox; word-by-word quote reveals; parallax photo bands; a racing timeline that draws as you scroll; moving client-logo rows; spinning reel in the shop band. All motion is switched off for visitors who prefer reduced motion.
- Original menu with scroll highlighting, sticky header (flush under the admin bar, at the very top on phones), full-screen phone menu, scroll progress bar.
- `/contact`: the original email form (Name, Email, Message, "Submit", "Thanks for your submission!"). Enquiries are saved under **Enquiries** in wp-admin and emailed to hello@orwo.family (change under Settings → General). Honeypot, minimum-time and rate-limit spam protection; no third-party service.
- `/pages/cookie-policy`: the original cookie policy at its original URL.
- Original page title and meta description, canonical, Open Graph/Twitter tags and sitemap entries when no SEO plugin is active.
- One-click "Complete ORWO Family setup" button (pretty permalinks + original site title).
- Fonts (Roboto, Roboto Condensed) are bundled; no requests to Google.
- In-WordPress updates from GitHub releases (Appearance → ORWO Family Updates). The GitHub check only runs in wp-admin/cron, never on front-end page loads.
