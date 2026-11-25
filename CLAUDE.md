# Blood Care — Project Instructions

"Blood Care" is a **training project**: a blood donation system that connects blood donors with people who need blood.
Keep everything **simple and beginner-friendly**. Readability beats cleverness.

## Tech Stack (only these)

| Layer     | Technology                                   |
|-----------|----------------------------------------------|
| Markup    | HTML5                                        |
| Styling   | Tailwind CSS (Play CDN — no build step)      |
| Frontend JS | Vanilla JavaScript (no frameworks, no jQuery, no npm) |
| Backend   | Raw PHP, **procedural only (no OOP, no classes)** |
| Database  | MySQL accessed through **PDO**               |

Do NOT introduce: React/Vue, npm build tools, Composer packages, PHP frameworks, classes/namespaces/traits, ORMs.

## Current Phase

1. Phase 1 (done): Static frontend design.
2. **Phase 2 (now): Dynamic PHP + MySQL.** All pages are `.php`, data comes from MySQL via PDO.
3. Later ideas: CSRF tokens, pagination, email notifications.

Runs on Laravel Herd (project served as `http://blood-donation.test`). Database settings are in `includes/config.php` (database `blood_donations`).
Setup: import `sql/schema.sql` (drops and recreates the tables), then optionally `sql/seed.sql` for sample data.
Demo logins (password `password`): admin mobile `01700000000`, user mobile `01711000001`. Users log in with **mobile number** (there is no email column).

## Folder Structure

```
blood-donation/
├── CLAUDE.md
├── index.php            Home
├── donors.php           Search/filter donors (GET form)
├── requests.php         Open blood requests; logged-in donors click "I will donate"
├── request-blood.php    Blood request form (login required)
├── register.php / login.php / logout.php
├── dashboard.php        My profile, my requests (complete/cancel), requests I accepted
├── profile.php          Edit profile
├── admin.php            Admin: verify/activate/delete users, cancel requests, add areas
├── about.php / contact.php   (contact is an info page, no form)
├── includes/
│   ├── config.php       DB settings + constants
│   ├── db.php           Creates $pdo
│   ├── functions.php    session_start, helpers (e(), redirect(), flash, login checks, card HTML)
│   ├── header.php       Top HTML + nav (set $page_title and $active before including)
│   └── footer.php
├── sql/schema.sql       Tables (areas, users, donation_requests, donation_requests_transactions)
├── sql/seed.sql         Sample data
└── assets/
    ├── css/style.css    Tiny custom CSS only
    └── js/
        ├── tailwind-config.js  Brand colors for Tailwind
        └── main.js             Mobile menu toggle only
```

### Page pattern
Each page: `require_once 'includes/functions.php';` → handle POST / run queries → set `$page_title`, `$active` → `require 'includes/header.php';` → HTML → `require 'includes/footer.php';`.
After a successful POST: `set_flash()` then `redirect()` (Post/Redirect/Get).

## Coding Rules

### General
- Simple code, short functions, clear names. Add a short comment above each logical block.
- No advanced patterns: no async/await chains, no modules/bundlers, no design patterns, no premature abstraction.
- Do not duplicate large blocks — but a little repetition is better than a confusing abstraction.

### HTML / Tailwind
- Use Tailwind utility classes directly in HTML. Mobile-first (`sm:`, `md:`, `lg:`).
- Brand color is `brand` (red) — defined in `assets/js/tailwind-config.js`. Use `bg-brand-600`, `text-brand-700`, etc.
- Use semantic tags (`header`, `main`, `section`, `footer`, `nav`) and proper `label` + `input` pairs.
- Every page includes the same `<head>` pattern: Tailwind CDN, `tailwind-config.js`, `style.css`.
- Page body uses `data-page="<name>"` so the nav can highlight the active link.
- Avoid inline `style=""` and avoid `@apply`/custom CSS unless truly needed.

### Vanilla JS
- `var`-free: use `const` / `let`. Plain functions, `document.getElementById`, `querySelector`, `addEventListener`.
- JS only improves the UI (e.g. menu toggle). Validation and data logic live in PHP. Keep forms as normal HTML forms.
- No `fetch`/AJAX unless the user asks.

### PHP
- Procedural only. Functions + `include`/`require`. **Never** `class`, `new` (except `new PDO`), `extends`, namespaces.
- Database: PDO with **prepared statements always** (never concatenate user input into SQL). `PDO::ERRMODE_EXCEPTION`.
- Passwords: `password_hash()` / `password_verify()`.
- Output escaping: `htmlspecialchars()` for every printed user value.
- Sessions for login; check login at the top of protected pages.
- Validate input on the server even if JS already validated it.

### MySQL
- Tables: `areas`, `users` (everyone: donors, seekers, admin via `role`), `donation_requests`, `donation_requests_transactions` (a donor accepting a request).
- The schema came from the team's own `blood_donations.sql`; keep its table/column names (including the `doner_id` spelling). Use the status constants from `includes/config.php`, never bare numbers.
- `users.status`: 1 active, 2 unavailable, 3 inactive (cannot login). `users.is_verified`: admin verified the user.
- Use `snake_case` names, `id` primary keys, `created_at` timestamps, `utf8mb4`.

## Domain Notes
- Blood groups: A+, A-, B+, B-, AB+, AB-, O+, O-.
- Roles: `user` (can both request and donate), `admin`. Posting a request and accepting one need login.
- A donor can donate again after **90 days** (show "Available" / "Not available").
- Request status: 1 pending, 2 ongoing, 3 accepted, 4 completed, 5 cancelled. Pending/ongoing requests (date not passed) are public.
- Flow: seeker posts request (pending) → matching-group donors accept → status is ongoing, then accepted when `quantity` donors accepted (`refresh_request_status()`) → seeker marks completed (donors' `last_donation_date` = today) or cancels.
- Transaction status: 1 accepted, 2 cancelled, 3 completed.
- Mobile numbers are shown only to logged-in users.

## Design Notes
- Clean, friendly, trustworthy: white background, red brand accent, soft gray sections, rounded cards, generous spacing.
- Fully responsive; test at ~375px and ~1280px widths.
- Accessible: visible focus states, sufficient contrast, alt text / `aria-label` on icons.
- No external images required — use inline SVG icons and emoji-free text-based avatars (initials).
